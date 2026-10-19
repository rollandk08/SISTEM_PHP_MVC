# Operación y seguridad

## Ambientes independientes

| Ambiente | Archivo local | Base | Uso |
|---|---|---|---|
| Desarrollo | `.env` | `isam_northwind` | Trabajo local |
| Pruebas | `.env.testing` | `isam_northwind_test` | PHPUnit y pruebas HTTP |
| Demo producción | `.env.production` | `isam_northwind_demo` | Solo demostración controlada |

Los ejemplos están en `config/environments`. Copie y configure cada archivo, claves APP_KEY y usuarios DB separados. Seleccione un ambiente por la variable de proceso `APP_ENV_FILE`, nunca por parámetros HTTP. El archivo debe tener ruta absoluta cuando se configura Apache/hosting. `APP_DEBUG` se mantiene false: los errores no se muestran al visitante incluso en desarrollo; consulte `storage/logs/php.log`.

En producción use HTTPS, `SESSION_SECURE=true`, una clave APP_KEY aleatoria de 64 caracteres y un usuario SQL restringido. No use root ni una contraseña vacía. Las credenciales conocidas del laboratorio deben cambiarse y las cuentas innecesarias desactivarse antes de publicar.

## Sesiones y acceso

BCrypt cost 12 para semillas; el cambio y creación de usuarios usan BCrypt. Cookies de sesión HttpOnly y SameSite=Lax; Secure se activa con HTTPS. Se regeneran los identificadores al autenticar y cambiar contraseña. Timeout de inactividad: 1800 segundos. `session_version` y `active` se comprueban contra la base en cada petición privada. Cinco errores de login por correo/IP bloquean intentos durante 15 minutos. No hay registro público.

CSRF usa 32 bytes aleatorios y comparación constante. CSP permite recursos locales. Exportaciones neutralizan fórmulas de hojas de cálculo. La auditoría registra actor, acción, entidad, identificador, campos afectados, IP y fecha; no contraseñas ni CSV completos. Los errores del servidor muestran una referencia y registran el detalle solo en storage. No introduzca datos personales reales en este laboratorio.

## Respaldar

ADMIN → Respaldos → Crear respaldo → Descargar. Alternativa CLI: `php tools/console.php backup`.

Formato JSON con payload firmado HMAC-SHA256, filas codificadas en base64 para preservar texto y binarios, lista de columnas y huella del esquema. Se guardan en `storage/backups/NOMBRE_BASE/`. Son íntegros, **no cifrados**: contienen datos y hashes de contraseñas. Guarde copia fuera del webroot con acceso limitado y conserve APP_KEY por separado. No agregue respaldos ni `.env` a Git. El archivo descargado puede devolverse manualmente a esa carpeta privada por un administrador; no se admiten SQL arbitrarios ni uploads públicos de respaldo.

## Restaurar (no se ejecutó contra la base principal)

ADMIN → Respaldos → Revisar restauración → revisar tablas/conteos → escribir RESTAURAR y contraseña actual → confirmar. CLI equivalente:

```powershell
php tools/console.php restore northwind-AAAAMMDD-HHMMSS-XXXXXXXX.json --confirm=isam_northwind
```

Se verifica la firma con APP_KEY y compatibilidad de esquema. Se genera una copia del estado actual. Después se reemplazan filas dentro de una transacción InnoDB, se validan referencias y se revocan sesiones. No se ejecuta DROP/TRUNCATE ni SQL contenido en un archivo del usuario. Los contadores AUTO_INCREMENT pueden mantenerse superiores a los del respaldo; esto evita reutilizar identificadores.

Una exclusión mutua MySQL serializa los POST privados y la creación/restauración de respaldos de esta aplicación. Operaciones manuales externas (phpMyAdmin, otro programa) deben detenerse durante la restauración. Lecturas concurrentes pueden ver la versión anterior hasta el commit. Una restauración fallida revierte los datos; revise el log y mantenga la copia previa.

## Migraciones y cambios

`php tools/console.php migrate` aplica los archivos SQL pendientes por orden. `schema_migrations` guarda SHA-256; alterar una migración aplicada produce error. Añada nuevos archivos `003_...sql`; no cambie 001/002. Los DDL de MySQL pueden hacer commit implícito, por lo que cualquier cambio de esquema requiere respaldo y ensayo en pruebas. No se incluye rollback DDL destructivo automático.

## Soporte

- Conexión rechazada: active MySQL, compruebe puerto/credenciales `.env`; puerto 3306 puede estar ocupado.
- 404: abra `/SISTEM_PHP_MVC/public/`; las rutas utilizan `?r=`.
- Cookies no persisten en localhost: `SESSION_SECURE=false` para HTTP local.
- 403: recargue el formulario y autentíquese si caducó la sesión.
- CSV inválido: UTF-8, separador coma, encabezados originales, máximo 1000 filas; use los ejemplos.
- No elimina un cliente/categoría/proveedor: las FK protegen registros relacionados.
- Backup incompatible: revise ambiente, APP_KEY y versión de migraciones; no intente modificar su firma.

Apache debe servir solo `public/` en despliegues de demostración. La `.htaccess` raíz añade protección para la instalación en htdocs, pero no sustituye esa configuración. Directorios app/config/database/migrations/storage/tests/tools/docs/vendor/deploy y archivos ocultos deben devolver 403. Se incluye vhost educativo en deploy/apache.
