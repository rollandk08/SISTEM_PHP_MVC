# Pruebas y aceptación

PHPUnit 9.6 se eligió por compatibilidad con el PHP 8.0.28 de este XAMPP; está fijado por composer.lock. Para una puesta en producción real, planifique actualización de PHP y la suite a versiones mantenidas y valídelas antes del cambio. El código no depende de PHPUnit en ejecución.

## Cobertura funcional

| Grupo | Verificación |
|---|---|
| Seguridad | Token CSRF, escape HTML, neutralización de fórmulas CSV, validación de longitudes y números |
| Datos | Conteos fuente, venta neta, 13 FK, filtros y cadenas de inyección tratadas como datos |
| Persistencia | Crear/editar/eliminar cliente temporal, restricción FK, rollback de transacción |
| Pedidos | Crear/editar/eliminar y variación exacta de stock; stock insuficiente sin cabecera parcial |
| ETL | Vista previa sin INSERT de negocio, confirmación única, duplicados en archivo/destino |
| Recuperación | Respaldo, restauración aislada, esquema, firma alterada rechazada |
| HTTP | Tres roles, rutas privadas, módulos, detalle, formularios, exportación, 403/404/403 y logout |

Los resultados ejecutados se registran en `build/phpunit.xml`, `build/http-results.json` y `build/database-verification.json`. `docs/VALIDACION.md` recoge el resultado final y las limitaciones.

La base principal mantiene los registros originales. Pruebas de creación, eliminación y restauración se ejecutan solo en `isam_northwind_test`. Los tests evitan borrar bases; limpian únicamente sus filas temporales o restauran su copia aislada. `tests/bootstrap.php` bloquea cualquier entorno que no sea testing o base sin sufijo `_test`.

## Casos manuales de aceptación

1. Login válido de tres roles; menú y rutas coherentes. Cinco fallos bloquean correo/IP durante 15 min.
2. CONSULTA intenta un POST de escritura: 403. ADMIN usa token incorrecto: 403.
3. Clientes: Germany → solo alemanes; vacío → todos; país inexistente → vacío; texto con SQL → sin ampliación.
4. ADMIN/OPERADOR crea y edita cada catálogo; campos obligatorios y números negativos se rechazan.
5. ADMIN intenta borrar registro referenciado: aviso claro y datos conservados.
6. Pedido con dos productos, descuento 0.10 y cantidad positiva; verificar subtotales y stock. Cantidad excesiva debe revertir toda la operación.
7. CSV inválido → ningún registro de negocio; CSV válido → previa, confirmar y conciliar. Repetir → duplicados.
8. Reporte por fechas y CSV coinciden. Campos con `=`, `+`, `-`, `@` se neutralizan al exportar.
9. Crear incidencia; ADMIN cambia su estado y consulta auditoría.
10. Backup firmado descargable; restaurar exclusivamente en prueba y verificar datos y sesiones. Firma alterada rechazada.
11. Desconectar Internet (sin apagar Apache/MySQL) y navegar el sistema: assets, gráficas y operaciones siguen locales.
12. Abrir en móvil o ventana estrecha: menú plegable, formularios y tablas desplazables.

No se afirma una auditoría de penetración, carga masiva o compatibilidad con un hosting remoto no utilizado. Es una implementación funcional de laboratorio empresarial con límites documentados.
