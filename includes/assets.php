<?php
/**
 * File containing hooks and functionality related to assets.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

// phpcs:disable
// Enqueue the plugin assets for back-end.
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\load_admin_assets');
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\load_frontend_assets');

// Add the dxp-dashboard body class, if accessing a DXP page.
add_filter('admin_body_class', __NAMESPACE__ . '\\style_admin_dashboard');
// phpcs:enable

/**
 * Add the dxp-dashboard body class.
 * Set the user meta here to trigger transition classes
 * @param string $classes Classes string.
 *
 * @return string
 */
function style_admin_dashboard($classes)
{
	if (is_dxp_dashboard()) {
		//add dxp-dashboard class to dxp pages and dxp-transition class if user meta does no thave dxp set to TRUE
		$classes .= is_dxp_dashboard_active_for_current_user() ? ' dxp-dashboard' : ' dxp-dashboard dxp-transition-in';
		//set the user meta to the defined constant
		set_dxp_meta_for_user(OSDXP_DASHBOARD_IS_ACTIVE);
	} elseif (is_dxp_dashboard_active_for_current_user()) {
		 //apply transition if user meta has dxp TRUE but we're on a non dxp page
		$classes .= ' dxp-transition-out';
		//set the user meta to the defined constant
		set_dxp_meta_for_user(OSDXP_DASHBOARD_IS_ACTIVE);
	}



	return $classes;
}

/**
 * Load the plugin assets.
 *
 * @return void
 */
function load_admin_assets()
{

	if (is_dxp_dashboard()) {

		// Enqueue the custom plugin styles.
		wp_enqueue_style(
			OSDXP_DASHBOARD_HANDLE,
			OSDXP_DASHBOARD_URL . (WP_DEBUG ? 'build/style-admin.css' : 'build/style-admin.min.css'),
			[],
			filemtime(OSDXP_DASHBOARD_DIR . (WP_DEBUG ? 'build/style-admin.css' : 'build/style-admin.min.css')),
			false
		);

		// Register, localize and enqueue the custom plugin scripts.
		// This is the compiled js file.
		wp_register_script(
			OSDXP_DASHBOARD_HANDLE,
			OSDXP_DASHBOARD_URL . (WP_DEBUG ? 'build/app.js' : 'build/app.min.js'),
			[ 'jquery' ],
			filemtime(OSDXP_DASHBOARD_DIR . (WP_DEBUG ? 'build/app.js' : 'build/app.min.js')),
			true
		);

		wp_localize_script(
			OSDXP_DASHBOARD_HANDLE,
			OSDXP_DASHBOARD_LOCALIZED_OBJECT_NAME,
			[
				'restNonce' => wp_create_nonce('wp_rest'),
				'restUrl' => get_rest_url(null, OSDXP_DASHBOARD_REST_NAMESPACE),
				'text' => [
					'licenseKeyRemovalConfirmation' => esc_html__(
						'Are you sure you want to remove this license key?',
						'osdxp-dashboard'
					),
				],
			]
		);

		wp_enqueue_script(OSDXP_DASHBOARD_HANDLE);
	} else {
		wp_enqueue_style(
			OSDXP_DASHBOARD_HANDLE,
			OSDXP_DASHBOARD_URL . (WP_DEBUG ? 'build/style-wp-admin.css' : 'build/style-wp-admin.min.css'),
			[],
			filemtime(OSDXP_DASHBOARD_DIR . (WP_DEBUG ? 'build/style-wp-admin.css' : 'build/style-wp-admin.min.css')),
			false
		);
	}
}

/**
 * Method to load frontend assets.
 *
 * @return void
 */
function load_frontend_assets()
{
	if (is_dxp_dashboard_active_for_current_user()) {
		// Enqueue the plugin admin bar custom style.
		wp_enqueue_style(
			OSDXP_DASHBOARD_HANDLE . '-admin-bar',
			OSDXP_DASHBOARD_URL . (WP_DEBUG ? 'build/admin-bar.css' : 'build/admin-bar.min.css'),
			[ 'admin-bar' ],
			filemtime(OSDXP_DASHBOARD_DIR . (WP_DEBUG ? 'build/admin-bar.css' : 'build/admin-bar.min.css')),
			false
		);
	} else {
		wp_enqueue_style(
			OSDXP_DASHBOARD_HANDLE . '-wp-admin-bar',
			OSDXP_DASHBOARD_URL . (WP_DEBUG ? 'build/wp-admin-bar.css' : 'build/wp-admin-bar.min.css'),
			[ 'admin-bar' ],
			filemtime(OSDXP_DASHBOARD_DIR . (WP_DEBUG ? 'build/wp-admin-bar.css' : 'build/wp-admin-bar.min.css'))
		);
	}
}
