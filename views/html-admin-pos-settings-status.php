<?php
/**
 * Admin View: Section - Status Report
 */

if ( ! defined( 'WOOCOMMERCE_POS' ) ) {
	exit;
}

// Link dokumentasi
$dok = esc_url( WC_POS()->url_dokumen );
$dok_table =                $dok . '#9';
$dok_weight_unit =          $dok . '#11';
$dok_currency_symbol =      $dok . '#10';
$dok_currency_decimals =    $dok . '#10';
?>

<table class="woocommerce_pos_status_table widefat is-large-screen" id="wc_pos_status" cellspacing="0">
	<thead>
    	<tr>
        	<th colspan="3"><?php echo __( 'AgenWebsite Product Status', 'agenwebsite' );?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="3" class="load_status"><center><img src="<?php echo WC_POS()->plugin_url();?>/assets/images/progress.gif" /> <span>Connecting to AgenWebsite ...</span></center></td>
        </tr>
   	</tbody>
</table>
<br><br>
<table class="woocommerce_pos_status_table widefat is-large-screen" cellspacing="0">
    <thead>
    	<tr>
        	<th colspan="3"><?php echo __( 'WooCommerce POS Settings Status', 'agenwebsite' ); printf( '<a href="%s" target="new">%s</a>', $dok_table, WC_POS()->help_tip( 'Klik untuk melihat penjelasan tentang table ini' ) )?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
        	<td><?php echo __( 'Plugin POS Version', 'agenwebsite' );?></td>
            <td><span id="aw_status_version"><mark class="yes"><?php echo WOOCOMMERCE_POS_VERSION;?></mark></span></td>
            <td><span id="aw_status_version_help" style="display:none"><?php echo WC_POS()->link_tip( 'Klik untuk download update terbaru di my account page', 'Download', '', 'new' );?></span></td>
        </tr>
        <tr>
        	<td>
				<?php echo __( 'WC Weight Unit', 'agenwebsite' );?>
                <?php echo WC_POS()->help_tip( 'Plugin ini akan berfungsi maksimal dengan kg dan g di pengaturan WooCommerce Weight Unit', 'right' );?>
            </td>
            <td>
				<?php
				$weight_status = WC_POS()->get_status_weight();
				echo ( $weight_status['message'] != 'error' ) ? '<mark class="yes">' . $weight_status['unit'] . '</mark>' : '<mark class="no">' . $weight_status['unit'] . '</mark>';
				?>
            </td>
            <td>
            	<?php 
				if( $weight_status['message'] == 'error'):
                	echo WC_POS()->link_tip( 'Klik untuk melihat cara ganti weight unit', 'Bantuan', $dok_weight_unit, 'new' );
                endif;
                ?>
            </td>
        </tr>
        <tr>
        	<td>
				<?php echo __( 'WC Currency Symbol', 'agenwebsite' );?>
                <?php echo WC_POS()->help_tip( 'Tarif pos menggunakan mata uang rupiah, pilih Rp. di pengaturan WooCommerce', 'right' );?>
            </td>
            <td><?php echo ( get_woocommerce_currency_symbol() == 'Rp' ) ? '<mark class="yes">' . get_woocommerce_currency_symbol() . '</mark>' : '<mark class="no">' . get_woocommerce_currency_symbol() . '</mark>';?></td>
            <td>
            <?php if( get_woocommerce_currency_symbol() != 'Rp' ) :?>
                <?php echo WC_POS()->link_tip( 'Saran: Gunakan Rp. Klik untuk melihat cara ganti currency symbol', 'Bantuan', $dok_currency_symbol, 'new' );?>
            <?php endif;?>
            </td>
        </tr>
        <tr>
        	<td>
				<?php echo __( 'WC Currency Decimals', 'agenwebsite' );?>
                <?php echo WC_POS()->help_tip( 'Pengaturan WooCommmerce untuk jumlah angka nol dibelakang koma. saran: maksimal 2', 'right' );?>                
            </td>
            <td><?php echo ( WC_POS()->shipping->get_price_decimals() > 2 ) ? '<mark class="no">' . WC_POS()->shipping->get_price_decimals() . '</mark>' : '<mark class="yes">' . WC_POS()->shipping->get_price_decimals() . '</mark>'?></td>
            <td>
            	<?php if( WC_POS()->shipping->get_price_decimals() > 2 ):?>
                    <?php echo WC_POS()->link_tip( 'Saran: maksimal sampai 2. Klik untuk melihat cara ganti currency decimals', 'Bantuan', $dok_currency_decimals, 'new' );?>
				<?php endif;?>
            </td>
        </tr>    
    </tbody>
</table>
