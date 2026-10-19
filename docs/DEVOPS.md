# Versionamiento y DevOps educativo

## Git y GitHub

Git local está inicializado; use `git status` y `git log --oneline`. `.env`, sesiones, vendor, logs, respaldos y resultados locales se excluyen. `composer.lock`, migraciones y assets locales sí se versionan. Revise `git diff --cached` antes de cada commit.

GitHub se deja preparado mediante `.github/workflows/php.yml`, plantilla de PR y guía. No se creó un remoto ni se publicaron archivos. Cuando el propietario autorice y proporcione un repositorio propio:

```powershell
git remote add origin https://github.com/SU_USUARIO/SU_REPOSITORIO.git
git push -u origin main
```

El workflow ejecuta lint y PHPUnit contra MariaDB de laboratorio en un runner Ubuntu, con PHP 8.0 y 8.3. El contenedor de base es exclusivo del CI remoto; no se instala Docker ni se usa para ejecutar el sistema principal en Windows. Las versiones de actions están fijadas por versión mayor; un repositorio público debe revisar periódicamente sus actualizaciones y puede fijarlas por SHA conforme a su política.

## Jenkins

`Jenkinsfile` usa un agente Linux previamente preparado con PHP, Composer y MariaDB. Inyecta credenciales con `credentials('isam-test-db')`. Cree esa credencial y una base de laboratorio terminada en `_test` en su Jenkins de práctica. El pipeline genera configuración efímera, instala migraciones y ejecuta lint/ PHPUnit. No configura ni arranca un servidor Jenkins en esta PC. No utiliza una base de producción.

## Infraestructura como código

- `deploy/ansible/site.yml`: instala Apache/PHP/PDO/MariaDB en un servidor Debian autorizado, copia una release local y configura public/ como raíz. Requiere inventario, SSH y credenciales propios. La base y `.env` se aprovisionan por separado para no colocar secretos en el playbook.
- `deploy/puppet/northwind.pp`: estado de paquetes/directorios/vhost en Debian. Primero ejecute `puppet parser validate`; luego dry run en VM autorizada.
- `deploy/kubernetes/northwind.example.yaml`: diseño educativo de Deployment/Service con Secret y volumen persistente preexistentes. Requiere imagen propia con PHP/PDO y la aplicación; no es el despliegue de XAMPP. No se construyó ninguna imagen ni se creó un clúster. La persistencia de sesiones/backups y la afinidad requieren una estrategia explícita antes de aumentar réplicas.

No ejecute estos ejemplos contra hosts ajenos o de producción. Son material para interpretar y adaptar, no dependencias operativas. Para XAMPP basta Apache + PHP + MySQL.
