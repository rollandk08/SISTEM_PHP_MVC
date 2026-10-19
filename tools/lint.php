<?php
if(PHP_SAPI!=='cli')exit;
$root=dirname(__DIR__);$failed=0;$count=0;
foreach(['app','config','public','tools','tests'] as $dir){
 if(!is_dir($root.'/'.$dir))continue;
 foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir,FilesystemIterator::SKIP_DOTS)) as $file){$path=$file->getPathname();if($file->getExtension()!=='php')continue;$count++;passthru(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path),$code);if($code)$failed++;}
}
$count++;passthru(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($root.'/index.php'),$code);if($code)$failed++;
echo "$count archivos; $failed errores de sintaxis.\n";exit($failed?1:0);
