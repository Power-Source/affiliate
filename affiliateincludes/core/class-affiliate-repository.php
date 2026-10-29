<?php

class Affiliate_Repository {

	private $db;
	private $data_table;
	private $referrers_table;
	private $records_table;

	public function __construct( $db, $data_table, $referrers_table, $records_table ) {
		$this->db = $db;
		$this->data_table = $data_table;
		$this->referrers_table = $referrers_table;
		$this->records_table = $records_table;
	}

	public function record_totals( Affiliate_Transaction $transaction ) {
		$user_id = $transaction->user_id();
		$period = $transaction->period();
		$amount = $transaction->amount()->to_decimal();

		switch ( $transaction->type() ) {
			case Affiliate_Transaction::TYPE_CLICK:
				$sql = $this->db->prepare(
					"INSERT INTO {$this->data_table} (user_id, period, uniques, credits, lastupdated) VALUES (%d, %s, 1, %f, NOW()) ON DUPLICATE KEY UPDATE uniques = uniques + 1, credits = credits + %f",
					$user_id,
					$period,
					$amount,
					$amount
				);
				break;
			case Affiliate_Transaction::TYPE_SIGNUP:
				$sql = $this->db->prepare(
					"INSERT INTO {$this->data_table} (user_id, period, signups, credits, lastupdated) VALUES (%d, %s, 1, %f, NOW()) ON DUPLICATE KEY UPDATE signups = signups + 1, credits = credits + %f",
					$user_id,
					$period,
					$amount,
					$amount
				);
				break;
			case Affiliate_Transaction::TYPE_PURCHASE:
				$sql = $this->db->prepare(
					"INSERT INTO {$this->data_table} (user_id, period, completes, credits, lastupdated) VALUES (%d, %s, 1, %f, NOW()) ON DUPLICATE KEY UPDATE completes = completes + 1, credits = credits + %f",
					$user_id,
					$period,
					$amount,
					$amount
				);
				break;
			case Affiliate_Transaction::TYPE_CREDIT:
				$sql = $this->db->prepare(
					"INSERT INTO {$this->data_table} (user_id, period, credits, lastupdated) VALUES (%d, %s, %f, NOW()) ON DUPLICATE KEY UPDATE credits = credits + %f",
					$user_id,
					$period,
					$amount,
					$amount
				);
				break;
			case Affiliate_Transaction::TYPE_DEBIT:
				$sql = $this->db->prepare(
					"INSERT INTO {$this->data_table} (user_id, period, debits, lastupdated) VALUES (%d, %s, %f, NOW()) ON DUPLICATE KEY UPDATE debits = debits + %f",
					$user_id,
					$period,
					$amount,
					$amount
				);
				break;
			default:
				return false;
		}

		return $this->query( $sql, 'record_totals' );
	}

	public function add_record( Affiliate_Transaction $transaction ) {
		$result = $this->db->insert( $this->records_table, $transaction->to_record() );
		if ( false === $result ) {
			$this->report_error( 'add_record' );
			return false;
		}

		return true;
	}

	public function add_referrer( $user_id, Affiliate_Period $period, $url ) {
		$sql = $this->db->prepare(
			"INSERT INTO {$this->referrers_table} (user_id, period, url, referred) VALUES (%d, %s, %s, 1) ON DUPLICATE KEY UPDATE referred = referred + 1",
			absint( $user_id ),
			(string) $period,
			(string) $url
		);

		return $this->query( $sql, 'add_referrer' );
	}

	public function find_user_by_meta( $meta_key, $meta_value ) {
		$allowed_keys = array( 'affiliate_hash', 'affiliate_reference', 'affiliate_referrer' );
		if ( ! in_array( $meta_key, $allowed_keys, true ) ) {
			return false;
		}

		return $this->db->get_var(
			$this->db->prepare(
				"SELECT user_id FROM {$this->db->usermeta} WHERE meta_key = %s AND meta_value = %s",
				$meta_key,
				$meta_value
			)
		);
	}

	public function find_signup_meta_by_email( $email ) {
		if ( empty( $this->db->signups ) ) {
			return false;
		}

		return $this->db->get_var(
			$this->db->prepare(
				"SELECT meta FROM {$this->db->signups} WHERE user_email = %s LIMIT 1",
				sanitize_email( $email )
			)
		);
	}

	public function has_commission( $user_id, $area, $area_id ) {
		$count = $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(id) FROM {$this->records_table} WHERE user_id = %d AND affiliatearea = %s AND area_id = %d",
				absint( $user_id ),
				$area,
				$area_id
			)
		);

		return (int) $count > 0;
	}

	public function find_records( $user_id, $period = null, $areas = array(), $area_id = null, $limit = 50 ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return array();
		}

		$sql = "SELECT * FROM {$this->records_table} WHERE user_id = %d";
		$args = array( $user_id );

		if ( null !== $period ) {
			$sql .= ' AND period = %s';
			$args[] = (string) $period;
		}

		$areas = array_values( array_filter( array_map( 'sanitize_text_field', (array) $areas ) ) );
		if ( $areas ) {
			$sql .= ' AND affiliatearea IN (' . implode( ', ', array_fill( 0, count( $areas ), '%s' ) ) . ')';
			$args = array_merge( $args, $areas );
		}

		if ( null !== $area_id ) {
			$sql .= ' AND area_id = %s';
			$args[] = sanitize_text_field( $area_id );
		}

		$sql .= ' LIMIT %d';
		$args[] = max( 1, min( 500, absint( $limit ) ) );

		return $this->db->get_results( $this->db->prepare( $sql, $args ) );
	}

	private function query( $sql, $operation ) {
		$result = $this->db->query( $sql );
		if ( false === $result ) {
			$this->report_error( $operation );
			return false;
		}

		return true;
	}

	private function report_error( $operation ) {
		do_action( 'affiliate_repository_error', $operation, $this->db->last_error );
	}
}
