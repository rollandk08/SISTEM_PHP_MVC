<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\{Database,Env,HttpException};
final class AuthMiddleware {
    public static function check(array $roles): void {
        if (empty($_SESSION['user']) || time()-($_SESSION['last_activity']??0)>(int)Env::get('SESSION_TIMEOUT','1800')) { $_SESSION=[]; header('Location: '.url('login'),true,303); exit; }
        $q=Database::connection()->prepare('SELECT id,name,email,role,active,session_version FROM app_users WHERE id=?'); $q->execute([$_SESSION['user']['id']]); $u=$q->fetch();
        if (!$u || !$u['active'] || $u['session_version']!==$_SESSION['user']['session_version']) { $_SESSION=[]; throw new HttpException(401,'Sesión revocada. Inicie sesión nuevamente.'); }
        $_SESSION['user']=$u; $_SESSION['last_activity']=time();
        if (!in_array($u['role'],$roles,true)) throw new HttpException(403,'Su rol no tiene permiso para esta operación.');
    }
}
