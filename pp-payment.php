<?php
/**
 * @package Prospress
 * @author Brent Shepherd
 * @version 0.1
 */

/*
Plugin Name: Prospress Payment
Plugin URI: http://prospress.com
Description: Money - the great enabler of trade. This plugin provides a payment system for Prospress posts.
Author: Brent Shepherd
Version: 0.1
Author URI: http://brentshepherd.com/
*/

if ( !defined( 'PP_PAYMENTS_DB_VERSION'))
	define ( 'PP_PAYMENTS_DB_VERSION', '0001' );

if( !defined( 'PP_PAYMENT_DIR' ) )
	define( 'PP_PAYMENT_DIR', PP_PLUGIN_DIR . '/pp-payment' );
if( !defined( 'PP_PAYMENT_URL' ) )
	define( 'PP_PAYMENT_URL', PP_PLUGIN_URL . '/pp-payment' );

if( !defined( 'PP_INVOICE_DIR' ) )
	define( 'PP_INVOICE_DIR', PP_PAYMENT_DIR . '/wp-invoice-m2m' );

//Payment tables
global $wpdb;
if ( !isset($wpdb->payments) || empty($wpdb->payments))
	$wpdb->payments = $wpdb->prefix . 'payments';
if ( !isset($wpdb->paymentsmeta) || empty($wpdb->paymentsmeta))
	$wpdb->paymentsmeta = $wpdb->prefix . 'paymentsmeta';
if ( !isset($wpdb->payments_log) || empty($wpdb->payments_log))
	$wpdb->payments_log = $wpdb->prefix . 'payments_log';

// The engine behind the payment system - TwinCitiesTech's WP Invoice
require_once( PP_INVOICE_DIR . '/WP-Invoice.php' );

$WP_Invoice = new WP_Invoice();	

register_activation_hook(__FILE__, array( $WP_Invoice, 'install' ) );
//register_activation_hook(__FILE__, $WP_Invoice->install() );
//register_deactivation_hook(__FILE__, "wp_invoice_deactivation");
