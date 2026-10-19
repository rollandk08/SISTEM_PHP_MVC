<?php
function e($value): string { return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function url(string $route='dashboard',array $params=[]): string { return 'index.php?'.http_build_query(['r'=>$route]+$params); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(\App\Core\Security::csrf()).'">'; }
function can_write(): bool { return in_array($_SESSION['user']['role']??'', ['ADMIN','OPERADOR'],true); }
function is_admin(): bool { return ($_SESSION['user']['role']??'')==='ADMIN'; }
function money($n): string { return '$ '.number_format((float)$n,2,'.',','); }
