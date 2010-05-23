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

if( !defined( 'PP_PAYMENT_DIR' ) )
	define( 'PP_PAYMENT_DIR', PP_PLUGIN_DIR . '/pp-payment' );
if( !defined( 'PP_PAYMENT_URL' ) )
	define( 'PP_PAYMENT_URL', PP_PLUGIN_URL . '/pp-payment' );
if( !defined( 'PP_INVOICE_DIR' ) )
	define( 'PP_INVOICE_DIR', PP_PAYMENT_DIR . '/wp-invoice-m2m' );


// The engine behind the payment system - TwinCitiesTech's WP Invoice
require_once( PP_INVOICE_DIR . '/WP-Invoice.php' );

