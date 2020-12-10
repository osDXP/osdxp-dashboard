<?php

/**
 * Module Information Iframe Panel
 *
 * @package osdxp-dashboard
 */

// phpcs:disable
if (! defined('IFRAME_REQUEST') && isset($_GET['tab']) && ( 'module-information' == $_GET['tab'] )) {
	define('IFRAME_REQUEST', true);
} else {
	exit;
}

/**
 * WordPress Administration Bootstrap.
 */
require_once(ABSPATH . '/wp-admin/admin.php');
require_once(OSDXP_DASHBOARD_DIR . '/includes/dependencies/wordpress/module-install.php');
require_once(OSDXP_DASHBOARD_DIR . '/includes/dependencies/wordpress/modules-update-information.php');

if (! current_user_can('install_plugins')) {
	wp_die(esc_html__('Sorry, you are not allowed to install modules on this site.', 'osdxp-dashboard'));
}

if (is_multisite() && ! is_network_admin()) {
	wp_redirect(network_admin_url('plugin-install.php'));
	exit();
}

display_module_information();
// phpcs:enable
