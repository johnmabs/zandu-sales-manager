#!/usr/bin/env python3
from pathlib import Path
import shutil, sys, re

if len(sys.argv) < 3:
    raise SystemExit('Usage: python tools/add_lot.py <number> "Lot title"')

number = sys.argv[1]
if not re.fullmatch(r'\d+', number):
    raise SystemExit('Lot number must be numeric.')
title = sys.argv[2].strip()
root = Path(__file__).resolve().parents[1]
template = root / 'docs' / 'ai' / 'lots' / '_TEMPLATE'
target = root / 'docs' / 'ai' / 'lots' / f'lot-{number}'
if target.exists():
    raise SystemExit(f'{target} already exists.')
shutil.copytree(template, target)
for p in target.glob('*.md'):
    text = p.read_text(encoding='utf-8')
    text = text.replace('<N>', number).replace('N.X', f'{number}.X').replace('EPIC-N.', f'EPIC-{number}.')
    text = text.replace('<Title>', title)
    p.write_text(text, encoding='utf-8')
print(f'Created {target.relative_to(root)}')
print('Next: add the complete source spec under docs/specs/, then fill CONTEXT.md and INDEX.md.')
