<?php
/***
 * PDF Support
 * used for certificates, receipts and invoices
 */

// convert info about a recording certificate into a base64 string for url inclusion
// ##### replaced by cc_certs_encode_parms #####
function ccpdf_recording_cert_parms_encode($recording_id, $user_id){
	$daft_number = $recording_id * $user_id + 35486;
	$string = $recording_id.'|'.$user_id.'|'.$daft_number;
	return 'r'.base64_encode($string);
}

// decode a recording cert parm string
function ccpdf_recording_cert_parms_decode($string){
	if(substr($string, 0, 1) <> 'r') return false;
	$string = substr($string, 1);
	$string = base64_decode($string);
	list($recording_id, $user_id, $daft_number) = explode("|", $string);
	if($daft_number == $recording_id * $user_id + 35486){
		return array(
			'recording_id' => $recording_id,
			'user_id' => $user_id,
		);
	}
	return false;
}

// convert info about a workshop certificate into a base64 string for url inclusion
// ##### replaced by cc_certs_encode_parms #####
function ccpdf_workshop_cert_parms_encode($workshop_id, $user_id){
	$daft_number = $workshop_id * $user_id + 10408;
	$string = $workshop_id.'|'.$user_id.'|'.$daft_number;
	return 'w'.base64_encode($string);
}

// decode a workshop cert parm string
function ccpdf_workshop_cert_parms_decode($string){
	if(substr($string, 0, 1) <> 'w') return false;
	$string = substr($string, 1);
	$string = base64_decode($string);
	list($workshop_id, $user_id, $daft_number) = explode("|", $string);
	if($daft_number == $workshop_id * $user_id + 10408){
		return array(
			'workshop_id' => $workshop_id,
			'user_id' => $user_id,
		);
	}
	return false;
}

// convert info about a receipt into a base64 string for url inclusion
function ccpdf_receipt_parms_encode($payment_id){
	$daft_number = $payment_id * $payment_id + 59844;
	$string = $payment_id.'|'.$daft_number;
	return 'x'.base64_encode($string);
}

// decode a recording cert parm string
function ccpdf_receipt_parms_decode($string){
	if(substr($string, 0, 1) <> 'x') return false;
	$string = substr($string, 1);
	$string = base64_decode($string);
	list($payment_id, $daft_number) = explode("|", $string);
	if($daft_number == $payment_id * $payment_id + 59844){
		return $payment_id;
	}
	return false;
}
