<?php
/**
 * WooCommerce POS Shipping
 *
 * Main file for the calculation and settings shipping
 *
 * @author AgenWebsite
 * @package WooCommerce POS Shipping
 * @since 4.0.0
 */

if ( !defined( 'WOOCOMMERCE_POS' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_POS_Shipping' ) ) :

/**
 * Class WooCommerce POS
 *
 * @since 4.0.0
 **/
class WC_POS_Shipping{
    
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 4.0.0
	 **/
    public function __construct(){
		/**
		 * Initialise POS shipping method.
		 **/
		add_action( 'woocommerce_shipping_init', array( &$this, 'shipping_method' ) );
        
		/**
		 * Add Shipping Method
		 *
		 * Tell method POS shipping to woocommerce. Hey Woo AgenWebsite POS is Here !! :D
		 *
		 * @since 4.0.0
		 **/
		add_filter( 'woocommerce_shipping_methods', array( &$this, 'add_pos_shipping_method' ) );
        
    }
    
	/**
	 * Init Shipping method
	 *
	 * @access public
	 * @since 4.0.0
	 **/
    public function shipping_method(){
        include_once( 'shipping-method.php' );
    }
    
	/**
	 * Add POS shipping method
	 *
	 * @access public
	 * @return mixed
	 * @since 4.0.0
	 **/
    public function add_pos_shipping_method( $methods ){
        $methods[] = 'WC_POS';
        return $methods;
    }
    
	/**
	 * Check plugin is active
	 *
	 * @access public
	 * @return bool
	 * @since 4.0.0
	 **/
	public function is_enable(){
		$settings = $this->get_settings();
		return ( ! empty( $settings['enabled'] ) && $settings['enabled'] == 'yes' ) ? TRUE : FALSE;
	}
    
    /*
     * Get settings pos shipping
     *
     * @access public
     * @return mixed
     * @since 4.0.0
     */
    public function get_settings(){
        return get_option( 'woocommerce_pos_shipping_settings' );
    }
	    
	/**
	 * Return the number of decimals after the decimal point.
	 *
	 * @access public
	 * @return int
	 * @since 4.0.0
	 **/
	public function get_price_decimals(){
		if( function_exists( 'wc_get_price_decimals' ) )
            return wc_get_price_decimals();
        else
            return absint( get_option( 'woocommerce_price_num_decimals', 2 ) );
    }
    
	/**
	 * Shipping service option default
	 *
	 * @access public
	 * @return array
	 * @since 4.0.0
	 **/
    public function default_service(){
		return array(
			array(
				'id'			=> 'kilat',
				'enable'		=> 1,
				'name'			=> 'Kilat',
				'extra_cost'	=> 0
			),
			array(
				'id'			=> 'express',
				'enable'		=> 0,
				'name'			=> 'Express',
				'extra_cost'	=> 0
			)
		);
    }
    
	/**
	 * Shipping form fields settings
	 *
	 * @access public
	 * @return array
	 * @since 4.0.0
	 **/
    public function form_fields(){
        $form_fields = array(
            'license_code'  => array(
                'type'          => 'license_code',
                'default'       => '',
            )
        );
        
        return apply_filters( 'woocommerce_jne_form_fields_settings', $form_fields );        
    }

    /**
	 * Shipping form fields settings
	 *
	 * @access public
	 * @return array
	 * @since 4.0.1
	 **/
    public function get_form_fields(){
		return array(
            'general' => array(
                'label' => __( 'General', 'agenwebsite' ),
                'fields' => array(
                    'enabled' => array(
                        'title'         => __( 'Aktifkan POS Shipping', 'agenwebsite' ), 
                        'type'          => 'checkbox', 
                        'label'         => __( 'Aktifkan WooCommerce POS Shipping', 'agenwebsite' ), 
                        'default'       => 'yes',
                    ), 
                    'title' => array(
                        'title'         => __( 'Label', 'agenwebsite' ), 
                        'description' 	=> __( 'Ubah label untuk fitur pengiriman kamu.', 'agenwebsite' ),
                        'type'          => 'text',
                        'default'       => __( 'POS Shipping', 'agenwebsite' ),
                    ),
                    'default_weight' => array(
                        'title'         => __( 'Berat default ( kg )', 'agenwebsite' ), 
                        'description' 	=> __( 'Otomatis setting berat produk jika kamu tidak setting pada masing-masing produk.', 'agenwebsite' ),
                        'type'          => 'number',
                        'custom_attributes' => array(
                            'step'	=>	'any',
                            'min'	=> '0'
                        ),
                        'placeholder'	=> '0.00',
                        'default'		=> '1',
                    ),
                    'license_code' => array(
                        'type'          => 'license_code',
                        'default'       => ''
                    ),
                    'api_location' => array(
                        'title'         => __( 'Lokasi API <span>Full Version</span>', 'agenwebsite' ),
                        'description'   => __( 'Pilih lokasi API AgenWebsite untuk kecepatan akses.' ),
                        'type'          => 'api_location',
                        'disabled'      => true,
                        'class_parent'  => 'premium-version',
                        'options'       => array(
                            'international' => 'International',
                        )
                    ),
                    'pos_service' => array(
                        'type'          => 'pos_service',
                        'default'       => ''
                    )
                )
            )
		);
    }
    
}

endif;