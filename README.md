# Obsidian2Kindle - Converter -> md2epub

## Info
A Converter written in PHP to convert .md Files from Obsidian to .epub and sending it to your Kindle.
It is used as Backend for the Plugin https://github.com/SimeonLukas/obsidian-kindle-export.

![Screen Shot](img/screenshot.png)

## Download .epub files

Just provide the Author & Mailadress.

(Test the Backend on: https://md2epub.staneks.de/)

## I used great Libraries:
https://github.com/PHPMailer/PHPMailer

https://github.com/luizomf/php-epub-creator

https://github.com/erusev/parsedown

https://github.com/erusev/parsedown-extra

## Use it:
Host the Files on your Server, and let the Plugin obsidian-kindle-export point to it. <br>
(See Settings for the Plugin and do not forget the **http://** Prefix)

## Easy Installation with Docker

https://hub.docker.com/r/simeonstanek/md2epub

Docker Image:

```shell
    docker pull simeonstanek/md2epub
```


Docker-Compose:

```docker-compose.yml
services:
    server:
        image: simeonstanek/md2epub:latest
        restart: always
        ports:
            - 1234:80

```

## Use without Docker:
### Dependencies you need:

1. PHP 8.x  
https://www.php.net/downloads
2. The GD Image Library  
https://www.php.net/manual/en/image.installation.php
3. The PHP-Zip extension.

### Host it local?
Install PHP on your System --> https://www.php.net/manual/de/install.php

Win: Include 
1. extension=openssl
2. extension=mbstring
3. extension=mysqli
4. extension=gd
5. extension=zip
in your php.ini

Linux: Install it via apt

```shell
sudo apt install php8.1-gd
sudo apt install php8.1-zip
```

Start yout local Server:

```shell 
$ cd ~/ob2ki-directory
$ php -S localhost:8000
```



