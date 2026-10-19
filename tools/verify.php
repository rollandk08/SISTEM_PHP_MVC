<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/app/bootstrap.php';
$pdo=\App\Core\Database::connection();$counts=[];
foreach(['Categories'=>8,'Customers'=>93,'Employees'=>9,'EmployeeTerritories'=>49,'Orders'=>830,'Order Details'=>2155,'Products'=>77,'Region'=>4,'Shippers'=>3,'Suppliers'=>29,'Territories'=>53,'CustomerCustomerDemo'=>0,'CustomerDemographics'=>0] as $table=>$expected){$count=(int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();$counts[$table]=['original'=>$expected,'actual'=>$count];}
$fk=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME NOT LIKE 'app_%'")->fetchColumn();
$out=['database'=>\App\Core\Env::get('DB_DATABASE'),'server'=>$pdo->query('SELECT VERSION()')->fetchColumn(),'counts'=>$counts,'northwind_foreign_keys'=>$fk,'discount_rows'=>(int)$pdo->query('SELECT COUNT(*) FROM `Order Details` WHERE Discount>0 AND Discount<1')->fetchColumn(),'net_sales'=>$pdo->query('SELECT ROUND(SUM(UnitPrice*Quantity*(1-Discount)),2) FROM `Order Details`')->fetchColumn(),'checked_at'=>date(DATE_ATOM)];
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
if(!is_dir(ROOT.'/build'))mkdir(ROOT.'/build',0700,true);file_put_contents(ROOT.'/build/database-verification.json',json_encode($out,JSON_PRETTY_PRINT));
exit($fk===13?0:1);
