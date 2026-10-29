<?php

if ( ! class_exists( 'MP_Order' ) ) {
	class MP_Order {
		public $ID;
		public $post_status = 'order_paid';
		private $total;

		public function __construct( $id, $total = 0 ) {
			$this->ID = $id;
			$this->total = $total;
		}

		public function get_cart() {
			return new class( $this->total ) {
				private $value;

				public function __construct( $value ) {
					$this->value = $value;
				}

				public function total() {
					return $this->value;
				}
			};
		}
	}
}

class AddonIntegrationTest extends AffiliateTestCase {

	public function test_directory_records_only_one_commission() {
		$customer_id = self::factory()->user->create();
		update_user_meta( $customer_id, 'affiliate_referred_by', $this->affiliate_user_id );
		$settings = array(
			'dr_one_time_whole_payment' => '4',
			'dr_one_time_partial_payment' => '50',
		);

		dr_affiliate_new_paid( $settings, $customer_id, 'one_time' );
		dr_affiliate_new_paid( $settings, $customer_id, 'one_time' );

		$this->assertSingleCommission( 'paid:directory', 4.50 );
	}

	public function test_classifieds_credit_and_one_time_orders_are_idempotent() {
		$customer_id = self::factory()->user->create();
		update_user_meta( $customer_id, 'affiliate_referred_by', $this->affiliate_user_id );
		$credit_settings = array(
			'cf_credit_commissions' => array(
				7 => array( 'mode' => 'fixed', 'value' => '1.25' ),
			),
		);
		$packages = array(
			array( 'product_id' => 7, 'quantity' => 2, 'price' => 10, 'label' => 'Credits' ),
		);

		cf_affiliate_credit_purchase( $credit_settings, $customer_id, 701, $packages );
		cf_affiliate_credit_purchase( $credit_settings, $customer_id, 701, $packages );
		$this->assertSingleCommission( 'paid:classifieds-credit', 2.50 );

		$one_time_settings = array(
			'cf_one_time_commission' => array( 'mode' => 'percent', 'value' => '10' ),
		);
		$purchase = array( 'quantity' => 1, 'price' => 40, 'label' => 'Anzeige' );
		cf_affiliate_one_time_purchase( $one_time_settings, $customer_id, 702, $purchase );
		cf_affiliate_one_time_purchase( $one_time_settings, $customer_id, 702, $purchase );
		$this->assertSingleCommission( 'paid:classifieds-one-time', 4.00 );
	}

	public function test_marketpress_legacy_order_is_idempotent() {
		$order_id = self::factory()->post->create( array( 'post_type' => 'mp_order' ) );
		update_post_meta( $order_id, 'mp_shipping_info', array( 'affiliate_referrer' => $this->affiliate_user_id ) );
		aff_update_option( 'affiliate_mp_percentage', 10 );
		$order = (object) array(
			'ID' => $order_id,
			'mp_order_total' => 100,
			'mp_shipping_total' => 10,
			'mp_tax_total' => 20,
		);

		affiliate_marketpress_paid_order( $order );
		affiliate_marketpress_paid_order( $order );

		$this->assertSingleCommission( 'paid:marketpress', 7.00 );
	}

	public function test_marketpress_modern_order_is_idempotent() {
		$order_id = self::factory()->post->create( array( 'post_type' => 'mp_order' ) );
		update_post_meta( $order_id, 'aff_user_id', $this->affiliate_user_id );
		aff_update_option( 'affiliate_mp_percentage', 15 );
		$order = new MP_Order( $order_id, 80 );

		aff_mp3_paid_order( $order );
		aff_mp3_paid_order( $order );

		$this->assertSingleCommission( 'paid:marketpress', 12.00 );
	}

	public function test_bloghosting_records_only_one_commission() {
		$blog_id = is_multisite() ? self::factory()->blog->create() : 1;
		update_blog_option( $blog_id, 'affiliate_referred_by', $this->affiliate_user_id );
		update_blog_option( $blog_id, 'affiliate_paid', 'no' );
		update_site_option( 'supporter_1_payment_type', 'percentage' );
		update_site_option( 'supporter_1_whole_payment', '10' );
		update_site_option( 'supporter_1_partial_payment', '00' );

		affiliate_supporter_paid( $blog_id, 50, '1', 1 );
		affiliate_supporter_paid( $blog_id, 50, '1', 1 );

		$this->assertSingleCommission( 'paid:prosites', 5.00 );
	}

	public function test_membership_records_only_one_commission() {
		$customer_id = self::factory()->user->create();
		update_user_meta( $customer_id, 'affiliate_referred_by', $this->affiliate_user_id );
		$membership = new class() {
			public $price = 100;

			public function get_custom_data( $key ) {
				return 'aff_reward_value' === $key ? 1000 : 'inv';
			}
		};
		$subscription = new class( $membership ) {
			const STATUS_ACTIVE = 'active';
			public $status = 'active';
			public $id = 91;
			private $membership;

			public function __construct( $membership ) {
				$this->membership = $membership;
			}

			public function get_membership() {
				return $this->membership;
			}
		};
		$invoice = (object) array(
			'user_id' => $customer_id,
			'subtotal' => 50,
			'id' => 92,
			'gateway_id' => 'test',
			'external_id' => 'txn-92',
		);

		$integration = $this->getMembershipIntegration();
		$integration->payment_processed( $invoice, $subscription );
		$integration->payment_processed( $invoice, $subscription );

		$this->assertSingleCommission( 'paid:membership2', 5.00 );
	}

	private function assertSingleCommission( $area, $amount ) {
		$records = $this->get_records( $area );
		$this->assertCount( 1, $records );
		$this->assertEquals( $amount, (float) $records[0]->amount );
	}

	private function getMembershipIntegration() {
		$callbacks = $GLOBALS['wp_filter']['ms_init']->callbacks;
		foreach ( $callbacks as $priority ) {
			foreach ( $priority as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof Affiliate_Membership2_Integration ) {
					$integration = $callback['function'][0];
					$property = new ReflectionProperty( $integration, 'ms_lib' );
					$property->setAccessible( true );
					$property->setValue(
						$integration,
						(object) array(
							'net' => new class() {
								public function current_url() {
									return 'https://affiliate.test/checkout';
								}

								public function current_ip() {
									return (object) array( 'ip' => '127.0.0.1' );
								}
							},
						)
					);
					return $integration;
				}
			}
		}

		$this->fail( 'Membership integration callback was not registered.' );
	}
}
