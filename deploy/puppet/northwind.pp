# Ejemplo Debian; provisionar release, BD y .env por canal privado antes de operar.
class northwind_lab {
  package { ['apache2', 'php', 'libapache2-mod-php', 'php-mysql', 'php-mbstring', 'mariadb-server']:
    ensure => installed,
  }
  file { '/var/www/northwind':
    ensure => directory, owner => 'root', group => 'www-data', mode => '0750',
    require => Package['apache2'],
  }
  file { '/var/www/northwind/storage':
    ensure => directory, owner => 'www-data', group => 'www-data', mode => '0700',
    require => File['/var/www/northwind'],
  }
  file { '/etc/apache2/sites-available/northwind.conf':
    ensure => file, mode => '0644',
    content => "<VirtualHost *:80>\n ServerName northwind.lab\n DocumentRoot /var/www/northwind/public\n <Directory /var/www/northwind/public>\n Options -Indexes\n AllowOverride All\n Require all granted\n </Directory>\n</VirtualHost>\n",
    require => Package['apache2'], notify => Service['apache2'],
  }
  file { '/etc/apache2/sites-enabled/northwind.conf':
    ensure => link,
    target => '/etc/apache2/sites-available/northwind.conf',
    require => File['/etc/apache2/sites-available/northwind.conf'],
    notify => Service['apache2'],
  }
  service { 'apache2': ensure => running, enable => true, require => Package['apache2'] }
}
include northwind_lab
