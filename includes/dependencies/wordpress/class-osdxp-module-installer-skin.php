<?php
/**
 * Upgrader API: OSDXP_Module_Installer_Skin class
 *
 * @see  class-wp-plugin-installer-skin.php
 *
 * @package osdxp-dashboard
 */

/** Plugin_Installer_Skin class */
require_once ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';

/**
 * Module Installer Skin for DXP Module Installer.
 *
 * @see WP_Upgrader_Skin
 */
class OSDXP_Module_Installer_Skin extends Plugin_Installer_Skin
{

	/**
	 * @param array $args
	 */
	public function __construct($args = array())
	{
		$defaults = array(
			'type'   => 'upload',
			'url'    => '',
			'plugin' => '',
			'nonce'  => '',
			'title'  => '',
		);
		$args     = wp_parse_args($args, $defaults);

		$this->type = $args['type'];
		$this->api  = isset($args['api']) ? $args['api'] : array();

		parent::__construct($args);
	}


	public function before()
	{
		if (! empty($this->api)) {
			/* translators: 1: name of API, 2: version of API */
			$this->upgrader->strings['process_success'] = sprintf(__('Successfully installed the module <strong>%1$s %2$s</strong>.'), $this->api->name, $this->api->version);
		}
	}


	public function after()
	{
		$module_file = $this->upgrader->plugin_info();

		$install_actions = array();

		$install_actions['activate_plugin'] = '<a class="button button-primary" href="' . esc_url_raw(wp_nonce_url('admin.php?page=dxp-modules-installed&action=activate&amp;module=' . urlencode($module_file), 'activate-module_' . $module_file)) . '" target="_parent">' . __('Activate Module') . '</a>';

		if (is_multisite() && current_user_can('manage_network_plugins')) {
			$install_actions['network_activate'] = '<a class="button button-primary" href="' . esc_url_raw(wp_nonce_url('admin.php?page=dxp-modules-installed&action=activate&amp;networkwide=1&amp;module=' . urlencode($module_file), 'activate-module_' . $module_file)) . '" target="_parent">' . __('Network Activate') . '</a>';
			unset($install_actions['activate_plugin']);
		}

		$install_actions['plugins_page'] = '<a href="' . self_admin_url('admin.php?page=dxp-modules-installed') . '" target="_parent">' . __('Return to Installed Modules page') . '</a>';

		if (! $this->result || is_wp_error($this->result)) {
			unset($install_actions['activate_plugin'], $install_actions['network_activate']);
		} elseif (! current_user_can('activate_plugin', $module_file)) {
			unset($install_actions['activate_plugin']);
		}

		/**
		 * Filters the list of action links available following a single module installation.
		 *
		 * @param string[] $install_actions Array of module action links.
		 * @param object   $api             Object containing WordPress.org API module data. Empty
		 *                                  for non-API installs, such as when a module is installed
		 *                                  via upload.
		 * @param string   $module_file     Path to the module file relative to the modules directory.
		 */
		$install_actions = apply_filters('install_plugin_complete_actions', $install_actions, $this->api, $module_file);

		if (! empty($install_actions)) {
			$this->feedback(implode(' ', (array) $install_actions));
		}
	}
}
