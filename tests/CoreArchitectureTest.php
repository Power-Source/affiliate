<?php

class Affiliate_Test_Repository extends Affiliate_Repository {

	public $totals = array();
	public $records = array();
	public $referrers = array();

	public function __construct() {
	}

	public function record_totals( Affiliate_Transaction $transaction ) {
		$this->totals[] = $transaction;
		return true;
	}

	public function add_record( Affiliate_Transaction $transaction ) {
		$this->records[] = $transaction;
		return true;
	}

	public function add_referrer( $user_id, Affiliate_Period $period, $url ) {
		$this->referrers[] = array( $user_id, (string) $period, $url );
		return true;
	}
}

class CoreArchitectureTest extends AffiliateTestCase {

	public function test_money_normalizes_decimal_values_without_float_output() {
		$this->assertSame( '12.35', Affiliate_Money::from_value( '12,345' )->to_decimal() );
		$this->assertSame( '-1.25', Affiliate_Money::from_value( '-1.25' )->to_decimal() );
		$this->assertSame( '0.00', Affiliate_Money::from_value( false )->to_decimal() );
	}

	public function test_money_rejects_non_numeric_values() {
		$this->expectException( InvalidArgumentException::class );
		Affiliate_Money::from_value( '12.00<script>' );
	}

	public function test_period_validates_year_and_month() {
		$this->assertSame( '202610', (string) Affiliate_Period::from_value( '202610' ) );

		$this->expectException( InvalidArgumentException::class );
		Affiliate_Period::from_value( '202613' );
	}

	public function test_transaction_service_uses_injected_repository() {
		$repository = new Affiliate_Test_Repository();
		$service = new Affiliate_Transaction_Service( $repository );

		$this->assertTrue(
			$service->record(
				Affiliate_Transaction::TYPE_PURCHASE,
				$this->affiliate_user_id,
				'4.75',
				'paid:test',
				123,
				'Test',
				array( 'source' => 'unit' )
			)
		);

		$this->assertCount( 1, $repository->totals );
		$this->assertCount( 1, $repository->records );
		$this->assertSame( '4.75', $repository->totals[0]->amount()->to_decimal() );
		$this->assertSame( 'paid:test', $repository->records[0]->area() );
	}

	public function test_request_context_is_independent_from_superglobals() {
		$context = new Affiliate_Request_Context(
			array(
				'HTTP_REFERER' => 'https://referrer.example/path',
				'HTTP_HOST' => 'shop.example',
				'REQUEST_URI' => '/checkout',
				'REMOTE_ADDR' => '127.0.0.1',
			),
			array( 'ref' => 'affiliate-code' ),
			array( 'affiliate_' . COOKIEHASH => 'affiliate-hash' )
		);

		$this->assertSame( 'affiliate-code', $context->reference() );
		$this->assertSame( 'referrer.example', $context->referrer_host() );
		$this->assertSame( 'affiliate-hash', $context->affiliate_hash() );
		$this->assertSame( '127.0.0.1', $context->metadata()['IP'] );
	}

	public function test_public_facade_exposes_injected_dependencies() {
		$repository = new Affiliate_Test_Repository();
		$service = new Affiliate_Transaction_Service( $repository );
		$facade = new affiliate( $repository, $service );

		$this->assertSame( $repository, $facade->get_repository() );
		$this->assertSame( $service, $facade->get_transaction_service() );

		remove_action( 'init', array( $facade, 'handle_affiliate_link' ) );
		remove_action( 'affiliate_click', array( $facade, 'record_click' ), 10 );
		remove_action( 'affiliate_signup', array( $facade, 'record_signup' ), 10 );
		remove_action( 'affiliate_purchase', array( $facade, 'record_purchase' ), 10 );
		remove_action( 'affiliate_credit', array( $facade, 'record_credit' ), 10 );
		remove_action( 'affiliate_debit', array( $facade, 'record_debit' ), 10 );
		remove_action( 'affiliate_referrer', array( $facade, 'record_referrer' ), 10 );
		remove_action( 'user_register', array( $facade, 'user_register' ), 10 );
		remove_action( 'wpmu_activate_user', array( $facade, 'wpmu_activate_user' ), 10 );
		remove_action( 'wpmu_new_blog', array( $facade, 'wpmu_new_blog' ), 10 );
	}
}
