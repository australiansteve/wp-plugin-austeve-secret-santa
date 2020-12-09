<?php
/*
Plugin Name: Secret Santa
Plugin URI: https://github.com/australiansteve/austeve-secret-santa
Description: Generates Secret Santa recipients and emails anonymously
Version: 1.0.0
Author: AustralianSteve
Author URI: http://australiansteve.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class AUSteve_SecretSanta {

	function __construct() {

		//register style
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_style') );


		/* Test only below this point */
		add_shortcode( 'secret_santa', array($this, 'display_secret_santa_form') );

		add_action( 'admin_post_nopriv_secret_santa', array($this, 'secret_santa') );
		add_action( 'admin_post_secret_santa', array($this, 'secret_santa') );

		add_action( 'admin_post_nopriv_generate_secret_santa', array($this, 'generate_secret_santa_form') );
		add_action( 'admin_post_generate_secret_santa', array($this, 'generate_secret_santa_form') );

	}

	function enqueue_style() {
		wp_enqueue_style( 'secret-santa-style', plugin_dir_url( __FILE__ )."style.css", array(), '1.0' );
	}

	function send_email($santaName, $santaEmail, $recipientName, $recipientEmail) {
		
		$title = "Friends Xmas Book Swap (FXBS)";
		error_log(''.$santaName.' ('.$santaEmail.') sends gift to: '.$recipientName.' ('.$recipientEmail.')');
		
		$emailMessage = "<p>Merry Christmas ".$santaName.",";
		$emailMessage .= "<p>Welcome to ".$title.".";
		$emailMessage .= "<p>This year you're giving a gift to: <strong>".$recipientName."</strong>";
		$emailMessage .= "<p>Seasons Greetings :)";

		if($emailMessage) {

		 	error_log("Email Message: ".$emailMessage);

		 	$to = $santaEmail;
		 	$subject = $title;
		 	$txt = $emailMessage;
		 	$headers = "MIME-Version: 1.0" . "\r\n";
		 	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		 	$headers .= "From: steve@australiansteve.com" . "\r\n";

		 	mail($to,$subject,$txt,$headers);
		 	return true;
		}
	}


	/* START: Test methods */

	function generate_random_assignment_order($max) {
		$numbers = range(0, $max - 1);
		shuffle($numbers);
		$comp = 0;
		foreach ($numbers as $number) {
		    error_log("$number");
		    if ($number == $comp++) {
		    	error_log("Oops - clash - regenerating...");
		    	return $this->generate_random_assignment_order($max);
		    }
		}
		return $numbers;
	}

	function secret_santa() {
		error_log("secret_santa from page ".$_POST['pageid']);
		$i = 1;

		$recipients = array();

		while(isset($_POST['name'.$i]) && $_POST['name'.$i] != "" && isset($_POST['email'.$i]) && $_POST['email'.$i] != "") {
			error_log($_POST['name'.$i].": ".$_POST['email'.$i]);
			$recipient = array('name' => $_POST['name'.$i], 
				'email' => $_POST['email'.$i]);
			$recipients[] = $recipient;
			$i++;
		}
		error_log("Recipients: ".print_r($recipients, true));

		if (count($recipients) > 1) {
			//generate order
			$order = $this->generate_random_assignment_order(count($recipients));
			error_log("Order: ".print_r($order, true));

			//send emails
			$counter = 0;
			foreach($order as $santa) {
				$this->send_email($recipients[$santa]['name'], $recipients[$santa]['email'], $recipients[$counter]['name'], $recipients[$counter]['email']);
				$counter++;
			}

		}
		else {
			error_log("There must be more than 1 recipient for secret santa to work");
		}
		wp_redirect( get_permalink( $_POST['pageid'] ) );
		exit();
	}

	function generate_secret_santa_form() {
		error_log("generate_secret_santa_form from page ".$_POST['pageid'].", names: ".$_POST['names']);

		wp_redirect( get_permalink( $_POST['pageid'])."?numberOfNames=".$_POST['names'] );
		exit();
	}

	function display_secret_santa_form( $atts ){

		$return = "<div id='content'>
		<frameset id='generate-form'>
		<form action='".esc_url( admin_url('admin-post.php') )."' method='post'>
		<label for='names'>Number of names:</label>
		<input type='number' name='names' id='names' value='5'>
		<input type='hidden' name='action' value='generate_secret_santa'>
		<input type='hidden' name='pageid' value='".get_the_ID()."'>
		<input type='submit' value='Generate Secret Santa'>
		</form>
		</frameset>

		<form id='secret-santa-display-form' action='".esc_url( admin_url('admin-post.php') )."' method='post'>
		<div id='names'>
		";
		$numName = isset($_GET['numberOfNames']) ? $_GET['numberOfNames'] : 0;
		for($i = 1; $i <= $numName; $i++) {
			$return .= "<div><label for='name".$i."'>Name #".$i."</label>
			<input type='text' name='name".$i."' id='name".$i."' value=''>
			<label for='email".$i."'>Email #".$i."</label>
			<input type='email' name='email".$i."' id='email".$i."' value=''></div>";
		}
		$return .="</div>
		<input type='hidden' name='action' value='secret_santa'>
		<input type='hidden' name='pageid' value='".get_the_ID()."'>
		<input type='submit' value='Assign Secret Santas'>
		</form>
		</div>";

		return $return;
	}


	/* END: Test methods */
}

$austeveSS = new AUSteve_SecretSanta();

?>
