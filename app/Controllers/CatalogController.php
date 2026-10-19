<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,Database};
use App\Models\Catalog;
use App\Services\{ValidationService,AuditService};
final class CatalogController extends Controller {
    private function model(): Catalog { return new Catalog((string)($_GET['module']??'')); }
    public function index(): void { $m=$this->model(); $this->view('catalog/index',['title'=>$m->meta['title'],'m'=>$m,'result'=>$m->listing($_GET),'options'=>$m->options()]); }
    public function form(): void { $m=$this->model(); $id=$_GET['id']??null; $this->view('catalog/form',['title'=>($id?'Editar ':'Nuevo ').$m->meta['singular'],'m'=>$m,'id'=>$id,'row'=>$id?$m->find($id):[],'errors'=>[],'options'=>$m->options()]); }
    public function save(): void {
        $m=$this->model(); $id=$_GET['id']??null; if ($id!==null) $m->find($id);
        [$data,$errors]=ValidationService::catalog($m->meta,$_POST,$id!==null);
        if (!$errors) try {
            Database::transaction(function()use($m,$id,$data){ $saved=$id; if ($id!==null) $m->update($id,$data); else $saved=$m->insert($data); AuditService::log($id!==null?'UPDATE':'CREATE',$m->meta['table'],$saved,['fields'=>array_keys($data)]); });
            $this->flash('Registro guardado correctamente.'); $this->redirect('catalog',['module'=>$m->module]);
        } catch (\PDOException $e) { if ($e->getCode()!=='23000') throw $e; $errors['_']='Código duplicado o referencia no válida. Revise los datos.'; }
        http_response_code(422); $this->view('catalog/form',['title'=>'Revisar datos','m'=>$m,'id'=>$id,'row'=>$_POST,'errors'=>$errors,'options'=>$m->options()]);
    }
    public function delete(): void {
        $m=$this->model(); $id=$_POST['id']??''; $m->find($id);
        try { Database::transaction(function()use($m,$id){$m->delete($id); AuditService::log('DELETE',$m->meta['table'],$id);}); $this->flash('Registro eliminado.'); }
        catch (\PDOException $e) { if ($e->getCode()!=='23000') throw $e; $this->flash('No se puede eliminar: existen registros relacionados.','warning'); }
        $this->redirect('catalog',['module'=>$m->module]);
    }
    public function export(): void { $m=$this->model(); AuditService::log('EXPORT',$m->meta['table']); $this->csv($m->module,$m->meta['list'],$m->listing($_GET,true)['rows']); }
}
