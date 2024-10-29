<?php
/**
 *
 * @link 			http://agenwebsite.com
 * @since 			4.0.0
 * @package 		AW WooCommerce POS Shipping
 *
 * @wordpress-plugin
 * Plugin Name: 	AW WooCommerce POS Shipping ( Free Version )
 * Plugin URI:		http://www.agenwebsite.com/products/woocommerce-pos-shipping
 * Description:		Plugin untuk WooCommerce dengan penambahan metode shipping POS. Penambahan POS Tracking yang terintegrasi dengan WooCommerce.
 * Version:			4.0.3
 * Author:			AgenWebsite
 * Author URI:		http://agenwebsite.com
 * License:			GPL-2.0+
 * License URI:		http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if( ! class_exists( 'WooCommerce_POS' )):

/**
 * Initiliase Class
 *
 * @since 4.0.0
 **/
class WooCommerce_POS{
    
	/**
	 * @var string
	 * @since 4.0.0
	 */
	public $version = '4.0.3';

	/**
	 * @var string
	 * @since 4.0.0
	 */
	public $db_version = '4.0.0';    

	/**
	 * @var string
	 */
	public $product_version = 'woocommerce-pos-free';

	/**
	 * @var woocommerce pos main class
	 * @since 4.0.0
	 */
	protected static $_instance = null;
    
	/**
	 * @var WC_POS_SHIPPING $shipping
	 * @since 4.0.0
	 */
	public $shipping = null;

	/**
	 * @var WC_POS_API $api
	 * @since 4.0.1
	 */
	public $api = null;	

	/**
	 * Various Links
	 * @var string
	 * @since 4.0.0
	 */
	public $url_dokumen = 'http://docs.agenwebsite.com/products/woocommerce-pos-shipping/';
	public $url_support = 'http://www.agenwebsite.com/support';
    
	/**
	 * WooCommerce POS Instance
	 *
	 * @access public
	 * @return Main Instance
	 * @since 4.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    
    /**
     * Constructur
     * 
     * @access public
     * @return void
     * @since 4.0.0
     */
    public function __construct(){
        self::define_constants();
        self::modules();
        self::init_hooks();
    }
    
	/**
	 * Define the Constant
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */	
    private function define_constants(){
		define( 'WOOCOMMERCE_POS', TRUE );
		define( 'WOOCOMMERCE_POS_VERSION', $this->version );
    }
    
	/**
	 * Inititialise Modules
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */	
    private function modules(){
        $this->shipping = WooCommerce_POS::shipping();
        WooCommerce_POS::ajax_includes();
    }
    
	/**
	 * Shipping modules
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */	
    private static function shipping(){
        WooCommerce_POS::requires_file( 'shipping' );
        
        return new WC_POS_Shipping();
    }

	/**
	 * Include AJAX file
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */	
	private function ajax_includes(){
		require_once( 'includes/wc-pos-ajax.php' );	
        require_once( 'includes/wc-pos-api.php' );
        require_once( 'includes/vendor/browser.php' );

        $this->api = new WC_POS_API( $this->product_version, $this->version, $this->get_license_code(), $this->get_api_location() );
    }
    
	/**
	 * Requires File
     * Its will be load require file by modules
	 *
	 * @access private
     * @param $modules string of module.
	 * @return void
	 * @since 4.0.0
	 */	
    private static function requires_file( $modules ){
        switch( $modules ):
            case 'shipping':
                require_once( 'includes/shipping/shipping.php' );
            break;
        endswitch;
    }
    
	/**
	 * Hooks action and filter
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 */	
    private function init_hooks(){
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'add_settings_link' ) );
        add_action( 'wp_enqueue_scripts', array( &$this, 'register_and_enqueue_script' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_register_and_enqueue_script' ) );
        add_action( 'admin_notices', array( &$this, 'notice_set_license' ) );
    }
    
	/**
	 * Add setting link to plugin list table
	 *
	 * @access public
	 * @param  array $links Existing links
	 * @return array		Modified links
	 * @since 4.0.0
	 */
    public function add_settings_link( $links ){
       	$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_pos' ) . '">' . __( 'Settings', 'agenwebsite' ) . '</a>',
			'<a href="' . $this->url_dokumen . '" target="new">' . __( 'Docs', 'agenwebsite' ) . '</a>',
           );
		
		return array_merge( $plugin_links, $links );
    }
    
	/**
	 * Notice to set license
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.1
	 */	
    public function notice_set_license(){
        if( $this->is_page_to_notice() && ! $this->get_license_code() ){
            printf('<div class="updated notice_wc_pos woocommerce-pos"><p><b>%s</b> &#8211; %s</p><p class="submit">%s %s</p></div>',
                   __( 'Kode lisensi tidak ada. Masukkan kode lisensi untuk mengaktifkan WooCommerce POS', 'agenwebsite' ),
                   __( 'anda bisa mendapatkan kode lisensi dari halaman akun AgenWebsite.', 'agenwebsite'  ),
                   '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_pos' ) . '" class="button-primary">' . __( 'Masukkan kode lisensi', 'agenwebsite' ) . '</a>',
                   '<a href="' . esc_url( $this->url_dokumen ) . '" class="button-primary" target="new">' . __( 'Baca dokumentasi', 'agenwebsite' ) . '</a>' );
        }
    }
    
	/**
	 * Check page to notice
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 */	
    public function is_page_to_notice(){
        global $pagenow;
        $user = wp_get_current_user();
        $screen = get_current_screen();
        if( $pagenow == 'plugins.php' || $screen->id == "woocommerce_page_wc-settings" ){
            if( isset( $_GET['section'] ) && $_GET['section'] === 'wc_pos' ) return false;
            
            return true;
        }
        
        return false;
    }
    
	/**
	 * Register and Enqueue Script FrontEnd
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 */
    public function register_and_enqueue_script(){
        
    }
    
	/**
	 * Register and Enqueue Script Admin
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 */
    public function admin_register_and_enqueue_script(){
		global $pagenow;

		$suffix	= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        
		if( $pagenow == 'admin.php' && ( isset( $_GET['page'] ) && $_GET['page'] == 'wc-settings' ) && ( isset( $_GET['tab'] ) && $_GET['tab'] == 'shipping' ) && ( isset( $_GET['section'] ) && $_GET['section'] == 'wc_pos' ) ) {

            // Load for admin common JS & CSS
			wp_enqueue_script( 'woocommerce-pos-js-admin', WC_POS()->plugin_url() . '/assets/js/admin' . $suffix . '.js', array( 'jquery' ), '1.0.2', true );
			wp_enqueue_style( 'woocommerce-pos-admin', WC_POS()->plugin_url() . '/assets/css/admin.css' );
			
            // Load localize admin params
			wp_localize_script( 'woocommerce-pos-js-admin', 'agenwebsite_pos_admin_params', $this->localize_script( 'admin' ) );
			
		 }        
    }
    
	/**
	 * Localize Scripts
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 */
    public function localize_script( $handle ){
        switch( $handle ):
        
            case 'admin':
                return array(
                    'license'                   => ( $_POST && isset( $_POST['woocommerce_pos_shipping_license_code'] ) ) ? $_POST['woocommerce_pos_shipping_license_code'] : '',
					'ajax_url'					=> self::ajax_url(),
					'pos_admin_wpnonce'			=> wp_create_nonce( 'woocommerce_pos_admin' )
                );
            break;
        
        endswitch;
    }

    /**
     * Get License code for access
     *
     * @access public
     * @return string of license code
     * @since 4.0.0
     */
    public function get_license_code(){
        return get_option( 'woocommerce_pos_shipping_license_code' );
    }
    
	 /**
	  * Get api location
	  *
	  * @access public
	  * @return string
	  * @since 8.1.13
	  **/
	 public function get_api_location(){
         $options = get_option( 'woocommerce_pos_shipping_settings' );
         if( is_array( $options ) && $options !== '' ){
             if( array_key_exists( 'api_location', $options ) ){
                 return $options['api_location'];
             }
         }

         return '';
	 }
    
    /**
     * Check valid license
     *
     * @access public
     * @return bool
     * @since 4.0.0
     */
    public function check_valid_license(){

        if( $this->get_license_code() == '' )
            return FALSE;
        
        $check_status = $this->api->remote_get( 'license' );

        if( is_array( $check_status ) ){

            if( $check_status['status'] == 'success' )
                return TRUE;
            else
                return FALSE;
        }
    }
    
	/**
	 * Convert Date
	 * convert date to another format
     *
	 * @access public
     * @param string $date
     * @param string $format
	 * @return string
	 * @since 4.0.0
	 */
    public function convert_date( $date, $format ){
        return date( $format, strtotime( $date ) );
    }

	/**
	 * Get status weight
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 */
	public function get_status_weight(){
		$weight_unit = $this->get_woocommerce_weight_unit();
		$status = '';
		$status['unit']	= $weight_unit;
		if( $weight_unit == 'g' || $weight_unit == 'kg' ){
			$status['message'] = 'yes';
		}else{
			$status['message'] = 'error';
		}
		
		return $status;
	}

	/**
	 * WooCommerce weight unit
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 **/
    public function get_woocommerce_weight_unit(){
        return get_option('woocommerce_weight_unit');
    }
    
	/**
	 * AJAX URL
	 *
	 * @access private
	 * @return string URL
	 * @since 4.0.0
	 **/
	private static function ajax_url(){
		return admin_url( 'admin-ajax.php' );
	}

    /**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 */
    public function plugin_url(){
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }
    
	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 * @since 4.0.0
	 */
	public function plugin_path(){
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

	/**
	 * Render help tip
	 *
	 * @access public
	 * @return HTML for the help tip image
	 * @since 4.0.0
	 **/
	public function help_tip( $tip, $float = 'none' ){
		return '<img class="help_tip" data-tip="' . $tip . '" src="' . $this->plugin_url() . '/assets/images/help.png" height="16" width="16" style="float:' . $float . ';" />';
	}

	/**
	 * Render link tip
	 *
	 * @access public
	 * @return HTML for the help tip link
	 * @since 4.0.0
	 **/
	public function link_tip( $tip, $text, $href, $target = NULL, $style = NULL ){
		return '<a href="' . $href . '" data-tip="' . $tip . '" target="' . $target . '" class="help_tip">' . $text . '</a>';
	}    
    
}

endif;

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {	
	
	/**
	 * Returns the main instance
	 *
	 * @since 4.0.0
	 * @return WooCommerce_POS
	 */
	function WC_POS(){
		return WooCommerce_POS::instance();
	}

	// Let's fucking rock n roll! Yeah!
	WooCommerce_POS::instance();
	
};
