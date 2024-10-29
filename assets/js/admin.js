/* 
name: main admin jquery
version: 1.0.2
package: WooCommerce POS Shipping
*/
jQuery(function($) {
	
	// agenwebsite_pos_admin_params is required to continue, ensure the object exists
	if ( typeof agenwebsite_pos_admin_params === 'undefined' ) {
		return false;
	}
	
	jQuery(document).ready(function($) {
        jQuery('.premium-version').click(function(){
            alert("Pengaturan ini tidak berfungsi untuk free version. Jika Anda membutuhkan fungsi ini, anda harus mengupgrade ke full version.");
            return false;
        });

    });

    /**
     * Load status
     */
    jQuery.post( agenwebsite_pos_admin_params.ajax_url, { action: 'woocommerce_pos_check_status', license_code: agenwebsite_pos_admin_params.license, _wpnonce: agenwebsite_pos_admin_params.pos_admin_wpnonce }, function(response){
        jQuery('#wc_pos_status tbody').html(response.message);
    });

});