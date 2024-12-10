<?php
/**
 * Helper Functions.
 */

/**
 * Logs data into file.
 *
 * @param mixed  $data Array or String.
 * @param string $file File Name.
 */
function wtewc_log( $data, $file = '_debug' ) {
	if ( defined( 'WTE_LOG' ) && WTE_LOG ) {
		if ( ! is_dir( WP_CONTENT_DIR . '/_log' ) ) {
			mkdir( WP_CONTENT_DIR . '/_log', 0700 );
		}
		$file = WP_CONTENT_DIR . "/_log/{$file}.log";
		if ( is_array( $data ) || is_object( $data ) ) {
			error_log( print_r( $data, true ) , 3, $file ); // phpcs:ignore
		} else {
			error_log( $data, 3, $file ); // phpcs:ignore
		}
	}
}
