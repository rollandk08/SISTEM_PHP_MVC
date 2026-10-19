<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
final class AuditService {
    public static function log(string $action,string $entity,$id=null,array $detail=[]): void {
        $q=Database::connection()->prepare('INSERT INTO app_audit(user_id,action,entity,record_id,detail,ip) VALUES(?,?,?,?,?,?)');
        $q->execute([$_SESSION['user']['id']??null,$action,$entity,$id,json_encode($detail,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$_SERVER['REMOTE_ADDR']??'CLI']);
    }
}
