<?php
/**
 * RTC Table Testing
 *
 * @package           RtcTableTesting
 */

namespace PWCC\RtcTableTesting\WPCLI_Command;

use WP_CLI_Command;
use function PWCC\RtcTableTesting\get_table_schema;

/**
 * WP-CLI command class for creating the collaboration table.
 *
 * @since 1.0.0
 */
class WPCLI_RTC_Table_Testing extends WP_CLI_Command {
	/**
	 * Create the collaboration table.
	 *
	 * @subcommand create-collaboration-table
	 *
	 * ## EXAMPLES
	 *
	 *     wp rtc-table-testing create-collaboration-table
	 */
	public function create_collaboration_table() {
		global $wpdb;

		$wpdb->collaboration = $wpdb->prefix . 'collaboration';

		$schema = get_table_schema();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $schema );
	}
}
