<?php
// debug on
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

$allowedOrigins = [
    'http://localhost',
    'app://obsidian.md' ,
    'capacitor://localhost' ,
 ];
 if(in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins))
 {
     $http_origin = $_SERVER['HTTP_ORIGIN'];
 } else {
     $http_origin = "app://obsidian.md";
 }
 header("Access-Control-Allow-Origin: $http_origin");
header("Access-Control-Allow-Headers: Content-Type, origin");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include 'MOBIClass/MOBI.php';
include 'parsedown.php';
$Parsedown = new Parsedown();
$date = date('d.m.y H-i-s');
$text = $Parsedown->text($_POST['text']);
$_POST['text'] = $text;

// $file = fopen("debug.log", "w");
// fwrite($file, $text);
// fclose($file);


if ($_POST['Bilder'] != ''){

$Bilder = explode(',', $_POST['Bilder']);
for ($i = 0; $i < count($Bilder); $i++) {
     file_put_contents('uploads/'.$Bilder[$i], base64_decode($_POST['file'.$i]));
    }
}
// add unique id to the h1, h2, h3
$text = preg_replace_callback('/<h([1-3])>(.*?)<\/h[1-3]>/', function($matches1) {
    $id = 'title_'.uniqid();
    return '<h'.$matches1[1].' id="'.$id.'">'.$matches1[2].'</h'.$matches1[1].'>';
}, $text);

// replace all carets
$text = preg_replace('/\^(.*?)</', '<', $text);
// replace all between &&
$text = preg_replace('/%%(.*?)%%/', '', $text);
// replace all between <div style="page-break-after: always;"></div>
$text = preg_replace('/<div style="page-break-after: always;"><\/div>/', '<div style="page-break-after: always;"><mbp:pagebreak/></div>', $text);

// make links from headers
$toc = '';
preg_replace_callback('/<h([1-3]) id="(.*?)">(.*?)<\/h[1-3]>/', function($matches3) use (&$toc)  {
    // get position in $text
    $pos = strpos($_POST['text'], $matches3[3]);
    $pos = $pos;
    $toc .= '<br><a href="#'.$matches3[2].'" filepos="'.$pos.'">'.$matches3[3].'</a>';
}, $text);


if ($_POST['toc'] == 'true' && str_contains($text, '<h1>') == true) {
    $toc = preg_replace_callback('/filepos="(.*?)"/', function($matches4) use (&$toc) {
    $pos = $matches4[1] + strlen($toc) + strlen($_POST['title']) + 130;
    return 'filepos="'.$pos.'"';
}, $toc); }

$_POST['text'] = $text;

$mobi = new MOBI();

        $content = new MOBIFile();
        $content->set('title', $_POST['title']);
        $content->set('author', $_POST['author']);
        $content->set('publisher', $_POST['author']);
        
// if ($_POST['cover'] != 'false') {
//     file_put_contents('uploads/cover.png', base64_decode($_POST['cover']));
//     $cover = imagecreatefromstring(file_get_contents('uploads/cover.png'));}
// else {
        $cover = imagecreatefromstring(file_get_contents('cover.png'));
        $text_color = imagecolorallocate($cover, 0, 0, 0);
        imagettftext($cover, 70, 0, 5, 100, $text_color, 'fonts/Roboto-Regular.ttf', $_POST['title']);
        imagettftext($cover, 40, 90, 60, 600, $text_color, 'fonts/Roboto-Regular.ttf', $_POST['author']);
        $content->appendImage($cover);
        $content->appendPageBreak();


        if ($_POST['toc'] == 'true') {
            if ($_POST['lang'] != 'de') {
                $content->appendParagraph("<h2>Content</h2>");
            } else {
                $content->appendParagraph("<h2>Inhalt</h2>");
            }
            $content->appendParagraph("<h4>" . $toc . "</h4>");
            $content->appendPageBreak();
        }
        else{}

        // text split by images
        $text = explode('<img ', $_POST['text']);
        for ($i = 0; $i < count($text); $i++) {
            if ($i == 0) {
                $content->appendParagraph($text[$i]);
            } else {
                // get link of src attribute of image
                $src = explode('src="', $text[$i]);
                $src = explode('"', $src[1]);
                $src = $src[0];
                $content->appendImage(imagecreatefromstring(file_get_contents($src)));
                // remove everything to >
                $text[$i] = substr($text[$i], strpos($text[$i], '>') + 1);
                $content->appendParagraph($text[$i]);
            }
        }



        $mobi->setContentProvider($content);

        //Get title and make it a 12 character long filename
        $title = $mobi->getTitle();
        if ($title === false) {
            $title = 'file';
        }
        $title = urlencode(str_replace(' ', '_', strtolower($title)));

        $mobi -> save($_POST['title'].'.mobi');
     


        if(isset($_POST['title'])) 
        { 
            
          
          
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            
            try {
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host       = $_POST['host'];                     //Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                $mail->Username   = $_POST['user'];                     //SMTP username
                $mail->Password   = $_POST['pass'];                               //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                $mail->Port       = intval($_POST['port']);                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            
            
                $mail->setFrom($_POST['email'], $_POST['author']);
                $mail->addAddress($_POST['kindle'] , $_POST['author']);     // Add a recipient
                // $mail->addReplyTo('sstanek@ebmuc.de', 'Simeon Stanek');
                // $mail->addBCC('sstanek@ebmuc.de');
                $mail->addAttachment($_POST['title'].'.mobi');
        
                // $mail->isHTML(true);                                
                $mail->Subject = $_POST['title'];
                $mail->Body    = ' ';
                $mail->send();
                if ($_POST['lang'] != 'de') {
                    echo '👍 Your Ebook has been sent to your kindle!';
                } else {
                    echo '👍 Ebook wurde versandt!';
                }               
                unlink($_POST['title'].'.mobi');
                // unlink folder
                $files = glob('uploads/*'); // get all file names
                foreach($files as $file){ // iterate files
                    if(is_file($file))
                        unlink($file); // delete file
                }
            } catch (Exception $e) {
                if ($_POST['lang'] != 'de') {
                    echo "👎 Your Ebook could not be sent to your kindle!Error: {$mail->ErrorInfo}";
                } else {
                    echo "👎 Ebook wurde nicht versandt! Error: {$mail->ErrorInfo}" ;
                }
                unlink($_POST['title'].'.mobi');
                // unlink folder
                $files = glob('uploads/*'); // get all file names
                foreach($files as $file){ // iterate files
                    if(is_file($file))
                        unlink($file); // delete file
                }
            }

        }

        die;
?>
