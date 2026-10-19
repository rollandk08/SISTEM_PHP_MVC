# AWS Educate y hosting PHP/MySQL de demostración

Fuentes verificadas el 19/10/2026. Las ofertas, límites y disponibilidad pueden cambiar.

## AWS Educate gratuito

La [página oficial de AWS Educate](https://aws.amazon.com/education/awseducate/) ofrece aprendizaje y laboratorios prácticos gratuitos sin tarjeta de crédito. El alumno se inscribe personalmente y usa los laboratorios que le presente su catálogo. Educate y una cuenta personal AWS con facturación son entornos diferentes; no presuponga créditos, permisos EC2/RDS ni acceso persistente a una VM.

Laboratorio sugerido (sin crear recursos desde esta entrega):

1. Entrar en Educate y seleccionar un laboratorio de introducción a cómputo, bases de datos o redes disponible.
2. Leer duración, servicios permitidos y mecanismo de cierre del sandbox.
3. Identificar equivalencias: Apache/PHP → servidor de aplicación, MariaDB → datos, firewall → acceso, backup → recuperación.
4. Si el sandbox permite servidor Linux, seguir las tareas guiadas; revisar el playbook como ejemplo de automatización y ensayar solo con permiso del instructor.
5. Registrar arquitectura y captura sin credenciales, cerrar el laboratorio y comprobar que finalizó.

Entregable: comparación localhost/cloud, responsabilidades y costo autorizado cero. No dejar servicios públicos ni introducir datos reales. El sistema local funciona independientemente de Educate.

## Hosting educativo PHP/MySQL

[InfinityFree](https://www.infinityfree.com/) anuncia hosting gratuito con PHP/MySQL. Es una opción de práctica, sujeta a [documentación y límites oficiales](https://forum.infinityfree.com/c/documentation/11). Verifique en su panel versión PHP ≥8, PDO MySQL, utf8mb4, límites de archivos, HTTPS y permisos antes de elegirlo; no se garantiza disponibilidad, backups automáticos ni SLA. No use hosting gratuito para información real de empresa.

Preparación local, sin abrir cuentas ni publicar:

1. Copiar proyecto a una carpeta de release. Excluir `.git`, tests, documentación de trabajo, logs, sesiones, backups y `.env` local. Ejecutar Composer en esa copia con `install --no-dev --optimize-autoloader` si se distribuirá vendor. Los assets locales ya están incluidos.
2. Preparar una base demo independiente y usuarios SQL restringidos. Crear `.env.production` a partir del ejemplo; APP_ENV=production, APP_DEBUG=false, APP_KEY aleatoria, SESSION_SECURE=true y credenciales reales del hosting. Desactivar usuarios de ejemplo o cambiar todas sus contraseñas antes de exportar la base.
3. Probar la release con el perfil demo y ejecutar las pruebas en `_test`, nunca en demo pública.

Tras autorización del propietario para crear/publicar en una cuenta suya:

4. Crear base desde el panel del proveedor. Importar primero `migrations/001_northwind.sql`, después `002_application.sql`, en esa base seleccionada. No importar el original. Alternativa preferida: exportación SQL de una base demo local ya instalada, con contraseñas cambiadas; así conserva schema_migrations y usuarios. No subir un respaldo JSON como si fuera SQL.
5. Si se importan migraciones manualmente y no existe CLI, importar además `database/manual_setup.sql` (generado con huellas actuales) y un usuario BCrypt creado localmente con `php tools/create_admin_sql.php correo nombre`. Este script lee la contraseña desde la variable temporal `ISAM_ADMIN_PASSWORD`; configure esa variable desde entrada protegida de su terminal, redirija la salida a SQL privado y elimine la variable y el SQL después de importarlo. El programa jamás crea un usuario desde un endpoint público.
6. Subir app/config/vendor/storage fuera de la raíz pública, o proteger el contenedor del proyecto con la `.htaccess` incluida y colocar public como raíz del sitio si el proveedor lo permite. En una release destinada a publicación autorizada, cambiar el `Require local` de la `.htaccess` raíz por la regla de acceso aprobada para esa demo; conservar todas las prohibiciones de archivos privados. Configurar APP_ENV_FILE en el servidor o instalar la configuración demo como `.env` privado. Mantener la ruta relativa bootstrap si se mueve public: adaptar require de forma explícita y comprobar acceso.
7. Asignar escritura solo a storage. Verificar que `.env`, `/database`, `/storage`, `/vendor`, `/tools` y `/.git/config` devuelven 403/404 por HTTP. Ningún directorio debe listar archivos.
8. Activar HTTPS, comprobar login, roles, filtros, pedidos, CSV y logs. Crear copia fuera del hosting. Si memoria o ejecución no permiten los respaldos PHP, usar exportación del panel y retirar acceso al módulo en esa demo; documentar la limitación antes de aceptar.

Rollback de demo: retirar acceso, volver a la release previa, restaurar datos solo tras aprobación y verificar. Ningún despliegue remoto fue ejecutado como parte de esta entrega.
