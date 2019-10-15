<?php
/**
 * Upgrader API: CF_Bulk_Module_Upgrader_Skin class
 *
 * @see  class-wp-bulk-plugin-upgrader-skin.php
 *
 * @package osdxp-dashboard
 */

/** Bulk_Upgrader_Skin class */
require_once ABSPATH . 'wp-admin/includes/class-bulk-upgrader-skin.php';

/**
 * Bulk Module Upgrader Skin for DXP Module Upgrades.
 *
 * @see Bulk_Upgrader_Skin
 */
class OSDXP_Bulk_Module_Upgrader_Skin extends Bulk_Upgrader_Skin
{
	public $module_info = array(); // Plugin_Upgrader::bulk_upgrade() will fill this in.

	public function add_strings()
	{
		parent::add_strings();

		/* translators: 1: name of module being updated, 2: number of updating module, 3: total number of modules being updated */
		$this->upgrader->strings['skin_before_update_header'] = __('Updating Module %1$s (%2$d/%3$d)');
	}


	/**
	 * @param string $title
	 */
	public function before($title = '')
	{
		parent::before($this->module_info['Title']);
	}

	/**
	 * @param string $title
	 */
	public function after($title = '')
	{
		parent::after($this->module_info['Title']);
		$this->decrement_update_count('plugin');
	}

	/**
	 */
	public function bulk_footer()
	{
		parent::bulk_footer();
		$update_actions = array(
			'modules_page' => '<a href="' . self_admin_url('admin.php?page=dxp-modules-installed') . '" target="_parent">' . __('Return to Installed Modules page') . '</a>',
			'available_modules_page' => '<a href="' . self_admin_url('admin.php?page=dxp-modules') . '" target="_parent">' . __('Return to Available Modules page') . '</a>',
		);
		if (! current_user_can('activate_plugins')) {
			unset($update_actions['modules_page']);
		}

		/**
		 * Filters the list of action links available following bulk plugin updates.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $update_actions Array of plugin action links.
		 * @param array    $plugin_info    Array of information for the last-updated plugin.
		 */
		$update_actions = apply_filters('update_bulk_plugins_complete_actions', $update_actions, $this->module_info);

		if (! empty($update_actions)) {
			$this->feedback(implode(' | ', (array) $update_actions));
		}
	}
}
