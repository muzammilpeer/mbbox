<?php
//error_reporting(E_ALL);
error_reporting(E_STRICT);

date_default_timezone_set('America/Toronto');

//include("class.smtp.php"); // optional, gets called from within class.phpmailer.php if not already loaded
//$activationLink = '';
//$username = 'Bob';
function sendActivationLinkEmail($activationLink,$username)
{
require_once('class.phpmailer.php');
$mail			 = new PHPMailer();

//$body			 = file_get_contents('contents.html');
$body = '<body style="margin: 10px;">
		<div style="width: 640px; font-family: Arial, Helvetica, sans-serif; font-size: 11px;">
		<div align="center"><img src="images/logo.png" style="height: 50px; width: 400px"></div><br>
		<br>
		&nbsp;mbBox Account Activation<br>
		<br>
		Dear User,  <strong>'.$username.'</strong>,<br>
		Hi, Please activate your mbBox account through this link <a href="'.$activationLink.'" > Activate </a><br>
		<br>
		Thanks,
		<br />
		mbBox 2013-2014<br />
		Author: mbBox (support.mbbox@muzammilpeer.me <br />
		Author: Muzammil Peer(muzammilpeer987@gmail.com)<br />
		</div>
		</body>';

$body			 = eregi_replace("[\]",'',$body);

$mail->IsSMTP(); // telling the class to use SMTP
$mail->Host	   = "smtp.gmail.com"; // SMTP server
$mail->SMTPDebug  = 2;					 // enables SMTP debug information (for testing)
										   // 1 = errors and messages
										   // 2 = messages only
$mail->SMTPAuth   = true;				  // enable SMTP authentication
$mail->SMTPSecure = "ssl";				 // sets the prefix to the servier
$mail->Host	   = "smtp.gmail.com";	  // sets GMAIL as the SMTP server
$mail->Port	   = 465;				   // set the SMTP port for the GMAIL server
$mail->Username   = "muzammilpeer987@gmail.com";  // GMAIL username
$mail->Password   = "P@5is8an";			// GMAIL password

$mail->SetFrom('dont-reply@muzammilpeer.me', 'mbBox-Systems');

$mail->AddReplyTo("muzammilpeer987@gmail.com","mbBox-Systems");

$mail->Subject	= "mbBox Email address verification and Account Activation";

$mail->AltBody	= "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

$mail->MsgHTML($body);

$address = "muzammilpeer987@gmail.com";
$mail->AddAddress($address, "Muzammil Peer");

$mail->AddAttachment("images/logo.png");	  // attachment

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
  echo "Message sent!";
}
}
?>

