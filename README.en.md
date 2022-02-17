<h1>REPOMANAGER</h1>

Repomanager is a packages for managing repository

Designed for enterprise use and to facilitate the deployment of updates on large Linux server farms, it makes it easy to create public repos mirrors (ex: Debian repos, CentOS, or other third-party vendors) and manage several versions per environment.

<b>Main features:</b>

- Create mirrors, update them, duplicate them, make them accessible to client servers.
- Sign its packet repos with GPG.
- System of environments (ex: preprod, prod...) allowing to make mirrors accessible to particular server environments
- Automatic schedules to execute the above actions at a desired date/time.


![alt text](https://github.com/lbr38/repomanager/blob/beta/screenshots/repomanager.png?raw=true)
![alt text](https://github.com/lbr38/repomanager/blob/beta/screenshots/repomanager-2.png?raw=true)
![alt text](https://github.com/lbr38/repomanager/blob/beta/screenshots/repomanager-3.png?raw=true)

<b>Resources:</b>

Repomanager only requires a web service + PHP (7 minimum) and sqlite.

The CPU and RAM are mainly used during the creation of mirrors and according to the number of packages to be copied and signed.
The disk space is to be adapted according to the number of mirrors created / number of packages they contain.


<h1>Version beta</h1>

Installation compatible on Redhat/CentOS and Debian/Ubuntu systems:
- Debian 10, Ubuntu bionic
- CentOS 7, 8, Fedora 33

<p>Latest Beta features</p>

| **Feature** | **Beta** |
|----------|---------------|
| Create mirrors from public repos | ✅ |
| Create local rests | ✅ |
| Update mirrors | ✅ |
| GPG Signed | ✅ |
| Enable / Restore repo | ✅ |
| Charger des patchs zero-day | ✅ |
| **Automating** | **Beta** |
| Schedule mirror updates | ✅ |
| Planning reminders (mail) | ✅ |
| **Statistics** | **Beta** |
| Graphs on the use and evolution of repos | ✅ |


<b>Dependencies</b>

repomanager requires the presence of certain software commonly installed on Linux distributions, such as:
<pre>
rsync, curl, wget, gnupg2
</pre>

As well as some specific software needed to create repo mirrors such as:
<pre>
yum-utils and createrepo (CentOS/Redhat)
rpmresign (RPM4 perl module) for signing repos (CentOS/Redhat)
debmirror (Debian)
</pre>

Repomanager will install these dependencies itself if it detects that they are not present on the system. So make sure that the server has at least access to the base repositories of its OS.

Note for Redhat/CentOS systems: adapt the SELinux configuration and ensure that it does not prevent the correct execution of PHP.


<h2>Installation</h2>

<b>Server web + PHP</b>

Repomanager is administered from a web interface. It is therefore necessary to install a web+php service and configure a dedicated vhost.

Repomanager is only tested with nginx+php-fpm (PHP 7.x) but compatibility with apache is not excluded.

<pre>
# Redhat / CentOS
yum install nginx php-fpm php-cli php-pdo php-json sqlite

# Debian
apt update && apt install nginx php-fpm php-cli php7.4-json php7.4-sqlite3 sqlite3
</pre>

<b>SQLite</b>

Make sure the sqlite extension for php is enabled (usually in /etc/php.d/):

<pre>
# Debian
vim /etc/php/7.4/mods-available/sqlite3.ini

# Redhat/CentOS
vim /etc/php.d/20-sqlite3.ini

extension=sqlite3.so
</pre>

<b>Vhost</b>

Sample vhost for nginx.

Adapt values:
  - path to php unix socket
  - the two variables $WWW_DIR and $REPOS_DIR
  - directives server_name, access_log, error_log, ssl_certificate, ssl_certificate_key
<pre>
#### Repomanager vhost ####

# Disable some logging
map $request_uri $loggable {
        /run.php?reload 0;
        default 1;
}

# Path to unix socket
upstream php-handler {
        server unix:/var/run/php-fpm/php-fpm.sock;
}

server {
        listen SERVER-IP:80 default_server;
        server_name SERVERNAME.MYDOMAIN.COM;

        # Path to log files
        access_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_access.log combined if=$loggable;
        error_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_error.log;

        # Redirect to https
        return 301 https://$server_name$request_uri;
}

server {
        # Set repomanager base directories variables
        set $WWW_DIR '/var/www/repomanager'; # default is /var/www/repomanager
        set $REPOS_DIR '/home/repo';         # default is /home/repo

        listen SERVER-IP:443 default_server ssl;
        server_name SERVERNAME.MYDOMAIN.COM;

        # Path to log files
        access_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_ssl_access.log combined if=$loggable;
        error_log /var/log/nginx/SERVERNAME.MYDOMAIN.COM_ssl_error.log;

        # Path to SSL certificate/key files
        ssl_certificate PATH-TO-CERTIFICATE.crt;
        ssl_certificate_key PATH-TO-PRIVATE-KEY.key;

        # Security headers
        add_header Strict-Transport-Security "max-age=15768000; includeSubDomains; preload;" always;
        add_header Referrer-Policy "no-referrer" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-Download-Options "noopen" always;
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Permitted-Cross-Domain-Policies "none" always;
        add_header X-Robots-Tag "none" always;
        add_header X-XSS-Protection "1; mode=block" always;

        # Remove X-Powered-By, which is an information leak
        fastcgi_hide_header X-Powered-By;

        # Path to repomanager root directory
        root $WWW_DIR/public;

        # Custom error pages
        error_page 404 /custom_404.html;
        error_page 500 502 503 504 /custom_50x.html;

        location = /custom_404.html {
                root $WWW_DIR/public/custom_errors;
                internal;
        }

        location = /custom_50x.html {
                root $WWW_DIR/public/custom_errors;
                internal;
        }

        location = /robots.txt {
                deny all;
                log_not_found off;
                access_log off;
        }

        # Enable gzip but do not remove ETag headers
        gzip on;
        gzip_vary on;
        gzip_comp_level 4;
        gzip_min_length 256;
        gzip_proxied expired no-cache no-store private no_last_modified no_etag auth;
        gzip_types application/atom+xml application/javascript application/json application/ld+json application/manifest+json application/rss+xml application/vnd.geo+json application/vnd.ms-fontobject application/x-font-ttf application/x-web-app-manifest+json application/xhtml+xml application/xml font/opentype image/bmp image/svg+xml image/x-icon text/cache-manifest text/css text/plain text/vcard text/vnd.rim.location.xloc text/vtt text/x-component text/x-cross-domain-policy;

        location / {
                rewrite ^ /index.php;
        }

        location ~ \.php$ {
                root $WWW_DIR/public;
                include fastcgi_params;
                fastcgi_param SCRIPT_FILENAME $request_filename;
                #include fastcgi.conf;
                fastcgi_param HTTPS on;
                # Avoid sending the security headers twice
                fastcgi_param modHeadersAvailable true;
                fastcgi_pass php-handler;
                fastcgi_intercept_errors on;
                fastcgi_request_buffering off;
        }

        location ~ \.(?:css|js|woff2?|svg|gif|map)$ {
                try_files $uri $uri/ =404;
                add_header Cache-Control "public, max-age=15778463";
                add_header Strict-Transport-Security "max-age=15768000; includeSubDomains; preload;" always;
                add_header Referrer-Policy "no-referrer" always;
                add_header X-Content-Type-Options "nosniff" always;
                add_header X-Download-Options "noopen" always;
                add_header X-Frame-Options "SAMEORIGIN" always;
                add_header X-Permitted-Cross-Domain-Policies "none" always;
                add_header X-Robots-Tag "none" always;
                add_header X-XSS-Protection "1; mode=block" always;
                access_log off;
        }

        location ~ \.(?:png|html|ttf|ico|jpg|jpeg|bcmap)$ {
                access_log off;
        }

        location = /main.conf {
                root $REPOS_DIR/profiles/_reposerver;
                allow all;
        }

        location /repo {
                alias $REPOS_DIR;
        }

        location /profiles {
                root $REPOS_DIR;
                allow all;
                autoindex on;
        }
}
</pre>


<b>Repomanager</b>

The program requires 2 directories chosen by the user at the time of installation:
<pre>
Installation directory (by default /var/www/repomanager/)
Repository mirror storage directory (default /home/repo/)
</pre>

The installation must be done as root or sudo so that the correct permissions are correctly established on the directories used by repomanager.

Download the latest release available in .tar.gz format. All releases are visible here: https://github.com/lbr38/repomanager/releases
<pre>
RELEASE="v2.5.1-beta" # choose the release
cd /tmp
wget https://github.com/lbr38/repomanager/releases/download/$RELEASE/repomanager_$RELEASE.tar.gz
tar xzf repomanager_$RELEASE.tar.gz
cd /tmp/repomanager/
</pre>

Launch the installation of repomanager:
<pre>
chmod 700 repomanager
sudo ./repomanager --install
</pre>