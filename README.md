# Northwind ISAM · Sistema empresarial PHP MVC

Aplicación instalada en `C:\xampp\htdocs\SISTEM_PHP_MVC`. PHP 8+, PDO MySQL, MVC, Bootstrap 5 y Chart.js locales. Después de la instalación funciona sin conexión a Internet. No necesita Docker, Node.js ni servicios de pago.

## Acceso en esta PC

Inicie Apache y MySQL en el panel XAMPP y abra **http://localhost/SISTEM_PHP_MVC/public/**.

| Rol | Usuario / correo | Contraseña de demostración |
|---|---|---|
| ADMIN | admin@isam.local | `Admin#ISAM2026!` |
| OPERADOR | operador@isam.local | `Opera#ISAM2026!` |
| CONSULTA | consulta@isam.local | `Consulta#ISAM2026!` |

Cambie las contraseñas desde el menú de cuenta antes de exponer una demostración a terceros. Estos son accesos de la aplicación; no son las credenciales de MySQL. El `.env` local usa `root` sin contraseña únicamente para el XAMPP de esta PC.

## Instalación en Windows con XAMPP

1. PHP 8.0 o superior, Apache y MariaDB/MySQL; extensiones `pdo_mysql`, `mbstring`, `openssl`, `json`, `dom`, `xml`, `xmlwriter` y `zip` para las pruebas. Active Apache y MySQL desde XAMPP.
2. Coloque el proyecto en `C:\xampp\htdocs\SISTEM_PHP_MVC`. Instale Composer desde su distribuidor oficial si aún no lo tiene. Git es opcional para ejecutar el sistema.
3. Copie `.env.example` como `.env`, elija una base NUEVA y configure acceso. Genere APP_KEY con `C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32));"`. Nunca use el SQL original directamente: contiene `DROP DATABASE`.
4. Abra PowerShell en la carpeta y ejecute:

```powershell
$env:Path='C:\xampp\php;'+$env:Path
composer install --prefer-dist --no-interaction
php tools/console.php install
php tools/verify.php
```

Alternativa: `powershell -File tools/install.ps1`. El script genera `.env` si falta y una clave aleatoria. No cambia un `.env` existente. Para una copia que ya incluye `vendor`, use `-SkipComposer`; la aplicación también incluye autoloading propio para funcionar sin dependencias de producción.

La instalación crea `isam_northwind`, aplica migraciones versionadas y crea usuarios si la tabla está vacía. No elimina bases existentes, no reinstala datos sobre tablas ajenas y no cambia contraseñas existentes. Las migraciones DDL no se pueden revertir automáticamente en MySQL: si una primera instalación falla a medias, revise el log y use una NUEVA base o solicite una recuperación revisada.

## Funcionalidad

- Autenticación BCrypt, cambio de contraseña, roles por ruta, CSRF, cookies HttpOnly/SameSite, caducidad de sesión, revocación y límite de intentos de acceso.
- Dashboard de ventas históricas, categorías, existencias y pedidos recientes.
- CRUD de clientes, proveedores, categorías y productos; pedidos con cabecera y múltiples detalles, transacción, validación de existencias y actualización por diferencia de cantidades.
- Búsqueda y paginación, filtro por país/categoría, reportes por período y exportación CSV protegida contra fórmulas.
- Importaciones de clientes/proveedores con staging, vista previa, validación por fila, duplicados, confirmación única, transacción e historial por usuario.
- Incidencias, auditoría, usuarios y respaldos firmados con restauración protegida y copia previa automática.

## Pruebas

```powershell
Copy-Item config/environments/testing.env.example .env.testing
# Configure una APP_KEY aleatoria y credenciales de pruebas en .env.testing.
$env:APP_ENV_FILE=(Join-Path $PWD '.env.testing')
php tools/console.php install
Remove-Item Env:APP_ENV_FILE
php vendor/phpunit/phpunit/phpunit --log-junit build/phpunit.xml
php tools/lint.php
```

Las pruebas exigen `APP_ENV=testing` y nombre de base terminado en `_test`. Las pruebas de restauración reemplazan exclusivamente los datos de esa base de laboratorio. No ejecute pruebas contra la base principal.

Pruebas HTTP adicionales: inicie `php -S 127.0.0.1:8089 -t public` con `APP_ENV_FILE` apuntando a `.env.testing`; en otra consola ejecute `python tools/http_test.py`. Python se usa solo como verificador opcional, no como dependencia de la aplicación.

## Organización

```text
app/Controllers  Coordinación de peticiones y vistas
app/Models       Persistencia y consultas de catálogos, pedidos y estadísticas
app/Views        HTML en español, sin SQL
app/Services     Reglas, importación, pedidos, auditoría, migraciones y respaldos
app/Middleware   Autenticación, caducidad y autorización
app/Core         Router, PDO, entorno, controlador, seguridad y helpers
config/          Rutas, metadatos de módulos y ejemplos de ambientes
public/          Único directorio público recomendado, assets locales
database/        Fuente original, conciliación y ejemplos CSV
migrations/      SQL versionado con comprobación SHA-256
tests/           PHPUnit y base aislada
docs/            Instalación, arquitectura, seguridad, DevOps y nueve clases
deploy/          Ejemplos educativos Ansible, Puppet, Apache y Kubernetes
storage/         Sesiones, logs y respaldos privados, excluidos de Git
tools/           Instalación, migración y comprobaciones CLI
```

## Documentación

- [Arquitectura y rutas](docs/ARQUITECTURA.md)
- [Origen y adaptación de Northwind](docs/NORTHWIND.md)
- [Operación, ambientes y recuperación](docs/OPERACION.md)
- [Pruebas y evidencias](docs/PRUEBAS.md)
- [Plan de nueve clases](docs/CLASES.md)
- [DevOps, GitHub, Jenkins e infraestructura](docs/DEVOPS.md)
- [AWS Educate y hosting educativo](docs/DESPLIEGUE_EDUCATIVO.md)

El repositorio Git se mantiene local. Los archivos de GitHub Actions y los ejemplos están preparados; no se creó ningún repositorio remoto, cuenta, clúster ni recurso cloud.
