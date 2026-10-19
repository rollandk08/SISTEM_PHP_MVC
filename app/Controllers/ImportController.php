<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Database,HttpException};
use App\Services\ImportService;
final class ImportController extends Controller {
    public function index(): void { $q=Database::connection()->prepare('SELECT * FROM app_imports WHERE user_id=? ORDER BY id DESC LIMIT 50');$q->execute([$_SESSION['user']['id']]);$this->view('imports/index',['title'=>'Importar datos','history'=>$q->fetchAll(),'batch'=>isset($_GET['id'])?(new ImportService)->find((int)$_GET['id']):null]); }
    public function preview(): void { $f=$_FILES['csv']??null;if(!$f || $f['error']!==UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']))throw new HttpException(422,'Seleccione un CSV válido de hasta 2 MB.');$id=(new ImportService)->preview((string)($_POST['module']??''),$f['tmp_name'],$f['name']);$this->redirect('imports',['id'=>$id]); }
    public function commit(): void { (new ImportService)->commit((int)($_POST['id']??0));$this->flash('Importación completada. Todas las filas fueron confirmadas.');$this->redirect('imports',['id'=>(int)$_POST['id']]); }
    public function template(): void { $module=$_GET['module']??'';if(!in_array($module,['clientes','proveedores'],true))throw new HttpException(404,'Plantilla no disponible.');$headers=$module==='clientes'?['CustomerID','CompanyName','ContactName','City','Country','Phone']:['CompanyName','ContactName','City','Country','Phone'];$this->csv('plantilla-'.$module,$headers,[]); }
}
