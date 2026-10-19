"""Regenera únicamente la migración 001 a partir de la fuente fija, sin ejecutar SQL."""
from pathlib import Path
import re,hashlib
root=Path(__file__).resolve().parent.parent
source=root/'database/source/northwind.original.sql'
expected='7d55797e0892134676d4906ce5cb2f69eb7e855fba66ff1be566470d1f5c079d'
if hashlib.sha256(source.read_bytes()).hexdigest()!=expected:
 raise SystemExit('Fuente distinta de la versión revisada. No se generó la migración.')
sql=source.read_text(encoding='utf-8-sig')
sql=re.sub(r'^(DROP DATABASE|CREATE DATABASE|USE northwind|TRUNCATE TABLE).*?;\s*','',sql,flags=re.M)
sql=sql.replace('`Discount` REAL(8,0)','`Discount` DECIMAL(5,4)')
sql=re.sub(r'\n\);','\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',sql)
(root/'migrations/001_northwind.sql').write_text('SET NAMES utf8mb4;\n'+sql,encoding='utf-8')
print('Migración regenerada. No se ejecutó ningún SQL.')
