<?php

class WPPUS_Crypto {

	const METHOD        = 'aes-256-cbc';
	const SLASH_REPLACE = '_';

	public static function encrypt( $message, $crypt_key, $sign_key ) {
		$cipher    = false;
		$crypt_key = hex2bin( hash( 'sha256', $crypt_key ) );

		if ( mb_strlen( $crypt_key, '8bit' ) !== 32 ) {

			throw new Exception( 'Needs a 256-bit key!' );
		}

		$ivsize     = openssl_cipher_iv_length( self::METHOD );
		$iv         = openssl_random_pseudo_bytes( $ivsize );
		$ciphertext = openssl_encrypt(
			$message,
			self::METHOD,
			$crypt_key,
			OPENSSL_RAW_DATA,
			$iv
		);

		if ( false !== $ciphertext ) {
			$hmac   = self::hmac_sign( $iv . $ciphertext, $sign_key );
			$cipher = self::base64url_encode( $iv . $hmac . $ciphertext );
		}

		return $cipher;
	}

	public static function decrypt( $cipher, $crypt_key, $sign_key ) {
		$message   = false;
		$crypt_key = hex2bin( hash( 'sha256', $crypt_key ) );

		if ( mb_strlen( $crypt_key, '8bit' ) !== 32 ) {

			throw new Exception( 'Needs a 256-bit key!' );
		}

		$cipher     = self::base64url_decode( $cipher );
		$ivsize     = openssl_cipher_iv_length( self::METHOD );
		$iv         = mb_substr( $cipher, 0, $ivsize, '8bit' );
		$hmac       = mb_substr( $cipher, $ivsize, $ivsize * 2, '8bit' );
		$ciphertext = mb_substr( $cipher, $ivsize * 3, null, '8bit' );
		$hmacnew    = self::hmac_sign( $iv . $ciphertext, $sign_key );

		if ( self::hmac_verify( $hmac, $hmacnew ) ) {
			$message = openssl_decrypt(
				$ciphertext,
				self::METHOD,
				$crypt_key,
				OPENSSL_RAW_DATA,
				$iv
			);
		}

		return $message;
	}

	public static function hmac_sign( $message, $sign_key ) {
		$signature = hash_hmac( 'sha256', $message, $sign_key, true );

		return $signature;
	}

	public static function hmac_verify( $original_val, $new_val ) {

		if ( function_exists( 'hash_equals' ) ) {

			return hash_equals( $original_val, $new_val );
		}

		if ( ! is_string( $original_val ) || ! is_string( $new_val ) ) {

			return false;
		}

		$original_length = mb_strlen( $original_val );

		if ( mb_strlen( $new_val ) !== $original_length ) {

			return false;
		}

		$result = 0;

		for ( $i = 0; $i < $original_length; ++$i ) {
			$result |= ord( $original_val[ $i ] ) ^ ord( $new_val[ $i ] );
		}

		return 0 === $result;
	}

	public static function base64url_encode( $s ) {

		return str_replace( '/', self::SLASH_REPLACE, base64_encode( $s ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	public static function base64url_decode( $s ) {

		return base64_decode( str_replace( self::SLASH_REPLACE, '/', $s ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}
}
