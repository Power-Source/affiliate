<?php

/*
Plugin Name: Partnerprogramm Debugging
Description: Fügt dem Affiliate-Plugin ein Debug-System hinzu, um potenzielle Cookie-Probleme aufzuspüren
Author: DerN3rd (PSOURCE)
Author URI: https://n3rds.work
*/

class affiliate_debugger {

	var $db;

	function __construct() {

		global $wpdb;

		$this->db =& $wpdb;

		// Add the debugging styles
		add_action( 'wp_head', array( &$this, 'add_affiliate_styles' ), 99 );

		// Add the debugging message
		add_action( 'wp_footer', array( &$this, 'add_affiliate_notice' ), 99 );
	}

	function affiliate_debugger() {
		$this->__construct();
	}

	function add_affiliate_styles() {
		?>
		<style type="text/css">
			#debugaffiliatefooter {
				position: fixed;
				width: 100%;
				min-height: 35px;
				bottom: 0px;
				left: 0px;
				background: #ffa82f;
			}

			#debugaffiliatefooter p {
				padding-left: 20px;
				padding-right: 20px;
				margin-top: 10px;
				margin-bottom: 5px;
			}
		</style>
		<?php
	}

	function add_affiliate_notice() {
		?>
		<div id='debugaffiliatefooter'>
			<p>
				<strong><?php _e( 'Partnerprogramm Debug : ', 'affiliate' ); ?></strong>
				<?php
				echo $this->debug_message();
				?>
			</p>
		</div>
		<?php
	}

	function debug_message() {


		if ( isset( $_COOKIE[ 'affiliate_' . COOKIEHASH ] ) ) {

			$hash    = addslashes( $_COOKIE[ 'affiliate_' . COOKIEHASH ] );
			$user_id = $this->db->get_var( $this->db->prepare( "SELECT user_id FROM {$this->db->usermeta} WHERE meta_key = 'affiliate_hash' AND meta_value = %s", $hash ) );

			$aff_user_login = "UNKNOWN";
			if ( ! empty( $user_id ) ) {
				$user = new WP_User( $user_id );
				if ( ( $user ) && ( ! empty( $user->user_login ) ) ) {
					$aff_user_login = $user->user_login;
				}
			}

			return sprintf( __( 'Ich habe einen Cookie für einen Partner aufgezeichnet: <strong>%s</strong>. Alle <strong>bezahlten</strong> Einkäufe/Anmeldungen werden dem Partner <strong>%d</strong> Tage nach dem Klick zugewiesen.', 'affiliate' ), $aff_user_login, AFFILIATE_COOKIE_DAYS );
		}

		if ( isset( $_COOKIE[ 'noaffiliate_' . COOKIEHASH ] ) ) {
			return __( 'Das Cookie <strong>Nicht über ein Partnerprogramm</strong> wurde gesetzt. Dies bedeutet, dass Du über einen gültigen, bei WordPress angemeldeten Benutzer oder einen Gast auf die Seite zugreifst. Ich ignoriere zukünftige Affiliate-Klicks für diese Browsersitzung.', 'affiliate' );
		}

		return __( 'Derzeit sind keine Cookies gesetzt - ich suche Cookies unter: ', 'affiliate' ) . COOKIE_DOMAIN . COOKIEPATH;

	}

}

$affiliate_debugger = new affiliate_debugger();

?>