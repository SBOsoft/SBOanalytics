# SBOanalytics
Web site log analytics


## Installation

### `git clone` for development environments

  - Clone the repository from https://github.com/SBOsoft/SBOanalytics. 
  - containers folder contains docker files and docker-compose.yml. 
variables.env files in each folder, e.g containers/web/variables.env, contain container settings. 
containers/web folder contains a dummy private key and certificate, which you should ideally replace with your own.
  - db folder contains database scripts which will be applied to the mysql container. Database files will be stored in 
containers/mysql_data folder which will be mounted by the mysql container.

Warning: Authentication is disabled by default when using the setup in containers folder. **It's not for production use.**

### Production deployments

Only webapp/DocumentRoot folder is needed for production deployments. If you want to deploy SBOanalytics as a separate virtual host, 
then the document root for the virtual host must point to webapp/DocumentRoot folder.
For example if you copied webapp/DocumentRoot folder to /var/www/sboanalytics/webapp/DocumentRoot 
then DocumentRoot for the Apache virtual host must point to /var/www/sboanalytics/webapp/DocumentRoot. For example:

```
<VirtualHost *:443>
	ServerName sboanalytics.example.com
	DocumentRoot /var/www/sboanalytics/webapp/DocumentRoot
...
```

Please review webapp/DocumentRoot/.htaccess first. You can define the required environment variables in VirtualHost settings,
e.g /etc/apache2/sites-enabled/yoursite.conf, and leave .htaccess as is. 
If you set environment variables in the .htaccess file then you will need to manually apply your changes during upgrades. 
If you configure environment variables  in the virtual host and leave SetEnv calls in the .htaccess then you will 
not need to manually apply configuration changes during upgrades.

#### Available configuration options 

  - SBO_AUTH_TYPE authentication type, only supported values are none and single at the moment. Anonymous access will be 
enabled when SBO_AUTH_TYPE is none. When SBO_AUTH_TYPE is set to **single** then there will be only 1 user, 
defined by SBO_AUTH_SINGLE_USER and SBO_AUTH_SINGLE_PWD environment variables.
  - SBO_DB_HOST mysql database host, such as 127.0.0.1 or 127.0.0.1:12345 where 12345 is the mysql port number.
  - SBO_DB_NAME name of the sboanalytics database
  - SBO_DB_USER mysql user
  - SBO_DB_PASSWORD mysql password
  - SBO_AUTH_SINGLE_USER username for SBO_AUTH_TYPE single
  - SBO_AUTH_SINGLE_PWD password for SBO_AUTH_TYPE single


By default only deployments on an Apache web server with PHP 8.x is supported. If you want to deploy on a different 
environment then you will need to migrate url rewrite rules from webapp/DocumentRoot/.htaccess to the other environment.
