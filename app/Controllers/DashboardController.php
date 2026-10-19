<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\Analytics;
use App\Services\AuditService;
final class DashboardController extends Controller {
    public function index(): void { $this->view('dashboard/index',['title'=>'Resumen general','data'=>(new Analytics)->dashboard()]); }
    public function reports(): void { $rows=(new Analytics)->sales($_GET);$this->view('reports/index',['title'=>'Reportes de ventas','rows'=>$rows]); }
    public function export(): void { AuditService::log('EXPORT','sales_report');$this->csv('ventas-por-cliente',['Código','Empresa','País','Pedidos','Venta neta'],(new Analytics)->sales($_GET)); }
}
