<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit;
$email=strtolower(trim($argv[1]??''));$name=trim($argv[2]??'Administrador');$password=getenv('ISAM_ADMIN_PASSWORD')?:'';
if(!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>150 || mb_strlen($name)>100 || strlen($password)<12 || strlen($password)>72){fwrite(STDERR,"Uso: defina ISAM_ADMIN_PASSWORD temporalmente (12-72 caracteres), luego php tools/create_admin_sql.php correo nombre. Redirija la salida a un archivo SQL privado.\n");exit(1);}
// Hex literals avoid SQL-mode-dependent escaping and preserve UTF-8.
$literal=static fn($s)=>'CONVERT(0x'.bin2hex($s).' USING utf8mb4)';
echo 'INSERT INTO app_users(name,email,role,password_hash) VALUES('.$literal($name).','.$literal($email).",'ADMIN',".$literal(password_hash($password,PASSWORD_BCRYPT,['cost'=>12])).");\n";
