<?php
/**
 * Template Name: Receipt PDF
 * Generate the PDF version of a receipt
 *
 * Uses TCPDF
 *
 */

if(isset($_GET['r']) && $_GET['r'] <> ''){
	$payment_id = ccpdf_receipt_parms_decode($_GET['r']);
	if($payment_id){
		$payment_data = cc_paymentdb_get_payment($payment_id);
		if($payment_data){

			// Include the main TCPDF library
			require_once(get_stylesheet_directory().'/inc/tcpdf/config/tcpdf_config.php');
			require_once(get_stylesheet_directory().'/inc/tcpdf/tcpdf.php');

			// Extend the TCPDF class to create custom Header and Footer
			class CCCertPDF extends TCPDF {
			    //Page header
			    public function Header() {
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
			$pdf->SetTitle('Receipt');
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
			$pdf->SetFont('helvetica', '', 10, '', true);
			// Add a page
			// This method has several options, check the source code documentation for more information.
			$pdf->AddPage();
			// set text shadow effect
			// $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
			// writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
			// $pdf->SetTextColor(36, 66, 92); // dark blue
			$pdf->SetTextColor(85, 85, 85);
			$pdf->SetFont('helvetica', 'B', 40, '', true);
			// Write($h, $txt, $link='', $fill=false, $align='', $ln=false, $stretch=0, $firstline=false, $firstblock=false, $maxh=0, $wadj=0, $margin='')
			$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
			// $pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
			$pdf->SetFont('helvetica', 'B', 14, '', true);
			$pdf->Write(7, 'Contextual Consulting Ltd', '', 0, 'R', true, 0, false, false, 0);
			$pdf->SetFont('helvetica', '', 12, '', true);
			$pdf->Write(7, 'Oakmoore Court', '', 0, 'R', true, 0, false, false, 0);
			$pdf->Write(7, 'Kingswood Road', '', 0, 'R', true, 0, false, false, 0);
			$pdf->Write(7, 'Droitwich, WR9 0QH', '', 0, 'R', true, 0, false, false, 0);

			$pdf->SetFont('helvetica', 'B', 20, '', true);
			$pdf->Write(35, 'RECEIPT', '', 0, 'C', true, 0, false, false, 0);

			$last_update = date('jS M Y', strtotime($payment_data['last_update']));
			$pdf->SetFont('helvetica', '', 12, '', true);
			$pdf->Write(7, 'DATE: '.$last_update, '', 0, '', true, 0, false, false, 0);

			$fao = $payment_data['firstname'].' '.$payment_data['lastname'].' ';
			$invoice_address = cc_payment_invoice_address( $payment_data, 'string' );
			if($invoice_address <> ''){
				$fao .= $invoice_address;
			}else{
				$fao .= $payment_data['address'];
			}
			$pdf->Write(7, 'FAO: '.$fao, '', 0, '', true, 0, false, false, 0);

			$pdf->Write(7, 'No: '.$payment_id, '', 0, '', true, 0, false, false, 0);

			$pdf->Write(0, ' ', '', 0, '', true, 0, false, false, 0);
			$pdf->Write(7, 'PAYMENT RECEIVED:', '', 0, '', true, 0, false, false, 0);
			$pdf->SetTextColor(186, 38, 38); // red
			$pdf->Write(7, 'PAYMENT RECEIVED WITH THANKS', '', 0, '', true, 0, false, false, 0);
			$pdf->Write(10, ' ', '', 0, '', true, 0, false, false, 0);

			$pdf->SetFont('helvetica', '', 16, '', true);
			$pdf->SetTextColor(85, 85, 85);
			$pdf->SetFillColor(238, 241, 244);
			// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
			$pdf->MultiCell(119, 20, 'Description', 0, '', 1, 0, '', '', true, 0, false, true, 20, 'M');
			$pdf->MultiCell(60, 20, 'COST', 0, 'R', 1, 1, '', '', true, 0, false, true, 20, 'M');

			$pdf->Write(10, ' ', '', 0, '', true, 0, false, false, 0);
			$purchase = html_entity_decode(get_the_title($payment_data['workshop_id']));
			if($payment_data['type'] <> 'recording'){
				$dates = workshop_calculated_prettydates($payment_data['workshop_id'], $locale='Europe/London');
				if($dates['london_date'] <> ''){
					$purchase .= ' on '.$dates['london_date'];
				}
				if($payment_data['upsell_workshop_id'] <> 0){
					$purchase .= ' PLUS '.html_entity_decode(get_the_title($payment_data['upsell_workshop_id']));
					$dates = workshop_calculated_prettydates($payment_data['upsell_workshop_id'], $locale='Europe/London');
					if($dates['london_date'] <> ''){
						$purchase .= ' on '.$dates['london_date'];
					}
				}
				if(workshop_is_multi_event($payment_data['workshop_id']) && $payment_data['event_ids'] <> ''){
					$event_ids = explode(',', $payment_data['event_ids']);
					foreach ($event_ids as $event_id) {
						$event_id = trim($event_id);
						if($event_id <> ''){
							$event_name = html_entity_decode(get_post_meta($payment_data['workshop_id'], 'event_'.$event_id.'_name', true));
							$purchase .= '<br> - '.$event_name;
						}
					}
				}
			}
			$pdf->SetFont('helvetica', '', 12, '', true);
			// writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
			$pdf->writeHTMLCell(119, 15, '', '', $purchase, 0, 0, false, true, '', true);

			$net_pmt = $payment_data['payment_amount'] - $payment_data['vat_included'];
			if($payment_data['currency'] == 'AUD' || $payment_data['currency'] == 'USD'){
				$currency_sign = '$';
			}elseif($payment_data['currency'] == 'EUR'){
				$currency_sign = '€';
			}else{
				$currency_sign = '£';
			}
			$txt = $currency_sign.number_format($net_pmt, 2, '.', '');
			$pdf->MultiCell(60, 15, $txt, 0, 'R', 0, 1, '', '', true, 0, false, true, 20, '');

			$pdf->writeHTMLCell(119, 15, '', '', ' ', 0, 0, false, true, '', true);

			$txt = $currency_sign.number_format($payment_data['vat_included'], 2, '.', '');
			$pdf->MultiCell(60, 15, 'VAT '.$txt, 0, 'J', 0, 1, '', '', true, 0, false, true, 20, '');

			$pdf->writeHTMLCell(119, 15, '', '', ' ', 0, 0, false, true, '', true);

			$txt = $currency_sign.number_format($payment_data['payment_amount'], 2, '.', '');
			$pdf->MultiCell(60, 15, 'TOTAL '.$txt, 0, 'J', 1, 1, '', '', true, 0, false, true, 15, 'M');

			// footer ... cannot spend more time trying to work out the proper way to do it!!!
			// Position at 30 mm from bottom
			$pdf->SetY(-30);
			// Set font
			$pdf->SetFont('helvetica', '', 8);

			$html = '<div style="display:inline-block;"><img src="'.get_stylesheet_directory()."/inc/images/cc_logo_only.png".'" style="max-width:100%;"></div>';
			$pdf->writeHTMLCell(20, 20, '', '', $html, 0, 0, 0, true, '', true);

			$html = 'Contextual Consulting Ltd is a private limited company registered in<br>England, Oakmoore Court, Kingswood Road, Droitwich, WR9 0QH<br>e - admin@contextualconsulting.co.uk<br>w - www.contextualconsulting.co.uk - Company No. 10164502 - VAT Reg No. 271420628';
			$pdf->writeHTMLCell(159, 20, '', '', $html, 0, 1, 0, true, 'C', true);

			/*

			// we want the title limited to about 2 or three lines
			$char_count = strlen($the_title);
			if($char_count < 50){
				$pdf->SetFont('helvetica', 'B', 40, '', true);
			}elseif($char_count < 75){
				$pdf->SetFont('helvetica', 'B', 36, '', true);
			}elseif($char_count < 100){
				$pdf->SetFont('helvetica', 'B', 30, '', true);
			}elseif($char_count < 125){
				$pdf->SetFont('helvetica', 'B', 28, '', true);
			}else{
				$pdf->SetFont('helvetica', 'B', 24, '', true);
			}

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


			/*
			$pdf->Write(0, $the_title, '', 0, 'C', true, 0, false, false, 0);

			$pdf->SetFont('helvetica', 'B', 40, '', true);
			$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
			$pdf->SetTextColor(85, 85, 85);
			$pdf->SetFont('helvetica', 'B', 30, '', true);
			$pdf->Write(2, 'Certificate of Attendance', '', 0, 'C', true, 0, false, false, 0);
			$pdf->SetFont('helvetica', '', 20, '', true);
			$pdf->Write(2, 'This is to certify that:', '', 0, 'C', true, 0, false, false, 0);
			$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
			$pdf->SetFont('helvetica', 'B', 30, '', true);
			$pdf->Write(2, $user_info->first_name.' '.$user_info->last_name, '', 0, 'C', true, 0, false, false, 0);
			$pdf->Write(0, ' ', '', 0, 'C', true, 0, false, false, 0);
			$pdf->SetFont('helvetica', 'B', 18, '', true);
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
			$pdf->SetFont('helvetica', 'B', 14, '', true);
			$txt = 'Dr Joe Oliver - Managing Director';
			$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);
			$txt = 'Contextual Consulting';
			$pdf->Write(2, $txt, '', 0, 'L', true, 0, false, false, 0);


			*/

			// Close and output PDF document
			// This method has several options, check the source code documentation for more information.
			$pdf->Output('receipt.pdf', 'I');




		}
	}
}

