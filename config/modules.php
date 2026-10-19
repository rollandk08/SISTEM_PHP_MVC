<?php
$text=static fn($label,$max,$required=false)=>['label'=>$label,'type'=>'text','max'=>$max,'required'=>$required];
$number=static fn($label,$max,$decimal=false)=>['label'=>$label,'type'=>'number','min'=>0,'max'=>$max,'decimal'=>$decimal];
$select=static fn($label,$table,$key,$name)=>['label'=>$label,'type'=>'select','table'=>$table,'key'=>$key,'name'=>$name];
$contact=[
 'CompanyName'=>$text('Empresa',40,true),'ContactName'=>$text('Contacto',30),'ContactTitle'=>$text('Cargo',30),
 'Address'=>$text('Dirección',60),'City'=>$text('Ciudad',15),'Region'=>$text('Región',15),'PostalCode'=>$text('Código postal',10),
 'Country'=>$text('País',15),'Phone'=>$text('Teléfono',24),'Fax'=>$text('Fax',24)
];
return [
 'clientes'=>['title'=>'Clientes','singular'=>'cliente','table'=>'Customers','pk'=>'CustomerID','auto'=>false,'search'=>['CustomerID','CompanyName','ContactName','Country'],'filter'=>'Country','list'=>['CustomerID','CompanyName','ContactName','City','Country','Phone'],'fields'=>['CustomerID'=>$text('Código (5 letras o números)',5,true)]+$contact],
 'proveedores'=>['title'=>'Proveedores','singular'=>'proveedor','table'=>'Suppliers','pk'=>'SupplierID','auto'=>true,'search'=>['CompanyName','ContactName','Country'],'filter'=>'Country','list'=>['SupplierID','CompanyName','ContactName','City','Country','Phone'],'fields'=>$contact+['HomePage'=>['label'=>'Página / notas','type'=>'textarea','max'=>5000]]],
 'categorias'=>['title'=>'Categorías','singular'=>'categoría','table'=>'Categories','pk'=>'CategoryID','auto'=>true,'search'=>['CategoryName','Description'],'list'=>['CategoryID','CategoryName','Description'],'fields'=>['CategoryName'=>$text('Nombre',15,true),'Description'=>['label'=>'Descripción','type'=>'textarea','max'=>5000]]],
 'productos'=>['title'=>'Productos','singular'=>'producto','table'=>'Products','pk'=>'ProductID','auto'=>true,'search'=>['ProductName','QuantityPerUnit'],'filter'=>'CategoryID','list'=>['ProductID','ProductName','UnitPrice','UnitsInStock','Discontinued'],'fields'=>[
 'ProductName'=>$text('Nombre del producto',40,true),'SupplierID'=>$select('Proveedor','Suppliers','SupplierID','CompanyName'),'CategoryID'=>$select('Categoría','Categories','CategoryID','CategoryName'),'QuantityPerUnit'=>$text('Presentación',20),
 'UnitPrice'=>$number('Precio unitario',999999.9999,true),'UnitsInStock'=>$number('Existencias',32767),'UnitsOnOrder'=>$number('Unidades solicitadas',32767),'ReorderLevel'=>$number('Punto de reposición',32767),'Discontinued'=>['label'=>'Descontinuado','type'=>'checkbox']
 ]]
];
