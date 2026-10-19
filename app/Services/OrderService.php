<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database,HttpException};
final class OrderService {
    public function save(array $in,?int $id=null): int {
        $head=[];
        if (!preg_match('/^[A-Z0-9]{5}$/',(string)($in['CustomerID']??''))) throw new HttpException(422,'Seleccione un cliente.');
        $head['CustomerID']=$in['CustomerID'];
        foreach (['EmployeeID','ShipVia'] as $field) { $v=$in[$field]??''; if ($v!=='' && !ctype_digit((string)$v)) throw new HttpException(422,'Referencia inválida.'); $head[$field]=$v===''?null:$v; }
        foreach (['OrderDate','RequiredDate','ShippedDate'] as $field) { $v=$in[$field]??''; if ($field==='OrderDate' && !$v) throw new HttpException(422,'Fecha del pedido obligatoria.'); if ($v && (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$v) || !checkdate((int)substr($v,5,2),(int)substr($v,8,2),(int)substr($v,0,4)))) throw new HttpException(422,'Fecha inválida.'); $head[$field]=$v?:null; }
        if ($head['RequiredDate'] && $head['RequiredDate']<$head['OrderDate']) throw new HttpException(422,'La fecha requerida no puede preceder al pedido.');
        foreach (['ShipName'=>40,'ShipAddress'=>60,'ShipCity'=>15,'ShipRegion'=>15,'ShipPostalCode'=>10,'ShipCountry'=>15] as $field=>$max) { $v=trim((string)($in[$field]??'')); if (mb_strlen($v)>$max) throw new HttpException(422,'El campo de envío supera su longitud máxima.'); $head[$field]=$v?:null; }
        $freight=(string)($in['Freight']??'0'); if (!preg_match('/^\d{1,6}(\.\d{1,4})?$/',$freight)) throw new HttpException(422,'Flete inválido.'); $head['Freight']=$freight;
        $lines=$in['lines']??[]; if (!is_array($lines) || count($lines)<1 || count($lines)>100) throw new HttpException(422,'Agregue entre 1 y 100 productos.'); $new=[];
        foreach ($lines as $line) {
            $pid=(string)($line['ProductID']??''); $qty=(string)($line['Quantity']??''); $price=(string)($line['UnitPrice']??''); $discount=(string)($line['Discount']??'0');
            if (!ctype_digit($pid) || isset($new[$pid]) || !ctype_digit($qty) || (int)$qty<1 || (int)$qty>32767 || !preg_match('/^\d{1,6}(\.\d{1,4})?$/',$price) || !is_numeric($discount) || (float)$discount<0 || (float)$discount>1) throw new HttpException(422,'Revise líneas: producto único, cantidad positiva, precio válido y descuento entre 0 y 1.');
            $new[$pid]=['ProductID'=>(int)$pid,'UnitPrice'=>$price,'Quantity'=>(int)$qty,'Discount'=>number_format((float)$discount,4,'.','')];
        }
        return Database::transaction(function($pdo)use($id,$head,$new){
            $old=[];
            if ($id) { $q=$pdo->prepare('SELECT OrderID FROM Orders WHERE OrderID=? FOR UPDATE');$q->execute([$id]); if (!$q->fetchColumn()) throw new HttpException(404,'Pedido no encontrado.'); $q=$pdo->prepare('SELECT * FROM `Order Details` WHERE OrderID=?');$q->execute([$id]); foreach ($q as $row) $old[$row['ProductID']]=$row; }
            $ids=array_unique([...array_keys($old),...array_keys($new)]);sort($ids);
            $q=$pdo->prepare('SELECT ProductID,UnitsInStock,CAST(Discontinued AS UNSIGNED) AS discontinued FROM Products WHERE ProductID IN('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY ProductID FOR UPDATE');$q->execute($ids);$products=[];foreach($q as $p)$products[$p['ProductID']]=$p;
            foreach ($ids as $pid) {
                if (!isset($products[$pid])) throw new HttpException(422,'Producto no encontrado.');
                $delta=($new[$pid]['Quantity']??0)-($old[$pid]['Quantity']??0); $stock=(int)$products[$pid]['UnitsInStock']-$delta;
                if ($stock<0 || $stock>32767 || ($delta>0 && $products[$pid]['discontinued'])) throw new HttpException(422,'Stock insuficiente o producto descontinuado: '.$pid);
                $pdo->prepare('UPDATE Products SET UnitsInStock=? WHERE ProductID=?')->execute([$stock,$pid]);
            }
            if ($id) { $sets=implode(',',array_map(fn($k)=>'`'.$k.'`=?',array_keys($head)));$pdo->prepare('UPDATE Orders SET '.$sets.' WHERE OrderID=?')->execute([...array_values($head),$id]); $pdo->prepare('DELETE FROM `Order Details` WHERE OrderID=?')->execute([$id]); }
            else { $pdo->prepare('INSERT INTO Orders (`'.implode('`,`',array_keys($head)).'`) VALUES('.implode(',',array_fill(0,count($head),'?')).')')->execute(array_values($head));$id=(int)$pdo->lastInsertId(); }
            $q=$pdo->prepare('INSERT INTO `Order Details`(OrderID,ProductID,UnitPrice,Quantity,Discount) VALUES(?,?,?,?,?)');foreach($new as $line)$q->execute([$id,...array_values($line)]);
            AuditService::log($old?'UPDATE':'CREATE','Orders',$id,['lines'=>count($new)]);return $id;
        });
    }
    public function delete(int $id): void {
        Database::transaction(function($pdo)use($id){
            $q=$pdo->prepare('SELECT OrderID FROM Orders WHERE OrderID=? FOR UPDATE');$q->execute([$id]);if(!$q->fetchColumn())throw new HttpException(404,'Pedido no encontrado.');
            $q=$pdo->prepare('SELECT * FROM `Order Details` WHERE OrderID=? ORDER BY ProductID');$q->execute([$id]);
            foreach($q->fetchAll() as $line) { $u=$pdo->prepare('UPDATE Products SET UnitsInStock=UnitsInStock+? WHERE ProductID=? AND UnitsInStock+?<=32767');$u->execute([$line['Quantity'],$line['ProductID'],$line['Quantity']]);if(!$u->rowCount())throw new HttpException(422,'La devolución supera la capacidad de existencias.'); }
            $pdo->prepare('DELETE FROM `Order Details` WHERE OrderID=?')->execute([$id]);$pdo->prepare('DELETE FROM Orders WHERE OrderID=?')->execute([$id]);AuditService::log('DELETE','Orders',$id);
        });
    }
}
