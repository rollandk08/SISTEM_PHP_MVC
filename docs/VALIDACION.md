# Validación ejecutada · 19 de octubre de 2026

Entorno real: Windows, XAMPP, Apache local, PHP 8.0.28, PDO MySQL y MariaDB 10.4.28. Proyecto: `C:\xampp\htdocs\SISTEM_PHP_MVC`. URL: http://localhost/SISTEM_PHP_MVC/public/.

| Comprobación | Resultado |
|---|---|
| Instalación | Base nueva isam_northwind, sin borrar bases existentes |
| Base fuente | 13 tablas originales, 13 claves foráneas, INSERT originales conservados |
| Datos | 93 clientes, 29 proveedores, 77 productos, 830 pedidos, 2155 detalles |
| Descuentos | 838 fracciones preservadas; venta histórica neta USD 1,265,793.04 |
| PHPUnit | 19 pruebas, 72 aserciones aprobadas en isam_northwind_test |
| Recuperación | Comparación de todos los valores de las 13 tablas antes/después de restaurar en pruebas; firma alterada rechazada |
| HTTP en Apache | 65/65 comprobaciones de tres roles, listados, formularios, filtros, exportación, CSRF y autorización |
| Sintaxis | Todos los archivos PHP propios sin errores |
| Composer | composer.json validado y requisitos de plataforma satisfechos; dependencias fijadas por lock |
| Archivos privados | .env, SQL fuente, logs, vendor, .git/config y tools devolvieron 403 |
| Interfaz | Dashboard revisado en navegador; gráficos y assets locales visibles |
| Respaldo principal | Copia firmada creada en storage/backups/isam_northwind; no se restauró la base principal |

Resultados de ejecución: `build/phpunit.xml`, `build/http-results.json`, `build/database-verification.json`. Imagen del dashboard: `docs/evidencias/dashboard.png`.

La repetición de pruebas detectó y permitió corregir el tratamiento de BIT al escribir productos y restaurar. La suite ahora comprueba tanto cantidades como estados y todos los valores de las tablas originales. También se corrigió el paso de variables a las vistas. CSRF usa HTTP 403 estándar, compatible con Apache y el servidor PHP integrado.

No se crearon cuentas, repositorios remotos, servicios cloud, clústeres ni despliegues externos. GitHub Actions, Jenkins, Ansible, Puppet y Kubernetes se entregan como configuraciones educativas sin afirmar que se hayan ejecutado en plataformas externas. La revisión visual fue de escritorio; la adaptabilidad móvil está implementada en CSS y queda incluida en la lista de aceptación manual.

Los usuarios/contraseñas publicados en README son de demostración local. La base principal mantiene los registros Northwind originales; los accesos, exportaciones y respaldos realizados durante la verificación generan eventos internos de auditoría. PHP 8.0 y este MariaDB son las versiones disponibles en esta PC; la guía indica actualizar el entorno antes de un uso productivo público.
