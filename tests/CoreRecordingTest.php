<?php

class CoreRecordingTest extends AffiliateTestCase {

	public function test_core_hooks_record_totals_details_and_referrers() {
		do_action( 'affiliate_click', $this->affiliate_user_id, 0.10, 'click:test', 11, 'Click', array( 'source' => 'test' ) );
		do_action( 'affiliate_signup', $this->affiliate_user_id, 0.20, 'signup:test', 12, 'Signup', array( 'source' => 'test' ) );
		do_action( 'affiliate_purchase', $this->affiliate_user_id, 5.25, 'paid:test', 13, 'Purchase', array( 'source' => 'test' ) );
		do_action( 'affiliate_credit', $this->affiliate_user_id, 1.50 );
		do_action( 'affiliate_debit', $this->affiliate_user_id, 0.75 );
		do_action( 'affiliate_referrer', $this->affiliate_user_id, 'https://example.test/referrer' );

		$totals = $this->get_totals();
		$this->assertSame( '1', $totals->uniques );
		$this->assertSame( '1', $totals->signups );
		$this->assertSame( '1', $totals->completes );
		$this->assertEquals( 7.05, (float) $totals->credits );
		$this->assertEquals( 0.75, (float) $totals->debits );
		$this->assertCount( 5, $this->get_records() );

		global $affiliate, $wpdb;
		$this->assertSame(
			'1',
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT referred FROM {$affiliate->affiliatereferrers} WHERE user_id = %d AND url = %s",
					$this->affiliate_user_id,
					'https://example.test/referrer'
				)
			)
		);
	}

	public function test_cookie_hash_resolves_affiliate_user() {
		global $affiliate;

		update_user_meta( $this->affiliate_user_id, 'affiliate_hash', 'known-hash' );
		$_COOKIE[ 'affiliate_' . COOKIEHASH ] = 'known-hash';

		$this->assertEquals( $this->affiliate_user_id, $affiliate->get_affiliate_user_id_from_hash() );

		unset( $_COOKIE[ 'affiliate_' . COOKIEHASH ] );
	}

	public function test_table_scope_matches_activation_mode() {
		global $affiliate, $wpdb;

		$expected_prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$this->assertSame( $expected_prefix . 'affiliatedata', $affiliate->affiliatedata );
	}
}
