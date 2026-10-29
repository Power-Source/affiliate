<?php

class SecurityRegressionTest extends AffiliateTestCase {

	public function test_period_and_decimal_sanitizers_reject_unsafe_input() {
		$this->assertSame( '202610', affiliate_sanitize_period( '202610' ) );
		$this->assertFalse( affiliate_sanitize_period( "202610' OR 1=1 --" ) );
		$this->assertSame( 12.34, affiliate_sanitize_decimal( '-12,34<script>' ) );
	}

	public function test_complete_record_filters_do_not_allow_sql_injection() {
		global $affiliate;

		do_action( 'affiliate_purchase', $this->affiliate_user_id, 2, 'paid:safe', 77, 'Safe', array() );
		do_action( 'affiliate_purchase', $this->affiliate_user_id, 3, 'paid:other', 78, 'Other', array() );

		$records = $affiliate->get_complete_records(
			$this->affiliate_user_id,
			gmdate( 'Ym' ),
			array( "paid:safe' OR 1=1 --" )
		);

		$this->assertSame( array(), $records );
	}

	public function test_marketpress_metabox_escapes_affiliate_identity() {
		$user_id = self::factory()->user->create(
			array(
				'display_name' => '<script>alert(1)</script>',
				'user_email' => 'safe@example.test',
			)
		);
		$order = (object) array(
			'post_status' => 'order_paid',
			'mp_shipping_info' => array( 'affiliate_referrer' => $user_id ),
		);

		ob_start();
		affiliate_marketpress_display_metabox( $order );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;', $output );
	}

	public function test_management_capability_is_required() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertFalse( affiliate_can_manage() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertTrue( affiliate_can_manage() );
	}

	public function test_profile_update_rejects_invalid_nonce() {
		global $affadmin;

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$_POST = array(
			'action' => 'update',
			'_wpnonce' => 'invalid',
			'enable_affiliate' => 'yes',
		);

		try {
			$affadmin->add_profile_report_page();
			$this->fail( 'An invalid nonce must stop the profile update.' );
		} catch ( WPDieException $exception ) {
			$this->assertNotSame( '', $exception->getMessage() );
		} finally {
			$_POST = array();
		}

		$this->assertSame( '', get_user_meta( $user_id, 'enable_affiliate', true ) );
	}
}
