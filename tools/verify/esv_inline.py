import re,sys
sys.path.insert(0,__file__.rsplit('/',1)[0])
import esv
from pathlib import Path
BOOKS={'Matt':'Matthew','Deut':'Deuteronomy','Josh':'Joshua','Num':'Numbers','Gal':'Galatians','Rom':'Romans','Heb':'Hebrews','Phil':'Philippians','Col':'Colossians','Rev':'Revelation','Gen':'Genesis','Exod':'Exodus','Isa':'Isaiah','Jer':'Jeremiah','Ps':'Psalms','Psalm':'Psalms','Dan':'Daniel','1 Cor':'1 Corinthians','2 Cor':'2 Corinthians','1 Tim':'1 Timothy','2 Pet':'2 Peter'}
pat=re.compile(r'"([^"]{6,260})"[^"{(\n]{0,60}?[{(]((?:[12] )?[A-Z][a-z]+) (\d+):[\d–-]+( · [A-Z]+)?')
n=bad=0
for f in sorted(Path(sys.argv[1]).glob(sys.argv[2] if len(sys.argv)>2 else '*.txt')):
    for line in f.read_text().splitlines():
        if line.startswith('> ') or line.startswith('::'): continue
        for m in pat.finditer(line):
            if m.group(2)=='Q' or (m.group(4) and m.group(4)!=' · ESV'): continue
            n+=1; book=BOOKS.get(m.group(2),m.group(2))
            ok,info=esv.check(m.group(1), f"{book} {m.group(3)}")
            if not ok: bad+=1; print(f.name, book, m.group(3), '::', info)
print(n,'inline quotes,',bad,'differ')
