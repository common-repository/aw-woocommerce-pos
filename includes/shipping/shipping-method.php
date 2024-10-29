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

if ( !class_exists( 'WC_POS' ) ) :

class WC_POS extends WC_Shipping_Method{
 
	/**
	 * @var string
	 * @since 4.0.0
	 */
	private $notices = array();

    /**
	 * Option name for save the settings
	 *
	 * @access private
	 * @var string
	 * @since 4.0.0
	 **/
	private $option_layanan;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 **/    
    public function __construct(){
        $this->id                   = 'pos_shipping';
        $this->method_title         = __('POS Shipping', 'agenwebsite');
        $this->method_description   = __( 'Plugin POS Shipping mengintegrasikan ongkos kirim dengan total belanja pelanggan Anda.', 'agenwebsite' );

		$this->option_layanan         = $this->plugin_id . $this->id . '_layanan';
        $this->option_license_code    = $this->plugin_id . $this->id . '_license_code';
        $this->option_data_kota       = $this->plugin_id . $this->id . '_nama_datakota';
        $this->option_expire_date     = $this->plugin_id . $this->id . '_expire_date';
        $this->option_account_email   = $this->plugin_id . $this->id . '_account_email';

        add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( &$this, 'sanitize_fields' ) );
        add_filter( 'woocommerce_settings_api_form_fields_' . $this->id, array( &$this, 'set_form_fields' ) );
		
		$this->init();
    }
    

	/**
	 * Init POS settings
	 *
	 * @access public
	 * @return void
	 * @since 4.0.0
	 **/
    public function init(){
		// Load the settings API
		// Override the method to add POS Shipping settings
		$this->form_fields = WC_POS()->shipping->form_fields();
		// Loads settings you previously init.
		$this->init_settings();
		
		// Load default services options
		$this->load_default_services();
		
		// Define user set variables
		$this->enabled                    = ( array_key_exists( 'enabled', $this->settings ) ) ? $this->settings['enabled'] : '';
		$this->title                      = ( array_key_exists( 'title', $this->settings ) ) ? $this->settings['title'] : '';
		$this->default_weight             = ( array_key_exists( 'default_weight', $this->settings ) ) ? $this->settings['default_weight'] : '';

		// Save settings in admin
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_options' ) );
        
    }
    
	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 * @since 4.0.0
	 **/
    public function calculate_shipping( $package = array() ){

        if( ! WC_POS()->check_valid_license() ) return FALSE;

        $layanan_pos = get_option( $this->option_layanan );
        
        $country = WC()->customer->get_country();
        $postcode = WC()->customer->get_postcode();
        
        if( $country != 'ID' ) return false;
        
        $total_weight = $this->calculate_weight( $package['contents'] );
        $weight_id = $this->get_weight_id( $total_weight );

        $args = array(
            'kodepos' => $postcode,
            'berat' => $total_weight
        );
        
        $cost = $this->getTarif( $args );

        $htnb = $this->calculate_htnb( $package['contents'] );
        
        if( $weight_id == 'harga' ){
            $total_weight = $total_weight / 1000;
        }
        
        $totalamount = floatval( preg_replace( '#[^\d]#', '', WC()->cart->get_cart_total() ) );
        
        if( empty( $cost ) && $cost == 0 ) return false;
                
        if( sizeof( $package ) == 0 ) return false;
        
        foreach( $layanan_pos as $layanan ){
            $service_id = $layanan['id'];
            $service_name = $layanan['name'];
            $service_enable = $layanan['enable'];
            $service_extra_cost = $layanan['extra_cost'];
            
            $etd = sprintf( '( ' . __( '%s hari', 'agenwebsite' ) . ' )', $cost[ $service_id . '_etd' ] );
            $label = $this->title . ' ' . $service_name;
            $tarif = $cost[ $service_id ];
            
            if( ! empty($tarif) && $tarif != 0 && $service_enable == 1) {
                
                if( ! empty( $service_extra_cost ) ) $tarif += $service_extra_cost;
            	
                // Add htnb
                $tarif = $tarif + $htnb;

                $args = array(
	             	'service_id' => $service_id,
	             	'totalamount' => $totalamount,
	             	'postcode' => $postcode 
                );
                
                $label = sprintf( '%s %s', $label, $etd );

                $rate = array(
                    'id'    => $this->id . '_' . $service_id,
                    'label' => $label,
                    'cost'  => $tarif
                );
                
                $this->add_rate( $rate );
                
            }
        }
    }
    
    /**
     * Get Tarif
     * Create params from array
     *
     * @access private
     * @param mixed $args
     * @return mixed $body
     */
    private function getTarif( $args ){

        $tarif = 0;
        
        if( !empty($args['kodepos']) ){

            // get data from API
            $response = WC_POS()->api->remote_get( 'tarif', $args );

            if( is_array($response) ) {
                if( $response['status'] == 'success' ){
                    $tarif = $response['result']['tarif'];
                }
            }
        }

        return $tarif;
    }
    
	/**
	 * Calculate HTNB / Asuransi POS
	 *
	 * @access private
	 * @param mixed $products
	 * @return integer
     * @since 4.0.1
	 */
	private function calculate_htnb( $products ){
   	foreach( $products as $item_id => $item ){
   		$product_price = $product_price + $item['line_total'];
		}
		$htnb = ( $product_price * 0.0024 ) + ( ( $product_price * 0.0024 ) * 0.10 );
		
		return $htnb;
	}
    
	/**
	 * Calculate Total Weight
	 * This function will calculated total weight for all product
	 *
	 * @access private
	 * @param mixed $products
	 * @return integer Total Weight in gram
	 * @since 4.0.0
	 **/    
    private function calculate_weight( $products ){
        $weight = 0;
        $weight_unit = WC_POS()->get_woocommerce_weight_unit();
        $default_weight = $this->settings['default_weight'];
        
        foreach( $products as $item_id => $item ){
            
            $product = $item['data'];
            $product_weight = $product->get_weight() ? $product->get_weight() : $default_weight;
            
            $product_weight = $product_weight * $item['quantity'];
            
            if( $weight_unit == 'kg' )
                $product_weight = $product_weight * 1000;
            
            $weight += $product_weight;
        }
        
        return $weight;
    }
    
	/**
	 * Get weight ID
	 *
	 * @access private
	 * @param integer $weight
	 * @return string
	 * @since 4.0.0
	 **/    
    private function get_weight_id( $weight ){
        if( $weight >= 0 && $weight <= 100 ){
            $weight_id = 'harga-100';
        }elseif( $weight > 100 && $weight <= 250 ){
            $weight_id = 'harga-250';
        }elseif( $weight > 250 && $weight <= 500 ){
            $weight_id = 'harga-500';
        }elseif( $weight > 500 && $weight <= 1000 ){
            $weight_id = 'harga-1000';
        }elseif( $weight > 1000 && $weight <= 2000 ){
            $weight_id = 'harga-2000';
        }elseif( $weight > 2000 && $weight <= 3001 ){
            $weight_id = 'harga-3001';
        }else{
            $weight_id = 'harga';
        }
        
        return $weight_id;
    }

	/**
	 * Load default POS services
	 *
	 * @access private
	 * @return void
	 * @since 4.0.0
	 **/
	private function load_default_services(){
		
		$servives_options = get_option( $this->option_layanan );
		if( empty ( $servives_options ) ) {
		
			$data_to_save = WC_POS()->shipping->default_service();
			
			update_option( $this->option_layanan, $data_to_save );
		}
	}
    
    /**
     * Sanitize Fields
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 4.0.1
     */
    public function sanitize_fields( $sanitize_fields ){
        /*
         * replace option settings with sanitize fields
         */
        $sanitize_fields = array_replace( $this->settings, $sanitize_fields );
        
        $new_sanitize_fields = $sanitize_fields;
        $options = get_option( $this->plugin_id . $this->id .'_settings' );
        $options_backup = get_option( $this->plugin_id . $this->id . '_settings_backup' );
        
        /*
         * jika license code kosong maka kosongkan post sanitize
         * dan lakukan update option ke settings backup
         * settings backup berfungsi untuk mengembalikan option ke option utama
         * jika license code ada dan option utama kosong maka sanitize field diisi dengan option settings backup
         */
        if( empty( $sanitize_fields['license_code'] ) ){
            $new_sanitize_fields = '';
            if( is_array( $options ) && ! empty( $options ) ){
                update_option( $this->plugin_id . $this->id . '_settings_backup', $options );
            }
        }else{
            if( $options_backup ){
                if( ! $options || empty( $options ) ){
                    $new_sanitize_fields = $options_backup;
                }
            }
        }
        
        return $new_sanitize_fields;
    }
    
    /**
     * Set form fields
     * before show fields, check license code exists or not
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 4.0.1
     */
    public function set_form_fields( $form_fields ){
        
        if( get_option( $this->option_license_code ) ){

            $current_tab = empty( $_GET['tab_pos'] ) ? 'general' : sanitize_title( $_GET['tab_pos'] );
            
            $form_field = WC_POS()->shipping->get_form_fields();
            foreach( $form_field as $name => $data ){
                if( $name == $current_tab ){
                    $form_fields = $data['fields'];
                }
            }
        }
        return $form_fields;
    }
    
	/**
	 * Settings Tab
	 *
	 * @access private 
	 * @return HTML
	 * @since 8.1.10
	 */
    private function settings_tab(){
        
        $tabs = array();
        
        if( get_option( $this->option_license_code ) ){
            
            foreach( WC_POS()->shipping->get_form_fields() as $name => $data ){
                if(!empty($data['fields'])){
                    $tab[$name] = $data['label'];
                    $tabs = array_merge( $tabs, $tab );
                }
            }
            
        }
        
        $current_tab = empty( $_GET['tab_pos'] ) ? 'general' : sanitize_title( $_GET['tab_pos'] );
        
        $tab  = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';
        
        foreach( $tabs as $name => $label ){
            $tab .= '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_pos&tab_pos=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
        }
        
        $tab .= '</h2>';
        
        return $tab;
    }
	
    /**
     * Validate license code
     * check license code to api
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 4.0.1
     */
    public function validate_license_code_field( $key ){
        $text = $this->get_option( $key );
        $field = $this->get_field_key( $key );
        
        if( isset( $_POST[ $field ] ) ){
            $text = wp_kses_post( trim( stripslashes( $_POST[ $field ] ) ) ); 
            $api_location = wp_kses_post( trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_api_location'] ) ) );
            
            $args = array(
                'license_code' => $text,
                'api_location' => $api_location
            );
            $valid_license = $this->validate_license_code( $args );
            
            update_option( $this->option_license_code, $valid_license );
        }
        
        return $valid_license;
    }

    /**
     * Validate license code
     *
     * @access private
     * @param string $code
     * @return array $ouput
     * @since 4.0.0
     */
    private function validate_license_code( $args ){
        $code = $args['license_code'];
        $api_location = $args['api_location'];
        $saved_license = get_option( $this->option_license_code );
        
        if( empty( $code ) || $saved_license == $code ) return $code;
        
        WC_POS()->api->license_code = $code;
        WC_POS()->api->api_location = $api_location;
        
        $response = WC_POS()->api->remote_put( 'license' );

        $this->notices['type'] = $response['status'];
        $this->notices['message'] = ( $response['status'] == 'success' ) ? $response['result']['message'] : $response['message'];
        
        if( $response['status'] == 'error' ){
            $code = '';
        }

        add_action( 'pos_admin_notices', array( &$this, 'notice' ) );

        return $code;

    }
    
    /**
     * Notice
     *
     * @access private
     * @return html
     * @since 4.0.0
     */
    public function notice(){
        $type = ( $this->notices['type'] == 'error' ) ? 'error' : 'updated';
        echo '<div class="' . $type . '"><p><strong>' . $this->notices['message'] . '</strong></p></div>';
    }
    
	/**
	 * Admin Options
	 * Setup the gateway settings screen.
	 *
	 * @access public
	 * @return HTML of the admin pos settings
	 * @since 4.0.0
	 */
    public function admin_options(){
        $html = '<div id="agenwebsite_woocommerce">' . "\n";
        
            // AW head logo and links and table status
            ob_start();
            $this->aw_head();
            $html .= ob_get_clean();

            $html .= sprintf( '<h3>%s %s</h3>', $this->method_title, __( 'Settings', 'agenwebsite' ) ) . "\n";
            $html .= '<p>' . $this->method_description . '</p>' . "\n";

            $html .= '<div class="banner">';
            ob_start();
            $this->generate_banner_html();
            $html .= ob_get_clean();
            $html .= '</div>';

            $html .= $this->settings_tab();

            $html .= '<div id="agenwebsite_notif">'. "\n";
            ob_start();
            do_action( 'pos_admin_notices' );
            $html .= ob_get_clean();
            $html .= '</div>'. "\n";

            $html .= '<table class="form-table hide-data">' . "\n";
            ob_start();
            $this->generate_settings_html();
            $html .= ob_get_clean();
            $html .= '</table>' . "\n";

            $html .= '</div>' . "\n";
        
        echo $html;
    }
    
	/**
	 * AgenWebsite Head
	 *
	 * @access private static
	 * @return HTML for the admin logo branding and usefull links.
	 * @since 4.0.0
	*/
    private function aw_head(){
		$html  = '<div class="agenwebsite_head">';
		$html .= '<div class="logo">' . "\n";
		$html .= '<a href="' . esc_url( 'http://www.agenwebsite.com/' ) . '" target="_blank"><img id="logo" src="' . esc_url( apply_filters( 'aw_logo', WC_POS()->plugin_url() . '/assets/images/logo.png' ) ) . '" /></a>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<ul class="useful-links">' . "\n";
			$html .= '<li class="documentation"><a href="' . esc_url( WC_POS()->url_dokumen ) . '" target="_blank">' . __( 'Dokumentasi', 'agenwebsite' ) . '</a></li>' . "\n";
			$html .= '<li class="support"><a href="' . esc_url( WC_POS()->url_support ) . '" target="_blank">' . __( 'Bantuan', 'agenwebsite' ) . '</a></li>' . "\n";
		$html .= '</ul>' . "\n";
		
        if( WC_POS()->get_license_code() != '' ){
            ob_start();
            include_once( WC_POS()->plugin_path() . '/views/html-admin-pos-settings-status.php' );
            $html .= ob_get_clean();
        }
			
		$html .= '</div>';
		echo $html;        
    }
    
	/**
	 * Generate banner
	 *
	 * @access private static
	 * @return HTML
	 * @since 4.0.2
	*/
    public function generate_banner_html(){
        $img_src = WC_POS()->plugin_url() . '/assets/images/upgrade-728x90.png';
        ?>
        <a href="http://agenwebsite.com/products/woocommerce-pos-shipping" title="Upgrade Plugin" target="_blank"><img src="<?php echo $img_src;?>" width="728px" height="90px" /></a>
        <?php
    }

	/**
	 * Field type license_code
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 **/
    public function generate_license_code_html(){
        $license_code = get_option( $this->option_license_code );
        
        $html = '';
        if( ! $license_code && empty( $license_code ) ){
            $html .= sprintf('<div class="notice_wc_pos woocommerce-pos"><p><b>%s</b> &#8211; %s</p><p class="submit">%s %s</p></div>',
                   __( 'Masukkan kode lisensi untuk mengaktifkan WooCommerce POS', 'agenwebsite' ),
                   __( 'anda bisa mendapatkan kode lisensi dari halaman akun AgenWebsite.', 'agenwebsite'  ),
                   '<a href="http://agenwebsite.com/account/license" target="new" class="button-primary">' . __( 'Dapatkan kode lisensi', 'agenwebsite' ) . '</a>',
                   '<a href="' . esc_url( WC_POS()->url_dokumen ) . '" class="button-primary" target="new">' . __( 'Baca dokumentasi', 'agenwebsite' ) . '</a>' );
        }

        $html .= '<tr valign="top">';
            $html .= '<th scope="row" class="titledesc"';
                $html .= '<label for="' . $this->option_license_code . '">' . __( 'Kode Lisensi', 'agenwebsite' ) . '</label>';
            $html .= '</th>';
            $html .= '<td class="forminp">';
                $html .= '<fieldset>';
                    $html .= '<legend class="screen-reader-text"><span>' . __( 'Kode Lisensi', 'agenwebsite' ) .'</span></legend>';
                    $html .= '<input class="input-text regular-input " type="text" name="' . $this->option_license_code . '" id="' . $this->option_license_code . '" style="" value="' . esc_attr( get_option( $this->option_license_code ) ) . '" placeholder="' . __( 'Kode Lisensi', 'agenwebsite' ) . '">';
                    $html .= '<p class="description">' . __( 'Masukkan kode lisensi yang kamu dapatkan dari halaman akun agenwebsite.', 'agenwebsite' ) . '</p>';
                $html .= '</fieldset>';
            $html .= '</td>';
		  $html .= '</tr>';
        
        return $html;
    }
    
	/**
	 * Field type pos_service
	 *
	 * @access public
	 * @return HTML
	 * @since 4.0.0
	 **/
    public function generate_pos_service_html(){
		$html = '<tr valign="top" class="premium-version">';
			$html .= '<th scope="row" class="titledesc">' . __( 'Layanan POS', 'agenwebsite' ) . '<span> Full Version</span></th>';
			$html .= '<td class="forminp">';
				$html .= '<table class="widefat wc_input_table sortable" cellspacing="0">';
					$html .= '<thead>';
						$html .= '<tr>';
							$html .= '<th class="sort">&nbsp;</th>';
							$html .= '<th>Nama Pengiriman ' . WC_POS()->help_tip( 'Metode pengiriman yang digunakan.' ) . '</th>';
							$html .= '<th>Tambahan Biaya ' . WC_POS()->help_tip( 'Biaya tambahan, bisa disetting untuk tambahan biaya packing dan lain-lain.' ) . '</th>';
							$html .= '<th style="width:14%;text-align:center;">Aktifkan</th>';
						$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';
						
						$i = 0;
						foreach( get_option( $this->option_layanan ) as $service ) :
						
							$html .= '<tr class="service">';
								$html .= '<td class="sort"></td>';
								$html .= '<td><input type="text" value="' . $service['name'] . '" name="service_name[' . $i . '][' . $service['id'] . ']" /></td>';
								$html .= '<td><input type="number" value="' . $service['extra_cost'] . '" name="service_extra_cost[' . $i . '][' . $service['id'] . ']" /></td>';
								$html .= '<td style="text-align:center;"><input type="checkbox" value="1" ' . checked( $service['enable'], 1, FALSE ) . ' name="service_enable[' . $i . '][' . $service['id'] . ']" /><input type="hidden" value="' . $service['id'] . '" name="service_id[' . $i . ']" /></td>';
							$html .= '</tr>';

							$i++;
						endforeach;
						
					$html .= '</tbody>';
				$html .= '</table>';
			$html .= '</td>';
		$html .= '</tr>';
		
		return $html;
    }
    
	/**
	 * Field type button
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 **/
    public function generate_button_html( $key, $data ){
        
        $field = $this->get_field_key( $key );
        $defaults = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array()
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        ob_start();
        ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php esc_attr( $field );?>"><?php echo wp_kses_post( $data['label'] );?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] );?></span></legend>
                        <button type="submit" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $field ); ?>" class="button <?php echo esc_attr( $data['class'] );?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data );?>><?php echo wp_kses_post( $data['placeholder'] );?></button>
                        <?php echo $this->get_description_html( $data ); ?>
                    </fieldset>
                </td>
            </tr>
        <?php
        return ob_get_clean();

    }
		
	/**
	 * Generate Select HTML.
	 *
	 * @param  mixed $key
	 * @param  mixed $data
	 * @since  1.0.0
	 * @return string
	 */
	public function generate_api_location_html( $key, $data ) {

		$field    = $this->get_field_key( $key );
		$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
            'class_parent'      => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array()
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top" class="<?php echo esc_attr( $data['class_parent']);?>">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<select class="select <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>>
						<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, esc_attr( $this->get_option( $key ) ) ); ?>><?php echo esc_attr( $option_value ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

}

endif;
