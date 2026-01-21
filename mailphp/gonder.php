<title>Posta Gönderme Sonuç Raporu</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
require("class.phpmailer.php"); // PHPMailer dosyamizi çagiriyoruz
$mail = new PHPMailer(); // Sinifimizi $mail degiskenine atadik
$mail->IsSMTP(true);  // Mailimizin SMTP ile gönderilecegini belirtiyoruz
$mail->From     = $_POST["forgot_password@invesakprimesyrk.com"];//"admin@localhost"; //Gönderen kisminda yer alacak e-mail adresi
$mail->Sender   = $_POST["forgot_password@invesakprimesyrk.com"];//"admin@localhost";//Gönderen Mail adresi
//$mail->ReplyTo  = ($_POST["forgot_password@invesakprimesyrk.com"]);//"admin@localhost";//Tekrar gönderimdeki mail adersi
$mail->AddReplyTo=($_POST["forgot_password@invesakprimesyrk.com"]);//"admin@localhost";//Tekrar gönderimdeki mail adersi
$mail->FromName = $_POST["adsoyad"];//"PHP Mailer";//gönderenin ismi
$mail->Host     = "mail.kurumsaleposta.com";//"localhost"; //SMTP server adresi
$mail->SMTPAuth = true; //SMTP server'a kullanici adi ile baglanilcagini belirtiyoruz
$mail->SMTPSecure = false;
$mail->SMTPAutoTLS = false;
$mail->Port     = 587; //Natro SMPT Mail Portu
$mail->CharSet = 'UTF-8'; //Türkçe yazı karakterleri için CharSet  ayarını bu şekilde yapıyoruz.
$mail->Username = "forgot_password@invesakprimesyrk.com";//"admin@localhost"; //SMTP kullanici adi
$mail->Password = "AsdAsd587823_?";//""; //SMTP mailinizin sifresi
$mail->WordWrap = 50;
$mail->IsHTML(true); //Mailimizin HTML formatinda hazirlanacagini bildiriyoruz.
$mail->Subject  = $_POST["konu"]." /PHP SMTP Ayarları/Mail Konusu";//"Deneme Maili"; // Mailin Konusu Konu
//Mailimizin gövdesi: (HTML ile)
$body  = "						"."Mail İçeriği Başlığı"."<br><br>";
$body .= "Gönderen Adi		: ".$_POST["adsoyad"]."<br>";
$body .= "E-posta Adresi	: ".$_POST["mailiniz"]."<br>";
$body .= "Telefonu&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ".$_POST["telefon"]."<br>";
$body .= "Yasadigi yer&nbsp;&nbsp;		: ".$_POST["yer"]."<br>";
$body .= "Konu&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ".$_POST["konu"]."<br>";
$body .= "Mesaj&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ".$_POST["mesaj"]."<br>";


//  $body = $_POST["mesaj"];//"Bu mail bir deneme mailidir. SMTP server ile gönderilmistir.";
// HTML okuyamayan mail okuyucularda görünecek düz metin:
$textBody = $body;//"Bu mail bir deneme mailidir. SMTP server ile gönderilmistir.";
$mail->Body = $body;
$mail->AltBody = $text_body;
if ($mail->Send()) echo "Sorunuz gönderildimiştir. <br>Natro Sistem Uzmanlarımız müsait olduğunda yanıtlayacaktır.";
else echo "Form göndermede hata oldu! Daha sonra tekrar deneyiniz.";
$mail->ClearAddresses();
$mail->ClearAttachments();
$mail->AddAttachment('images.png'); //mail içinde resim göndermek için
$mail->addCC('mailadi@alanadiniz.site');// cc email adresi
$mail->addBCC('mailadi@alanadiniz.site');// bcc email adresi
$mail->AddAddress("mailadi@alanadiniz.site"); // Mail gönderilecek adresleri ekliyoruz.
$mail->AddAddress("mailadi@alanadiniz.site"); // Mail gönderilecek adresleri ekliyoruz. Birden fazla ekleme yapılabilir.
$mail->Send();
$mail->ClearAddresses();
$mail->ClearAttachments();
?>