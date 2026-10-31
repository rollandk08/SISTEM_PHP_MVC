<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Analytics {
    public function dashboard(): array {
        $pdo=Database::connection();$stats=[];foreach(['Clientes'=>'Customers','Productos'=>'Products','Pedidos'=>'Orders','Proveedores'=>'Suppliers'] as $label=>$table)$stats[$label]=(int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();
        return ['stats'=>$stats,'revenue'=>$pdo->query('SELECT COALESCE(SUM(UnitPrice*Quantity*(1-Discount)),0) FROM `Order Details`')->fetchColumn(),'monthly'=>$pdo->query("SELECT DATE_FORMAT(o.OrderDate,'%Y-%m') AS month,ROUND(SUM(d.UnitPrice*d.Quantity*(1-d.Discount)),2) AS total FROM Orders o JOIN `Order Details` d ON d.OrderID=o.OrderID GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll(),'categories'=>$pdo->query('SELECT c.CategoryName AS label,COUNT(p.ProductID) AS total FROM Categories c LEFT JOIN Products p ON p.CategoryID=c.CategoryID GROUP BY c.CategoryID,c.CategoryName ORDER BY total DESC')->fetchAll(),'lowStock'=>$pdo->query('SELECT ProductID,ProductName,UnitsInStock,ReorderLevel FROM Products WHERE UnitsInStock<=ReorderLevel AND Discontinued=0 ORDER BY UnitsInStock LIMIT 6')->fetchAll(),'recent'=>$pdo->query('SELECT o.OrderID,c.CompanyName,o.OrderDate,o.ShipCountry FROM Orders o LEFT JOIN Customers c ON c.CustomerID=o.CustomerID ORDER BY o.OrderID DESC LIMIT 5')->fetchAll()];
    }
    public function sales(array $in): array {
        $where=[];$args=[];foreach(['from'=>'>=','to'=>'<='] as $key=>$op)if(($in[$key]??'')!=='') {if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$in[$key]))throw new \App\Core\HttpException(422,'Fecha inválida.');$where[]='DATE(o.OrderDate) '.$op.' ?';$args[]=$in[$key];}
        $q=Database::connection()->prepare('SELECT c.CustomerID,c.CompanyName,c.Country,COUNT(DISTINCT o.OrderID) AS OrdersCount,ROUND(SUM(d.UnitPrice*d.Quantity*(1-d.Discount)),2) AS Sales FROM Customers c JOIN Orders o ON o.CustomerID=c.CustomerID JOIN `Order Details` d ON d.OrderID=o.OrderID'.($where?' WHERE '.implode(' AND ',$where):'').' GROUP BY c.CustomerID,c.CompanyName,c.Country ORDER BY Sales DESC');$q->execute($args);return $q->fetchAll();
    }

}
