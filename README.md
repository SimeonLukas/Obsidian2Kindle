# Project 2: Obsidian2Kindle - Converter
## News
Due to the changes of Amazon to the send-to-kindle feature, .mobi file won't work anymore.
So now the backend is converting to .epub.
I'm sorry but covers not working, there shown in the file but not on the bookshelf.
## Info
A Converter written in PHP to convert .md Files from Obsidian to .epub and sending it to your Kindle.
It is used as Backend for the Plugin https://github.com/SimeonLukas/obsidian-kindle-export.

## I used great Libraries:
https://github.com/PHPMailer/PHPMailer

https://github.com/luizomf/php-epub-creator

https://github.com/erusev/parsedown

## Use it:
Host the Files on your Server, and let the Plugin obsidian-kindle-export point to it. <br>
(See Settings for the Plugin and do not forget the **http://** Prefix)

## Dependencies you need:

1. PHP 8.x
https://www.php.net/downloads
2. The GD Image Library
https://www.php.net/manual/en/image.installation.php
3. The PHP-Zip extension.

### Host it local:
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



