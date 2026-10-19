-- Solo lectura. Seleccione la base isam_northwind en phpMyAdmin.
SELECT 'Customers' AS tabla, COUNT(*) AS filas FROM Customers
UNION ALL SELECT 'Suppliers',COUNT(*) FROM Suppliers
UNION ALL SELECT 'Products',COUNT(*) FROM Products
UNION ALL SELECT 'Orders',COUNT(*) FROM Orders
UNION ALL SELECT 'Order Details',COUNT(*) FROM `Order Details`;
SELECT COUNT(*) AS relaciones_northwind FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME NOT LIKE 'app_%';
SELECT COUNT(*) AS descuentos_fraccionarios,ROUND(SUM(UnitPrice*Quantity*(1-Discount)),2) AS venta_con_descuento
FROM `Order Details` WHERE Discount>0 AND Discount<1;
SELECT ROUND(SUM(UnitPrice*Quantity*(1-Discount)),2) AS venta_neta_total FROM `Order Details`;
SELECT c.CustomerID,c.CompanyName,c.Country,COUNT(o.OrderID) AS pedidos
FROM Customers c LEFT JOIN Orders o ON o.CustomerID=c.CustomerID
GROUP BY c.CustomerID,c.CompanyName,c.Country ORDER BY pedidos DESC;
