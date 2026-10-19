pipeline {
  agent { label 'php-mariadb-lab' }
  options { disableConcurrentBuilds(); timestamps() }
  environment {
    APP_ENV_FILE = '.env.testing'
    // Credencial Jenkins tipo Username with password; SQL con permiso solo sobre isam_northwind_test.
    TEST_DB = credentials('isam-test-db')
  }
  stages {
    stage('Checkout') { steps { checkout scm } }
    stage('Dependencias') { steps { sh 'composer install --prefer-dist --no-interaction' } }
    stage('Configurar laboratorio') {
      steps {
        sh '''
          set +x
          cp config/environments/testing.env.example .env.testing
          php -r '$f=fopen(".env.testing","a");fwrite($f,"DB_USERNAME=".getenv("TEST_DB_USR").PHP_EOL."DB_PASSWORD=".getenv("TEST_DB_PSW").PHP_EOL."APP_KEY=".bin2hex(random_bytes(32)).PHP_EOL);fclose($f);'
          php tools/console.php install
        '''
      }
    }
    stage('Sintaxis') { steps { sh 'composer lint' } }
    stage('Pruebas') { steps { sh 'php vendor/bin/phpunit --log-junit build/phpunit.xml' } }
  }
  post {
    always { junit allowEmptyResults: true, testResults: 'build/phpunit.xml' }
    cleanup { sh 'rm -f .env.testing' }
  }
}
