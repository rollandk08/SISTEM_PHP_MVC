# Clase 08 · Requisitos y MVC
Objetivo: seguir una petición desde interfaz a base con PDO.

Historia: como gerente quiero filtrar clientes por país para localizar compradores de un mercado.

CA-01 Germany devuelve solo clientes alemanes. CA-02 país inexistente devuelve listado vacío. CA-03 filtro vacío devuelve todos con paginación. CA-04 entrada SQL inesperada se trata como valor literal sin errores ni ampliación del resultado.

Ruta: catalog&module=clientes → CatalogController::index → Catalog::conditions/listing → consulta preparada PDO → Views/catalog/index. La vista no consulta la base. El router autoriza al usuario antes del controlador.

1. Abrir las cuatro capas y ubicar el filtro Country.
2. Ejecutar los cuatro criterios y revisar tests/DatabaseTest.php.
3. Proponer una mejora pequeña, escribir su requisito y modificar en rama.
4. Revisar diff, sintaxis y pruebas; registrar commit.

Evidencia: historia, criterios, esquema de flujo y resultados; separar requisito (necesidad) de solución (implementación).
