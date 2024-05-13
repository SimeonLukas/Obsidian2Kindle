<?php
// debug on
// error_reporting(E_ALL);
error_reporting(0);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

// Settings
$BACKGROUND = "https://source.unsplash.com/random/1920x1080";
$CLEANUP = true; // remove .epub files after the end of the day

//Script
//Cleanup
if ($CLEANUP) {
    $datefolder = date('ymd');
    scandir('epubs');

    foreach (glob('epubs/*') as $folders) {
        if ($folders != "epubs/" . $datefolder && is_dir($folders)) {
            deleteDirectory($folders);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "<html style = \"background-image: url(" . $BACKGROUND . "); background-size: cover;\">";
    echo "<head>";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">";
    echo "<link rel=\"stylesheet\" href=\"/css/index.css\">";
    echo "</head>";
    echo "<body style=\"height: unset; box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.2); padding: 0 20px 20px 20px; max-width: 500px; border-radius: 10px; margin: 33px auto; background-color: #ffffffbb; backdrop-filter: blur(5px);\">";
    echo "<h1>md2epub</h1><p>Add your Credentials to get your .epub. You'll get your Credentials from your Settings in Obsidian.</p>";
    echo "<form action=\"index.php\" method=\"get\"><input name=\"name\"  placeholder=\"Author\"/><br>";
    echo "<br><input type=\"email\" name=\"mail\" placeholder=\"Email\"/> <br><br><input type=\"submit\" value=\"Show .epub files\"/></form>";

    if (isset($_GET['name']) && isset($_GET['mail']) && $_GET['name'] != '' && $_GET['mail'] != '') {
        $name = $_GET['name'];
        $mail = $_GET['mail'];
        $date = date('d.m.y');
        $datefolder = date('ymd');
        $folder = hash('sha256', $name . $date . $mail);
        $folder = urlencode($folder);

        if (file_exists('epubs/' . $datefolder . '/' . $folder) && count(scandir('epubs/' . $datefolder . '/' . $folder)) > 2) {
            $files = scandir('epubs/' . $datefolder . '/' . $folder);
            echo "<hr><h2>Epubs for " . $name . "</h2>";
            if ($CLEANUP) {
                echo "<p>Documents are available until the end of the day.</p>";
            }

            foreach ($files as $file) {

                if (is_dir('epubs/' . $datefolder . '/' . $folder . '/' . $file)) {
                    continue;
                }

                if (strpos($file, 'epub') !== false) {
                    echo "<a  class=\"button\" href=\"epubs/" . $datefolder . "/" . $folder . "/" . $file . "\">" . $file . "</a><br>";
                }

            }
        } else {
            echo "<hr><h2>There are no Epubs for " . $name . "</h2>";
        }

    }
    echo "<hr><br>";
    echo "<a class=\"button\" href=\"https://github.com/SimeonLukas/Obsidian2Kindle\">View on GitHub</a> <a class=\"button error\" href=\"https://github.com/SimeonLukas/Obsidian2Kindle/issues\">Report an Issue</a> ";
    echo '<a class="button success" href="https://www.buymeacoffee.com/simeonlukas" target="_blank" >Buy me a ☕</a><br><br>';
    echo '<p style="text-align: right;"><small style="color: #9e9e9e; font-family: monospace;">Made with ❤️ by <a href="https://github.com/SimeonLukas" target="_blank">Simeon</a></small></p>';
    echo "<br></body>";
    die;

}

$allowedOrigins = [
    'http://localhost',
    'app://obsidian.md',
    'capacitor://localhost',
];
if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    $http_origin = $_SERVER['HTTP_ORIGIN'];
} else {
    $http_origin = "app://obsidian.md";
}
header("Access-Control-Allow-Origin: $http_origin");
header("Access-Control-Allow-Headers: Content-Type, origin");

// Check for extensions

if (!extension_loaded('gd')) {
    echo '❌ Error: Please install php extension: gd';
    die;
}

if (!extension_loaded('zip')) {
    echo '❌ Error: Please install php extension: zip';
    die;
}

// Check for Postrequest content
if ($_POST['text'] == '') {
    echo '❌ Error: No content found';
    die;
}

// Check for first Headline

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';
require 'include/TPEpubCreator.php';
include 'include/parsedown.php';
include 'include/parsdownExtra.php';
$Parsedown = new ParsedownExtra();
$date = date('d.m.y H-i-s');
// Fix for Failure in Plugin
$_POST['text'] = "# " . substr($_POST['text'], 1);
$text = $Parsedown->text($_POST['text']);
$_POST['text'] = $text;

// add unique id to the h1, h2, h3
$text = preg_replace_callback('/<h([1-3])>(.*?)<\/h[1-3]>/', function ($matches1) {
    $id = 'title_' . uniqid();
    return '<h' . $matches1[1] . ' id="' . $id . '">' . $matches1[2] . '</h' . $matches1[1] . '>';
}, $text);
// replace all carets
$text = preg_replace('/<div class="footnotes">\n<hr \/>/', '<div class="footnotes">', $text);
$text = preg_replace('/\^(.*?)</', '<', $text);
// replace all between &&
$text = preg_replace('/%%(.*?)%%/', '', $text);
// do the close tag after an image
$text = preg_replace_callback('/<img(.*?)>/', function ($image) {
    return '<img' . $image[1] . '></img>';
}, $text);
// make links from headers
$toc = '';
$number = 1;
preg_replace_callback('/<h([1-3]) id="(.*?)">(.*?)<\/h[1-3]>/', function ($matches3) use (&$toc, &$number) {
    $number = $number + 1;
    $toc .= '<a href="page' . $number . '.xhtml">' . $matches3[3] . '</a><br />';
}, $text);

$_POST['text'] = $text;

$epub = new TPEpubCreator();

if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}
if (!file_exists('temp_folder')) {
    mkdir('temp_folder', 0777, true);
}
if (!file_exists('epubs')) {
    mkdir('epubs', 0777, true);
}
$datefolder = date('ymd');

$userfolder = hash('sha256', $_POST['author'] . date('d.m.y') . $_POST['email']);
$userfolder = urlencode($userfolder);

mkdir('epubs/' . $datefolder . '/' . $userfolder, 0777, true);

$epub->temp_folder = 'temp_folder/';
$epub->epub_file = 'epubs/' . $datefolder . '/' . $userfolder . '/' . $_POST['title'] . '.epub';

$epub->title = $_POST['title'];
$epub->creator = $_POST['author'];
$epub->language = $_POST['lang'];
$epub->rights = 'Public Domain';
$epub->publisher = $_POST['author'];

$epub->css = file_get_contents('css/base.css');

$cover = imagecreatefromstring(file_get_contents('cover.png'));
$text_color = imagecolorallocate($cover, 0, 0, 0);
imagettftext($cover, 50, 0, 5, 220, $text_color, 'fonts/Tahu!.ttf', $_POST['title']);
imagettftext($cover, 40, 0, 5, 100, $text_color, 'fonts/Karu-ExtraLight.ttf', $_POST['author']);
imagettftext($cover, 30, 0, 5, 1550, $text_color, 'fonts/Karu-ExtraLight.ttf', 'OBSIDIAN');
// save image to file
imagepng($cover, 'uploads/cover.png');
$epub->AddImage('uploads/cover.png', false, true);

if ($_POST['Bilder'] != '') {

    $Bilder = explode(',', $_POST['Bilder']);
    for ($i = 0; $i < count($Bilder); $i++) {
        file_put_contents('uploads/' . $Bilder[$i], base64_decode($_POST['file' . $i]));
        $epub->AddImage('uploads/' . $Bilder[$i], false, false);
    }
}

if ($_POST['toc'] == 'true') {
    if ($_POST['lang'] != 'de') {
        $epub->AddPage("<h1>Content</h1>" . $toc, false, 'Content');
    } else {
        $epub->AddPage("<h1>Inhalt</h1>" . $toc, false, 'Inhalt');
    }
} else {}

// if text contains <h

$pages = explode('<h', $text);

foreach ($pages as $page) {
    // skip first page because it's the title
    // if ($page == $pages[0]) {
    //     continue;
    // }
    if ($page != '') {
        // replace <i> and <b>
        $pagewoi = preg_replace_callback('/<i>(.*?)<\/i>/', function ($matches5) {
            return $matches5[1];
        }, $page);
        $headline = strpos($pagewoi, '>');
        $headline = substr($pagewoi, $headline + 1);
        $headline = substr($headline, 0, strpos($headline, '<'));
        $epub->AddPage('<h' . $page, false, $headline);
    }
}

// $epub->AddPage($text, false, $_POST['title']);

if (!$epub->error) {

    // Since this can generate new errors when creating a folder
    // We'll check again
    $epub->CreateEPUB();

    // If there's no error here, you're e-book is successfully created
    if (!$epub->error) {

        if (isset($_POST['title'])) {

            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';

            try {
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
                $mail->isSMTP(); //Send using SMTP
                $mail->Host = $_POST['host']; //Set the SMTP server to send through
                $mail->SMTPAuth = true; //Enable SMTP authentication
                $mail->Username = $_POST['user']; //SMTP username
                $mail->Password = $_POST['pass']; //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
                $mail->Port = intval($_POST['port']); //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

                $mail->setFrom($_POST['email'], $_POST['author']);
                $mail->addAddress($_POST['kindle'], $_POST['author']); // Add a recipient

                // rename file to title.png
                $mail->addAttachment('epubs/' . $datefolder . '/' . $userfolder . '/' . $_POST['title'] . '.epub');

                // $mail->isHTML(true);
                $mail->Subject = $_POST['title'];
                $mail->Body = ' ';
                $mail->send();
                if ($_POST['lang'] != 'de') {
                    echo '👍 Your Ebook has been sent!';
                } else {
                    echo '👍 Ebook wurde versandt!';
                }
                // unlink('epubs/' . $_POST['title'].'.epub');
                $files = glob('uploads/*'); // get all file names
                foreach ($files as $file) { // iterate files
                    if (is_file($file)) {
                        unlink($file);
                    }
                    // delete file
                }
                $file = fopen("counter.log", "a");
                fwrite($file, $date . "\n");
                $lines = file('counter.log');
                $count = count($lines);
                fclose($file);
                $file = fopen("counter", "w");
                fwrite($file, '
                        {
                            "schemaVersion": 1,
                            "label": "Books exported",
                            "message": "' . $count . '",
                            "color": "brightgreen"
                          }');
                fclose($file);

            } catch (Exception $e) {
                if ($_POST['lang'] != 'de') {
                    echo "👎 Your Ebook could not be sent! Just try it again!😊 Error: {$mail->ErrorInfo}";
                } else {
                    echo "👎 Ebook wurde nicht versandt! Versuchs einfach nochmal!😊 Error: {$mail->ErrorInfo}";
                }
                // delete all files in uploads folder
                $files = glob('uploads/*'); // get all file names
                foreach ($files as $file) { // iterate files
                    if (is_file($file)) {
                        unlink($file);
                    }
                    // delete file
                }

            }

        }

    }

} else {
    // If for some reason you're e-book hasn't been created, you can see whats
    // going on
    echo $epub->error;
}

die;

function deleteDirectory($dirPath)
{
    if (is_dir($dirPath)) {
        $files = scandir($dirPath);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $dirPath . '/' . $file;
                if (is_dir($filePath)) {
                    deleteDirectory($filePath);
                } else {
                    unlink($filePath);
                }
            }
        }
        rmdir($dirPath);
    }
}
