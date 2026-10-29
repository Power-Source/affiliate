<?php

final class Affiliate_Money {

	private $minor_units;

	private function __construct( $minor_units ) {
		$this->minor_units = (int) $minor_units;
	}

	public static function from_value( $value ) {
		if ( false === $value || null === $value || '' === $value ) {
			return new self( 0 );
		}

		if ( is_string( $value ) ) {
			$value = str_replace( ',', '.', $value );
		}

		if ( ! is_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Affiliate money values must be numeric.' );
		}

		return new self( (int) round( (float) $value * 100 ) );
	}

	public function to_decimal() {
		return number_format( $this->minor_units / 100, 2, '.', '' );
	}

	public function is_zero() {
		return 0 === $this->minor_units;
	}
}
