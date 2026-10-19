<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use App\Core\{Database,HttpException};
use App\Services\{OrderService,ImportService,BackupService};
final class WorkflowTest extends TestCase {
    private function order(array $lines): array {return ['CustomerID'=>'ALFKI','OrderDate'=>'2026-10-19','Freight'=>'0','lines'=>$lines];}
    private function line(int $qty): array {return ['ProductID'=>'1','Quantity'=>(string)$qty,'UnitPrice'=>'18','Discount'=>'0.1'];}
    public function testOrderCreateUpdateDeleteAdjustsStockAtomically(): void {
        $pdo=Database::connection();$stock=(int)$pdo->query('SELECT UnitsInStock FROM Products WHERE ProductID=1')->fetchColumn();$s=new OrderService;$id=null;
        try{$id=$s->save($this->order([$this->line(2)]));$this->assertSame($stock-2,(int)$pdo->query('SELECT UnitsInStock FROM Products WHERE ProductID=1')->fetchColumn());$s->save($this->order([$this->line(3)]),$id);$this->assertSame($stock-3,(int)$pdo->query('SELECT UnitsInStock FROM Products WHERE ProductID=1')->fetchColumn());$s->delete($id);$id=null;$this->assertSame($stock,(int)$pdo->query('SELECT UnitsInStock FROM Products WHERE ProductID=1')->fetchColumn());}finally{if($id)$s->delete($id);}
    }
    public function testInsufficientStockRollsBackEntireOrder(): void {$pdo=Database::connection();$count=(int)$pdo->query('SELECT COUNT(*) FROM Orders')->fetchColumn();try{(new OrderService)->save($this->order([$this->line(32767)]));$this->fail('Se esperaba rechazo.');}catch(HttpException $e){$this->assertSame(422,$e->status);}$this->assertSame($count,(int)$pdo->query('SELECT COUNT(*) FROM Orders')->fetchColumn());}
    public function testImportPreviewThenCommitIsAtomicAndSingleUse(): void {
        $pdo=Database::connection();$s=new ImportService;$file=tempnam(sys_get_temp_dir(),'isam');file_put_contents($file,"CustomerID,CompanyName,Country\nTST88,Empresa prueba importacion,Peru\n");$id=null;
        try{$id=$s->preview('clientes',$file,'prueba.csv');$this->assertSame('PREVIA',$s->find($id)['status']);$this->assertSame(0,(int)$pdo->query("SELECT COUNT(*) FROM Customers WHERE CustomerID='TST88'")->fetchColumn());$s->commit($id);$this->assertSame('COMPLETADO',$s->find($id)['status']);try{$s->commit($id);$this->fail('No debe confirmar dos veces.');}catch(HttpException $e){$this->assertSame(422,$e->status);}}finally{$pdo->exec("DELETE FROM Customers WHERE CustomerID='TST88'");if($id){$pdo->prepare('DELETE FROM app_import_rows WHERE import_id=?')->execute([$id]);$pdo->prepare('DELETE FROM app_imports WHERE id=?')->execute([$id]);}unlink($file);}
    }
    public function testImportDetectsDatabaseAndFileDuplicates(): void {$s=new ImportService;$file=tempnam(sys_get_temp_dir(),'isam');file_put_contents($file,"CompanyName,Country\nExotic Liquids,UK\nNueva empresa repetida,Peru\nNueva empresa repetida,Peru\n");$id=null;try{$id=$s->preview('proveedores',$file,'duplicados.csv');$batch=$s->find($id);$this->assertSame('INVALIDO',$batch['status']);$this->assertNotEmpty(json_decode($batch['rows'][0]['errors'],true));$this->assertNotEmpty(json_decode($batch['rows'][2]['errors'],true));$this->expectException(HttpException::class);$s->commit($id);}finally{if($id){$pdo=Database::connection();$pdo->prepare('DELETE FROM app_import_rows WHERE import_id=?')->execute([$id]);$pdo->prepare('DELETE FROM app_imports WHERE id=?')->execute([$id]);}unlink($file);}}
    public function testBackupRoundTripPreservesBinaryDataAndRejectsTampering(): void {
        $b=new BackupService;$pdo=Database::connection();$stock=$pdo->query('SELECT UnitsInStock FROM Products WHERE ProductID=1')->fetchColumn();$file=$b->create();$payload=$b->inspect($file);$this->assertCount(20,$payload['tables']);
        $bits=$pdo->query('SELECT ProductID,CAST(Discontinued AS UNSIGNED) AS d FROM Products ORDER BY ProductID')->fetchAll();
        $hashes=[];foreach($payload['tables'] as $table=>$unused)if(!str_starts_with($table,'app_') && $table!=='schema_migrations')$hashes[$table]=hash('sha256',serialize($pdo->query('SELECT * FROM `'.$table.'`')->fetchAll()));
        $pdo->exec('UPDATE Products SET UnitsInStock=0 WHERE ProductID=1');$b->restore($file);$this->assertSame($stock,$pdo->query('SELECT UnitsInStock FROM Products WHERE ProductID=1')->fetchColumn());
        $this->assertSame($bits,$pdo->query('SELECT ProductID,CAST(Discontinued AS UNSIGNED) AS d FROM Products ORDER BY ProductID')->fetchAll());
        foreach($hashes as $table=>$hash)$this->assertSame($hash,hash('sha256',serialize($pdo->query('SELECT * FROM `'.$table.'`')->fetchAll())),$table.' conserva cada valor después de restaurar');
        $path=$b->path($file);$content=json_decode(file_get_contents($path),true);$content['hmac']=str_repeat('0',64);file_put_contents($path,json_encode($content));try{$b->inspect($file);$this->fail('Firma alterada aceptada.');}catch(HttpException $e){$this->assertSame(422,$e->status);}finally{unlink($path);}
    }
}
