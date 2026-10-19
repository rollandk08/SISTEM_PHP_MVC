<?php
use App\Controllers\{AuthController,CatalogController,OrderController,ImportController,DashboardController,AdminController,BackupController};
$all=['ADMIN','OPERADOR','CONSULTA'];$write=['ADMIN','OPERADOR'];$admin=['ADMIN'];
$router->add('GET','login',[AuthController::class,'form']);$router->add('POST','login',[AuthController::class,'login']);
$router->add('POST','logout',[AuthController::class,'logout'],$all);
$router->add('GET','password',[AuthController::class,'password'],$all);$router->add('POST','password',[AuthController::class,'password'],$all);
$router->add('GET','dashboard',[DashboardController::class,'index'],$all);
foreach(['catalog'=>'index','catalog-export'=>'export'] as $r=>$method)$router->add('GET',$r,[CatalogController::class,$method],$all);
$router->add('GET','catalog-form',[CatalogController::class,'form'],$write);$router->add('POST','catalog-save',[CatalogController::class,'save'],$write);$router->add('POST','catalog-delete',[CatalogController::class,'delete'],$admin);
foreach(['orders'=>'index','order-show'=>'show','orders-export'=>'export'] as $r=>$method)$router->add('GET',$r,[OrderController::class,$method],$all);
$router->add('GET','order-form',[OrderController::class,'form'],$write);$router->add('POST','order-save',[OrderController::class,'save'],$write);$router->add('POST','order-delete',[OrderController::class,'delete'],$admin);
$router->add('GET','imports',[ImportController::class,'index'],$write);$router->add('GET','import-template',[ImportController::class,'template'],$write);$router->add('POST','import-preview',[ImportController::class,'preview'],$write);$router->add('POST','import-commit',[ImportController::class,'commit'],$write);
$router->add('GET','reports',[DashboardController::class,'reports'],$all);$router->add('GET','reports-export',[DashboardController::class,'export'],$all);
$router->add('GET','incidents',[AdminController::class,'incidents'],$all);$router->add('POST','incident-save',[AdminController::class,'incidentSave'],$write);
$router->add('GET','audit',[AdminController::class,'audit'],$admin);$router->add('GET','users',[AdminController::class,'users'],$admin);$router->add('POST','user-save',[AdminController::class,'userSave'],$admin);$router->add('POST','user-toggle',[AdminController::class,'userToggle'],$admin);
$router->add('GET','backups',[BackupController::class,'index'],$admin);$router->add('POST','backup-create',[BackupController::class,'create'],$admin);$router->add('GET','backup-download',[BackupController::class,'download'],$admin);$router->add('POST','backup-restore',[BackupController::class,'restore'],$admin);
