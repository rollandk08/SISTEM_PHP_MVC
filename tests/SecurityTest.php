<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use App\Core\{Security,HttpException};
use App\Services\ValidationService;
final class SecurityTest extends TestCase {
    public function testCsrfAcceptsOnlySessionToken(): void {$_SESSION['csrf']=str_repeat('a',64);Security::verifyCsrf(str_repeat('a',64));$this->expectException(HttpException::class);Security::verifyCsrf('alterado');}
    public function testCsvPreventsSpreadsheetFormulaExecution(): void {$this->assertSame("'=HYPERLINK(\"evil\")",Security::csvCell('=HYPERLINK("evil")'));$this->assertSame("'\t+SUM(A1)",Security::csvCell("\t+SUM(A1)"));$this->assertSame('ALFKI',Security::csvCell('ALFKI'));}
    public function testValidationRejectsMissingAndOversizedFields(): void {$modules=require ROOT.'/config/modules.php';[$data,$errors]=ValidationService::catalog($modules['clientes'],['CustomerID'=>'a','CompanyName'=>str_repeat('X',41)]);$this->assertArrayHasKey('CustomerID',$errors);$this->assertArrayHasKey('CompanyName',$errors);}
    public function testNegativePricesAndFractionalStocksRejected(): void {$modules=require ROOT.'/config/modules.php';[, $errors]=ValidationService::catalog($modules['productos'],['ProductName'=>'Prueba','UnitPrice'=>'-1','UnitsInStock'=>'1.5']);$this->assertArrayHasKey('UnitPrice',$errors);$this->assertArrayHasKey('UnitsInStock',$errors);}
    public function testHtmlEscaping(): void {$this->assertSame('&lt;script&gt;&quot;&amp;',e('<script>"&'));}
}
