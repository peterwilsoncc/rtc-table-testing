# RTC Table Testing

Test the new table approaches for Real-time Collaboration in WordPress Core

## Description

Allows hosting providers to test the proposed new table for Real Time Collaboration in production.

There are two approaches that can be tested via this plugin.

### Table Only

This approach always uses the new table for both updates and for awareness.

This is included in the [WordPress/WordPress-Develop#11256](https://github.com/WordPress/wordpress-develop/pull/11256/) pull request.

In order to test this approach, you must define the constant `RTC_TABLE_TESTING_TEST_CASE` in the wp-config file:

```
define('RTC_TABLE_TESTING_TEST_CASE', 'table_storage_only');
```

### Table storage with object cache for awareness (default)

This approach uses the new table for content updates.

Awareness uses the object-cache only if the site uses a persistent object cache such as Redis or Memcached.

If a persistent object-cache is not available awareness is stored in the custom table.

### Addition of the new table

By default the plugin will attempt to create the new table on each time the WordPress REST API is loaded. This is included for practical reasons but not entirely recommended.

*Recommended: Add the table via WP-CLI*

It is recommended the table be added to sites using WP-CLI and to turn off the attempted table creation on page load.

Once tables have been created on the server, the action to create the table can be unhooked.

```
add_action( 'rest_api_init', function() {
	remove_action( 'rest_api_init', 'PWCC\\RtcTableTesting\\maybe_create_table', 5 );
}, 4 );
```

On a single site install or WordPress run the command:

```
wp rtc-table-testing create-collaboration-table
```

On a multisite installation the table needs to be added to each site being tested.

If you are testing on all sites in a multisite install, then you can run the command:

```
for site in $(wp site list --field=url);
  do wp rtc-table-testing create-collaboration-table --url="$site";
done;
```

## Changelog

### 1.1.0

* Added: Create new table when a new Multisite sub-site is created.
* Developer: Add `@wordpress/env` for local development.

### 1.0.0

Initial release.

