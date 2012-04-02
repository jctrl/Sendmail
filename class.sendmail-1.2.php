<?php
/*
	SENDMAIL v 1.2
	
	Allows the building and sending of an email using PHP's 'mail' script.
	Copyright (C) 2012, M Jason Vertucio.
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define ('MSG_TXT',1);
define ('MSG_HTML',2);
define ('MSG_ATT',4);

class tofSendMail {
	/**
	 * $sender and $receiver
	 * array containing ['name'] and ['email'] variables
	**/
	public $sender;
	public $receiver;

	/**
	 * $file
	 * Associative array including the ['filename'], the MIME ['type'] of the file, and
	 * the ['base64']-encoded file itself
	**/
	private $file;
	
	/**
	 * $cc, $bcc
	 * Array of Associative Arrays ['name'], ['email'] for CC and BCC's
	**/
	private $cc = array();
	private $bcc = array();
	
	/**
	 * Other basic message variables
	**/
	public $subject;
	public $headers;
	public $css;
	public $html_message;
	public $text_message;
	public $footer;
	
	public $send_return_message;
	public $send_return_to_cc;
	public $send_return_to_bcc;
	public $return_subject;
	public $return_html;
	public $return_text;
	
	/**
	 * $yahoo_type
	 * if $yahoo_type is set to TRUE then a special header must be sent through the mail() function
	 * So called because it's the only instance I've found this needs to be set.
	**/
	private $yahoo_type;
	
	/**	Message types supported are: 
	 *		MSG_TXT = Send Message as Text
	 *		MSG_HTML = Send Message as HTML
	 *		MSG_ATT = Send Message with Attachment
	 * 
	 * In order to set this when constructing, use the format MSG_TXT + MSG_HTML + MSG_ATT
	**/
	private $msg_type;
	
	/**
	 * $debug_mode
	 * If set to TRUE, outputs "email" direct to screen, and returns TRUE
	 * If not set, or set to FALSE, the object operates normally.
	**/
	private $debug_mode;

/* STARTUP FUNCTIONS */
	
	function __Construct($msgtype = MSG_TXT, $return_email = FALSE, $yah = FALSE,$debug = FALSE) {
		
		$message_footer = '';
		
		$this->footer = $message_footer;
		$this->yahoo_type = $yah;
		$this->msg_type = $msgtype;
		$this->send_return_message = $return_email;
		$this->headers = NULL;
		$this->debug_mode = $debug;
	}
	
/* FUNCTIONS TO SET THE DATA */

	function setSendReturnMessage ($do_you_want_to_send_a_return_message) { $this->send_return_message = $do_you_want_to_send_a_return_message; }
	function setDebugMode ($debug_mode) { $this->debug_mode = $debug_mode; }

	function setSender ($name,$email) { $this->sender['name'] = $name; $this->sender['email'] = $email; }	
	function setReceiver ($name,$email) { $this->receiver['name'] = $name; $this->receiver['email'] = $email; }	
	function setHtmlCss ($css) { $this->css = $css; }
	function setHtmlStyle ($css) { $this->setHtmlCss ($css); }
	function addHeader ($head) { $this->headers .= trim($head)."\n"; }
	function setFooter ($foot) { $this->footer = $foot; }

	function setSubject ($message_subject) { $this->subject = $message_subject; }	
	function setTextMessage ($text_message) { $this->text_message = $text_message; }
	function setHtmlMessage ($html_message) { $this->html_message = $html_message; }

	function setReturnMail ($do_you_want_to_send_a_return_message, $cc_array_A, $bcc_array_A) {
		$this->send_return_message = $do_you_want_to_send_a_return_message;
		$this->send_return_to_cc = $cc_array_A;
		$this->send_return_to_bcc = $bcc_array_A;
	}
	function setReturnSubject ($return_subjecg) { $this->return_subject = $return_subjecg; }	
	function setReturnTextMessage ($text_message) { $this->return_text = $text_message; }
	function setReturnHtmlMessage ($html_message) { $this->return_html = $html_message; }
	
	/**
	 * addCC, addBCC
	 * Adds an email address to the CC or BCC list
	 *
	 * @param String $name The new recipient's name
	 * @param String $email The new recipient's email address
	 *
	 * @Return Bool Whether or not the recipient was added
	**/
	function addCC ($name, $email) {
		foreach ($this->cc as $i) {
			if ($i['email'] == $email) return false;
		}
		array_push ($this->cc, array ('name' => $name , 'email' => $email));
		return true;
	}
	function addBCC ($name, $email) {
		foreach ($this->bcc as $i) {
			if ($i['email'] == $email) return false;
		}
		array_push ($this->bcc, array ('name' => $name , 'email' => $email));
		return true;
	}
	
	/**
	 * removeCC, removeBCC
	 * Searches to see if email address is in said list and deletes it.
	 *
	 * @param String $email email address to delete
	 *
	 * @return Bool True or false, if item was deleted.
	**/
	function removeCC ($email) { return $this->remove_from_array ($email, $this->cc); }
	function removeBCC ($email) { return $this->remove_from_array ($email, $this->bcc); }
	
	private function remove_from_array ($needle, &$array) {
		for ($cnt = 0; $cnt < sizeof ($array); $cnt++) {
			if ($array[$cnt]['email'] == $needle) { 
				unset ($array[$cnt]); 
				$array = array_values ($array);
				return true;
			}
		}
		return false;
	}

	/**
	 * Aliases for removeCC or removeBCC
	**/
	function deleteCC ($email) { return $this->removeCC ($email); }
	function deleteBCC ($email) { return $this->removeBCC ($email); }
	function delCC ($email) { return $this->removeCC ($email); }
	function delBCC ($email) { return $this->removeBCC ($email); }
	
	/**
	 * getCC, getBCC
	 * Returns the array of values
	**/
	function getCC() { return $this->cc; }
	function getBCC() { return $this->bcc; }
	
	/**
	 * setFile (and setAttachment)
	 * Adds the file information to the object
	 *
	 * @param Array $a 
	 * $a is an associative array parsed as follows:
	 *		'filename' => the name of the file
	 *		'type' => the file's MIME-TYPE
	 *		'base64' => base64-encoded file contents
	**/
	function setFile ($a) {
		$this->file = $a;
	}
	function setAttachment ($a) {
		$this->file = $a;
	}
	/**
	 * reparseMail
	 * Meant to be run before message body is created, this
	 * function will change any variables passed.
	 * Variables that look like {$variable} will be replaced.
	 * 
	 * @param String $msg The message to send.
	 * @param Array $replace An array as follows:
	 *		(key) => (value)
	 *		(key) => (value)
	 *
	 * @return String Returns the reparsed body.
	**/
	function reparseMail ($msg, $replace) {
		foreach ($replace as $key => $value) {
			$new_msg = str_replace ( "{\$".$key."}", $value, $msg );
		}
		return $new_msg;
	}
	
	/**
	 * getEmailFromFile
	 * Reads an email from a file.
	 *
	 * @param String $filename Full or relative path required as server dictates.
	 *
	 * @return String Contents of File
	**/
	function GetEmailFromFile ($filename) {
		$fh = fopen ( $filename , "r" );
		if (!$fh) return flase;
		$full_file = '';
		while ( $line = fgets ( $fh ) ) {
			$full_file .= $line;
		}
		return $full_file;
	}

	/**
	 * send
	 * Handles the sending of the message and the return message if set.

	**/
	public function send () {
		$to = array (
			'name'=>$this->receiver['name'],
			'email'=>$this->receiver['email']
		);
		$from = array (
			'name'=>$this->sender['name'],
			'email'=>$this->sender['email']
		);
		
		$result = $this->send_message ($to, $from, $this->subject, $this->text_message, $this->html_message);
		
		if ($result && $this->send_return_message) {
			if (!$this->send_return_to_cc) $this->cc = array();
			if (!$this->send_return_to_bcc) $this->bcc = array();
			$a = $this->send_message ($from, $to, $this->return_subject, $this->return_text, $this->return_html);
		}
		
		return $result;
	}
/* ------------------------------------------------------------------------------------------ */

	/**
	 * to_header_string
	 * Breaks up an array of ['name'] ['email'] arrays into a string for the email header.
	 *
	 * @param String $type The email type, like Cc or Bcc.
	 * @param Array $emails The array of email addresses to add.
	 *
	 * @returns Mixed the String representation of the Array, or FALSE if something went wrong.
	**/
	function to_header_string ($type, $emails) {
		$return_string = '';
		foreach ($emails as $i) {
			$return_string .= "{$type}: {$i['name']} <{$i['email']}>\n";
		}
		return trim($return_string);
	}

	/**
	 * get_content_type_string
	 * Creates the Content-Type line for the header
	 *
	 * @param Int $msg_type The message type, so we know which header to create
	 * @param String $mime_boundary (if applicable) The boundary string for Multi-part emails.
	 *
	 * @return String the Content-Type, Mime-Version, etc -- well, the string to drop into the email header.
	**/
	private function get_content_type_string ($a,$mime_boundary = '') {
		if ($a & MSG_ATT)
			return "MIME-Version: 1.0\nContent-Type: multipart/mixed; boundary=\"mix-{$mime_boundary}\"";
		if ($a & MSG_TXT && $a & MSG_HTML)
			return "MIME-Version: 1.0\nContent-Type: multipart/alternative; charset=iso-8859-1; boundary=\"{$mime_boundary}\"";
		if ($a & MSG_TXT ) 
			return "Content-Type: text/plain; charset=iso-8859-1\nContent-Transfer-Encoding: 7bit";
		if ($a & MSG_HTML )
			return "Content-Type: text/html; charset=iso-8859-1";
	}

	/**
	 * send_message
	 * Builds and sends the email using PHP's mail() function
	 *
	**/
	private function send_message ($to, $from, $subject, $txt_msg, $html_msg) {
		
		if ($this->debug_mode) echo "Parsing Info...<br>\n";
		
		@$sender_name = $from['name'];
		@$sender_email = $from['email'];
		@$recipient_name = $to['name'];
		@$recipient_email = $to['email'];
		@$file_info = $this->file;
		
		if ($this->debug_mode) echo "From: {$sender_name} &lt;{$sender_email}&gt;<br>To: {$recipient_name} &lt;{$recipient_email}&gt;<br>\n";

		
		if ( $sender_name == '' || 
			$sender_email == '' || 
			$recipient_email == '' 
		) return FALSE;

		if ($this->debug_mode) echo "Creating Headers...<br>\n";
		
		$random_hash = md5(date('r', time())); 
		$mime_boundary = "==PHP-alt-{$random_hash}";
		$phpversion = phpversion();
		
		$content_type = trim($this->get_content_type_string ($this->msg_type,$mime_boundary));
		$additional_headers = trim ($this->headers);
		$cc = $this->to_header_string ("Cc",$this->cc);
		$bcc = $this->to_header_string ("Bcc",$this->bcc);
		
		$headers = "From: {$sender_name} <{$sender_email}>\n";
		$headers .= "Reply-To: {$sender_name} <{$sender_email}>\n";
		$headers .= "X-MAILER: PHP/{$phpversion}\n";
		if ($cc) $headers .= "{$cc}\n";
		if ($bcc) $headers .= "{$bcc}\n";
		if ($additional_headers) $headers .= "{$additional_headers}\n";
		$headers .= "{$content_type}\n";

MSGHEADER;
		
		$headers = trim($headers)."\r\n";
	
		$html_style = $this->css;
		
		switch ($this->msg_type) {
// BEGIN TEXT MESSAGE -----------------------------------------------------------------------------------------------------------
			case 2:
				$total_msg = <<<EOR
<html><body><style type="text/css">{$this->css}</style>{$html_msg}{$this->footer}</body></html>
EOR;
				$total_msg = stripslashes ($total_msg);
				break;
// BEGIN HTML MESSAGE -----------------------------------------------------------------------------------------------------------
			case 1:
				$total_msg = "{$txt_msg}\n\n\n";
				$total_msg .= "".strip_tags ($this->footer);
				break;
// BEGIN FILE_ATTACHMENT MESSAGE ------------------------------------------------------------------------------------------------
			case 4:
				$txt_message_footer = strip_tags ($this->footer);
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


<html><body><style type="text/css">{$this->css}</style>{$html_msg}{$this->footer}</body></html>

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
				$txt_footer = strip_tags ($this->footer);
				$total_msg = <<<HTXT

--{$mime_boundary}
Content-Type: text/plain; charset=iso-8859-1
Content-Transfer-Encoding: 7bit

{$txt_msg}

----------
{$txt_footer}

--{$mime_boundary}
Content-Type: text/html; charset=iso-8859-1

<html><body><style type="text/css">{$this->css}</style>{$html_msg}
{$this->footer}</body></html>

--{$mime_boundary}--
HTXT;
				$total_msg = stripslashes ($total_msg);
				break;
		}
		
		// the @ suppresses errors, and the mail() function will return a boolean of TRUE or FALSE.
		
		if ($this->debug_mode) {
			$p = "Mailto: {$recipient_name} <{$recipient_email}>\n".$headers.$total_msg;
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
}

?>