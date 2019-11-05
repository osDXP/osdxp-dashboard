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
	$module_names = array_column($osdxp_modules, 'name');
	$module_slugs = array_keys($osdxp_modules);

	foreach ($plugins as $slug => $plugin_data) {
		$name_key = array_search($plugin_data['Name'], $module_names);
		$module_name = (false !== $name_key) ? $module_names[$name_key] : in_array($slug, $module_slugs, true);

		// An osDXP module but not osDXP dashboard.
		if ($module_name && 'Open Source DXP Dashboard' !== $module_name) {
			unset($plugins[$slug]);
		}
	}

	return $plugins;
}

/**
 * Method to display DXP modules
 *
 * @param array $plugins Plugins array.
 *
 * @return array
 *
 * @see class-osdxp-modules-list.php apply_filters( 'osdxp_get_modules', get_plugins() )
 */
function filter_modules($plugins)
{
	if (!$plugins || !is_array($plugins)) {
		return [];
	}

	$modules = get_osdxp_available_modules();
	$module_names = array_combine(array_keys($modules), array_column($modules, 'name'));

	foreach ($plugins as $slug => $module_data) {
		if (isset($modules[$slug])) {
			$available_module_data = $modules[$slug];
		} elseif (false !== $name_key = array_search($module_data['Name'], $module_names)) {
			$available_module_data = $modules[$name_key];
		} else {
			$available_module_data = false;
		}

		// Not an OSDXP module.
		if (false === $available_module_data) {
			unset($plugins[$slug]);
			continue;
		} elseif (!empty($available_module_data['logo'])) {
			$module_data['logo'] = $available_module_data['logo'];
		}

		//set name from endpoint
		$module_data['Name'] = $available_module_data['name'];
		//set logo to placeholder if missing
		$module_data['logo'] = empty($module_data['logo'])
			? OSDXP_DASHBOARD_PLACEHOLDER_IMAGE_URL
			: esc_url($module_data['logo']);
		$module_data['logo'] = '<img src="' . $module_data['logo'] . '">';

		$plugins[$slug] = $module_data;
	}

	return $plugins;
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
