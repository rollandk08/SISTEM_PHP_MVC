param([string]$XamppPath='C:\xampp',[switch]$SkipComposer)
$ErrorActionPreference='Stop'
$taskRoot=Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $taskRoot
$taskPhp=Join-Path $XamppPath 'php\php.exe'
if(!(Test-Path -LiteralPath $taskPhp)){throw 'No se encontró PHP de XAMPP.'}
if(!(Test-Path -LiteralPath '.env')){
 Copy-Item -LiteralPath '.env.example' -Destination '.env'
 $taskKey=& $taskPhp -r 'echo bin2hex(random_bytes(32));'
 (Get-Content -LiteralPath '.env' -Raw).Replace('CAMBIAR_POR_64_CARACTERES_ALEATORIOS',$taskKey) | Set-Content -LiteralPath '.env' -Encoding utf8
}
$env:Path=(Join-Path $XamppPath 'php')+';'+$env:Path
if(!$SkipComposer){
 if(Test-Path -LiteralPath 'C:\composer\composer.phar'){& $taskPhp 'C:\composer\composer.phar' install --prefer-dist --no-interaction}
 else{composer install --prefer-dist --no-interaction}
 if($LASTEXITCODE -ne 0){throw 'Composer falló.'}
}
& $taskPhp tools/console.php install
if($LASTEXITCODE -ne 0){throw 'Instalación fallida. Compruebe MySQL y .env.'}
& $taskPhp tools/verify.php
if($LASTEXITCODE -ne 0){throw 'La verificación falló.'}
Write-Host 'Listo. Abra http://localhost/SISTEM_PHP_MVC/public/ y consulte README.md.'
