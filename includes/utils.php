<?php

/**
 * File containing various functions used throughout the plugin.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

// phpcs:disable
/**
 * Hooks needed for various reasons
 *
 * @see  below
 */
add_action('init', __NAMESPACE__ . '\\app_output_buffer');
add_action('init', __NAMESPACE__ . '\\hook_update_check');
add_action('admin_init', __NAMESPACE__ . '\\osdxp_activation_redirect');
add_action('init', __NAMESPACE__ . '\\app_output_buffer');
// phpcs:enable

/**
 * Hooks update checks
 * Need get_plugins()
 *
 * @see init_update_check
 */
function hook_update_check()
{
	//hook update check for OSDXP-DASHBOARD
	$osdxpUpdater = new OsdxpModuleUpdateChecker(
		OSDXP_DASHBOARD_UPDATE_URL,
		OSDXP_DASHBOARD_DIR . 'osdxp-dashboard.php',
		'osdxp-dashboard'
	);
}

//headers already sent bugfix. reference: https://tommcfarlin.com/wp_redirect-headers-already-sent/
function app_output_buffer()
{
	ob_start();
}
/**
 * Method to return true if the current user is accessing the DXP Dashboard.
 *
 * @return bool
 *
 * @see init_dxp_dashboard()
 */
function is_dxp_dashboard()
{
	return defined('OSDXP_DASHBOARD_IS_ACTIVE') && OSDXP_DASHBOARD_IS_ACTIVE;
}

/**
 * Check if the current user
 *
 * @return bool
 */
function is_dxp_dashboard_active_for_current_user()
{
	$user_id = get_current_user_id();

	if (! $user_id) {
		return false;
	}

	$dxp_dashboard = get_user_meta($user_id, 'dxp-dashboard', true);

	return (bool) $dxp_dashboard;
}

/**
 * Sets user meta for the dxp dashboard
 *
 * @param  bool $switch (optional) Switch user meta
 * @return  void
 */
function set_dxp_meta_for_user(bool $switch = null)
{
	$user_id = get_current_user_id();

	if (! $user_id) {
		return false;
	}

	if ($switch) {
		//set the meta based on arg
		update_user_meta($user_id, 'dxp-dashboard', $switch);
	} else {
		//switch the meta if arg not provided
		$switch = !is_dxp_dashboard_active_for_current_user();
		update_user_meta($user_id, 'dxp-dashboard', $switch);
	}
}

/**
 * Unsets constants on deactivation
 *
 *  @see  osdxp-dashboard.php
 *  @return  void
 */
function osdxp_deactivate()
{
	//deletes metadata
	delete_metadata(
		'user', //for users
		0, //not required
		'dxp-dashboard', //delete dxp-dashboard metadata
		'', //not required
		true //delete metadata from all users
	);

	//deletes transient
	delete_transient(OSDXP_DASHBOARD_AVAILABLE_MODULES_TRANSIENT);
	//deletes scheduled cronjob
	wp_clear_scheduled_hook('activate_available_modules_cron_event');
}

/**
 * Set activation hook to register option
 * Redirect to dashboard based on option
 *
 *  @see  osdxp-dashboard.php
 *  @see  osdxp_activation_redirect in utils.php
 *
 *  @return  void
 */
function osdxp_activate()
{
	//activate dxp on plugin activation
	if (!is_network_admin()) {
		add_option('osdxp_activation_redirect', true);
	}
	//schedule cron event
	if (! wp_next_scheduled('activate_available_modules_cron_event')) {
		wp_schedule_event(
			time(),
			OSDXP_DASHBOARD_AVAILABLE_MODULES_CRON_SCHEDULE,
			'activate_available_modules_cron_event'
		);
	}
	//add cron action
	add_action(
		'activate_available_modules_cron_event',
		function () {
			require_once OSDXP_DASHBOARD_DIR . '/includes/class-osdxp-render-available-modules.php';
			$osdxp_available_module = new OSDXPAvailableModules();
			$osdxp_available_module->getModulesData();
		}
	);
}

/**
 * Redirect to dashboard on plugin activation
 * Delete option for cleanup
 *
 *  @see  osdxp-dashboard.php
 *  @see  osdxp_activate in utils.php
 *
 *  @return  void
 */
function osdxp_activation_redirect()
{
	//ignore multisite since it will always be dxp on network dashboard
	if (get_option('osdxp_activation_redirect', false) && !is_network_admin()) {
		delete_option('osdxp_activation_redirect');
		wp_safe_redirect(esc_url(self_admin_url('?dxp=on')));
		exit;
	}
}

/**
 * Method to return the escaped license field markup.
 *
 * @param string $license_field_markup License field markup.
 *
 * @return string
 */
function esc_license_field_markup($license_field_markup)
{
	return wp_kses(
		$license_field_markup,
		[
			'button' => [
				'class' => [],
			],
			'div' => [
				'class' => [],
			],
			'input' => [
				'class' => [],
				'data-module' => [],
				'id' => [],
				'size' => [],
				'type' => [],
			],
			'label' => [
				'for' => [],
			],
			'strong' => [],
		]
	);
}

/**
 * Method to return the escaped license key markup.
 *
 * @param string $license_key_markup License key markup.
 *
 * @return string
 */
function esc_license_key_markup($license_key_markup)
{
	return wp_kses(
		$license_key_markup,
		[
			'a' => [
				'class' => [],
				'data-module' => [],
				'href' => [],
			],
			'p' => [
				'class' => [],
			],
			'strong' => [],
		]
	);
}

/**
 * Extend array_change_key_case for multidimensional arrays
 *
 * @param  array $arr multidimensional array
 *
 * @return array $array with keys changed
 */
function array_change_key_case_recursive($arr)
{
	return array_map(function ($item) {
		if (is_array($item)) {
			$item = array_change_key_case_recursive($item);
		}
		return $item;
	}, array_change_key_case($arr));
}
