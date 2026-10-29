<?php

final class Affiliate_Request_Context {

	private $server;
	private $request;
	private $cookies;

	public function __construct( array $server, array $request, array $cookies ) {
		$this->server = $server;
		$this->request = $request;
		$this->cookies = $cookies;
	}

	public static function from_globals() {
		return new self( $_SERVER, $_GET, $_COOKIE );
	}

	public function reference() {
		return isset( $this->request['ref'] ) ? sanitize_text_field( wp_unslash( $this->request['ref'] ) ) : false;
	}

	public function referrer_host() {
		if ( empty( $this->server['HTTP_REFERER'] ) ) {
			return '';
		}

		$host = wp_parse_url( esc_url_raw( $this->server['HTTP_REFERER'] ), PHP_URL_HOST );
		return is_string( $host ) ? $host : '';
	}

	public function has_affiliate_cookie() {
		return isset( $this->cookies[ 'affiliate_' . COOKIEHASH ] );
	}

	public function has_no_affiliate_cookie() {
		return isset( $this->cookies[ 'noaffiliate_' . COOKIEHASH ] );
	}

	public function affiliate_hash() {
		if ( ! $this->has_affiliate_cookie() ) {
			return false;
		}

		return sanitize_text_field( wp_unslash( $this->cookies[ 'affiliate_' . COOKIEHASH ] ) );
	}

	public function metadata() {
		$remote_url = isset( $this->server['HTTP_REFERER'] ) ? esc_url_raw( $this->server['HTTP_REFERER'] ) : '';
		$host = isset( $this->server['HTTP_HOST'] ) ? sanitize_text_field( $this->server['HTTP_HOST'] ) : '';
		$uri = isset( $this->server['REQUEST_URI'] ) ? sanitize_text_field( $this->server['REQUEST_URI'] ) : '';
		$ip = isset( $this->server['HTTP_X_FORWARD_FOR'] ) ? $this->server['HTTP_X_FORWARD_FOR'] : ( isset( $this->server['REMOTE_ADDR'] ) ? $this->server['REMOTE_ADDR'] : '' );

		return array(
			'REMOTE_URL' => $remote_url,
			'LOCAL_URL'  => ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri,
			'IP'         => sanitize_text_field( $ip ),
		);
	}
}
