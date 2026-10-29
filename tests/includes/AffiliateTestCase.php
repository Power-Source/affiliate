<?php

abstract class AffiliateTestCase extends WP_UnitTestCase {

	protected $affiliate_user_id;

	public function set_up() {
		parent::set_up();

		global $affiliate, $wpdb;

		$this->affiliate_user_id = self::factory()->user->create();
		$_SERVER['HTTP_HOST'] = 'affiliate.test';
		$_SERVER['REQUEST_URI'] = '/checkout';
		$_SERVER['HTTP_REFERER'] = 'https://affiliate.test/source';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$wpdb->query( "TRUNCATE TABLE {$affiliate->affiliatedata}" );
		$wpdb->query( "TRUNCATE TABLE {$affiliate->affiliatereferrers}" );
		$wpdb->query( "TRUNCATE TABLE {$affiliate->affiliaterecords}" );
	}

	protected function get_totals() {
		global $affiliate, $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$affiliate->affiliatedata} WHERE user_id = %d AND period = %s",
				$this->affiliate_user_id,
				gmdate( 'Ym' )
			)
		);
	}

	protected function get_records( $area = null ) {
		global $affiliate, $wpdb;

		$sql = $wpdb->prepare(
			"SELECT * FROM {$affiliate->affiliaterecords} WHERE user_id = %d",
			$this->affiliate_user_id
		);
		if ( null !== $area ) {
			$sql .= $wpdb->prepare( ' AND affiliatearea = %s', $area );
		}

		return $wpdb->get_results( $sql );
	}
}
