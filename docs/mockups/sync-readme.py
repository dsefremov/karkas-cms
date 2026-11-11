"""Refresh README counts and PNG inventory from existing generated artifacts."""
from pathlib import Path
import json,re
P=Path(__file__).parent
m=json.loads((P/'manifest.json').read_text());r=json.loads((P/'render-report.json').read_text())
s=(P/'README.md').read_text()
summary=f"{len(m)} статичных HTML-экранов и {len(r)} PNG: {sum(x['width']==1440 for x in r)} desktop 1440px + {sum(x['width']==390 for x in r)} mobile 390px."
s=re.sub(r'\d+ статичных HTML-экранов и \d+ PNG: \d+ desktop 1440px \+ \d+ mobile 390px\.',summary,s)
s=re.sub(r'полный комплект — \d+ PNG',f'полный комплект — {len(r)} PNG',s)
a=s.index('## PNG');b=s.index('## Проверка и просмотр',a)
rows='\n'.join('| '+x['title']+' | '+str(x['width'])+' | `'+x['png']+'` |' for x in r)
s=s[:a]+'## PNG\n\nАвтоматически сформировано из render-report.json.\n\n| Экран | Ширина | PNG |\n|---|---|---|\n'+rows+'\n\n'+s[b:]
(P/'README.md').write_text(s)
print(summary)
