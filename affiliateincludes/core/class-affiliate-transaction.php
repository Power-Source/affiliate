<?php

final class Affiliate_Transaction {

	const TYPE_CLICK = 'click';
	const TYPE_SIGNUP = 'signup';
	const TYPE_PURCHASE = 'purchase';
	const TYPE_CREDIT = 'credit';
	const TYPE_DEBIT = 'debit';

	private $type;
	private $user_id;
	private $period;
	private $amount;
	private $area;
	private $area_id;
	private $note;
	private $meta;

	public function __construct( $type, $user_id, Affiliate_Period $period, Affiliate_Money $amount, $area = null, $area_id = null, $note = null, $meta = array() ) {
		$types = array( self::TYPE_CLICK, self::TYPE_SIGNUP, self::TYPE_PURCHASE, self::TYPE_CREDIT, self::TYPE_DEBIT );
		if ( ! in_array( $type, $types, true ) ) {
			throw new InvalidArgumentException( 'Unsupported affiliate transaction type.' );
		}

		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			throw new InvalidArgumentException( 'Affiliate transactions require a valid user ID.' );
		}

		$this->type = $type;
		$this->user_id = $user_id;
		$this->period = $period;
		$this->amount = $amount;
		$this->area = false === $area || '' === $area ? null : (string) $area;
		$this->area_id = false === $area_id ? null : $area_id;
		$this->note = false === $note ? null : $note;
		$this->meta = is_array( $meta ) ? $meta : array();
	}

	public function type() {
		return $this->type;
	}

	public function user_id() {
		return $this->user_id;
	}

	public function period() {
		return (string) $this->period;
	}

	public function amount() {
		return $this->amount;
	}

	public function has_area() {
		return null !== $this->area;
	}

	public function area() {
		return $this->area;
	}

	public function to_record() {
		return array(
			'user_id'       => $this->user_id,
			'period'        => (string) $this->period,
			'affiliatearea' => $this->area,
			'area_id'       => $this->area_id,
			'affiliatenote' => $this->note,
			'amount'        => $this->amount->to_decimal(),
			'meta'          => maybe_serialize( $this->meta ),
			'timestamp'     => current_time( 'mysql', true ),
		);
	}
}
