<?php
/*
	SENDMAIL v.0.9
		--	Builds the email to send from a contact form. 
	
*/

define ('MSG_TXT',1);
define ('MSG_HTML',2);
define ('MSG_ATT',4);

class tofSendMail {
	
	private $sender_name;
	private $sender_email;
	
	private $receiver_name;
	private $receiver_email;

	private $send_data;
		// Associative Array which can be parsed for the email.
		
	private $file;
	
	private $message_subject;
	private $message_headers;
	private $message_html_css;
	private $message_html;
	private $message_text;
	private $message_footer;
	
	private $send_return_message;
	private $return_subject;
	private $return_html;
	private $return_text;
	
	private $yahoo_type;
		/* Explanation
				if $yahoo_type is set to TRUE then a special header must be sent through the mail() function
		*/
	
	private $msg_type;
		/*	Message types supported are: 
				MSG_TXT = Send Message as Text
				MSG_HTML = Send Message as HTML
				MSG_ATT = Send Message with Attachment
			Currently the variable is set by adding the 
		*/
		
	private $debug_mode;

/* STARTUP FUNCTIONS */
	
	function __Construct($msgtype = MSG_TXT, $return_email = FALSE, $yah = FALSE,$debug = FALSE) {
		
		$message_footer = <<<FOOTER_TIME
<hr style="height:1px; color: #797979;"><font size="1" color="#999999" style="color:#999999; font-size:7pt;"><i>This message has been sent using the tofSendMail.<i></font>
FOOTER_TIME;
		
		$this->message_footer = $message_footer;
		$this->yahoo_type = $yah;
		$this->msg_type = $msgtype;
		$this->send_return_message = $return_email;
		$this->message_headers = NULL;
		$this->debug_mode = $debug;
	}
	
	private function parseSetup ($setup_array) {
		foreach ($setup_array as $key=>$v) {
			switch ($key) {
				case 'senderName':
					$this->sender_name = $v;
					break;
				case 'senderEmail':
					$this->sender_email = $v;
					break;
				case 'receiverName':
					$this->receiver_name = $v;
					break;
				case 'receiverEmail':
					$this->receiver_email = $v;
					break;
				case 'subject':
					$this->message_subject = $v;
			}
		}
	}
	
/* FUNCTIONS TO SET THE DATA */

	function setSendReturnMessage ($a) { $this->send_return_message = $a; }
	function setDebugMode ($a) { $this->debug_mode = $a; }

	function setSender ($a,$b) { $this->sender_name = $a; $this->sender_email = $b; }	
	function setReceiver ($a,$b) { $this->receiver_name = $a; $this->receiver_email = $b; }	
	function setHtmlCss ($c) { $this->message_html_css = $c; }
	function setHtmlStyle ($c) { $this->setHtmlCss ($c); }
	function setHeaders ($head) { $this->message_headers = trim($head)."\n"; }
	function addHeader ($head) { $this->message_headers .= trim($head)."\n"; }	
	function setFooter ($foot) { $this->message_footer = $foot; }	

	function setSubject ($a) { $this->message_subject = $a; }	
	function setTextMessage ($a) { $this->message_text = $a; }	
	function setHtmlMessage ($b) { $this->message_html = $b; }	

	function setReturnSubject ($r) { $this->return_subject = $r; }	
	function setReturnTextMessage ($d) { $this->return_text = $d; }	
	function setReturnHtmlMessage ($e) { $this->return_html = $e; }	
	
	function setFile ($a) {
		$this->file = $a;
			// $a is an associative array parsed as follows:
			//	'filename' => the name of the file
			//	'type' => the file's MIME-TYPE
			// 	'base64' => base64-encoded file contents
	}
	
/* FUNCTIONS TO ACTUALLY BUILD AND SEND THE EMAIL */

	private function getContentTypeString ($a,$mime_boundary) {
		if ($a & MSG_ATT)
			return "MIME-Version: 1.0\nContent-Type: multipart/mixed; boundary=\"mix-{$mime_boundary}\"";
		if ($a & MSG_TXT && $a & MSG_HTML)
			return "MIME-Version: 1.0\nContent-Type: multipart/alternative; charset=iso-8859-1; boundary=\"{$mime_boundary}\"";
		if ($a & MSG_TXT ) 
			return 'Content-Type: text/plain; charset=iso-8859-1\nContent-Transfer-Encoding: 7bit';
		if ($a & MSG_HTML )
			return 'Content-Type: text/html; charset=iso-8859-1';
	}


	private function sendMsg ($to, $from, $subject, $txt_msg, $html_msg) {
		
		@$sender_name = $from['name'];
		@$sender_email = $from['email'];
		@$recipient_name = $to['name'];
		@$recipient_email = $to['email'];
		@$file_info = $this->file;
		
		if ( $sender_name == '' || 
			$sender_email == '' || 
			$recipient_name == '' || 
			$recipient_email == '' 
		) return FALSE;
		
		$random_hash = md5(date('r', time())); 
		$mime_boundary = "==PHP-alt-{$random_hash}";
		$phpversion = phpversion();
		
		$content_type = trim($this->getContentTypeString ($this->msg_type,$mime_boundary));
		
		$headers = <<<MSGHEADER
From: {$sender_name} <{$sender_email}>
Reply-To: {$sender_name} <{$sender_email}>
X-MAILER: PHP/{$phpversion}
{$content_type}
{$this->message_headers}

MSGHEADER;

		$headers = trim($headers)."\r\n";
	
		$html_style = $this->message_html_css;
		
		switch ($this->msg_type) {
// BEGIN TEXT MESSAGE -----------------------------------------------------------------------------------------------------------
			case 2:
				$total_msg = <<<EOR
<html><body><style type="text/css">{$this->message_html_css}</style>{$html_msg}{$this->message_footer}</body></html>
EOR;
				$total_msg = stripslashes ($total_msg);
				break;
// BEGIN HTML MESSAGE -----------------------------------------------------------------------------------------------------------
			case 1:
				$total_msg = "{$txt_msg}\n\n\n";
				$total_msg .= "".strip_tags ($this->message_footer);
				break;
// BEGIN FILE_ATTACHMENT MESSAGE ------------------------------------------------------------------------------------------------
			case 4:
				$txt_message_footer = strip_tags ($this->message_footer);
				$total_msg = <<<HTML_ALL
				
--mix-{$mime_boundary}
Content-TYpe: multipart/alternative; charset=utf-8; boundary="{$mime_boundary}"

--{$mime_boundary}
Content-Type: text/plain; charset=iso-8859-1
Content-Transfer-Encoding: 7bit

{$txt_msg}

----------
{$txt_message_footer}

--{$mime_boundary}
Content-Type: text/html; charset=iso-8859-1


<html><body><style type="text/css">{$this->message_html_css}</style>{$html_msg}{$this->message_footer}</body></html>

--{$mime_boundary}--

--mix-{$mime_boundary}
Content-Type: {$file_info['type']}; name="{$file_info['filename']}"
Content-Transfer-Encoding: base64
Content-Disposition: Attachment; filename="{$file_info['filename']}"

{$file_info['base64']}

--mix-{$mime_boundary}--
HTML_ALL;
				$total_msg = stripslashes ($total_msg);
				break;
// BEGIN HTML/TEXT MESSAGE ----------------------------------------------------------------------------------------------------
		case 3:
			default:
				$txt_footer = strip_tags ($this->message_footer);
				$total_msg = <<<HTXT

--{$mime_boundary}
Content-Type: text/plain; charset=iso-8859-1
Content-Transfer-Encoding: 7bit

{$txt_msg}

----------
{$txt_footer}

--{$mime_boundary}
Content-Type: text/html; charset=iso-8859-1

<html><body><style type="text/css">{$this->message_html_css}</style>{$html_msg}
{$this->message_footer}</body></html>

--{$mime_boundary}--
HTXT;
				$total_msg = stripslashes ($total_msg);
				break;
		}
		
		// the @ suppresses errors, and the mail() function will return a boolean of TRUE or FALSE.
			
		if ($this->debug_mode) {
			$p = "Mailto {$recipient_name} <{$recipient_email}>\n\n".$headers.$total_msg;
			$p = htmlspecialchars ($p);
			echo "<pre>\n{$p}\n</pre>";
			$sendmail = TRUE;
		} else {
			if (!$this->yahoo_type)
				@$sendmail = mail ($recipient_email, $subject, $total_msg, $headers); 
			else
				@$sendmail = mail ($recipient_email, $subject, $total_msg, $headers, $this->additional_parameters);
		}
		
		return $sendmail;
	}
	
	public function send () {
		$to = array (
			'name'=>$this->receiver_name,
			'email'=>$this->receiver_email
		);
		$from = array (
			'name'=>$this->sender_name,
			'email'=>$this->sender_email
		);
		
		$result = $this->sendMsg ($to, $from, $this->message_subject, $this->message_text, $this->message_html);
		
		if ($result && $this->send_return_message) {
			$this->msg_type = MSG_TXT | MSG_HTML;
			$a = $this->sendMsg ($from, $to, $this->return_subject, $this->return_text, $this->return_html);
		}
		
		return $result;
	}
}

?>