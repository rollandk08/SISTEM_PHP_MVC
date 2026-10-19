<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
use App\Services\{MigrationService,BackupService};
try{
 $command=$argv[1]??'help';
 switch($command){
  case 'install': (new MigrationService)->run();(new MigrationService)->seed();break;
  case 'migrate': (new MigrationService)->run();break;
  case 'backup': echo (new BackupService)->create().PHP_EOL;break;
  case 'restore':
   if(($argv[3]??'')!=='--confirm='.\App\Core\Env::get('DB_DATABASE'))throw new RuntimeException('Restauración reemplaza datos. Use restore archivo.json --confirm=NOMBRE_BASE tras revisar el respaldo.');
   (new BackupService)->restore(basename($argv[2]??''));echo "Restauración confirmada. Sesiones revocadas.\n";break;
  default: echo "Uso: php tools/console.php install|migrate|backup|restore archivo.json --confirm=BASE\n";
 }
}catch(Throwable $e){fwrite(STDERR,'ERROR: '.$e->getMessage().PHP_EOL);exit(1);}
