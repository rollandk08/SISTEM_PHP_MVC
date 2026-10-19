<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\{Database,HttpException};
final class Order {
    public function listing(array $in,bool $all=false): array {
        $where=[]; $args=[];
        if (($in['q']??'')!=='') { $where[]='(CAST(o.OrderID AS CHAR) LIKE ? OR c.CompanyName LIKE ?)'; $args[]='%'.mb_substr($in['q'],0,100).'%'; $args[]=end($args); }
        foreach (['from'=>'>=','to'=>'<='] as $key=>$op) if (($in[$key]??'')!=='') { if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$in[$key])) throw new HttpException(422,'Fecha de filtro inválida.'); $where[]='DATE(o.OrderDate) '.$op.' ?'; $args[]=$in[$key]; }
        $sql=' FROM Orders o LEFT JOIN Customers c ON c.CustomerID=o.CustomerID'.($where?' WHERE '.implode(' AND ',$where):'');
        $pdo=Database::connection(); $q=$pdo->prepare('SELECT COUNT(*)'.$sql); $q->execute($args); $total=(int)$q->fetchColumn();
        $page=max(1,min(max(1,(int)ceil($total/15)),(int)($in['page']??1)));
        $q=$pdo->prepare('SELECT o.OrderID,c.CompanyName,o.OrderDate,o.ShipCountry,o.Freight,(SELECT COALESCE(SUM(d.UnitPrice*d.Quantity*(1-d.Discount)),0) FROM `Order Details` d WHERE d.OrderID=o.OrderID) AS Total'.$sql.' ORDER BY o.OrderID DESC'.($all?'':' LIMIT 15 OFFSET '.(($page-1)*15))); $q->execute($args);
        return ['rows'=>$q->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>(int)ceil($total/15)];
    }
    public function find($id): array {
        $pdo=Database::connection(); $q=$pdo->prepare('SELECT * FROM Orders WHERE OrderID=?'); $q->execute([$id]); $order=$q->fetch(); if (!$order) throw new HttpException(404,'Pedido no encontrado.');
        $q=$pdo->prepare('SELECT d.*,p.ProductName FROM `Order Details` d JOIN Products p ON p.ProductID=d.ProductID WHERE OrderID=?'); $q->execute([$id]); $order['lines']=$q->fetchAll(); return $order;
    }
    public function options(): array {
        $pdo=Database::connection();return ['customers'=>$pdo->query('SELECT CustomerID,CompanyName FROM Customers ORDER BY CompanyName')->fetchAll(),'employees'=>$pdo->query('SELECT EmployeeID,CONCAT(FirstName," ",LastName) AS name FROM Employees')->fetchAll(),'shippers'=>$pdo->query('SELECT ShipperID,CompanyName FROM Shippers')->fetchAll(),'products'=>$pdo->query('SELECT ProductID,ProductName,UnitPrice,UnitsInStock,CAST(Discontinued AS UNSIGNED) AS Discontinued FROM Products ORDER BY ProductName')->fetchAll()];
    }
}
