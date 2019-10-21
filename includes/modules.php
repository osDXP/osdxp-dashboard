<?php
/**
 * File containing modules functionality.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

// phpcs:disable
add_filter('osdxp_get_modules', __NAMESPACE__ . '\\filter_modules', 9999);
add_filter('all_plugins', __NAMESPACE__ . '\\filter_plugins', 9999);
// phpcs:enable

/**
 * Method to hide DXP modules from plugins list
 *
 * @param  array $plugins Plugins array
 */
function filter_plugins($plugins)
{
	if (!$plugins || !is_array($plugins)) {
		return [];
	}

	$osdxp_modules = get_osdxp_available_modules();
	$osdxp_modules_name = array_column($osdxp_modules, 'name');

	foreach ($plugins as $key => $plugin_data) {
		$is_osdxp_module = array_search($plugin_data['Name'], $osdxp_modules_name);

		// An OSDXP module.
		if (false !== $is_osdxp_module) {
			unset($plugins[$key]);
		}
	}

	return $plugins;
}

/**
 * Method to display DXP modules
 *
 * @param array $modules Modules array.
 *
 * @return array
 *
 * @see class-osdxp-modules-list.php apply_filters( 'osdxp_get_modules', get_plugins() )
 */
function filter_modules($modules)
{
	if (!$modules || !is_array($modules)) {
		return [];
	}

	$osdxp_modules = get_osdxp_available_modules();
	$osdxp_modules_name = array_combine(array_keys($osdxp_modules), array_column($osdxp_modules, 'name'));

	foreach ($modules as $key => $module_data) {
		$osdxp_key = array_search($module_data['Name'], $osdxp_modules_name);
		$available_module_data = (false !== $osdxp_key) ? $osdxp_modules[$osdxp_key] : $osdxp_key;

		// Not an OSDXP module.
		if (!isset($module_data['Name']) || empty($available_module_data)) {
			unset($modules[$key]);
			continue;
		} elseif (!empty($available_module_data['logo'])) {
			$module_data['logo'] = $available_module_data['logo'];
		}

		//set logo to placeholder if missing
		$module_data['logo'] = empty($module_data['logo'])
			? OSDXP_DASHBOARD_PLACEHOLDER_IMAGE_URL
			: esc_url($module_data['logo']);
		$module_data['logo'] = '<img src="' . $module_data['logo'] . '">';

		$modules[$key] = $module_data;
	}

	return $modules;
}

/**
 * Method to return an array of modules names.
 *
 * @return array
 */
function get_osdxp_available_modules()
{
	$osdxp_available_module = new OSDXPAvailableModules();
	$available_modules_json = $osdxp_available_module->getModulesData();
	$available_modules_array =  $osdxp_available_module->transformData($available_modules_json);

	if (!$available_modules_array || !is_array($available_modules_array)) {
		$available_modules_array = [];
	}

	if (!in_array(OSDXP_DASHBOARD_PLUGIN_NAME, array_column($available_modules_array, 'name'))) {
		$available_modules_array[OSDXP_DASHBOARD_PLUGIN_BASENAME] = [
			'name' => OSDXP_DASHBOARD_PLUGIN_NAME,
			'logo' => OSDXP_DASHBOARD_PLUGIN_LOGO
		];
	}

	return $available_modules_array;
}
