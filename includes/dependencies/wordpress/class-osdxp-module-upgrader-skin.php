<?php

/**
 * Upgrader API: OSDXP_Module_Upgrader_Skin class
 *
 * @see  class-wp-plugin-upgrader-skin.php
 *
 * @package osdxp-dashboard
 */

/**
 * Module Upgrader Skin for DXP Module Upgrades.
 *
 * @see WP_Upgrader_Skin
 */

/** Plugin_Upgrader_Skin class */
// phpcs:disable
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
// phpcs:enable

class OSDXP_Module_Upgrader_Skin extends WP_Upgrader_Skin
{
	public $plugin                = '';
	public $plugin_active         = false;
	public $plugin_network_active = false;
	/**
	 * @param array $args
	 */
	public function __construct($args = array())
	{
		$defaults = array(
			'url'    => '',
			'module' => '',
			'nonce'  => '',
			'title'  => __('Update Module'),
		);
		$args     = wp_parse_args($args, $defaults);

		$this->module = $args['module'];

		$this->module_active         = is_plugin_active($this->module);
		$this->module_network_active = is_plugin_active_for_network($this->module);

		parent::__construct($args);
	}

	public function header()
	{
		if ($this->done_header) {
			return;
		}
		$this->done_header = true;
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__('Update Module') . '</h1>';
	}

	public function after()
	{
		$this->module = $this->upgrader->plugin_info();
		if (! empty($this->module) && ! is_wp_error($this->result) && $this->module_active) {
			if ('osdxp-dashboard/osdxp-dashboard.php' === $this->module) {
				echo '<iframe title="' . esc_attr__('Update progress') . '" style="border:0;overflow:hidden" width="100%" height="30" src="' . wp_nonce_url('update.php?action=activate-plugin&networkwide=' . $this->module_network_active . '&plugin=' . urlencode($this->module), 'activate-plugin_' . $this->module) . '"></iframe>'; // phpcs:ignore
			} else {
				// Currently used only when JS is off for a single plugin update?
				echo '<iframe title="' . esc_attr__('Update progress') . '" style="border:0;overflow:hidden" width="100%" height="18" src="' . esc_url_raw(wp_nonce_url('admin.php?page=dxp-modules-update&noheader&action=activate-module&networkwide=' . $this->module_network_active . '&module=' . urlencode($this->module), 'activate-module_' . $this->module)) . '"></iframe>'; // phpcs:ignore
			}
		}

		$this->decrement_update_count('plugin');
		if ('osdxp-dashboard/osdxp-dashboard.php' === $this->module) {
			$update_actions = array(
				'activate_module' => '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . urlencode($this->module), 'activate-plugin_' . $this->module) . '" target="_parent">' . __('Activate Module') . '</a>', // phpcs:ignore
				'plugins_page'    => '<a href="' . self_admin_url('admin.php?page=dxp-modules-installed') . '" target="_parent">' . __('Return to Modules page') . '</a>', // phpcs:ignore
			);
		} else {
			$update_actions = array(
				'activate_module' => '<a href="' . esc_url_raw(wp_nonce_url('admin.php?page=dxp-modules-installed&action=activate&module=' . urlencode($this->module), 'activate-module_' . $this->module)) . '" target="_parent">' . __('Activate Module') . '</a>', // phpcs:ignore
				'plugins_page'    => '<a href="' . self_admin_url('admin.php?page=dxp-modules-installed') . '" target="_parent">' . __('Return to Modules page') . '</a>', // phpcs:ignore
			);
		}

		if ($this->module_active || ! $this->result || is_wp_error($this->result) || ! current_user_can('activate_plugin', $this->module)) { // phpcs:ignore
			unset($update_actions['activate_module']);
		}

		/**
		 * Filters the list of action links available following a single plugin update.
		 *
		 * @since 2.7.0
		 *
		 * @param string[] $update_actions Array of plugin action links.
		 * @param string   $plugin         Path to the plugin file relative to the plugins directory.
		 */
		$update_actions = apply_filters('update_plugin_complete_actions', $update_actions, $this->module);

		if (! empty($update_actions)) {
			$this->feedback(implode(' | ', (array) $update_actions));
		}
	}
}
