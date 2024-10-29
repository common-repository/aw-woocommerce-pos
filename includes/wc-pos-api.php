<?php
/**
 * Main API Handles
 *
 * Handles for API AgenWebsite
 *
 * @author AgenWebsite
 * @package WooCommerce POS Shipping
 * @since 4.0.2
 */

if ( !defined( 'WOOCOMMERCE_POS' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'WC_POS_API' ) ):

class WC_POS_API{
	
	/* @var array */
	private $data;    
    
    /**
     * Constructor
     *
     * @return void
     * @since 4.0.2
     */
    public function __construct( $product, $product_version, $license_code, $api_location ){
        $this->product = $product;
        $this->product_version = $product_version;
        $this->license_code = $license_code;
        $this->api_location = $api_location;
        $this->url = 'http://api.agenwebsite.com/v1';

        //Detect Browser
        global $browser;
        $this->browser = array( 
            'name' => $browser->getBrowser(),
            'version' => $browser->getVersion(),
            'platform' => $browser->getPlatform()
        );

    }
    
    /**
     * Magic Setter
     *
     * @return void
     * @since 4.0.2
     */
    public function __set( $name, $value ){
        $this->data[$name] = $value;
    }
    
    /**
     * Magic Getter
     *
     * @return mixed
     * @since 4.0.2
     */
    public function __get( $name ){
        if( array_key_exists($name, $this->data) ){
            return $this->data[$name];
        }
    }
        
    /**
     * Get URL of API AgenWebsite
     *
     * @access public
     * @param string $method 'license_auth'
     * @param array $param
     * @return string $uri
     * @since 4.0.2
     */
    public function get_uri( $method, $param = array() ){        
        $uri = $this->url;
        
        switch( $method ){
            case 'license':
                $uri .= '/license/';
            break;
            case 'kota':
                $uri .= '/pos/kota/';
            break;
            case 'tarif':
                $uri .= '/pos/tarif/';
            break;
        }
        
        $uri .= $this->build_param( $param );

        return $uri;
    }
    
    /**
     * Build param API from array
     *
     * @access private
     * @param array $param
     * @return string $param
     * @since 4.0.2
     */
    private function build_param( $args = array() ){
        $param = '';
        
        $i = 0;
        foreach( $args as $name => $value ){
            $param .= ( $i == 0 ) ? '?' : '';
            $param .= $name .'='. rawurlencode($value);
            $param .= ( ++$i == count($args) ) ? '' : '&';
        }
        
        return $param;
    }
    
    /**
     * Remote get
     *
     * @access public
     * @return array $result
     * @since 4.0.2
     */
    public function remote_get( $method, $param = array() ){

        if( $this->check_license() ){

            $params = $this->build_param($param);

            $param['license'] = $this->license_code;
            $param['product'] = $this->product;

            $headers['browser'] = sprintf( '%s; %s', $this->browser['name'], $this->browser['version'] );
            $headers['platform'] = $this->browser['platform'];
            $headers['app'] = sprintf( '%ss; %s', $this->product, $this->product_version );
            $headers['param'] = $params;

            $this->response = wp_remote_get( $this->get_uri( $method, $param ), array( 'timeout' => 20, 'headers' => $headers ) );

            $this->process_result();

        }
        
        return $this->result;        
    }
    
    /**
     * Remote put
     *
     * @access public
     * @return array $result
     * @since 4.0.2
     */
    public function remote_put( $method, $param = array() ){

        if( $this->check_license() ){

            $params = $this->build_param($param);

            $body['license'] = $this->license_code;
            $body['product'] = $this->product;

            $headers['browser'] = sprintf( '%s; %s', $this->browser['name'], $this->browser['version'] );
            $headers['platform'] = $this->browser['platform'];
            $headers['app'] = sprintf( '%s; %s', $this->product, $this->product_version );
            $headers['param'] = $params;

            $this->response = wp_remote_post( $this->get_uri( $method, $param ), array( 'method' => 'PUT', 'timeout' => 20, 'body' => $body, 'headers' => $headers ) );

            $this->process_result();

        }
        
        return $this->result;
    }
    
    /**
     * Check license empty or not
     *
     * @access public
     * @return array $result
     * @since 4.0.2
     */
    public function check_license(){
        if( $this->license_code == '' ){
            $result['status'] = 'error';
            $result['message'] = __( 'Kode Lisensi belum diisi.', 'agenwebsite' );
            $result['result'] = '';
            
            $this->result = $result;
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Process result
     *
     * @access public
     * @return void
     * @since 4.0.2
     */
    public function process_result(){

        $cant_connect = false;
        
        if( ! is_wp_error( $this->response ) ){

            $body = json_decode( $this->response['body'], TRUE );

            $result['status'] = $body['status'];

            if( !empty($result['status']) ){
                if( $body['status'] == 'success' ){

                    $result['result'] = $body['data'];

                }else{

                    $result['message'] = $body['message'];
                    $result['result'] = '';

                }

            }else{

                $cant_connect = true;

            }

        }else{
            
            $cant_connect = true;            
            
        }
        
        if($cant_connect){
            $result['status'] = 'error';
            $result['message'] = __( 'Gagal terhubung dengan AgenWebsite', 'agenwebsite' );
            $result['result'] = '';
        }
        
        $this->result = $result;
    }    
    
}
	
endif;