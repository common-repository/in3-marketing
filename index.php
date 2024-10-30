<?php
/**
 * Plugin Name: iDEAL in3 On-site Messaging plug-in for WooCommerce
 * Plugin URI: https://www.payin3.nl/
 * Description: Let your customers immediately see the purchase price &quot;divided by three&quot; on the product page, shopping cart or check-out.
 * Version: 1.1.0
 * Author: Tallest
 * Author URI: https://www.tallest.nl/
 * Domain Path: /languages/
 * Text Domain: in3
 * Requires PHP: 7.4
 * WC requires at least: 5.9
 * WC tested up to: 8.9
 * License: GPL v2
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

if ( ! function_exists( 'In3' ) ) {

	// Include the main class.
	if ( ! class_exists( 'In_3' ) ) {
		include_once dirname( __FILE__ ) . '/class-in3.php';
	}
	/**
	 * Main instance of In_3.
	 *
	 * Returns the main instance of In_3 to prevent the need to use globals.
	 *
	 * @return In_3
	 * @since  1.0.0
	 */
	function In3() {
		$inst = In_3::instance();
		register_activation_hook( __FILE__, array( $inst, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $inst, 'deactivation' ) );

		return $inst;
	}

	In3();
}
