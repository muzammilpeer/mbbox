<?php
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	require_once('controller/MembershipManager.php' );
	require_once('class.phpmailer.php');
	$member = new MembershipManager();
	
	if( isset($_POST['firstname']) && isset($_POST['lastname']) && isset($_POST['nickname']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['secretkey'])
	)
	{
		$firstname = $member->MongoDBSanatize($_POST['firstname']);
		$lastname = $member->MongoDBSanatize($_POST['lastname']);
		$nickname = $member->MongoDBSanatize($_POST['nickname']);
		$email = $member->MongoDBSanatize($_POST['email']);
		$password = $member->MongoDBSanatize($_POST['password']);
		$secretkey = $member->MongoDBSanatize($_POST['secretkey']);

		$document = array();
		$document['FirstName'] = (string)$firstname;
		$document['LastName'] = (string)$lastname;
		$document['NickName'] = (string)$nickname;
		$document['Email'] = (string)$email;
		$document['Password'] = (string)$password;
		$document['HomeDirId'] = '';
		$document['DateCreated'] = date('d M Y H:m');
		$document['DateModified'] = date('d M Y H:m');
		$document['SecurityKey'] = (string)$secretkey;
		$document['Active'] = false;
		$uniqueKey =  uniqid();
		$document['ActivationKey'] = $uniqueKey;
		
		if($member->AddUser($document))
		{
		$activationLink = 'http://ombbox.muzammilpeer.me/activate.php?key='.$uniqueKey;
		$username = $firstname.$lastname;
			//sendActivationLinkEmail($activationLink,$firstname.$lastname)
			////Send Email////////////
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
			
			
			/////Page Redirection////////	
			header('Location: ../../login.html');
			exit;
		}/*
		else
		{
			header('Location: signup.php?email=1');
			exit;
		}*/
	}else if (isset($_POST['email']))
	{
		if($member->isUserExist((string)$_POST['email']))
		{
			print '1';
		}else 
		{
			print '0';
		}
	}
?>