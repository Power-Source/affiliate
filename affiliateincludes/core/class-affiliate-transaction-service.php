<?php

class Affiliate_Transaction_Service {

	private $repository;

	public function __construct( Affiliate_Repository $repository ) {
		$this->repository = $repository;
	}

	public function record( $type, $user_id, $amount = false, $area = false, $area_id = false, $note = false, $meta = array(), $record_without_area = false ) {
		try {
			$money = Affiliate_Money::from_value( $amount );
			$transaction = new Affiliate_Transaction(
				$type,
				$user_id,
				Affiliate_Period::current(),
				$money,
				$area,
				$area_id,
				$note,
				$meta
			);
		} catch ( InvalidArgumentException $exception ) {
			do_action( 'affiliate_transaction_rejected', $exception->getMessage(), $type, $user_id, $amount );
			return false;
		}

		if ( ! $this->repository->record_totals( $transaction ) ) {
			return false;
		}

		if ( $transaction->has_area() || $record_without_area ) {
			return $this->repository->add_record( $transaction );
		}

		return true;
	}

	public function record_referrer( $user_id, $url ) {
		if ( ! is_numeric( $user_id ) || empty( $url ) ) {
			return false;
		}

		return $this->repository->add_referrer( $user_id, Affiliate_Period::current(), $url );
	}
}
