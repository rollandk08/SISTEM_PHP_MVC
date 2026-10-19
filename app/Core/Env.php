<?php
declare(strict_types=1);
namespace App\Core;
final class Env {
    private static array $values=[];
    public static function load(string $path): void {
        if (!is_file($path)) throw new \RuntimeException('Falta .env. Copie .env.example y configure la instalación.');
        foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
            $line=trim(preg_replace('/^\xEF\xBB\xBF/','',$line)); if ($line==='' || $line[0]==='#' || !str_contains($line,'=')) continue;
            [$key,$value]=explode('=',$line,2); self::$values[trim($key)]=trim(trim($value),'"\'');
        }
    }
    public static function get(string $key, ?string $default=null): ?string {
        $external=getenv($key); return $external!==false ? $external : (self::$values[$key]??$default);
    }
}
