# Origen, adaptación y conciliación

Fuente solicitada: https://gist.github.com/ryguyrg/70b8d5942e9c968eb3fe1e3cec5a3dac

Archivo fijo: https://gist.githubusercontent.com/ryguyrg/70b8d5942e9c968eb3fe1e3cec5a3dac/raw/2139a5cb648a20acc84cf00773ea298f7d32c6a9/northwind.sql

Se conserva sin ejecutar en `database/source/northwind.original.sql`. SHA-256 original: `7d55797e0892134676d4906ce5cb2f69eb7e855fba66ff1be566470d1f5c079d`.

La cabecera atribuye el script MySQL 5 a Valon Hoti (2010); el gist fue publicado por ryguyrg. No se atribuye una licencia nueva a esos datos. Revise su procedencia antes de redistribuir fuera del laboratorio.

## Adaptaciones explícitas

1. Se retiran `DROP DATABASE`, `CREATE DATABASE`, `USE northwind` y `TRUNCATE TABLE` de la migración ejecutable. El instalador selecciona una base nueva por `.env`. No se ejecutó el borrado incluido en el original.
2. Las 13 tablas originales conservan nombres, columnas, PK, índices y las 13 FK, incluido `Order Details` con espacio, `Region` y tablas puente. Se usa InnoDB y utf8mb4 explícitamente.
3. Se cambia únicamente el tipo de `Order Details.Discount`, de `REAL(8,0)` a `DECIMAL(5,4)`. El original incluye fracciones como 0.05/0.10/0.25; el tipo original redondearía esos valores. Se conservan los valores INSERT y la venta neta calculada con ellos.
4. No se corrigen ni traducen los nombres empresariales, textos, fechas, salarios, fotos o demás datos del gist. Los defectos de representación presentes en la fuente se conservan para no inventar información. Las interfaces están en español.
5. Se añaden seis tablas `app_*` y `schema_migrations` sin modificar las entidades Northwind: usuarios, intentos, auditoría, lotes CSV, staging CSV e incidencias.

## Conteos de esta fuente (antes de operaciones del usuario)

| Tabla | Filas |
|---|---:|
| Categories | 8 |
| Customers | 93 |
| CustomerDemographics | 0 |
| CustomerCustomerDemo | 0 |
| Employees | 9 |
| EmployeeTerritories | 49 |
| Orders | 830 |
| Order Details | 2155 |
| Products | 77 |
| Region | 4 |
| Shippers | 3 |
| Suppliers | 29 |
| Territories | 53 |

La fuente contiene 93 clientes, no el conteo de 91 habitual de otras versiones. Hay 838 detalles con descuentos fraccionarios. Venta histórica neta: **USD 1,265,793.04**, sin fletes ni impuestos. Moneda usada como convención de presentación para el laboratorio; el esquema no contiene moneda por transacción.

Ejecute `php tools/verify.php` o `database/verification.sql`. La primera herramienta registra conteos, versión, relaciones y ventas en `build/database-verification.json`. Los conteos pueden crecer tras utilizar CRUD o importar; la prueba de línea base requiere la base de pruebas original.

La migración 001 se genera a partir del original retirando solo las instrucciones de administración y ajustando motor/codificación y Discount. El manifiesto `database/source/manifest.json` registra ambas huellas. Los INSERT del original y de la migración se comparan íntegramente en PHPUnit.
