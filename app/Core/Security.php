<?php
declare(strict_types=1);
namespace App\Core;
final class Security {
    public static function csrf(): string { return $_SESSION['csrf']??=bin2hex(random_bytes(32)); }
    public static function verifyCsrf(?string $token): void {
        if (!$token || !hash_equals(self::csrf(),$token)) throw new HttpException(403,'La sesión del formulario venció. Actualice la página.');
    }
    public static function csvCell($value): string {
        $value=(string)($value??''); return preg_match('/^[\x00-\x20]*[=+@\-]/u',$value) ? "'".$value : $value;
    }
    public static function headers(): void {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
        header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: same-origin'); header('X-Frame-Options: DENY'); header('Cache-Control: no-store');
    }
}
