# Clase 02 · ETL e importación CSV
Objetivo: explicar origen, destino, mapeo, staging y conciliación.

1. Revisar CSV de `database/samples` y las longitudes del esquema.
2. Cargar `clientes_invalidos.csv`; inspeccionar cada error y estado INVALIDO.
3. Cargar el CSV válido en la base de laboratorio; inspeccionar staging `app_import_rows` antes de confirmar.
4. Confirmar una vez y comprobar el incremento de dos clientes. Repetir el archivo: debe detectar duplicados.
5. Repetir con proveedores. Comparar historial y auditoría.

Evidencia: archivo, captura de previa, errores, conteos antes/después e ID de lote. Aceptación: cero filas insertadas cuando existe cualquier error; exactamente dos cuando se confirma el ejemplo válido. No sobreescribir datos existentes.
