# Arquitectura MVC

Petición → `public/index.php` → bootstrap/env → Router → AuthMiddleware/CSRF → Controller → Model/Service → PDO → Views/layout.

Las rutas usan `index.php?r=...`; esto evita depender de URL rewriting en hosting compartido. Los módulos del catálogo se definen mediante una lista cerrada en `config/modules.php`; el usuario nunca decide nombres SQL. Los valores de búsquedas, filtros y escrituras se enlazan con PDO y consultas preparadas reales.

Los controladores coordinan. `Catalog`, `Order`, `User` y `Analytics` contienen acceso de lectura/persistencia. Los servicios contienen validación, reglas de stock, importaciones, migraciones, respaldos y auditoría. Las vistas solo presentan datos escapados y formularios. Composer proporciona PSR-4 `App\` → `app/`; hay autoload alternativo propio para una instalación sin vendor de producción.

## Rutas y autorización

| Ruta | Método | ADMIN | OPERADOR | CONSULTA |
|---|---|---|---|---|
| login | GET/POST | Pública | Pública | Pública |
| logout, password | POST / GET+POST | Sí | Sí | Sí |
| dashboard, catalog, orders, order-show, reports | GET | Sí | Sí | Sí |
| catalog-export, orders-export, reports-export | GET | Sí | Sí | Sí |
| catalog-form, order-form | GET | Sí | Sí | No |
| catalog-save, order-save | POST | Sí | Sí | No |
| catalog-delete, order-delete | POST | Sí | No | No |
| imports, import-template | GET | Sí | Sí | No |
| import-preview, import-commit | POST | Sí | Sí | No |
| incidents | GET | Sí | Sí | Sí |
| incident-save | POST | Alta/estado | Alta | No |
| users, audit, backups, backup-download | GET | Sí | No | No |
| user-save, user-toggle, backup-create, backup-restore | POST | Sí | No | No |

`module=clientes|proveedores|productos|categorias` selecciona el catálogo. Todos los POST requieren CSRF, incluido el inicio de sesión. Rutas desconocidas devuelven 404; métodos incorrectos, 405; roles incorrectos, 403; validaciones, 422; sesión de formulario, 403; demasiados intentos, 429.

## Pedidos

Cabecera y líneas se guardan en una transacción. Se bloquean pedido y productos (`FOR UPDATE`), se rechazan productos repetidos, stock insuficiente, cantidades inválidas y nuevos incrementos de productos descontinuados. La diferencia entre unidades antiguas y nuevas ajusta el inventario. La eliminación autorizada devuelve las unidades. No se recalculó el stock histórico durante la instalación. Modificar/eliminar pedidos históricos sí aplica la misma regla de diferencia/devolución; practique sobre una copia si quiere mantener la referencia original.

Reportes usan el precio y descuento de `Order Details`; el dashboard muestra los últimos 12 meses con datos, no los últimos 12 meses del calendario actual. Fechas y conteos provienen del historial original.

## Importaciones

Upload temporal PHP → lectura UTF-8 (coma, encabezados permitidos) → validación de longitudes/obligatorios → duplicados en archivo y destino → staging persistente `app_import_rows` → vista previa → confirmación del mismo usuario → revalidación dentro de una transacción → INSERT y estado COMPLETADO. Los lotes con errores no insertan filas de negocio. Límite 2 MB/1000 filas, previa válida por una hora. Historial visible: últimos 50 lotes propios. Los duplicados no sobrescriben datos.

## Límites deliberados

No incluye facturación fiscal, impuestos, multimoneda, pasarelas de pago, correo, recuperación de contraseña por Internet ni integración ERP externa. Los listados operativos usan 15 filas por página; auditoría muestra los últimos 200 eventos e incidencias las últimas 100. Los datos completos permanecen en la base. Los respaldos PHP se construyen en memoria: apropiados para Northwind, no para bases de varios GB.
