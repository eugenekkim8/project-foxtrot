<html>
<head>
	<title>My page</title>
</head>
<body>
test?
<?php
	require 'vendor/autoload.php';

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;

	//List of carriers
	$carriers = [
		"T-Mobile" => "tmomail.net",
		"AT&T" => "txt.att.net",
		"Verizon" => "vtext.com"
	];

	//Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);

    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = getenv('GMAIL_ADDR');                     //SMTP username
    $mail->Password   = getenv('GMAIL_PASS');                               //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    $mail->setFrom('admin@test.com', 'Foxtrot');

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Check-in';
    $mail->Body    = 'Good morning! Here is a <a href = "google.com">link</a>.';
    $mail->AltBody = 'Good morning! Here is a link: google.com';

    //Connect to DB
    $dbopts = parse_url(getenv('DATABASE_URL'));

	$connect_str = "host = " . $dbopts["host"] . " port = " . $dbopts["port"] . " dbname = " . ltrim($dbopts["path"], "/") . " user = " . $dbopts["user"] . " password = " . $dbopts["pass"];

	$conn = pg_connect($connect_str) or die("Could not connect" . pg_last_error());

	//Pull current user list
	$query = "SELECT phone_num, carrier FROM users WHERE is_active AND text_consent";
	$results = pg_query($query) or die ("Query failed:" . pg_last_error());

	while ($this_user = pg_fetch_array($results)){
		$mail->clearAllRecipients();

		$phone_num = $this_user['phone_num'];
		$carrier_domain = $carriers[$this_user['carrier']];
		$address = $phone_num . "@" . $carrier_domain;
		$mail->addAddress($address);
		echo $address;

		try {
		    $mail->send();
		    echo 'Message has been sent';
		} catch (Exception $e) {
		    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		}

	}



?>
</body>
</html>

