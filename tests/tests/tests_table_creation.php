<?php
/**
 * Tests for collaboration table creation.
 *
 * Covers creation of the collaboration table during REST API initialization
 * and when provisioning new sites in multisite.
 *
 * @package WordPress
 * @subpackage Collaboration
 *
 * @group collaboration
 */

namespace PWCC\RtcTableTesting\Tests;

use WP_UnitTestCase;

/**
 * Test class from PR.
 *
 * @package PWCC\RtcTableTesting\Tests
 */
class Tests_Table_Creation extends WP_UnitTestCase {
	/**
	 * Test the table is created on the rest_api_init action.
	 */
	public function test_table_creation_on_rest_api_init() {
		global $wpdb;

		// Fire the rest_api_init action to trigger table creation.
		do_action( 'rest_api_init' );
		// Check the table now exists.
		$this->assertSame( $wpdb->collaboration, $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->collaboration}'" ) );
	}

	/**
	 * Test the table is created for a new site on a multisite instance.
	 *
	 * @group ms-required
	 */
	public function test_table_creation_on_new_multisite_site() {
		global $wpdb;
		// Create a new site which should trigger the creation of the table for that site.
		$new_site_id = $this->factory()->blog->create();
		switch_to_blog( $new_site_id );

		$expected_table_name = $wpdb->get_blog_prefix( $new_site_id ) . 'collaboration';
		$actual_table_name   = $wpdb->collaboration;
		$table_fields        = $wpdb->get_results( "DESCRIBE {$wpdb->collaboration};" );

		restore_current_blog();

		$this->assertSame( $expected_table_name, $actual_table_name, 'The $wpdb->collaboration property should be set to the correct table name for the new site.' );
		$this->assertNotEmpty( $table_fields, 'The collaboration table should exist for the new site.' );
	}
}
