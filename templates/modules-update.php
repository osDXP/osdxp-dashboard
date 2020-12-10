<?php

/**
 * Update/Install Module administration panel.
 *
 * @see  wp-admin/update.php
 * @package osdxp-dashboard
 */

// phpcs:disable
if (! defined('IFRAME_REQUEST') && isset($_GET['action']) && in_array($_GET['action'], array( 'update-selected', 'activate-module' ))) {
	define('IFRAME_REQUEST', true);
}
// phpcs:enable

/** WordPress Administration Bootstrap */
require_once(ABSPATH . 'wp-admin/admin.php');

/** Module Upgrader Class */
require_once OSDXP_DASHBOARD_DIR . 'includes/dependencies/wordpress/class-osdxp-module-upgrader.php';

//filter footer text
add_filter('admin_footer_text', '__return_empty_string', 11);
add_filter('update_footer', '__return_empty_string', 11);

if (isset($_GET['action'])) {
	$module = isset($_REQUEST['module']) ? trim($_REQUEST['module']) : ''; // phpcs:ignore
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : ''; // phpcs:ignore

	if ('update-selected' == $action) {
		if (! current_user_can('update_plugins')) {
			wp_die(esc_html__('Sorry, you are not allowed to update modules for this site.'));
		}

		check_admin_referer('bulk-update-modules');

		if (isset($_GET['modules'])) {
			$modules = explode(',', stripslashes($_GET['modules']));
		} elseif (isset($_POST['checked'])) {
			$modules = (array) $_POST['checked'];
		} else {
			$modules = array();
		}

		$modules = array_map('urldecode', $modules);

		$url   = 'admin.php?page=dxp-modules-update&action=update-selected&modules=' . urlencode(implode(',', $modules)); // phpcs:ignore
		$nonce = 'bulk-update-modules';

		// wp_enqueue_script( 'updates' );
		iframe_header();

		$upgrader = new OSDXP_Module_Upgrader(new OSDXP_Bulk_Module_Upgrader_Skin(compact('nonce', 'url')));
		$upgrader->bulk_upgrade($modules);

		iframe_footer();
	} elseif ('upgrade-module' == $action) {
		if (! current_user_can('update_plugins')) {
			wp_die(esc_html__('Sorry, you are not allowed to update modules for this site.'));
		}

		check_admin_referer('upgrade-module_' . $module);

		$title        = esc_html__('Update Module');
		$parent_file  = 'admin.php?page=dxp-modules';
		$submenu_file = 'admin.php?page=dxp-modules-installed';

		// wp_enqueue_script( 'updates' );
		require_once(ABSPATH . 'wp-admin/admin-header.php');

		$nonce = 'upgrade-module_' . $module;
		$url   = 'admin.php?page=dxp-modules-update&noheader&action=upgrade-module&module=' . urlencode($module);

		$upgrader = new OSDXP_Module_Upgrader(
			new OSDXP_Module_Upgrader_Skin(compact('title', 'nonce', 'url', 'module'))
		);
		$upgrader->upgrade($module);

		include(ABSPATH . 'wp-admin/admin-footer.php');
	} elseif ('activate-module' == $action) {
		if (! current_user_can('update_plugins')) {
			wp_die(esc_html__('Sorry, you are not allowed to update modules for this site.'));
		}

		check_admin_referer('activate-module_' . $module);
		if (! isset($_GET['failure']) && ! isset($_GET['success'])) {
			wp_redirect(
				admin_url('admin.php?page=dxp-modules-update&noheader&action=activate-module&failure=true&module=' . urlencode($module) . '&_wpnonce=' . $_GET['_wpnonce']) // phpcs:ignore
			);
			activate_plugin($module, '', ! empty($_GET['networkwide']), true);
			wp_redirect(
				admin_url('admin.php?page=dxp-modules-update&noheader&action=activate-module&success=true&module=' . urlencode($module) . '&_wpnonce=' . $_GET['_wpnonce']) // phpcs:ignore
			);
			die();
		}
		iframe_header(esc_html__('Module Reactivation'), true);
		if (isset($_GET['success'])) {
			echo '<p style="margin:0">' . esc_html__('Module reactivated successfully.') . '</p>';
		}

		if (isset($_GET['failure'])) {
			echo '<p style="margin:0">' . esc_html__('Module failed to reactivate due to a fatal error.') . '</p>';

			error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR); // phpcs:ignore
			@ini_set('display_errors', true); //Ensure that Fatal errors are displayed.
			wp_register_plugin_realpath(WP_PLUGIN_DIR . '/' . $module);
			include(WP_PLUGIN_DIR . '/' . $module);
		}
		iframe_footer();
	} elseif ('upload-module' == $action) {
		if (! current_user_can('upload_plugins')) {
			wp_die(esc_html__('Sorry, you are not allowed to install modules on this site.'));
		}

		check_admin_referer('module-upload');

		$file_upload = new File_Upload_Upgrader('pluginzip', 'package');

		$title        = esc_html__('Upload Module');
		$parent_file  = 'admin.php?page=dxp-modules';
		$submenu_file = 'admin.php?page=dxp-modules-installed';
		require_once(ABSPATH . 'wp-admin/admin-header.php');

		$title = sprintf(
			esc_html__('Installing Module from uploaded file: %s'),
			esc_html(basename($file_upload->filename))
		);
		$nonce = 'module-upload';
		$url   = add_query_arg(
			array( 'package' => $file_upload->id ),
			'admin.php?page=dxp-modules-update&action=upload-module'
		);
		$type  = 'upload'; //Install module type, From Web or an Upload.

		$upgrader = new OSDXP_Module_Upgrader(
			new OSDXP_Module_Installer_Skin(compact('type', 'title', 'nonce', 'url'))
		);
		$result   = $upgrader->install($file_upload->package);

		if ($result || is_wp_error($result)) {
			$file_upload->cleanup();
		}

		include(ABSPATH . 'wp-admin/admin-footer.php');
	} else {
		/**
		 * Fires when a custom module update request is received.
		 *
		 * The dynamic portion of the hook name, `$action`, refers to the action
		 * provided in the request for wp-admin/update.php. Can be used to
		 * provide custom update functionality for modules.
		 *
		 */
		do_action("update-custom_{$action}");
	}
}
