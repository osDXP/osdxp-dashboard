<?php

/**
 * File containing licensing functionality.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

// phpcs:disable
add_action('init', __NAMESPACE__ . '\\init_update_checker');
add_action('admin_notices', __NAMESPACE__ . '\\render_plugins_license_errors');
add_action('rest_api_init', __NAMESPACE__ . '\\register_rest_endpoints');

add_filter('osdxp_dashboard_license_data', __NAMESPACE__ . '\\filter_license_data', 10, 2);
add_filter('osdxp_dashboard_license_key_markup', __NAMESPACE__ . '\\filter_license_key_markup', 10, 2);
add_filter('osdxp_dashboard_license_deletion_response', __NAMESPACE__ . '\\process_license_key_deletion', 10, 2);
add_filter('osdxp_dashboard_license_submit_response', __NAMESPACE__ . '\\process_license_key_submit', 10, 3);
add_filter('osdxp_license_key', __NAMESPACE__ . '\\filter_license_key', 10, 2);
// phpcs:enable

/**
 * Method to filter license data.
 *
 * @param array $license_data
 * @param $plugin_slug
 *
 * @return array|null|false
 */
function filter_license_data($license_data, $plugin_slug)
{
	if (is_internal_licensed_plugin($plugin_slug)) {
		return get_site_option(get_license_data_option_name($plugin_slug));
	}

	return $license_data;
}

/**
 * Method to filter license key.
 *
 * @param string $license_key License key.
 * @param string $plugin_slug Plugin slug.
 *
 * @return string
 */
function filter_license_key($license_key, $plugin_slug)
{
	if (!is_internal_licensed_plugin($plugin_slug)) {
		return $license_key;
	}

	// Display the license input field if there is a license error.
	if (get_site_transient(get_license_error_transient_name($plugin_slug))) {
		return null;
	}

	return get_license_key($plugin_slug);
}

/**
 * Method to get license key markup.
 *
 * @param string $license_key License key.
 * @param string $plugin_slug Plugin slug.
 *
 * @return string
 */
function filter_license_key_markup($license_key, $plugin_slug)
{
	$html = <<<'HTML'
<p class="display-license">
	<strong>%1$s:</strong> %2$s
	<a class="js-osdxp-module-remove-license button-primary" data-module="%3$s" href="#">%4$s</a>
</p>
HTML;

	return sprintf(
		$html,
		esc_html__('License Key', 'osdxp-dashboard'),
		esc_html($license_key),
		esc_attr($plugin_slug),
		esc_html__('Remove Key', 'osdxp-dashboard')
	);
}

/**
 * Method to get license key name.
 *
 * @param string $plugin_slug Plugin slug.
 *
 * @return string License key name
 */
function get_license_data_option_name($plugin_slug)
{
	return OSDXP_DASHBOARD_LICENSE_DATA_OPTION_NAME . '_' . $plugin_slug;
}

/**
 * Method to get the transient name for a license error.
 *
 * @param string $plugin_slug Plugin slug.
 *
 * @return string
 */
function get_license_error_transient_name($plugin_slug)
{
	return OSDXP_DASHBOARD_LICENSE_ERROR_TRANSIENT_NAME . '_' . $plugin_slug;
}

/**
 * Method to get license key.
 *
 * @param string $plugin_slug Plugin slug.
 *
 * @return mixed
 */
function get_license_key($plugin_slug)
{
	$license_key = get_site_option(get_license_key_option_name($plugin_slug));

	/*
	If the license key is false, we're returning null, so that the license key input is displayed,
	as false would trigger it to not render at all.

	See apply_filters('osdxp_license_key'...) in
	wp-content/plugins/osdxp-dashboard/includes/dependencies/wordpress/class-osdxp-modules-list-table.php
	*/
	if (false === $license_key) {
		return null;
	}

	return $license_key;
}

/**
 * Method to get license key name.
 *
 * @param string $plugin_slug Plugin slug.
 *
 * @return string License key name
 */
function get_license_key_option_name($plugin_slug)
{
	return OSDXP_DASHBOARD_API_KEY_OPTION . '_' . $plugin_slug;
}

/**
 * Method to handle license deletion.
 *
 * @param \WP_REST_Request $request REST request object.
 *
 * @return array
 */
function handle_license_deletion(\WP_REST_Request $request)
{
	$plugin_slug = $request->get_param('plugin_slug');
	$response = [];
	/**
	 * Filter the license deletion response.
	 *
	 * Using this filter you can intercept the license deletion request, process it, and return a response.
	 *
	 * @param array $response Response array.
	 * @param string $plugin_slug Plugin slug.
	 */
	$response = apply_filters('osdxp_dashboard_license_deletion_response', $response, $plugin_slug);

	return $response;
}

/**
 * Method to handle license submit.
 *
 * @param \WP_REST_Request $request REST request object.
 *
 * @return array
 */
function handle_license_submit(\WP_REST_Request $request)
{
	$plugin_slug = $request->get_param('plugin_slug');
	$license_key = $request->get_param('license_key');

	/**
	 * Filter the license submit response.
	 *
	 * Using this filter you can intercept the license key, process it, and return a response.
	 *
	 * The possible keys that can be in the response are:
	 * - license_key_markup: if present, the license key field will be replaced with this markup string;
	 * - error_messages: an array of error messages;
	 * - success_message: an array of success messages.
	 *
	 * @param array $response Response array.
	 * @param string $plugin_slug Plugin slug.
	 * @param string $license_key License key.
	 */
	$response = [];
	$response = apply_filters('osdxp_dashboard_license_submit_response', $response, $plugin_slug, $license_key);

	if (!empty($response['license_key_markup'])) {
		$response['license_key_markup'] = esc_license_key_markup($response['license_key_markup']);
	}

	return $response;
}

/**
 * Method to init update checker.
 *
 * Callback for the 'init' filter.
 *
 * @return void
 */
function init_update_checker()
{
	/**
	 * Filter plugins that will use the OSDXP Dashboard plugin update functionality.
	 *
	 * @param array $plugins An array of plugins slugs.
	 */
	$plugins = apply_filters('osdxp_dashboard_plugin_update_checker_list', []);

	// Do not continue if we don't have any internal plguins or if we lack a valid api key.
	if (!$plugins || !is_array($plugins)) {
		return;
	}
	foreach ($plugins as $plugin_slug) {
		(new LicenseAPI($plugin_slug))->initUpdateChecker();
	}
}

/**
 * Method to determine if a plugin is an internal licensed plugin.
 *
 * @param string $plugin_slug Plugin slug.
 *
 * @return bool
 */
function is_internal_licensed_plugin($plugin_slug)
{
	/**
	 * Filter internal licensed plugins.
	 *
	 * @param array $plugins An array of plugins slugs.
	 */
	$plugins = apply_filters('osdxp_dashboard_internal_licensed_plugins', []);

	return $plugins && is_array($plugins) && in_array($plugin_slug, $plugins, true);
}

/**
 * Method to process license key deletion for internal plugins.
 *
 * Callback for the 'osdxp_dashboard_license_deletion_response' filter.
 *
 * @param array $response Response.
 * @param string $plugin_slug Plugin slug.
 *
 * @return array Response array.
 */
function process_license_key_deletion($response, $plugin_slug)
{
	// Return response as-is if there are no internal plugins or if the provided plugin slug was not found.
	if (!is_internal_licensed_plugin($plugin_slug)) {
		return $response;
	}

	if (!is_array($response)) {
		$response = [];
	}

	remove_license_key_and_data($plugin_slug);

	$response['success'] = 1;
	$response['success_messages'][] = esc_html__('License successfully removed.', 'osdxp-dashboard');

	return $response;
}

/**
 * Method to process license key submit for internal plugins.
 *
 * Callback for the 'osdxp_dashboard_license_submit_response' filter.
 *
 * @param array $response Response.
 * @param string $plugin_slug Plugin slug.
 * @param string $license_key License key.
 *
 * @return array Response array.
 */
function process_license_key_submit($response, $plugin_slug, $license_key)
{
	// Return response as-is if there are no internal plugins or if the provided plugin slug was not found.
	if (!is_internal_licensed_plugin($plugin_slug)) {
		return $response;
	}

	if (!is_array($response)) {
		$response = [];
	}

	// Sanitize license key.
	$license_key = sanitize_text_field($license_key);

	// Verify license with the license server.
	$license_api = new LicenseAPI($plugin_slug);
	$license_api->setAPIKey($license_key);
	$license_data = $license_api->getAccount();

	if (is_wp_error($license_data)) {
		// Encountered a specific error.
		if (empty($response['error_messages']) || !is_array($response['error_messages'])) {
			$response['error_messages'] = [];
		}

		$response['error_messages'] = array_merge(
			$response['error_messages'],
			array_map('esc_html', $license_data->get_error_messages())
		);
	} elseif ($license_data) {
		// Save license key and license data.
		save_license_key_and_data($plugin_slug, $license_key, $license_data);

		// Get license key markup.
		$response['license_key_markup'] = apply_filters(
			'osdxp_dashboard_license_key_markup',
			$license_key,
			$plugin_slug
		);

		if (empty($response['success_messages']) || !is_array($response['success_messages'])) {
			$response['success_messages'] = [];
		}

		$response['success_messages'][] = esc_html__('License successfully added.', 'osdxp-dashboard');
	} else {
		// No error was encountered, but the license wasn't successfully added either.
		$response['error_messages'][] = esc_html__(
			'An error has occurred when trying to process your license key. Please try again later.',
			'osdxp-dashboard'
		);
	}

	return $response;
}

/**
 * Method to remove license key and data for a plugin.
 *
 * @param string $plugin_slug Plugin slug.
 *
 * @return void
 */
function remove_license_key_and_data($plugin_slug)
{
	delete_site_option(get_license_key_option_name($plugin_slug));
	delete_site_option(get_license_data_option_name($plugin_slug));

	// Delete any license errors.
	delete_site_transient(get_license_error_transient_name($plugin_slug));
}

/**
 * Method to render license errors.
 *
 * @return void
 */
function render_plugins_license_errors()
{
	/**
	 * Filter licensed plugins.
	 *
	 * @param array $plugins An array of plugins slugs.
	 */
	$plugins = apply_filters('osdxp_dashboard_licensed_plugins', []);

	if (!$plugins || !is_array($plugins)) {
		return;
	}

	foreach ($plugins as $plugin_slug) {
		$error = get_site_transient(get_license_error_transient_name($plugin_slug));

		if (!$error) {
			continue;
		}

		// Get plugin name.
		$plugin_name = apply_filters('osdxp_dashboard_plugin_name_' . $plugin_slug, null);

		$error = sprintf(
			'%s: %s',
			$plugin_name,
			$error
		);
		?>

		<div class="notice notice-error <?php echo esc_attr($plugin_slug); ?>-license-error">
			<p><?php echo wp_kses_post($error); ?></p>
		</div>

		<?php
	}
}

/**
 * Method to register license submit endpoint.
 *
 * @return void
 */
function register_rest_endpoints()
{
	register_rest_route(
		OSDXP_DASHBOARD_REST_NAMESPACE,
		'/license/(?P<plugin_slug>[-\w]+)/(?P<license_key>[-\w]+)',
		[
			'callback' => __NAMESPACE__ . '\\handle_license_submit',
			'methods' => \WP_REST_Server::CREATABLE,
			'permission_callback' => __NAMESPACE__ . '\\user_is_admin',
		]
	);

	register_rest_route(
		OSDXP_DASHBOARD_REST_NAMESPACE,
		'/license/(?P<plugin_slug>[-\w]+)',
		[
			'callback' => __NAMESPACE__ . '\\handle_license_deletion',
			'methods' => \WP_REST_Server::DELETABLE,
			'permission_callback' => __NAMESPACE__ . '\\user_is_admin',
		]
	);
}

/**
 * Method to save license key and data for a plugin.
 *
 * @param string $plugin_slug Plugin slug.
 * @param string $license_key License key.
 * @param array $license_data License data.
 *
 * @return void
 */
function save_license_key_and_data($plugin_slug, $license_key, $license_data)
{
	update_site_option(get_license_key_option_name($plugin_slug), $license_key);
	update_site_option(get_license_data_option_name($plugin_slug), $license_data);

	// Delete any license errors.
	delete_site_transient(get_license_error_transient_name($plugin_slug));
}

/**
 * Method to check if the current user is an administrator.
 *
 * @return bool
 */
function user_is_admin()
{
	return current_user_can('administrator');
}
