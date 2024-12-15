<?php
/**
 * Plugin Name: Uni CPO hidden/disabled options
 * Plugin URI: https://builderius.io
 * Description: Allows use Dynamic Notice value in Text Input options.
 * Version: 1.0.0
 * Author: MooMoo Agency
 * Author URI: http://moomoo.agency
 * Domain Path: /languages/
 * Text Domain: uni-cpo-enquiries
 * Requires PHP: 7.2
 * WC requires at least: 4.5
 * WC tested up to: 4.8.0
 * License: GPL v2
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'UniCpoScrtopts' ) ) {

	// Include the main class.
	if ( ! class_exists( 'UniCpoScrtopts' ) ) {
		include_once dirname( __FILE__ ) . '/class-uni-cpo-scrtopts.php';
	}
	/**
	 * Main instance of Uni_Cpo_Scrtopts.
	 *
	 * Returns the main instance of Uni_Cpo_Scrtopts to prevent the need to use globals.
	 *
	 * @return Uni_Cpo_Scrtopts
	 * @since  1.0.0
	 */
	function UniCpoScrtopts() {
		return Uni_Cpo_Scrtopts::instance();
	}

    add_action( 'before_woocommerce_init', function() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    } );

	// Global for backwards compatibility.
	$GLOBALS['unicposcrtopts'] = UniCpoScrtopts();
}
