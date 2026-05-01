<?php
/**
 * RTC Table Testing
 *
 * @package           RtcTableTesting
 */

namespace PWCC\RtcTableTesting;

const PLUGIN_VERSION = '1.0.0';

/**
 * Bootstrap the plugin.
 */
function bootstrap() {
	if (
		! function_exists( 'wp_is_collaboration_enabled' ) ||
		! wp_is_collaboration_enabled() ||
		class_exists( 'WP_Collaboration_Table_Storage' ) ||
		class_exists( 'WP_HTTP_Polling_Collaboration_Server' )
	) {
		// Noop the plugin.
		return;
	}

	require_once __DIR__ . '/class-wp-collaboration-table-storage.php';
	require_once __DIR__ . '/class-wp-http-polling-collaboration-server.php';

	if ( defined( 'WP_CLI' ) && \WP_CLI ) {
		require_once __DIR__ . '/class-wpcli-rtc-table-testing.php';
		\WP_CLI::add_command( 'rtc-table-testing', WPCLI_Command\WPCLI_RTC_Table_Testing::class );
	}

	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_collaboration_endpoints' );

	add_action( 'init', __NAMESPACE__ . '\\register_collaboration_table', 5 );
	add_action( 'rest_api_init', __NAMESPACE__ . '\\maybe_create_table', 5 );

	add_action( 'wp_delete_old_collaboration_data', __NAMESPACE__ . '\\wp_delete_old_collaboration_data' );

	if ( ! wp_next_scheduled( 'wp_delete_old_collaboration_data' ) ) {
		wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'wp_delete_old_collaboration_data' );
	}
}

/**
 * Register the replacement REST API endpoints for collaboration.
 *
 * Run on the `rest_api_init` action.
 */
function register_collaboration_endpoints() {
	$controller = new WP_HTTP_Polling_Collaboration_Server( new WP_Collaboration_Table_Storage() );
	$controller->register_routes();
}

/**
 * Define the collaboration table name on the $wpdb global.
 *
 * Runs on the `init, 5` action.
 *
 * @global \wpdb $wpdb WordPress database abstraction object.
 */
function register_collaboration_table() {
	global $wpdb;
	$wpdb->collaboration = $wpdb->prefix . 'collaboration';
}

/**
 * Get the SQL schema for the collaboration table.
 *
 * @global \wpdb $wpdb WordPress database abstraction object.
 *
 * @return string SQL schema for the collaboration table.
 */
function get_table_schema() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	/*
	 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 */
	$max_index_length = 191;

	return "CREATE TABLE $wpdb->collaboration (
		collaboration_id bigint(20) unsigned NOT NULL auto_increment,
		room varchar($max_index_length) NOT NULL default '',
		type varchar(32) NOT NULL default '',
		client_id varchar(32) NOT NULL default '',
		user_id bigint(20) unsigned NOT NULL default '0',
		data longtext NOT NULL,
		date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (collaboration_id),
		KEY type_client_id (type,client_id),
		KEY room (room,collaboration_id),
		KEY room_type_date (room,type,date_gmt),
		KEY date_gmt (date_gmt)
	) $charset_collate;\n";
}

/**
 * Create the collaboration table if it doesn't already exist.
 *
 * Runs on the `rest_api_init, 5` action. Prior to default endpoint registration.
 */
function maybe_create_table() {
	$schema = get_table_schema();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $schema );
}

/**
 * Deletes stale collaboration data from the collaboration table.
 *
 * Removes non-awareness rows older than 7 days and awareness rows older
 * than 60 seconds. Rows left behind by abandoned collaborative editing
 * sessions are cleaned up to prevent unbounded table growth.
 *
 * @since 7.0.0
 *
 * @global \wpdb $wpdb WordPress database abstraction object.
 */
function wp_delete_old_collaboration_data(): void {
	global $wpdb;

	if ( ! wp_is_collaboration_enabled() ) {
		/*
		 * Collaboration was enabled in the past but has since been disabled.
		 * Unschedule the cron job prior to clean up so this callback does not
		 * continue to run.
		 */
		wp_clear_scheduled_hook( 'wp_delete_old_collaboration_data' );
	}

	// Clean up rows older than 7 days.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->collaboration} WHERE date_gmt < %s",
			gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS )
		)
	);

	// Clean up awareness rows older than 60 seconds.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->collaboration} WHERE type = 'awareness' AND date_gmt < %s",
			gmdate( 'Y-m-d H:i:s', time() - 60 )
		)
	);
}
