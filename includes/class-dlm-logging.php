<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Logging class.
 */
class DLM_Logging {

	/**
	 * Check if logging is enabled
	 *
	 * @return bool
	 */
	public function is_logging_enabled() {
		return (1 == get_option( 'dlm_enable_logging', 0 ));
	}

	/**
	 * create_log function.
	 *
	 * @access public
	 * @return void
	 */
	public function create_log( $type, $status, $message, $download, $version ) {
		global $wpdb;

		$wpdb->hide_errors();

		$wpdb->insert(
			$wpdb->download_log,
			array(
				'type'                    => $type,
				'user_id'                 => absint( get_current_user_id() ),
				'user_ip'                 => DLM_Utils::get_visitor_ip(),
				'user_agent'              => DLM_Utils::get_visitor_ua(),
				'download_id'             => absint( $download->id ),
				'version_id'              => absint( $version->id ),
				'version'                 => $version->version,
				'download_date'           => current_time( 'mysql' ),
				'download_status'         => $status,
				'download_status_message' => $message
			),
			array(
				'%s',
				'%d',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s'
			)
		);

		return $wpdb->insert_id;
	}

}

