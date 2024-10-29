<?php
/**
 * Main AJAX Handles
 *
 * Handles for all request file
 *
 * @author AgenWebsite
 * @package WooCommerce POS Shipping
 * @since 4.0.0
 */

if ( !defined( 'WOOCOMMERCE_POS' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'WC_POS_AJAX' ) ):

class WC_POS_AJAX{
	
	/**
	 * Hook in methods
	 */
	public static function init(){
		
		// ajax_event => nopriv
		$ajax_event = array(
            'check_status'                      => false,
		);
		
		foreach( $ajax_event as $ajax_event => $nopriv ){
			add_action( 'wp_ajax_woocommerce_pos_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			
			if( $nopriv ){
				add_action( 'wp_ajax_nopriv_woocommerce_pos_' . $ajax_event, array( __CLASS__, $ajax_event ) );	
			}
		}
			
    }
    
	/**
	 * AJAX Checking status
	 *
	 * @access public
	 * @return json
	 * @since 4.0.0
	 **/
    public static function check_status(){
        
        check_ajax_referer( 'woocommerce_pos_admin' );
        
        $license = ( ! empty($_POST['license_code']) ) ? $_POST['license_code'] : WC_POS()->get_license_code();

        WC_POS()->api->license = $license;
        $result = WC_POS()->api->remote_get( 'license' );

        $message = ( $result['status'] == 'success' ) ? $result['result']['message'] : $result['message'];

        ob_start();
        woocommerce_get_template( 'html-aw-product-status.php', array(
            'status' => $result['status'],
            'message' => $message,
            'data' => $result['result'],
        ), WC_POS()->product_version, untrailingslashit( WC_POS()->plugin_path() ) . '/views/' );
        $output['message'] = ob_get_clean();        
        
        wp_send_json( $output );
        
        wp_die();
        
    }
	
}
	
WC_POS_AJAX::init();
	
endif;
