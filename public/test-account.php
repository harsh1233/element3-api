<!DOCTYPE html>

<!-- Test account payment screenshort: https://prnt.sc/un0qz8 -->

<html>
<body>

	<h1>Concardis Payment Gateway Demo!</h1>
	<h3>Welcome to Test Account payment page.</h3>

	<?php 

	$PSPID = '40F08695';
	$passphrase = 'test-sha-in-pass-phrase';
	$price = 151;
	$accept_url='http://www.youraccepturl.com';
	$cancel_url='http://www.yourcancelurl.com';
	$credit=$price*100; /*note that you have to multiply the price with 100 for Ogone*/
	$message = 'Payment message';
	$firstname = 'thai ja bhai';
	$email = "nakkamo@yopmail.com";


	$Ogone_sha1 =
	"ACCEPTURL=".$accept_url.$passphrase.
	"AMOUNT=".$credit.$passphrase.
	"CANCELURL=".$cancel_url.$passphrase.
	"CN=".$firstname.' '.$name.$passphrase.
	"CURRENCY=EUR".$passphrase.
	"DECLINEURL=".$cancel_url.$passphrase.
	"EMAIL=".$email.$passphrase.
	"EXCEPTIONURL=".$cancel_url.$passphrase.
	"LANGUAGE=en_US".$passphrase.
	"ORDERID=".$credit.$passphrase.
	"OWNERADDRESS=".$firstname.$passphrase.
	"OWNERTOWN=".$firstname.$passphrase.
	"PSPID=".$PSPID.$passphrase;

	/*Creating the signature*/
	$Ogone_sha1 = sha1($Ogone_sha1);

	$form1 = '<form name="directpayment1" id="directpayment" action="https://secure.ogone.com/ncol/Test/orderstandard.asp" method="post" >
	<input type="hidden" name="PSPID" value="'.$PSPID.'" />
	<input type="hidden" name="CN" value="'.$firstname.' '.$name.'">
	<input type="hidden" name="OWNERADDRESS" value="'.$address.'">
	<input type="hidden" name="OWNERTOWN" value="'.$town.'">
	<input type="hidden" name="AMOUNT" value="'.$credit.'" />
	<input type="hidden" name="ACCEPTURL" value="'.$accept_url.'" />
	<input type="hidden" name="CANCELURL" value="'.$cancel_url.'" />
	<input type="hidden" name="DECLINEURL" value="'.$cancel_url.'">
	<input type="hidden" name="EXCEPTIONURL" value="'.$cancel_url.'">
	<input type="hidden" name="EMAIL" value="'.$email.'">
	<input type="hidden" name="ORDERID" value="'.$credit.'" />
	<input type="hidden" name="CURRENCY" value="EUR" />
	<input type="hidden" name="LANGUAGE" value="en_US" />
	<input type="hidden" name="SHASIGN" value="'.$Ogone_sha1.'" />
	<input type="submit" name="submit" id="submit" value="PAY"></form>';
	echo $form1;

	exit;

	//$passphrase = 'Drich48lciZkxlkd9ciui3klxlczqpslABCIN';
	//$passphrase = '209113288F93A9AB8E474EA78D899AFDBB874355';
	$PSPID = 'internetkassacom';
	$accept_url='http://3.7.129.143/element3/test-account.php?status=success';
	$cancel_url='http://3.7.129.143/element3/test-account.php?status=cancel';
	$declineurl = 'http://3.7.129.143/element3/test-account.php?status=decline';
	$exceptionurl = 'http://3.7.129.143/element3/test-account.php?status=exceptio';
	$price = 10000;
	//$credit= $price*100; /*note that you have to multiply the price with 100 for Ogone*/
	$order = '121212121212';
	$cn = 'PS SH';
	$currency = 'EUR';
	$language = 'en_US';
	//$amount = '100000';
	/*now build your string, fields need to be in alphabetical order and uppercase*/
	$sha_data = "AMOUNT=".$price."CURRENCY=".$currency."LANGUAGE=".$language."ORDERID=".$order."PSPID=".$PSPID;
	// $sha_data = "AMOUNT=".$price."CURRENCY=".$currency."LANGUAGE=".$language."ORDERID=".$order."PSPID=".$PSPID."ACCEPTURL=".$accept_url."DECLINEURL=".$cancel_url."EXCEPTIONURL=".$cancel_url."CANCELURL=".$cancel_url;
	
	//"CANCELURL=".$cancel_url.
	//"ACCEPTURL=".$accept_url.
	//"CN=".$cn.
	//"DECLINEURL=".$cancel_url.
	//"EXCEPTIONURL=".$cancel_url.

	/*Creating the signature*/

	$test = sha1($sha_data);
	?>
	<p><strong>SHASIGN: </strong><?php echo $sha_data;?></p>
	<p><strong>SHA-1: </strong><?php echo $test;?></p>
	<?php
	/*creating the form*/
	$form1 = '<form name="directpayment1" id="directpayment" action="https://secure.payengine.de/ncol/test/orderstandard_utf8.asp" method="post">
	<input type="hidden" name="PSPID" value="'.$PSPID.'" />
	<input type="hidden" name="CN" value="PS SH">
	<input type="hidden" name="AMOUNT" value="'.$price.'" />
	<input type="hidden" name="ACCEPTURL" value="'.$accept_url.'" />
	<input type="hidden" name="CANCELURL" value="'.$cancel_url.'" />
	<input type="hidden" name="DECLINEURL" value="'.$declineurl.'">
	<input type="hidden" name="EXCEPTIONURL" value="'.$exceptionurl.'">
	<input type="hidden" name="ORDERID" value="'.$order.'" />
	<input type="hidden" name="CURRENCY" value="'.$currency.'" />
	<input type="hidden" name="LANGUAGE" value="'.$language.'" />
	<input type="hidden" name="SHASIGN" value="'.$test.'" />
	<input type="submit" name="submit" id="submit" value="Pay on concardis"></form>';
	echo $form1;
	?>
</body>
</html>