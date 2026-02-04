<?php
/**
 * Template Name: Certificate PDF
 * Generate the PDF version of a certificate
 * This is the CC Certificate
 *
 * Uses TCPDF
 *
 */

if(isset($_GET['c']) && $_GET['c'] <> ''){
	if(substr($_GET['c'], 0, 1) == 'r'){
		$type = 'recording';
		$cert_parms = ccpdf_recording_cert_parms_decode($_GET['c']);
	}else{
		$type = 'workshop';
		$cert_parms = ccpdf_workshop_cert_parms_decode($_GET['c']);
	}
	if($cert_parms){

		if($type == 'recording'){
			$hrs_text = '';
			$certificate = get_post_meta($cert_parms['recording_id'], 'workshop_certificate', true);
			if(is_numeric($certificate) && $certificate > 0 && $certificate == round($certificate, 0)){
				$hrs_text = $certificate.' hour ';
			}
			$the_title = html_entity_decode(get_the_title($cert_parms['recording_id']));
			$presenters = cc_presenters_names($cert_parms['recording_id'], 'none');
			if($presenters <> ''){
				$the_title .= ' with '.$presenters;
			}
			// $recording_meta = get_user_meta($cert_parms['user_id'], 'cc_rec_wshop_'.$cert_parms['recording_id'], true);
			$recording_meta = get_recording_meta( $cert_parms['user_id'], $cert_parms['recording_id'] );
			if(isset($recording_meta['first_viewed']) && $recording_meta['first_viewed'] <> ''){
				$first_viewed_date = DateTime::createFromFormat('d/m/Y H:i:s', $recording_meta['first_viewed']);
				$attendance_txt = 'Attended a recording of the above '.$hrs_text.'workshop on '.$first_viewed_date->format('jS M Y');
			}else{
				$attendance_txt = 'Attended a recording of the above '.$hrs_text.'workshop which was originally hosted ';
				$workshop_dates = html_entity_decode(get_post_meta($cert_parms['recording_id'], 'workshop_dates', true));
				if($workshop_dates == ''){
					$attendance_txt .= 'by Contextual Consulting';
				}else{
					$attendance_txt .= 'on '.$workshop_dates;
				}
			}
		}else{
			$hrs_text = '';
			$certificate = get_post_meta($cert_parms['workshop_id'], 'workshop_certificate', true);
			if(is_numeric($certificate) && $certificate > 0 && $certificate == round($certificate, 0)){
				$hrs_text = $certificate.' hour ';
			}
			$the_title = html_entity_decode(get_the_title($cert_parms['workshop_id']));
			$presenters = cc_presenters_names($cert_parms['workshop_id'], 'none');
			if($presenters <> ''){
				$the_title .= ' with '.$presenters;
			}
			$attendance_txt = 'Attended the above '.$hrs_text.'workshop hosted by Contextual Consulting';
			$dates = workshop_calculated_prettydates($cert_parms['workshop_id'], $locale='Europe/London');
			if($dates['london_date'] <> ''){
				$attendance_txt .= ' on '.$dates['london_date'];
			}
		}

		$user_info = get_userdata($cert_parms['user_id']);

		// Include the main TCPDF library
		require_once(get_stylesheet_directory().'/inc/tcpdf/config/tcpdf_config.php');
		require_once(get_stylesheet_directory().'/inc/tcpdf/tcpdf.php');

		// Extend the TCPDF class to create custom Header and Footer
		class CCCertPDF extends TCPDF {
		    //Page header
		    public function Header() {
		    	global $cert_parms;
		        // Logo
		        // $image_file = K_PATH_IMAGES.'logo_example.jpg';
		        // Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
		        // $this->Image($image_file, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		        $this->Image(get_stylesheet_directory()."/inc/images/cclogo.png", 10, 10, 100, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false, false, array());
		    }

		}

		// create new PDF document
		$pdf = new CCCertPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetTitle('Certificate of Attendance');
		// set default header data
		// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		// $pdf->setFooterData(array(0,64,0), array(0,64,128));
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		// $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		// remove the footer
		$pdf->setPrintFooter(false);
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		// set auto page breaks
		// $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->SetAutoPageBreak(TRUE, 0);
		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		// ---------------------------------------------------------
		// set default font subsetting mode
		$pdf->setFontSubsetting(true);
		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		$pdf->SetFont('dejavusans', '', 10, '', true);
		// $pdf->SetFont('helvetica', '', 10, '', true);
		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();
		// set text shadow effect
		// $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
		// writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
		$pdf->SetTextColor(36, 66, 92);
		$pdf->SetFont('dejavusans', 'B', 40, '', true);
		// $pdf->SetFont('helvetica', 'B', 40, '', true);
		// Write($h, $txt, $link='', $fill=false, $align='', $ln=false, $stretch=0, $firstline=false, $firstblock=false, $maxh=0, $wadj=0, $margin='')
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		// we want the title limited to about 2 or three lines
		$char_count = strlen($the_title);
		if($char_count < 50){
			$pdf->SetFont('dejavusans', 'B', 40, '', true);
			// $pdf->SetFont('helvetica', 'B', 40, '', true);
		}elseif($char_count < 75){
			$pdf->SetFont('dejavusans', 'B', 36, '', true);
			// $pdf->SetFont('helvetica', 'B', 36, '', true);
		}elseif($char_count < 100){
			$pdf->SetFont('dejavusans', 'B', 30, '', true);
			// $pdf->SetFont('helvetica', 'B', 30, '', true);
		}elseif($char_count < 125){
			$pdf->SetFont('dejavusans', 'B', 28, '', true);
			// $pdf->SetFont('helvetica', 'B', 28, '', true);
		}elseif($char_count < 150){
			$pdf->SetFont('dejavusans', 'B', 24, '', true);
		}else{
			$pdf->SetFont('dejavusans', 'B', 20, '', true);
			// $pdf->SetFont('helvetica', 'B', 24, '', true);
		}
		// $pdf->Write(0, $char_count, '', 0, 'C', true, 0, false, false, 0);

		/*
		$lorem = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
		$pdf->SetFont('helvetica', 'B', 40, '', true);		
		$pdf->Write(0, substr($lorem, 0, 50), '', 0, 'C', true, 0, false, false, 0);
		$pdf->writeHTML("<hr>", true, false, false, false, '');
		$pdf->SetFont('helvetica', 'B', 36, '', true);		
		$pdf->Write(0, substr($lorem, 0, 75), '', 0, 'C', true, 0, false, false, 0);
		// $pdf->writeHTML("<hr>", true, false, false, false, '');
		$pdf->SetFont('helvetica', 'B', 30, '', true);		
		$pdf->Write(0, substr($lorem, 0, 100), '', 0, 'C', true, 0, false, false, 0);
		// $pdf->writeHTML("<hr>", true, false, false, false, '');
		$pdf->SetFont('helvetica', 'B', 28, '', true);		
		$pdf->Write(0, substr($lorem, 0, 125), '', 0, 'C', true, 0, false, false, 0);
		// $pdf->writeHTML("<hr>", true, false, false, false, '');
		$pdf->SetFont('helvetica', 'B', 24, '', true);		
		$pdf->Write(0, substr($lorem, 0, 150), '', 0, 'C', true, 0, false, false, 0);
		// $pdf->writeHTML("<hr>", true, false, false, false, '');
		*/

		$pdf->Write(0, $the_title, '', 0, 'C', true, 0, false, false, 0);

		$pdf->SetFont('dejavusans', 'B', 40, '', true);
		// $pdf->SetFont('helvetica', 'B', 40, '', true);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetTextColor(85, 85, 85);
		$pdf->SetFont('dejavusans', 'B', 30, '', true);
		// $pdf->SetFont('helvetica', 'B', 30, '', true);
		$pdf->Write(2, 'Certificate of Attendance', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', '', 20, '', true);
		// $pdf->SetFont('helvetica', '', 20, '', true);
		$pdf->Write(2, 'This is to certify that:', '', 0, 'C', true, 0, false, false, 0);
		$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
		$pdf->SetFont('dejavusans', 'B', 30, '', true);
		// $pdf->SetFont('helvetica', 'B', 30, '', true);
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
		// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
		// turn borders on to see what's going on!
		$pdf->writeHTMLCell(130, 50, '', '', $html, 0, 0, 0, true, '', true);
		$html = '<img src="'.get_stylesheet_directory()."/inc/images/cc_logo_only.png".'">';
		$pdf->writeHTMLCell(45, 50, '', '', $html, 0, 1, 0, true, 'R', true);
		$pdf->SetFont('dejavusans', 'B', 14, '', true);
		// $pdf->SetFont('helvetica', 'B', 14, '', true);
		$txt = 'Dr Joe Oliver - Managing Director';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
		$txt = 'Contextual Consulting';
		$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);

		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output('certificate.pdf', 'I');

	}

}