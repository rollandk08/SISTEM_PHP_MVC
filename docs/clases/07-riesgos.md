# Clase 07 · Riesgos y modelo relacional
Objetivo: priorizar fallos y comprender PK/FK.

| Riesgo | Probabilidad / impacto | Responsable propuesto | Prevención | Respuesta |
|---|---|---|---|---|
| Pérdida de datos | Media / Alta | ADMIN | Backup y ensayo | Restaurar copia validada |
| Duplicados CSV | Alta / Media | Operador | Previa + revalidación | Corregir lote |
| Exceso de permisos | Media / Alta | ADMIN | Roles por ruta | Desactivar cuenta/revocar |
| Puerto/servicio caído | Media / Media | Soporte | Diagnóstico XAMPP | Revisar servicio y logs |
| Credenciales públicas | Media / Crítica | Responsable despliegue | Secretos fuera de Git | Rotar y revisar accesos |
| Error de stock concurrente | Media / Alta | Desarrollo | Transacción y bloqueos | Revertir y auditar |

Relaciones: Customers 1:N Orders; Employees 1:N Orders; Shippers 1:N Orders; Orders N:M Products mediante Order Details; Categories y Suppliers 1:N Products; Employees N:M Territories mediante EmployeeTerritories; Region 1:N Territories. CustomerDemographics se relaciona N:M con Customers mediante CustomerCustomerDemo. Employees.ReportsTo es autorreferencia.

Ejecutar JOIN de `database/verification.sql`, identificar PK/FK e intentar eliminar un cliente referenciado en PRUEBAS: la FK debe rechazarlo. Evidencia: matriz con estado, responsable real asignado por el equipo y consulta JOIN.
