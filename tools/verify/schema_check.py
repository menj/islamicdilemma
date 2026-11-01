#!/usr/bin/env python3
"""Check the JSON-LD on a running site against the schema.org vocabulary.

Usage: python3 -I tools/verify/schema_check.py <base-url> <urls-file | path ...>

For every page: the JSON-LD parses, every @type is a schema.org class, every property exists and applies to the
node's type (or one of its ancestors), nested nodes and plain values fit the property's range, every @id is
unique, and every {"@id": ...} reference points at a node in the same graph.
The vocabulary is downloaded once to tools/verify/cache (git-ignored).
"""
import json, re, subprocess, sys
from pathlib import Path

CACHE = Path(__file__).parent / "cache"
VOCAB = CACHE / "schemaorg-current-https.jsonld"
URL = "https://schema.org/version/latest/schemaorg-current-https.jsonld"
# Accepted though not in the vocabulary file: Google's search-box pseudo-property.
EXTRA_PROPS = {"query-input": {"SearchAction"}}
DATA_TYPES = {"Text", "URL", "Number", "Integer", "Float", "Boolean", "Date", "DateTime", "Time", "CssSelectorType", "XPathType", "PronounceableText"}


def short(value):
    if isinstance(value, dict):
        value = value.get("@id", "")
    return value.rsplit("/", 1)[-1].replace("schema:", "")


def load_vocab():
    CACHE.mkdir(exist_ok=True)
    if not VOCAB.exists():
        subprocess.run(["curl", "-sSL", "-m", "120", "-o", str(VOCAB), URL], check=True)
    graph = json.loads(VOCAB.read_text())["@graph"]
    classes, parents, props = set(), {}, {}
    for n in graph:
        types = n.get("@type")
        types = types if isinstance(types, list) else [types]
        name = short(n["@id"])
        if "rdfs:Class" in types:
            classes.add(name)
            sup = n.get("rdfs:subClassOf") or []
            sup = sup if isinstance(sup, list) else [sup]
            parents[name] = {short(s) for s in sup}
        if "rdf:Property" in types:
            def many(key):
                v = n.get(key) or []
                v = v if isinstance(v, list) else [v]
                return {short(x) for x in v}
            props[name] = {"domain": many("schema:domainIncludes"), "range": many("schema:rangeIncludes")}
    # Data types are classes too.
    classes |= DATA_TYPES
    return classes, parents, props


def ancestors(t, parents):
    seen, todo = set(), [t]
    while todo:
        x = todo.pop()
        if x not in seen:
            seen.add(x)
            todo.extend(parents.get(x, ()))
    return seen


def types_of(node):
    t = node.get("@type", [])
    return t if isinstance(t, list) else [t]


DURATION = re.compile(r"^P(?=\d|T\d)(\d+Y)?(\d+M)?(\d+W)?(\d+D)?(T(?=\d)(\d+H)?(\d+M)?(\d+(\.\d+)?S)?)?$")
DATE = re.compile(r"^\d{4}(-\d{2}(-\d{2})?)?$")
DATETIME = re.compile(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:\d{2})?$")
TIME = re.compile(r"^\d{2}:\d{2}(:\d{2})?(Z|[+-]\d{2}:\d{2})?$")
URL_VALUE = re.compile(r"^https?://[^\s/$.?#][^\s]*$")
NUMBER_TYPES = {"Number", "Integer", "Float"}


def is_number(value):
    return isinstance(value, (int, float)) and not isinstance(value, bool)


def scalar_fits(value, rng):
    """True when a plain value is a valid value of one of the data types in the range.

    Returns None when the range holds no data type; the caller then applies the conventions below."""
    kinds = rng & DATA_TYPES
    if not kinds:
        return None
    if isinstance(value, bool):
        return "Boolean" in kinds
    if is_number(value):
        return bool(kinds & NUMBER_TYPES) and (isinstance(value, int) or "Integer" not in kinds or bool(kinds & {"Number", "Float"}))
    if not isinstance(value, str):
        return False
    if kinds & {"Text", "CssSelectorType", "XPathType", "PronounceableText"}:
        return True
    if "URL" in kinds and URL_VALUE.match(value):
        return True
    if "Date" in kinds and DATE.match(value):
        return True
    if "DateTime" in kinds and DATETIME.match(value):
        return True
    if "Time" in kinds and TIME.match(value):
        return True
    return False


def tolerated(value, rng):
    """Conventions every consumer accepts where the range holds no data type: a bare number for a size,
    an ISO 8601 string for a duration, a full URL standing in for a Thing."""
    if is_number(value) and rng & {"QuantitativeValue", "Distance"}:
        return True
    if isinstance(value, str) and "Duration" in rng and DURATION.match(value):
        return True
    return isinstance(value, str) and "Thing" in rng and bool(URL_VALUE.match(value))


def check_page(url, html, vocab):
    classes, parents, props = vocab
    errors = []
    blocks = re.findall(r'<script type="application/ld\+json">(.*?)</script>', html, re.S)
    if not blocks:
        return ["no JSON-LD found"]
    nodes, refs, ids = [], [], {}
    for raw in blocks:
        try:
            doc = json.loads(raw)
        except ValueError as err:
            errors.append(f"invalid JSON: {err}")
            continue
        nodes.extend(doc.get("@graph", [doc]))

    def walk(node, path):
        if not isinstance(node, dict):
            return
        if "@id" in node:
            if node["@id"] in ids:
                errors.append(f"duplicate @id {node['@id']}")
            ids[node["@id"]] = node
        ts = types_of(node)
        anc = set()
        for t in ts:
            if t not in classes:
                errors.append(f"{path}: unknown type {t}")
            anc |= ancestors(t, parents)
        for key, value in node.items():
            if key.startswith("@"):
                continue
            allowed = props.get(key, {}).get("domain")
            if allowed is None and key in EXTRA_PROPS:
                allowed = EXTRA_PROPS[key]
            if allowed is None:
                errors.append(f"{path}: unknown property {key}")
                continue
            if not anc & allowed:
                errors.append(f"{path}: {key} is not a property of {'/'.join(ts)} (domain: {', '.join(sorted(allowed))})")
            rng = props.get(key, {}).get("range", set())
            for v in value if isinstance(value, list) else [value]:
                if isinstance(v, dict) and set(v) == {"@id"}:
                    refs.append((f"{path}.{key}", v["@id"], rng))
                elif isinstance(v, dict):
                    vt = types_of(v)
                    vanc = set()
                    for t in vt:
                        vanc |= ancestors(t, parents)
                    if rng and vt and not vanc & rng and not (rng & {"Thing"} and "Thing" in vanc):
                        errors.append(f"{path}.{key}: {'/'.join(vt)} is not in range ({', '.join(sorted(rng))})")
                    walk(v, f"{path}.{key}")
                elif rng and isinstance(v, (str, int, float, bool)):
                    fits = scalar_fits(v, rng)
                    if fits is False or (fits is None and not tolerated(v, rng)):
                        errors.append(f"{path}.{key}: {v!r} is not a valid value (range: {', '.join(sorted(rng))})")

    for i, n in enumerate(nodes):
        walk(n, "/".join(types_of(n)) or f"node{i}")
    # A reference must point at a node, and the node's type must fit the property's range.
    for where, target, rng in refs:
        node = ids.get(target)
        if node is None:
            errors.append(f"{where}: reference {target} has no node")
            continue
        anc = set()
        for t in types_of(node):
            anc |= ancestors(t, parents)
        if rng and not anc & rng and not (rng & {"Thing"} and "Thing" in anc):
            errors.append(f"{where}: {target} is a {'/'.join(types_of(node))}, outside the range ({', '.join(sorted(rng))})")
    return errors


def main():
    base = sys.argv[1].rstrip("/")
    arg = sys.argv[2:]
    paths = Path(arg[0]).read_text().split() if len(arg) == 1 and Path(arg[0]).is_file() else arg
    vocab = load_vocab()
    bad = 0
    for path in paths:
        html = subprocess.run(["curl", "-sL", "-m", "90", base + path], capture_output=True, text=True).stdout
        errs = sorted(set(check_page(path, html, vocab)))
        bad += len(errs)
        print(f"{'OK  ' if not errs else 'FAIL'} {path}")
        for e in errs[:25]:
            print("     ", e)
    print(f"{len(paths)} pages checked, {bad} problems")
    sys.exit(1 if bad else 0)


if __name__ == "__main__":
    main()
