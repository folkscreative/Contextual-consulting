<?php
/**
 * Template Name: All certificates
 * Generate the PDF version of any certificate
 * This works for CC, recording, APA, BACB, NBCC, ICF certificates
 * UEL should be /training-certificate
 *
 * Uses TCPDF
 *
 */

if( isset( $_GET['c'] ) && $_GET['c'] <> '' ){
	$parms = cc_certs_decode_parms( sanitize_text_field( $_GET['c'] ) );

	/* for testing ........
	$parms = array(
		'cert' => 'i',
		'training_id' => 5092,
		'event_id' => 0,
		'user_id' => 27369,
	);
	*/

	// keep some stats
	$option_key = 'certs_requested_'.$parms['cert'].'_'.date('Y_m');
	$certs_count = get_option( $option_key, 0 );
	update_option( $option_key, $certs_count + 1 );

	// Include the main TCPDF library
	require_once(get_stylesheet_directory().'/inc/tcpdf/config/tcpdf_config.php');
	require_once(get_stylesheet_directory().'/inc/tcpdf/tcpdf.php');

	// Extend the TCPDF class to create custom Header and Footer
	class CCCertPDF extends TCPDF {
	    public function Header() {
	    	global $parms;
			if( $parms['cert'] <> 'n' && $parms['cert'] <> 'i' ){
		        $this->Image(get_stylesheet_directory()."/inc/images/cclogo.png", 10, 10, 100, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false, false, array());
		    }else{
		    	//
		    }
		}
	}

	if( $parms['cert'] == 'w' ){
		// workshop CC cert
		$hrs_text = '';
		$ce_credits = get_post_meta( $parms['training_id'], 'ce_credits', true );
		if( is_numeric( $ce_credits ) && $ce_credits > 0 && $ce_credits == round( $ce_credits, 1 ) ){
			$hrs_text = $ce_credits.' hour ';
		}
		$the_title = html_entity_decode( get_the_title( $parms['training_id'] ) );
		$presenters = cc_presenters_names( $parms['training_id'], 'none' );
		if( $presenters <> '' ){
			$the_title .= ' with '.$presenters;
		}
		$attendance_txt = 'Attended the above '.$hrs_text.'workshop hosted by Contextual Consulting';
		$dates = workshop_calculated_prettydates( $parms['training_id'], cc_timezone_get_user_timezone( $parms['user_id'] ) );
		if($dates['locale_date'] <> ''){
			$attendance_txt .= ' on '.$dates['locale_date'];
		}

		$user_info = get_userdata($parms['user_id']);

		$pdf = new CCCertPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Certificate of Attendance');
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		$pdf->setFontSubsetting(true);
		$pdf->SetFont('dejavusans', '', 10, '', true);
		$pdf->AddPage();
		$pdf->SetTextColor(36, 66, 92);
		$pdf->SetFont('dejavusans', 'B', 40, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		// we want the title limited to about 2 or three lines
		$char_count = strlen($the_title);
		if($char_count < 50){
			$pdf->SetFont('dejavusans', 'B', 40, '', true);
		}elseif($char_count < 75){
			$pdf->SetFont('dejavusans', 'B', 32, '', true);
		}elseif($char_count < 100){
			$pdf->SetFont('dejavusans', 'B', 30, '', true);
		}elseif($char_count < 125){
			$pdf->SetFont('dejavusans', 'B', 28, '', true);
		}elseif($char_count < 150){
			$pdf->SetFont('dejavusans', 'B', 24, '', true);
		}else{
			$pdf->SetFont('dejavusans', 'B', 20, '', true);
		}
		$pdf->Write(0, $the_title, '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', 'B', 40, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetTextColor(85, 85, 85);
		$pdf->SetFont('dejavusans', 'B', 30, '', true);
		$pdf->Write(2, 'Certificate of Attendance', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', '', 20, '', true);
		$pdf->Write(2, 'This is to certify that:', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', 'B', 30, '', true);
		$pdf->Write(2, $user_info->first_name.' '.$user_info->last_name, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$char_count = strlen($attendance_txt);
		if( $char_count < 85 ){
			$pdf->SetFont('dejavusans', 'B', 18, '', true);
		}elseif( $char_count < 95 ){
			$pdf->SetFont('dejavusans', 'B', 16, '', true);
		}else{
			$pdf->SetFont('dejavusans', 'B', 14, '', true);
		}
		$pdf->Write(2, $attendance_txt, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cert_sig.png".'">';
		$pdf->writeHTMLCell(130, 50, '', '', $html, 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cc_logo_only.png".'">';
		$pdf->writeHTMLCell(45, 50, '', '', $html, 0, 1, 0, true, 'R', true);
		$pdf->SetFont('dejavusans', 'B', 14, '', true);
		$txt = 'Dr Joe Oliver - Managing Director';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$txt = 'Contextual Consulting';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$pdf->Output('certificate.pdf', 'I');

	}elseif( $parms['cert'] == 'r' ){
		// recording CC cert
		$hrs_text = '';
		$ce_credits = get_post_meta( $parms['training_id'], 'ce_credits', true );
		if( is_numeric( $ce_credits ) && $ce_credits > 0 && $ce_credits == round( $ce_credits, 1 ) ){
			$hrs_text = $ce_credits.' hour ';
		}
		$the_title = html_entity_decode( get_the_title( $parms['training_id'] ) );
		$presenters = cc_presenters_names( $parms['training_id'], 'none' );
		if( $presenters <> '' ){
			$the_title .= ' with '.$presenters;
		}
		// $recording_meta = get_user_meta( $parms['user_id'], 'cc_rec_wshop_'.$parms['training_id'], true );
		$recording_meta = get_recording_meta( $parms['user_id'], $parms['training_id'] );
		$first_viewed_date = false;
		if( isset( $recording_meta['first_viewed'] ) && $recording_meta['first_viewed'] <> '' ){
			$first_viewed_date = DateTime::createFromFormat('d/m/Y H:i:s', $recording_meta['first_viewed']);
		}
		if( $first_viewed_date ){
			$attendance_txt = 'Attended a recording of the above '.$hrs_text.'workshop on '.$first_viewed_date->format('jS M Y');
		}else{
			$attendance_txt = 'Attended a recording of the above '.$hrs_text.'workshop which was originally hosted ';
			$workshop_dates = html_entity_decode( get_post_meta( $parms['training_id'], 'workshop_dates', true ) );
			if($workshop_dates == ''){
				$attendance_txt .= 'by Contextual Consulting';
			}else{
				$attendance_txt .= 'on '.$workshop_dates;
			}
		}

		$user_info = get_userdata($parms['user_id']);

		$pdf = new CCCertPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Certificate of Attendance');
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		$pdf->setFontSubsetting(true);
		$pdf->SetFont('dejavusans', '', 10, '', true);
		$pdf->AddPage();
		$pdf->SetTextColor(36, 66, 92);
		$pdf->SetFont('dejavusans', 'B', 40, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		// we want the title limited to about 2 or three lines
		$char_count = strlen($the_title);
		if($char_count < 50){
			$pdf->SetFont('dejavusans', 'B', 40, '', true);
		}elseif($char_count < 75){
			$pdf->SetFont('dejavusans', 'B', 32, '', true);
		}elseif($char_count < 100){
			$pdf->SetFont('dejavusans', 'B', 30, '', true);
		}elseif($char_count < 125){
			$pdf->SetFont('dejavusans', 'B', 28, '', true);
		}elseif($char_count < 150){
			$pdf->SetFont('dejavusans', 'B', 24, '', true);
		}else{
			$pdf->SetFont('dejavusans', 'B', 20, '', true);
		}
		$pdf->Write(0, $the_title, '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', 'B', 40, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetTextColor(85, 85, 85);
		$pdf->SetFont('dejavusans', 'B', 30, '', true);
		$pdf->Write(2, 'Certificate of Attendance', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', '', 20, '', true);
		$pdf->Write(2, 'This is to certify that:', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', 'B', 30, '', true);
		$pdf->Write(2, $user_info->first_name.' '.$user_info->last_name, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$char_count = strlen($attendance_txt);
		if( $char_count < 85 ){
			$pdf->SetFont('dejavusans', 'B', 18, '', true);
		}elseif( $char_count < 95 ){
			$pdf->SetFont('dejavusans', 'B', 16, '', true);
		}else{
			$pdf->SetFont('dejavusans', 'B', 14, '', true);
		}
		$pdf->Write(2, $attendance_txt, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cert_sig.png".'">';
		$pdf->writeHTMLCell(130, 50, '', '', $html, 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cc_logo_only.png".'">';
		$pdf->writeHTMLCell(45, 50, '', '', $html, 0, 1, 0, true, 'R', true);
		$pdf->SetFont('dejavusans', 'B', 14, '', true);
		$txt = 'Dr Joe Oliver - Managing Director';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$txt = 'Contextual Consulting';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$pdf->Output('certificate.pdf', 'I');

	}elseif( $parms['cert'] == 'e' ){
		// workshop event CC cert
		$hrs_text = '';
		$ce_credits = get_post_meta( $parms['training_id'], 'ce_credits', true );
		if( is_numeric( $ce_credits ) && $ce_credits > 0 && $ce_credits == round( $ce_credits, 1 ) ){
			$hrs_text = $ce_credits.' hour ';
		}
		$the_title = html_entity_decode( get_the_title( $parms['training_id'] ).': '.get_post_meta( $parms['training_id'], 'event_'.$parms['event_id'].'_name', true) );
		$presenters = cc_presenters_names( $parms['training_id'], 'none' );
		if( $presenters <> '' ){
			$the_title .= ' with '.$presenters;
		}
		$attendance_txt = 'Attended the above '.$hrs_text.'workshop hosted by Contextual Consulting';
		$dates = workshop_calculated_prettydates( $parms['training_id'], cc_timezone_get_user_timezone( $parms['user_id'] ) );
		if($dates['locale_date'] <> ''){
			$attendance_txt .= ' on '.$dates['locale_date'];
		}

		$user_info = get_userdata($parms['user_id']);

		$pdf = new CCCertPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Certificate of Attendance');
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		$pdf->setFontSubsetting(true);
		$pdf->SetFont('dejavusans', '', 10, '', true);
		$pdf->AddPage();
		$pdf->SetTextColor(36, 66, 92);
		$pdf->SetFont('dejavusans', 'B', 40, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		// we want the title limited to about 2 or three lines
		$char_count = strlen($the_title);
		if($char_count < 50){
			$pdf->SetFont('dejavusans', 'B', 40, '', true);
		}elseif($char_count < 75){
			$pdf->SetFont('dejavusans', 'B', 32, '', true);
		}elseif($char_count < 100){
			$pdf->SetFont('dejavusans', 'B', 30, '', true);
		}elseif($char_count < 125){
			$pdf->SetFont('dejavusans', 'B', 28, '', true);
		}elseif($char_count < 150){
			$pdf->SetFont('dejavusans', 'B', 24, '', true);
		}else{
			$pdf->SetFont('dejavusans', 'B', 20, '', true);
		}
		$pdf->Write(0, $the_title, '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', 'B', 40, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetTextColor(85, 85, 85);
		$pdf->SetFont('dejavusans', 'B', 30, '', true);
		$pdf->Write(2, 'Certificate of Attendance', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', '', 20, '', true);
		$pdf->Write(2, 'This is to certify that:', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', 'B', 30, '', true);
		$pdf->Write(2, $user_info->first_name.' '.$user_info->last_name, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$char_count = strlen($attendance_txt);
		if( $char_count < 85 ){
			$pdf->SetFont('dejavusans', 'B', 18, '', true);
		}elseif( $char_count < 95 ){
			$pdf->SetFont('dejavusans', 'B', 16, '', true);
		}else{
			$pdf->SetFont('dejavusans', 'B', 14, '', true);
		}
		$pdf->Write(2, $attendance_txt, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cert_sig.png".'">';
		$pdf->writeHTMLCell(130, 50, '', '', $html, 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cc_logo_only.png".'">';
		$pdf->writeHTMLCell(45, 50, '', '', $html, 0, 1, 0, true, 'R', true);
		$pdf->SetFont('dejavusans', 'B', 14, '', true);
		$txt = 'Dr Joe Oliver - Managing Director';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$txt = 'Contextual Consulting';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$pdf->Output('certificate.pdf', 'I');

	}elseif( $parms['cert'] == 'a' ){
		// APA certificate
		cc_debug_log_anything('CE Cert generated for user: '.$parms['user_id'].' for training_id: '.$parms['training_id'].' event_id: '.$parms['event_id']);
		// and also put it into the new CE Cert log table
		cc_cert_log_request( $parms['user_id'], $parms['training_id'], $parms['event_id'] );

		if( course_training_type( $parms['training_id'] ) == 'workshop' ){
			$attendance_txt = 'has attended, in its entirety, the following continuing education activity sponsored by Contextual Consulting';
		}else{
			$attendance_txt = 'has attended the on-demand continuing education activity sponsored by Contextual Consulting';
		}		
		// $num_credits = cc_ce_credits_number_credits($parms['training_id'], $parms['event_id']);
		if( $parms['event_id'] > 0 ){
			$meta_prefix = 'event_'.$parms['event_id'].'_';
		}else{
			$meta_prefix = '';
		}
		$num_credits = get_post_meta( $parms['training_id'], $meta_prefix.'ce_credits', true );

		$the_title = html_entity_decode( get_the_title( $parms['training_id'] ) );
		if( $parms['event_id'] > 0 ){
			$the_title .= ': '.html_entity_decode( get_post_meta( $parms['training_id'], 'event_'.$parms['event_id'].'_name', true) );
		}

		if( course_training_type( $parms['training_id'] ) == 'workshop' ){
			$pretty_dates = workshop_calculated_prettydates( $parms['training_id'] );
			$date_text = 'hosted on '.$pretty_dates['london_date'];
		}else{
			$workshop_dates = get_post_meta( $parms['training_id'], 'workshop_dates', true );
			if($workshop_dates <> ''){
				$date_text = 'originally hosted on '.$workshop_dates;
			}else{
				$date_text = '';
			}
		}

		$user_info = get_userdata($parms['user_id']);

		$pdf = new CCCertPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Certificate of Attendance');
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		$pdf->setFontSubsetting(true);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->AddPage();
		$pdf->SetTextColor(36, 66, 92);
		$pdf->SetFont('helvetica', 'B', 50, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetTextColor(85, 85, 85);
		$pdf->SetFont('helvetica', 'B', 30, '', true);
		$pdf->Write(2, 'Certificate of Attendance', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 20, '', true);
		$pdf->Write(2, 'This is to certify that:', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', 'B', 30, '', true);
		$pdf->Write(2, $user_info->first_name.' '.$user_info->last_name, '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(2, $attendance_txt, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		// we want the title limited to about 2 or three lines
		$char_count = strlen($the_title);
		if($char_count < 50){
			$pdf->SetFont('helvetica', 'B', 40, '', true);
		}elseif($char_count < 75){
			$pdf->SetFont('helvetica', 'B', 32, '', true);
		}elseif($char_count < 100){
			$pdf->SetFont('helvetica', 'B', 28, '', true);
		}elseif($char_count < 125){
			$pdf->SetFont('helvetica', 'B', 24, '', true);
		}else{
			$pdf->SetFont('helvetica', 'B', 20, '', true);
		}
		$pdf->Write(0, $the_title, '', 0, 'C', true, 0, false, false, 0);
		$presenters = cc_presenters_names($parms['training_id'], 'none');
		if($presenters <> ''){
			$pdf->SetFont('helvetica', 'B', 20, '', true);
			$pdf->Write(0, 'with '.$presenters, '', 0, 'C', true, 0, false, false, 0);
		}
		$pdf->SetFont('helvetica', 'B', 30, '', true);
		$pdf->Write(2, $num_credits.' CE credits', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 18, '', true);
		$pdf->Write(2, $date_text, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cert_sig.png".'">';
		// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
		// turn borders on to see what's going on!
		$pdf->writeHTMLCell(80, 40, '', '', $html, 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cc_logo_only.png".'">';
		$pdf->writeHTMLCell(40, 40, 155, '', $html, 0, 1, 0, true, 'R', true);
		$pdf->SetFont('helvetica', 'B', 14, '', true);
		$txt = 'Dr Joe Oliver - Managing Director';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$txt = 'Contextual Consulting';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 12, '', true);
		$pdf->Write(0, 'Contextual Consulting is approved by the American Psychological Association to sponsor continuing education for psychologists. Contextual Consulting maintains responsibility for the program and its contents.', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, 'Mailing address: England, Oakmoore Court, Kingswood Road, Droitwich, WR9 0QH', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, 'Telephone: +44 (0)20 3143 4772', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Output('certificate.pdf', 'I');

	}elseif( $parms['cert'] == 'b' ){
		// BACB certificate
		$bacb_num = get_user_meta( $parms['user_id'], 'bacb_num', true );

		$the_title = html_entity_decode( get_the_title( $parms['training_id'] ) );
		if( $parms['event_id'] > 0 ){
			$the_title .= ': '.html_entity_decode( get_post_meta( $parms['training_id'], 'event_'.$parms['event_id'].'_name', true) );
		}

		if( course_training_type( $parms['training_id'] ) == 'workshop' ){
			$dates = workshop_calculated_prettydates( $parms['training_id'], cc_timezone_get_user_timezone( $parms['user_id'] ) );
			$date_text = $dates['locale_date'];
			// we are not doing anything for events ... :-(
		}else{
			$workshop_dates = get_post_meta( $parms['training_id'], 'workshop_dates', true );
			if($workshop_dates <> ''){
				$date_text = $workshop_dates;
			}else{
				$date_text = '';
			}
		}
		
		if( $parms['event_id'] == 0 ){
			$ce_credits = get_post_meta( $parms['training_id'], 'ce_credits', true );
		}else{
			$ce_credits = get_post_meta( $parms['training_id'], 'event_'.$parms['event_id'].'_ce_credits', true );
		}

		$user_info = get_userdata($parms['user_id']);

		$pdf = new CCCertPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Learning Continuing Education');
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		$pdf->setFontSubsetting(true);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->AddPage();
		$pdf->SetTextColor(36, 66, 92);
		$pdf->SetFont('helvetica', 'B', 50, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetTextColor(85, 85, 85);
		$pdf->SetFont('helvetica', 'B', 30, '', true);
		$pdf->Write(2, 'Learning Continuing Education', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
		$pdf->MultiCell(80, 4, $user_info->first_name.' '.$user_info->last_name, 'B', 'C', 0, 0, '', '', true);
		$pdf->MultiCell(20, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(80, 4, $bacb_num, 'B', 'C', 0, 1, '', '', true);

		$pdf->SetFont('helvetica', 'I', 12, '', true);
		$pdf->MultiCell(80, 4, 'Participant name', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(20, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(80, 4, 'BACB certification number (if applicable)', 0, 'C', 0, 1, '', '', true);

		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', 'B', 24, '', true);
		$pdf->Write(2, 'Event Information', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		// Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M')
		$pdf->Cell(0, 0, $the_title, 'B', 1, 'C', 0, 0);
		$pdf->SetFont('helvetica', 'I', 12, '', true);
		$pdf->Cell(0, 0, 'Event Name', 0, 1, 'C', 0, 0);

		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->MultiCell(80, 4, $date_text, 'B', 'C', 0, 0, '', '', true);
		$pdf->MultiCell(20, 4, ' ', 0, 'C', 0, 0, '', '', true);
		if( course_training_type( $parms['training_id'] ) == 'workshop' ){
			$pdf->MultiCell(80, 4, 'Online Synchronous', 'B', 'C', 0, 1, '', '', true);
		}else{
			$pdf->MultiCell(80, 4, 'Online Asynchronous', 'B', 'C', 0, 1, '', '', true);
		}
		$pdf->SetFont('helvetica', 'I', 12, '', true);
		$pdf->MultiCell(80, 4, 'Event Dates', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(20, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(80, 4, 'Event Modality', 0, 'C', 0, 1, '', '', true);

		$pdf->SetFont('helvetica', '', 16, '', true);
		// $pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->MultiCell(50, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(80, 4, $ce_credits, 'B', 'C', 0, 1, '', '', true);
		$pdf->SetFont('helvetica', 'I', 12, '', true);
		$pdf->MultiCell(50, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(80, 4, 'Total Number of CEUs', 0, 'C', 0, 1, '', '', true);

		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', 'B', 24, '', true);
		$pdf->Write(2, 'ACE Coordinator Information', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Cell(0, 0, 'Natalie Savage', 'B', 1, 'C', 0, 0);
		$pdf->SetFont('helvetica', 'I', 12, '', true);
		$pdf->Cell(0, 0, 'ACE Coordinator Name', 0, 1, 'C', 0, 0);

		$pdf->SetFont('helvetica', '', 16, '', true);
		// $pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 15, '', true);
		$pdf->MultiCell(60, 4, 'Contextual Consulting', 'B', 'C', 0, 0, '', '', true);
		$pdf->MultiCell(10, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(50, 4, 'OP-20-3415', 'B', 'C', 0, 0, '', '', true);
		$pdf->MultiCell(10, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(50, 4, 'NA', 'B', 'C', 0, 1, '', '', true);
		$pdf->SetFont('helvetica', 'I', 12, '', true);
		$pdf->MultiCell(60, 4, 'ACE Provider Name', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(10, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(50, 4, 'ACE Provider Number', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(10, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(50, 4, 'Instructor Number (if applicable)', 0, 'C', 0, 1, '', '', true);

		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cert_sig.png".'" style="width:200px;">';
		// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
		// turn borders on to see what's going on!
		$pdf->writeHTMLCell(80, 30, '', '', $html, 0, 0, 0, true, '', true);
		$pdf->MultiCell(20, 30, ' ', 0, 'C', 0, 0, '', '', true);
		// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
		$pdf->MultiCell(80, 30, date('jS M Y'), 0, 'C', 0, 1, '', '', true, 0, false, true, 30, 'B', false );
		$pdf->SetFont('helvetica', 'I', 12, '', true);
		$pdf->MultiCell(80, 4, 'ACE Provider Signature', 'T', 'C', 0, 0, '', '', true);
		$pdf->MultiCell(20, 4, ' ', 0, 'C', 0, 0, '', '', true);
		$pdf->MultiCell(80, 4, 'Date', 'T', 'C', 0, 1, '', '', true);

		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/BACB_ACE_sml.jpg".'" style="width:80px;">';
		$pdf->writeHTMLCell(90, 30, '', '', $html, 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cclogo.png".'" style="width:250px;">';
		$pdf->writeHTMLCell(90, 30, '', '', $html, 0, 0, 0, true, 'R', true);

		$pdf->Output('certificate.pdf', 'I');

	}elseif( $parms['cert'] == 'n' ){
		// NBCC certificate
		if( $parms['event_id'] > 0 ){
			$meta_prefix = 'event_'.$parms['event_id'].'_';
		}else{
			$meta_prefix = '';
		}
		$num_credits = get_post_meta( $parms['training_id'], $meta_prefix.'ce_credits', true );

		$the_title = html_entity_decode( get_the_title( $parms['training_id'] ) );
		if( $parms['event_id'] > 0 ){
			$the_title .= ': '.html_entity_decode( get_post_meta( $parms['training_id'], 'event_'.$parms['event_id'].'_name', true) );
		}

		if( course_training_type( $parms['training_id'] ) == 'workshop' ){
			$pretty_dates = workshop_calculated_prettydates( $parms['training_id'] );
			$date_text = 'hosted on '.$pretty_dates['london_date'];
			$attendance_txt = 'has attended the live continuing education program sponsored by Contextual Consulting';
		}else{
			$workshop_dates = get_post_meta( $parms['training_id'], 'workshop_dates', true );
			if($workshop_dates <> ''){
				$date_text = 'hosted on '.$workshop_dates;
			}else{
				$date_text = '';
			}
			$attendance_txt = 'has attended the on-demand continuing education program sponsored by Contextual Consulting';
		}

		$user_info = get_userdata($parms['user_id']);

		$pdf = new CCCertPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Certificate of Completion');
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		$pdf->setFontSubsetting(true);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->AddPage();
		$pdf->SetTextColor(36, 66, 92);
		$pdf->SetFont('helvetica', 'B', 50, '', true);

		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cclogo.png".'" style="width:300px;">';
		// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
		$pdf->writeHTMLCell(90, 30, '', '', $html, 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/nbcc_logo_sml.png".'" style="width:80px;">';
		$pdf->writeHTMLCell(90, 30, '', '', $html, 0, 1, 0, true, 'R', true);

		$pdf->SetTextColor(85, 85, 85);
		$pdf->SetFont('helvetica', 'B', 30, '', true);
		$pdf->Write(2, 'Certificate of Completion', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 20, '', true);
		$pdf->Write(2, 'This is to certify that:', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', 'B', 30, '', true);
		$pdf->Write(2, $user_info->first_name.' '.$user_info->last_name, '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(2, $attendance_txt, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		// we want the title limited to about 2 or three lines
		$char_count = strlen($the_title);
		if($char_count < 50){
			$pdf->SetFont('helvetica', 'B', 40, '', true);
		}elseif($char_count < 75){
			$pdf->SetFont('helvetica', 'B', 34, '', true);
		}elseif($char_count < 100){
			$pdf->SetFont('helvetica', 'B', 28, '', true);
		}elseif($char_count < 125){
			$pdf->SetFont('helvetica', 'B', 24, '', true);
		}else{
			$pdf->SetFont('helvetica', 'B', 20, '', true);
		}
		$pdf->Write(0, $the_title, '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 30, '', true);
		$pdf->Write(2, $num_credits.' credit hours issued by NBCC ACEP No. 7578', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 18, '', true);
		$pdf->Write(2, $date_text, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cert_sig.png".'" style="width:250px;">';
		// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
		// turn borders on to see what's going on!
		$pdf->writeHTMLCell(80, 40, '', '', $html, 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cc_logo_only.png".'" style="width:175px;">';
		$pdf->writeHTMLCell(40, 40, 155, '', $html, 0, 1, 0, true, 'R', true);
		$pdf->SetFont('helvetica', 'B', 14, '', true);
		$txt = 'Dr Joe Oliver - Managing Director';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$txt = 'Contextual Consulting';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$pdf->Output('certificate.pdf', 'I');

	}elseif( $parms['cert'] == 'i' ){

		// ICF certificate
		if( $parms['event_id'] > 0 ){
			$meta_prefix = 'event_'.$parms['event_id'].'_';
		}else{
			$meta_prefix = '';
		}
		$num_credits = get_post_meta( $parms['training_id'], $meta_prefix.'ce_credits', true );

		$the_title = html_entity_decode( get_the_title( $parms['training_id'] ) );
		if( $parms['event_id'] > 0 ){
			$the_title .= ': '.html_entity_decode( get_post_meta( $parms['training_id'], 'event_'.$parms['event_id'].'_name', true) );
		}
		$presenters = cc_presenters_names( $parms['training_id'], 'none' );
		if( $presenters <> '' ){
			$the_title .= ' with '.$presenters;
		}

		if( course_training_type( $parms['training_id'] ) == 'workshop' ){
			$pretty_dates = workshop_calculated_prettydates( $parms['training_id'] );
			$date_text = 'hosted on '.$pretty_dates['london_date'];
			$attendance_txt = 'has attended, in its entirety, the following continuing coach education program, delivered by Anderson Turner Ltd and sponsored by Contextual Consulting';
		}else{
			$workshop_dates = get_post_meta( $parms['training_id'], 'workshop_dates', true );
			if($workshop_dates <> ''){
				$date_text = 'hosted on '.$workshop_dates;
			}else{
				$date_text = '';
			}
			$attendance_txt = 'has attended the following on-demand continuing coach education program, delivered by Anderson Turner Ltd and sponsored by Contextual Consulting';
		}

		$user_info = get_userdata($parms['user_id']);

		$pdf = new CCCertPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Certificate of Attendance');
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(0);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, 0);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		$pdf->setFontSubsetting(true);
		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->AddPage();


		// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
		$pdf->writeHTMLCell(90, 20, '', '', '', 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/ICF_CCE_logo.png".'" style="width:100px;height:90px;">';
		$pdf->writeHTMLCell(90, 0, '', '', $html, 0, 1, 0, true, 'R', false);

		$pdf->SetTextColor(85, 85, 85);
		$pdf->SetFont('helvetica', 'B', 30, '', true);
		// Write($h, $txt, $link='', $fill=false, $align='', $ln=false, $stretch=0, $firstline=false, $firstblock=false, $maxh=0, $wadj=0, $margin=null)
		$pdf->Write(2, 'Certificate of Attendance', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 20, '', true);
		$pdf->Write(2, 'This is to certify that:', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', 'B', 30, '', true);
		$pdf->Write(2, $user_info->first_name.' '.$user_info->last_name, '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 16, '', true);
		$pdf->Write(2, $attendance_txt, '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		// we want the title limited to about 2 or three lines
		$char_count = strlen($the_title);
		if($char_count < 50){
			$pdf->SetFont('helvetica', 'B', 40, '', true);
		}elseif($char_count < 75){
			$pdf->SetFont('helvetica', 'B', 34, '', true);
		}elseif($char_count < 100){
			$pdf->SetFont('helvetica', 'B', 28, '', true);
		}elseif($char_count < 125){
			$pdf->SetFont('helvetica', 'B', 24, '', true);
		}else{
			$pdf->SetFont('helvetica', 'B', 20, '', true);
		}
		$pdf->Write(0, $the_title, '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('helvetica', '', 18, '', true);
		$pdf->Write(2, $date_text, '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 24, '', true);
		$pdf->Write(2, $num_credits.' CCE points completed', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', '', 20, '', true);
		$pdf->Write(2, '4.5 Core Competencies', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(2, '1 Resource Development', '', 0, 'C', true, 0, false, false, 0);

		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('helvetica', 'B', 14, '', true);

		// footer
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cert_sig.png".'" style="width:150px;">';
		// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
		// turn borders on to see what's going on!
		$pdf->writeHTMLCell(110, 20, '', '', $html, 0, 0, 0, true, '', true);

		$html = '<img src="'.get_stylesheet_directory()."/inc/images/Hazel_Anderson_Turner_sig.jpg".'" style="width:200px;">';
		$pdf->writeHTMLCell(70, 20, '', '', $html, 0, 1, 0, true, '', true);

		// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x=null, $y=null, $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
		$pdf->MultiCell(110, 4, 'Dr Joe Oliver', 0, 'L', 0, 0, '', '', true);
		$pdf->MultiCell(70, 4, 'Hazel Anderson-Turner', 0, 'L', 0, 1, '', '', true);

		$pdf->MultiCell(110, 4, 'Managing Director', 0, 'L', 0, 0, '', '', true);
		$pdf->MultiCell(70, 4, 'Workshop leader', 0, 'L', 0, 1, '', '', true);

		$pdf->MultiCell(110, 4, 'Contextual Consulting Ltd', 0, 'L', 0, 0, '', '', true);
		$pdf->MultiCell(70, 4, 'Anderson Turner Ltd', 0, 'L', 0, 1, '', '', true);

		$pdf->MultiCell(110, 4, '', 0, 'L', 0, 0, '', '', true);
		$pdf->MultiCell(70, 4, '', 0, 'L', 0, 1, '', '', true);

		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cclogo.png".'" style="width:150px;">';
		$pdf->writeHTMLCell(110, 20, '', '', $html, 0, 0, 0, true, '', true);

		$html = '<img src="'.get_stylesheet_directory()."/inc/images/hazel_anderson_turner.png".'" style="width:150px;">';
		$pdf->writeHTMLCell(70, 20, '', '', $html, 0, 1, 0, true, '', true);

		$pdf->SetFont('helvetica', '', 10, '', true);
		$pdf->Write(2, 'England, Oakmoore Court, Kingswood Road, Droitwich, WR9 0QH', '', 0, 'C', true, 0, false, false, 0);

		$pdf->Output('certificate.pdf', 'I');

	}

}
