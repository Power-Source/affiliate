<?php

final class Affiliate_Period {

	private $value;

	private function __construct( $value ) {
		$this->value = $value;
	}

	public static function current() {
		return new self( date( 'Ym' ) );
	}

	public static function from_value( $value ) {
		$value = (string) $value;
		if ( ! preg_match( '/^\d{6}$/', $value ) ) {
			throw new InvalidArgumentException( 'Affiliate periods must use YYYYMM format.' );
		}

		$month = (int) substr( $value, 4, 2 );
		if ( $month < 1 || $month > 12 ) {
			throw new InvalidArgumentException( 'Affiliate periods must contain a valid month.' );
		}

		return new self( $value );
	}

	public function __toString() {
		return $this->value;
	}
}
