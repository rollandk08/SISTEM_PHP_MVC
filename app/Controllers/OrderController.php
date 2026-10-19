<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Controller,HttpException};
use App\Models\Order;
use App\Services\{OrderService,AuditService};
final class OrderController extends Controller {
    public function index(): void { $this->view('orders/index',['title'=>'Pedidos','result'=>(new Order)->listing($_GET)]); }
    public function form(): void { $id=isset($_GET['id'])?(int)$_GET['id']:null; $this->view('orders/form',['title'=>$id?'Editar pedido #'.$id:'Nuevo pedido','id'=>$id,'row'=>$id?(new Order)->find($id):['OrderDate'=>date('Y-m-d'),'lines'=>[]],'options'=>(new Order)->options(),'error'=>null]); }
    public function show(): void { $this->view('orders/show',['title'=>'Detalle del pedido','row'=>(new Order)->find((int)($_GET['id']??0))]); }
    public function save(): void { $id=isset($_GET['id'])?(int)$_GET['id']:null;try { $id=(new OrderService)->save($_POST,$id);$this->flash('Pedido y existencias guardados en una transacción.');$this->redirect('order-show',['id'=>$id]); }catch(HttpException $e){if($e->status!==422)throw $e;$error=$e->getMessage();}catch(\PDOException $e){if($e->getCode()!=='23000')throw $e;$error='Cliente, empleado o transportista inválido.';} http_response_code(422);$this->view('orders/form',['title'=>'Revisar pedido','id'=>$id,'row'=>$_POST,'options'=>(new Order)->options(),'error'=>$error]); }
    public function delete(): void { (new OrderService)->delete((int)($_POST['id']??0));$this->flash('Pedido eliminado y existencias devueltas.');$this->redirect('orders'); }
    public function export(): void { AuditService::log('EXPORT','Orders');$this->csv('pedidos',['Pedido','Cliente','Fecha','País','Flete','Venta neta'],(new Order)->listing($_GET,true)['rows']); }
}
