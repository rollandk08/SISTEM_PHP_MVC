<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class User {
    public function byEmail(string $email) { $q=Database::connection()->prepare('SELECT * FROM app_users WHERE email=? AND active=1'); $q->execute([$email]); return $q->fetch(); }
}
