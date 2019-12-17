<?php

/**
 * File containing core functionality.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

// phpcs:disable
add_action('init', __NAMESPACE__ . '\\init_dxp_dashboard');
add_action('login_redirect', __NAMESPACE__ . '\\set_dxp_meta_on_login', 10, 3);
add_action('admin_init', __NAMESPACE__ . '\\limit_admin_color_options', 1);
add_filter('get_user_option_admin_color', __NAMESPACE__ . '\\force_user_color');
add_filter('admin_footer_text', __NAMESPACE__ . '\\override_admin_footer_text', 11);
add_filter('update_footer', __NAMESPACE__ . '\\override_update_footer', 11);
// phpcs:enable

/**
 * Method to return DXP version details under the form of an object
 * containing the 'current' (string), 'upgrade' (bool) and 'url'  properties.
 *
 * @return object
 */
function get_dxp_version_details()
{
	/*
	TODO: retrieve this information from a transient that's set by a cron job
	that actually checks against the dxp repo and retrieves the details of the latest version.
	*/
	return (object) [
		'current' => OSDXP_DASHBOARD_VER,
		'upgrade' => false,
		'url'     => OSDXP_DASHBOARD_SITE,
	];
}

/**
 * Method to set dxp meta to true when user logs in
 * Checks if the user has no dxp meta set
 *
 *
 * @return void
 */
function set_dxp_meta_on_login($redirect_to, $request, $user)
{
	if (
		($user instanceof \WP_User && user_can($user, 'edit_posts'))
		&& (
			! array_key_exists('dxp-dashboard', get_metadata('user', $user->ID))
			|| get_user_meta($user->ID, 'dxp-dashboard', true) === '1'
		)
	) {
			$redirect_to = esc_url(self_admin_url('?dxp=on'));
	}
	return $redirect_to;
}

/**
 * Method to check and activate the DXP Dashboard, if the user is accessing a DXP Dashboard page,
 * or if the user has switched to the DXP Dashboard.
 *
 * We are using the 'init' action and not the 'admin_init', because the latter happens to late,
 * and we'll want to execute this code as soon as possible,
 * for example, before the admin_menu action.
 *
 * @return void
 */
function init_dxp_dashboard()
{
	// Only execute the following code on the admin.
	if (! is_admin()) {
		return;
	}

	$dxp = false;
	$dxp_menu_pages = get_dxp_menu_pages();

	if ($dxp_menu_pages && is_array($dxp_menu_pages)) {
		//get the path without parameters
		$url = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

		//get endpoint
		$page = explode('/', $url)[2];

		//check if on multisite
		$network = ($page === 'network');

		//set denied pages
		$denied_pages = [
			'edit-comments.php',
			'plugins.php',
			'plugin-install.php',
			'plugin-editor.php'
		];

		//check if page is denied
		$page = !(in_array($page, $denied_pages));

		//check if query has dxp param
		if (isset($_REQUEST['dxp'])) { // phpcs:ignore
			$dxp = ($_REQUEST['dxp'] === 'on') ? true : false; // phpcs:ignore

		//check if on network dashboard
		} elseif ($network) {
			$dxp = true;

		//check if user opted out of dxp
		} elseif (! is_dxp_dashboard_active_for_current_user()) {
			$dxp = false;

		//check if accepted pages
		} elseif ($page) {
			$dxp = true;

		// check if page parameter is in dxp pages
		} elseif (isset($_REQUEST['page']) && false !== array_search($_REQUEST['page'], array_column($dxp_menu_pages, 'menu_slug'))) { // phpcs:ignore
			$dxp = true;
		}
	}

		//define the constant to if we're dxp or not
	if (apply_filters('osdxp_dashboard_is_active', true) && ! defined('OSDXP_DASHBOARD_IS_ACTIVE')) {
		define('OSDXP_DASHBOARD_IS_ACTIVE', $dxp);
	}
}

/**
 * Method to override the "Thank you for creating with WordPress." admin footer text with the DXP admin footer text.
 *
 * @param string $text Admin footer text.
 *
 * @return string
 */
function override_admin_footer_text($text)
{
	if (!is_dxp_dashboard()) {
		return $text;
	}

	$text = sprintf(
		// Translators: %s - Open Source DXP website.
		__('<a href="%1s">Open Source DXP</a>. Powered by <a href="%2s">WordPress</a>', 'osdxp-dashboard'),
		OSDXP_DASHBOARD_SITE,
		'https://wordpress.org'
	);

	return sprintf(
		'<span id="footer-thankyou">%s</span>',
		$text
	);
}

/**
 * Method to override the admin footer WordPress version text with the DXP version text.
 *
 * @param string $text Admin footer version text.
 *
 * @return string
 */
function override_update_footer($text)
{
	if (!is_dxp_dashboard()) {
		return $text;
	}

	// Set the DXP version text.
	if (current_user_can('update_plugins')) {
		$dxp_version = get_dxp_version_details();

		if (!empty($dxp_version->upgrade)) {
			$dxp_text = sprintf(
				// Translators: %1$s: installed DXP version, %2$s: DXP URL, %3$s: latest DXP version.
				esc_html__(
					'Open Source DXP %1$s installed. <a href="%2$s">Get current version, %3$s</a>.',
					'osdxp-dashboard'
				),
				OSDXP_DASHBOARD_VER,
				esc_url($dxp_version->url),
				$dxp_version->current
			);
		}
	}

	// DXP version text fallback.
	if (empty($dxp_text)) {
		$dxp_text = sprintf(
			__('Open Source DXP %1$s installed.', 'osdxp-dashboard'),
			OSDXP_DASHBOARD_VER
		);
	}

	// Set the WP version text.
	if (current_user_can('update_core')) {
		$version = get_preferred_from_update_core();

		if (!is_object($version)) {
			$version = new \stdClass();
		}

		if (!isset($version->current)) {
			$version->current = '';
		}

		if (!isset($version->url)) {
			$version->url = '';
		}

		if (!isset($version->response)) {
			$version->response = '';
		}

		if ('development' === $version->response) {
			$wp_text = sprintf(
				// Translators: %1$s: WordPress version number, %2$s: WordPress updates admin screen URL.
				esc_html__(
					'You are using a development version (%1$s). Cool! Please <a href="%2$s">stay updated</a>.',
					'osdxp-dashboard'
				),
				get_bloginfo('version', 'display'),
				network_admin_url('update-core.php')
			);
		} elseif ('upgrade' === $version->response) {
			$wp_text = sprintf(
				wp_kses(
					// phpcs:ignore Translators: %1$s: installed WordPress version, %2$s: WP core update page, %3$s: latest WordPress version.
					__('WordPress %1$s installed. <a href="%2$s">Get current version, %3$s</a>.', 'osdxp-dashboard'),
					[
						'a' => [
							'href' => [],
						],
					]
				),
				get_bloginfo('version', 'display'),
				network_admin_url('update-core.php'),
				$version->current
			);
		}
	}

	// WP version text fallback.
	if (empty($wp_text)) {
		$wp_text = sprintf(
			// Translators: %s: installed WordPress version.
			esc_html__('WordPress %s installed.', 'osdxp-dashboard'),
			get_bloginfo('version', 'display')
		);
	}

	return $dxp_text . '<br>' . $wp_text;
}

/**
 * Method to remove admin color theme picker if on osDXP.
 *
 * @return void
 */
function limit_admin_color_options()
{
	if (!is_dxp_dashboard()) {
		return;
	}
	global $_wp_admin_css_colors;

	$fresh_color_data = $_wp_admin_css_colors['fresh'];
	$fresh_color_data->icon_colors = [
		'base' => '#fff',
		'focus' => '#3C15B4',
		'current' => '#3C15B4',
	];

	$_wp_admin_css_colors = array( 'fresh' => $fresh_color_data );
}

/**
 * Method to force color theme if on osDXP.
 *
 * @param  $color string of color theme
 * @return string
 */
function force_user_color($color)
{
	return is_dxp_dashboard() ? 'fresh' : $color;
}
