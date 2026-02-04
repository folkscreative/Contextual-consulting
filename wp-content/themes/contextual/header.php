<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Contextual
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<!-- no cache -->
	<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
	<meta http-equiv="Pragma" content="no-cache">
	<meta http-equiv="Expires" content="0">
	<?php 
	// if using faviconit ....
	$favicon_folder = $_SERVER['DOCUMENT_ROOT'].'/favicon/';
	if(file_exists($favicon_folder)){ ?>
		<link rel="shortcut icon" href="/favicon/favicon.ico?v=1">
		<link rel="icon" sizes="16x16 32x32 64x64" href="/favicon/favicon.ico?v=1">
		<link rel="icon" type="image/png" sizes="196x196" href="/favicon/favicon-192.png?v=1">
		<link rel="icon" type="image/png" sizes="160x160" href="/favicon/favicon-160.png?v=1">
		<link rel="icon" type="image/png" sizes="96x96" href="/favicon/favicon-96.png?v=1">
		<link rel="icon" type="image/png" sizes="64x64" href="/favicon/favicon-64.png?v=1">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32.png?v=1">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16.png?v=1">
		<link rel="apple-touch-icon" sizes="152x152" href="/favicon/favicon-152.png?v=1">
		<link rel="apple-touch-icon" sizes="144x144" href="/favicon/favicon-144.png?v=1">
		<link rel="apple-touch-icon" sizes="120x120" href="/favicon/favicon-120.png?v=1">
		<link rel="apple-touch-icon" sizes="114x114" href="/favicon/favicon-114.png?v=1">
		<link rel="apple-touch-icon" sizes="76x76" href="/favicon/favicon-76.png?v=1">
		<link rel="apple-touch-icon" sizes="72x72" href="/favicon/favicon-72.png?v=1">
		<link rel="apple-touch-icon" href="/favicon/favicon-57.png?v=1">
		<meta name="msapplication-TileColor" content="#FFFFFF">
		<meta name="msapplication-TileImage" content="/favicon/favicon-144.png?v=1">
		<meta name="msapplication-config" content="/favicon/browserconfig.xml">
	<?php } ?>
	<?php wp_head();
	if(LIVE_SITE){ ?>
		<!-- Facebook Pixel Code -->
		<script>
		  !function(f,b,e,v,n,t,s)
		  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
		  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
		  n.queue=[];t=b.createElement(e);t.async=!0;
		  t.src=v;s=b.getElementsByTagName(e)[0];
		  s.parentNode.insertBefore(t,s)}(window, document,'script',
		  'https://connect.facebook.net/en_US/fbevents.js');
		  fbq('init', '331184945943129');
		  fbq('track', 'PageView');
		</script>
		<noscript>
		  <img height="1" width="1" style="display:none" 
		       src="https://www.facebook.com/tr?id=331184945943129&ev=PageView&noscript=1"/>
		</noscript>
		<!-- End Facebook Pixel Code -->	
	<?php } ?>
</head>
<body <?php body_class(); ?>>
<div id="page" class="site">
	<a class="skip-link sr-only" href="#content"><?php esc_html_e( 'Skip to content', 'contextual' ); ?></a>
	<?php echo contextual_header(); ?>
	<div id="content" class="site-content">
