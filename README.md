htaccess file content   
  
<VirtualHost *:80>  
  
        ServerAdmin webmaster@localhost  
        DocumentRoot /var/www/html/web 
        <Directory /var/www/html/web>  
                        Options Indexes FollowSymLinks  
                        AllowOverride All  
                        Require all granted  
        </Directory>  
  
        ErrorLog ${APACHE_LOG_DIR}/error.log  
        CustomLog ${APACHE_LOG_DIR}/access.log combined  
  
</VirtualHost>  
