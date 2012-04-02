<?php

ini_set('display_errors',1); error_reporting(E_ALL);

require ("class.sendmail.php");

$send = new tofSendMail (MSG_TXT | MSG_HTML ,TRUE); // Send Text and HTML email, send return email to sender
$send->setDebugMode (FALSE);

$send->setReceiver ('M. Jason Vertucio','m@jasonvertucio.com');
$send->setSender ('Jason Vertucio','bigcartoonjay@gmail.com');
$send->setSubject ("This is a test email thingie!");
$send->setReturnSubject ("Re: The Message Back!");

$html_css = '
html, body { color:#003344;font-family:Verdana,Arial,sans-serif;font-size:10pt;margin:0px; padding:0px; }
h1 { color:#300200; font-family:Georgia, \"Times New Roman\", serif; }
';

$txt_msg = "Hello this is a test! How's it going?\n\nMy mom told me, \"Don't talk to strangers.\"";
$html_msg = "<h1>Hello!</h1><p><i>This is a <b>test!</b></i> How's it going?</p><p>My mom told me, \"<i>Don't talk to strangers.</i>\"</p>";

$txt_ret = "This is the return email!";
$html_ret = "<h1>This</h1><p><b>is the return email!</b></p>";

$send->setTextMessage ($txt_msg);
$send->setHtmlMessage ($html_msg);
$send->setReturnTextMessage ($txt_ret);
$send->setReturnHtmlMessage ($html_ret);
$send->setHtmlCss ($html_css);
$send->addHeader("Cc:me@jasonvertucio.com\n\n\n\n\n");
$send->addHeader("Cc:inyuk_chuk@me.com");
$send->addHeader("Bcc:bulletproof0418@hotmail.com\n\n\n");

$r = $send->send();

if ($r)
	echo "<br><br><b>SUCCESS!</b>";
else
	echo "FAIL!";


?>