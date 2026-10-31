"""Write serialized markup into the theme's parts, templates and patterns."""
import sys, pathlib
src, th = pathlib.Path(sys.argv[1]), pathlib.Path(sys.argv[2])
meta = {
 "hero-argument": ("Homepage hero with argument card", "The headline beside the premise-by-premise argument card. Each premise is a Details block; pick its verdict in the Styles panel."),
 "begin-here": ("Begin here: three steps", "Three cards leading to the argument, the response and the sources."),
 "two-alternatives": ("Two alternatives and the one left out", "The dilemma's two alternatives, with the Qur'an's own position and Q 5:48."),
 "bible-mirror": ("The Bible on scribal corruption", "Two photographs, Jeremiah 8:8 and Matthew 23:2–3, and a short note."),
 "recent-enrichment": ("Recent Enrichment articles", "The three latest Enrichment posts as cards."),
 "ayah": ("Arabic verse card", "Arabic verse, translation and reference."),
 "article-with-sidebar": ("Article with sidebar", "Two columns: the article, and a sidebar with the 'On this page' list and margin notes."),
 "premise-card": ("Premise card", "One premise with its verdict and reply. Choose the verdict in the Styles panel."),
}
for f in sorted(src.glob("*.html")):
    kind, name = f.stem.split("--")
    html = f.read_text(encoding="utf-8").strip() + "\n"
    if kind == "part":
        (th / "parts" / f"{name}.html").write_text(html, encoding="utf-8")
    elif kind == "template":
        (th / "templates" / f"{name}.html").write_text(html, encoding="utf-8")
    else:
        title, desc = meta[name]
        html = html.replace("__THEME_URI__", "<?php echo esc_url( get_stylesheet_directory_uri() ); ?>")
        head = f"<?php\n/**\n * Title: {title}\n * Slug: islamicdilemma/{name}\n * Categories: islamicdilemma\n * Description: {desc}\n * Inserter: yes\n *\n * @package islamicdilemma\n */\n\n?>\n"
        (th / "patterns" / f"{name}.php").write_text(head + html, encoding="utf-8")
print("theme files written")
