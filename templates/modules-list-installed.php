<?php
/**
 * Modules administration panel.
 *
 * @see  wp-admin/plugins.php
 * @package osdxp-dashboard
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
/** WordPress Administration Bootstrap */
require_once(ABSPATH . '/wp-admin/admin.php');

//globals required to display modules table
global $status, $totals, $page, $orderby, $order, $s, $user_ID, $modules;
?>
<div id="osdxp-module-upload-field" class="upload-plugin">
	<p class="install-help">
		<?php esc_html_e('If you have a module in a .zip format, you may install it by uploading it here.'); ?>
	</p>
	<form
		method="post"
		enctype="multipart/form-data"
		class="wp-upload-form"
		action="<?php echo esc_url(self_admin_url('admin.php?page=dxp-modules-update&action=upload-module')); ?>"
	>
		<?php wp_nonce_field('module-upload'); ?>
		<label class="screen-reader-text" for="pluginzip"><?php esc_html_e('Module zip file'); ?></label>
		<input type="file" id="pluginzip" name="pluginzip" />
		<?php submit_button(esc_html__('Install Now'), '', 'install-plugin-submit', false); ?>
	</form>
</div>
<?php
if (! current_user_can('activate_plugins')) {
	wp_die(esc_html__('Sorry, you are not allowed to manage modules for this site.'));
}

require_once(OSDXP_DASHBOARD_DIR . '/includes/dependencies/wordpress/class-osdxp-modules-list-table.php');
require_once(OSDXP_DASHBOARD_DIR . '/includes/dependencies/wordpress/modules-print-updates.php');

//print update notices
osdxp_module_update_rows();

use OSDXP_Dashboard\OSDXP_Modules_List_Table as OSDXP_Modules_List_Table;

$osdxp_modules_list_table = new OSDXP_Modules_List_Table();
$pagenum       = $osdxp_modules_list_table->get_pagenum();

$action = $osdxp_modules_list_table->current_action();

$module = isset($_REQUEST['module']) ? wp_unslash($_REQUEST['module']) : '';
$s      = isset($_REQUEST['s']) ? urlencode(wp_unslash($_REQUEST['s'])) : '';

$dxpmodule = 'osdxp-dashboard/osdxp-dashboard.php';
// Clean up request URI from temporary args for screen options/paging uri's to work as expected.
$_SERVER['REQUEST_URI'] = remove_query_arg(
	array( 'error', 'deleted', 'activate', 'activate-multi', 'deactivate', 'deactivate-multi', '_error_nonce' ),
	$_SERVER['REQUEST_URI']
);

if ($action) {
	switch ($action) {
		case 'activate':
			if (! current_user_can('activate_plugin', $module)) {
				wp_die(esc_html__('Sorry, you are not allowed to activate this module.'));
			}

			if (is_multisite() && ! is_network_admin() && is_network_only_plugin($module)) {
				wp_redirect(
					self_admin_url("admin.php?page=dxp-modules-installed&module_status=$status&paged=$page&s=$s")
				);
				exit;
			}

			check_admin_referer('activate-module_' . $module);

			$result = activate_plugin(
				$module,
				self_admin_url('admin.php?page=dxp-modules-installed&error=true&module=' . urlencode($module)),
				is_network_admin()
			);
			if (is_wp_error($result)) {
				if ('unexpected_output' == $result->get_error_code()) {
					$redirect = self_admin_url('admin.php?page=dxp-modules-installed&error=true&charsout=' . strlen($result->get_error_data()) . '&module=' . urlencode($module) . "&module_status=$status&paged=$page&s=$s"); // phpcs:ignore
					wp_redirect(
						add_query_arg(
							'_error_nonce',
							wp_create_nonce('module-activation-error_' . $module),
							$redirect
						)
					);
					exit;
				} else {
					wp_die(esc_url($result));
				}
			}

			if (! is_network_admin()) {
				$recent = (array) get_option('recently_activated');
				unset($recent[ $module ]);
				update_option('recently_activated', $recent);
			} else {
				$recent = (array) get_site_option('recently_activated');
				unset($recent[ $module ]);
				update_site_option('recently_activated', $recent);
			}

			if (isset($_GET['from']) && 'import' == $_GET['from']) {
				wp_redirect(self_admin_url('import.php?import=' . str_replace('-importer', '', dirname($module)))); // phpcs:ignore -- overrides the ?error=true one above and redirects to the Imports page, stripping the -importer suffix
			} elseif (isset($_GET['from']) && 'press-this' == $_GET['from']) {
				wp_redirect(self_admin_url('press-this.php'));
			} else {
				wp_redirect(self_admin_url("admin.php?page=dxp-modules-installed&activate=true&module_status=$status&paged=$page&s=$s")); // phpcs:ignore -- overrides the ?error=true one above
			}
			exit;

		case 'activate-selected':
			if (! current_user_can('activate_plugins')) {
				wp_die(esc_html_e('Sorry, you are not allowed to activate modules for this site.'));
			}

			check_admin_referer('bulk-modules');

			$modules = isset($_POST['checked']) ? (array) wp_unslash($_POST['checked']) : array();

			if (is_network_admin()) {
				foreach ($modules as $i => $module) {
					// Only activate modules which are not already network activated.
					if (is_plugin_active_for_network($module)) {
						unset($modules[ $i ]);
					}
				}
			} else {
				foreach ($modules as $i => $module) {
					// Only activate modules which are not already active and are not network-only when on Multisite.
					if (is_plugin_active($module) || ( is_multisite() && is_network_only_plugin($module) )) {
						unset($modules[ $i ]);
					}
					// Only activate modules which the user can activate.
					if (! current_user_can('activate_plugin', $module)) {
						unset($modules[ $i ]);
					}
				}
			}

			if (empty($modules)) {
				wp_redirect(
					self_admin_url("admin.php?page=dxp-modules-installed&module_status=$status&paged=$page&s=$s")
				);
				exit;
			}

			activate_plugins(
				$modules,
				self_admin_url('admin.php?page=dxp-modules-installed&error=true'),
				is_network_admin()
			);

			if (! is_network_admin()) {
				$recent = (array) get_option('recently_activated');
			} else {
				$recent = (array) get_site_option('recently_activated');
			}

			foreach ($modules as $module) {
				unset($recent[ $module ]);
			}

			if (! is_network_admin()) {
				update_option('recently_activated', $recent);
			} else {
				update_site_option('recently_activated', $recent);
			}

			wp_redirect(
				self_admin_url("admin.php?page=dxp-modules-installed&activate-multi=true&module_status=$status&paged=$page&s=$s") // phpcs:ignore
			);
			exit;

		case 'update-selected':
			check_admin_referer('bulk-modules');

			if (isset($_GET['modules'])) {
				$modules = explode(',', wp_unslash($_GET['modules']));
			} elseif (isset($_POST['checked'])) {
				$modules = (array) wp_unslash($_POST['checked']);
			} else {
				$modules = array();
			}

			$title       = __('Update Modules');


			// wp_enqueue_script( 'updates' );
			require_once(ABSPATH . 'wp-admin/admin-header.php');

			echo '<div class="wrap">';
			echo '<h1>' . esc_html($title) . '</h1>';

			$url = self_admin_url('admin.php?page=dxp-modules-update&noheader&action=update-selected&amp;modules=' . urlencode(join(',', $modules))); // phpcs:ignore
			$url = esc_url_raw(wp_nonce_url($url, 'bulk-update-modules'));

			echo "<iframe src='$url' style='width: 100%; height:100%; min-height:850px;'></iframe>"; // phpcs:ignore
			echo '</div>';
			// require_once(  ABSPATH . '/wp-admin/admin-footer.php' );
			exit;

		case 'error_scrape':
			if (! current_user_can('activate_plugin', $module)) {
				wp_die(esc_html_e('Sorry, you are not allowed to activate this module.'));
			}

			check_admin_referer('module-activation-error_' . $module);

			$valid = validate_plugin($module);
			if (is_wp_error($valid)) {
				wp_die(esc_url($valid));
			}

			if (! WP_DEBUG) {
				error_reporting(E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR); // phpcs:ignore
			}

			@ini_set('display_errors', true); //Ensure that Fatal errors are displayed.
			// Go back to "sandbox" scope so we get the same errors as before
			plugin_sandbox_scrape($module);
			/** This action is documented in wp-admin/includes/plugin.php */
			do_action("activate_{$module}");
			exit;

		case 'deactivate':
			if (! current_user_can('deactivate_plugin', $module)) {
				wp_die(esc_html__('Sorry, you are not allowed to deactivate this module.'));
			}

			check_admin_referer('deactivate-module_' . $module);

			if (! is_network_admin() && is_plugin_active_for_network($module)) {
				wp_redirect(
					self_admin_url("admin.php?page=dxp-modules-installed&module_status=$status&paged=$page&s=$s")
				);
				exit;
			}

			deactivate_plugins($module, false, is_network_admin());

			if (! is_network_admin()) {
				update_option(
					'recently_activated',
					array( $module => time() ) + (array) get_option('recently_activated')
				);
			} else {
				update_site_option(
					'recently_activated',
					array( $module => time() ) + (array) get_site_option('recently_activated')
				);
			}
			if ($module === $dxpmodule) {
				if (headers_sent()) {
					echo "<meta http-equiv='refresh' content='" . esc_attr("0;url=plugins.php?deactivate=true&plugin_status=$status&paged=$page&s=$s") . "' />"; // phpcs:ignore
				} else {
					wp_redirect(self_admin_url("plugins.php?deactivate=true&plugin_status=$status&paged=$page&s=$s"));
				}
			} else {
				if (headers_sent()) {
					echo "<meta http-equiv='refresh' content='" . esc_attr("0;url=admin.php?page=dxp-modules-installed&deactivate=true&module_status=$status&paged=$page&s=$s") . "' />"; // phpcs:ignore
				} else {
					wp_redirect(
						self_admin_url("admin.php?page=dxp-modules-installed&deactivate=true&module_status=$status&paged=$page&s=$s") // phpcs:ignore
					);
				}
			}

			exit;

		case 'deactivate-selected':
			if (! current_user_can('deactivate_plugins')) {
				wp_die(esc_html__('Sorry, you are not allowed to deactivate modules for this site.'));
			}

			check_admin_referer('bulk-modules');

			$modules = isset($_POST['checked']) ? (array) wp_unslash($_POST['checked']) : array();
			// Do not deactivate modules which are already deactivated.
			if (is_network_admin()) {
				$modules = array_filter($modules, 'is_plugin_active_for_network');
			} else {
				$modules = array_filter($modules, 'is_plugin_active');
				$modules = array_diff($modules, array_filter($modules, 'is_plugin_active_for_network'));

				foreach ($modules as $i => $module) {
					// Only deactivate modules which the user can deactivate.
					if (! current_user_can('deactivate_plugin', $module)) {
						unset($modules[ $i ]);
					}
				}
			}
			if (empty($modules)) {
				wp_redirect(
					self_admin_url("admin.php?page=dxp-modules-installed&module_status=$status&paged=$page&s=$s")
				);
				exit;
			}

			deactivate_plugins($modules, false, is_network_admin());

			$deactivated = array();
			foreach ($modules as $module) {
				$deactivated[ $module ] = time();
				if ($module === $dxpmodule) {
					$dxpmodule = true;
				}
			}

			if (! is_network_admin()) {
				update_option('recently_activated', $deactivated + (array) get_option('recently_activated'));
			} else {
				update_site_option('recently_activated', $deactivated + (array) get_site_option('recently_activated'));
			}

			if ($dxpmodule) {
				wp_redirect(self_admin_url("plugins.php?deactivate-multi=true&plugin_status=$status&paged=$page&s=$s"));
			} else {
				wp_redirect(
					self_admin_url("admin.php?page=dxp-modules-installed&deactivate-multi=true&module_status=$status&paged=$page&s=$s") // phpcs:ignore
				);
			}
			exit;

		case 'delete-selected':
			if (! current_user_can('delete_plugins')) {
				wp_die(esc_html__('Sorry, you are not allowed to delete modules for this site.'));
			}

			check_admin_referer('bulk-modules');

			//$_POST = from the module form; $_GET = from the FTP details screen.
			$modules = isset($_REQUEST['checked']) ? (array) wp_unslash($_REQUEST['checked']) : array();
			if (empty($modules)) {
				wp_redirect(
					self_admin_url("admin.php?page=dxp-modules-installed&module_status=$status&paged=$page&s=$s") // phpcs:ignore
				);
				exit;
			}

			$modules = array_filter($modules, 'is_plugin_inactive'); // Do not allow to delete Activated modules.
			if (empty($modules)) {
				wp_redirect(
					self_admin_url("admin.php?page=dxp-modules-installed&error=true&main=true&module_status=$status&paged=$page&s=$s") // phpcs:ignore
				);
				exit;
			}

			// Bail on all if any paths are invalid.
			// validate_file() returns truthy for invalid files
			$invalid_module_files = array_filter($modules, 'validate_file');
			if ($invalid_module_files) {
				wp_redirect(
					self_admin_url("admin.php?page=dxp-modules-installed&module_status=$status&paged=$page&s=$s") // phpcs:ignore
				);
				exit;
			}

			include(OSDXP_DASHBOARD_DIR . 'templates/modules-update.php');

			if (! isset($_REQUEST['verify-delete'])) {
				wp_enqueue_script('jquery');
				require_once(ABSPATH . 'wp-admin/admin-header.php');
				?>
			<div class="wrap">
				<?php
					$module_info              = array();
					$have_non_network_modules = false;
				foreach ((array) $modules as $module) {
					$module_slug = dirname($module);

					if ('.' == $module_slug) {
						if ($data = get_plugin_data(WP_PLUGIN_DIR . '/' . $module)) {
							$module_info[ $module ]                     = $data;
							$module_info[ $module ]['is_uninstallable'] = is_uninstallable_plugin($module);
							if (! $module_info[ $module ]['Network']) {
								$have_non_network_modules = true;
							}
						}
					} else {
						// Get modules list from that folder.
						if ($folder_modules = get_plugins('/' . $module_slug)) {
							foreach ($folder_modules as $module_file => $data) {
								$module_info[ $module_file ] = _get_plugin_data_markup_translate($module_file, $data);
								$module_info[ $module_file ]['is_uninstallable'] = is_uninstallable_plugin($module);
								if (! $module_info[ $module_file ]['Network']) {
									$have_non_network_modules = true;
								}
							}
						}
					}
				}
					$modules_to_delete = count($module_info);
				?>
				<?php if (1 == $modules_to_delete) : ?>
					<h1><?php esc_html_e('Delete Module'); ?></h1>
					<?php if ($have_non_network_modules && is_network_admin()) : ?>
						<div class="error">
							<p>
								<strong><?php esc_html_e('Caution:'); ?></strong>
								<?php esc_html_e('This module may be active on other sites in the network.'); ?>
							</p>
						</div>
					<?php endif; ?>
					<p><?php esc_html_e('You are about to remove the following module:'); ?></p>
				<?php else : ?>
					<h1><?php esc_html_e('Delete Modules'); ?></h1>
					<?php if ($have_non_network_modules && is_network_admin()) : ?>
						<div class="error">
							<p>
								<strong><?php esc_html_e('Caution:'); ?></strong>
								<?php esc_html_e('These modules may be active on other sites in the network.'); ?>
							</p>
						</div>
					<?php endif; ?>
					<p><?php esc_html_e('You are about to remove the following modules:'); ?></p>
				<?php endif; ?>
					<ul class="ul-disc">
						<?php
						$data_to_delete = false;
						foreach ($module_info as $module) {
							if ($module['is_uninstallable']) {
								/* translators: 1: module name, 2: module author */
								echo '<li>', sprintf(esc_html__('%1$s by %2$s (will also delete its data)'), '<strong>' . esc_attr($module['Name']) . '</strong>', '<em>' . esc_attr($module['AuthorName'])) . '</em>', '</li>'; // phpcs:ignore
								$data_to_delete = true;
							} else {
								/* translators: 1: module name, 2: module author */
								echo '<li>', sprintf(_x('%1$s by %2$s', 'module'), '<strong>' . esc_attr($module['Name']) . '</strong>', '<em>' . esc_attr($module['AuthorName'])) . '</em>', '</li>'; // phpcs:ignore
							}
						}
						?>
					</ul>
				<p>
				<?php
				if ($data_to_delete) {
					esc_html_e('Are you sure you wish to delete these files and data?');
				} else {
					esc_html_e('Are you sure you wish to delete these files?');
				}
				?>
				</p>
				<form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" style="display:inline;">
					<input type="hidden" name="verify-delete" value="1" />
					<input type="hidden" name="action" value="delete-selected" />
					<?php
					foreach ((array) $modules as $module) {
						echo '<input type="hidden" name="checked[]" value="' . esc_attr($module) . '" />';
					}
					?>
					<?php wp_nonce_field('bulk-modules'); ?>
					<?php
					submit_button(
						$data_to_delete
							? esc_html__('Yes, delete these files and data')
							: esc_html__('Yes, delete these files'),
						'',
						'submit',
						false
					);
					?>
				</form>
				<?php
				$referer = wp_get_referer();
				?>
				<form method="post" action="<?php echo $referer ? esc_url($referer) : ''; ?>" style="display:inline;">
					<?php submit_button(esc_html__('No, return me to the module list'), '', 'submit', false); ?>
				</form>
			</div>
				<?php
				require_once(ABSPATH . '/wp-admin/admin-footer.php');
				exit;
			} else {
				$modules_to_delete = count($modules);
			} // endif verify-delete

			$delete_result = delete_plugins($modules);
			//Store the result in a cache rather than a URL param due to object type & length
			set_transient('modules_delete_result_' . $user_ID, $delete_result);
			wp_redirect(
				self_admin_url("admin.php?page=dxp-modules-installed&deleted=$modules_to_delete&module_status=$status&paged=$page&s=$s") // phpcs:ignore
			);
			exit;

		case 'clear-recent-list':
			if (! is_network_admin()) {
				update_option('recently_activated', array());
			} else {
				update_site_option('recently_activated', array());
			}
			break;

		case 'resume':
			if (is_multisite()) {
				return;
			}

			if (! current_user_can('resume_plugin', $module)) {
				wp_die(esc_html_e('Sorry, you are not allowed to resume this module.'));
			}

			check_admin_referer('resume-module_' . $module);

			$result = resume_plugin(
				$module,
				self_admin_url("admin.php?page=dxp-modules-installed&error=resuming&module_status=$status&paged=$page&s=$s") // phpcs:ignore
			);

			if (is_wp_error($result)) {
				wp_die(esc_html($result));
			}

			wp_redirect(
				self_admin_url("admin.php?page=dxp-modules-installed&resume=true&module_status=$status&paged=$page&s=$s") // phpcs:ignore
			);
			exit;

		default:
			if (isset($_POST['checked'])) {
				check_admin_referer('bulk-modules');
				$modules  = isset($_POST['checked']) ? (array) wp_unslash($_POST['checked']) : array();
				$sendback = wp_get_referer();

				/** This action is documented in wp-admin/edit-comments.php */
				$sendback = apply_filters(
					'handle_bulk_actions-' . get_current_screen()->id,
					$sendback,
					$action,
					$modules
				);
				wp_safe_redirect($sendback);
				exit;
			}
			break;
	}
}

$osdxp_modules_list_table->prepare_items();

// wp_enqueue_script( 'plugin-install' );
add_thickbox();


$title       = esc_html__('Installed Modules');

require_once(ABSPATH . 'wp-admin/admin-header.php');
$invalid = validate_active_plugins();
if (! empty($invalid)) {
	foreach ($invalid as $module_file => $error) {
		echo '<div id="message" class="error"><p>';
		printf(
			/* translators: 1: module file, 2: error message */
			esc_html__('The module %1$s has been <strong>deactivated</strong> due to an error: %2$s'),
			'<code>' . esc_html($module_file) . '</code>',
			esc_html($error->get_error_message())
		);
		echo '</p></div>';
	}
}
?>

<?php
if (isset($_GET['error'])) :
	if (isset($_GET['main'])) {
		$errmsg = esc_html__('You cannot delete a module while it is active on the main site.');
	} elseif (isset($_GET['charsout'])) {
		$errmsg  = sprintf(
			_n(
				'The module generated %d character of <strong>unexpected output</strong> during activation.',
				'The module generated %d characters of <strong>unexpected output</strong> during activation.',
				$_GET['charsout']
			),
			$_GET['charsout']
		);
		$errmsg .= ' ' . esc_html__('If you notice &#8220;headers already sent&#8221; messages, problems with syndication feeds or other issues, try deactivating or removing this module.'); // phpcs:ignore
	} elseif ('resuming' === $_GET['error']) {
		$errmsg = __('Module could not be resumed because it triggered a <strong>fatal error</strong>.');
	} else {
		$errmsg = __('Module could not be activated because it triggered a <strong>fatal error</strong>.');
	}
	?>
	<div id="message" class="error"><p><?php echo esc_attr($errmsg); ?></p>
	<?php
	if (
		!isset($_GET['main'])
		&& !isset($_GET['charsout'])
		&& wp_verify_nonce($_GET['_error_nonce'], 'module-activation-error_' . $module)
	) {
		$iframe_url = add_query_arg(
			array(
				'action'   => 'error_scrape',
				'module'   => esc_url_raw(urlencode($module)),
				'_wpnonce' => esc_url_raw(urlencode($_GET['_error_nonce'])),
			),
			admin_url('admin.php?page=dxp-modules-installed')
		);
	?><iframe style="border:0" width="100%" height="70px" src="<?php echo esc_url($iframe_url); ?>"></iframe><?php // phpcs:ignore
	}
	?>
	</div>
	<?php
elseif (isset($_GET['deleted'])) :
		$delete_result = get_transient('modules_delete_result_' . $user_ID);
		// Delete it once we're done.
		delete_transient('modules_delete_result_' . $user_ID);

	if (is_wp_error($delete_result)) :
		?>
		<div id="message" class="error notice is-dismissible">
			<p>
				<?php
				printf(
					esc_html__('Module could not be deleted due to an error: %s'),
					esc_html($delete_result->get_error_message())
				);
				?>
			</p>
		</div>
	<?php else : ?>
		<div id="message" class="updated notice is-dismissible">
			<p>
				<?php
				if (1 == (int) $_GET['deleted']) {
					_e('The selected module has been <strong>deleted</strong>.');// phpcs:ignore
				} else {
					_e('The selected modules have been <strong>deleted</strong>.');// phpcs:ignore
				}
				?>
			</p>
		</div>
	<?php endif; ?>
<?php elseif (isset($_GET['activate'])) : ?>
	<div id="message" class="updated notice is-dismissible"><p><?php _e('Module <strong>activated</strong>.'); // phpcs:ignore?></p></div>
<?php elseif (isset($_GET['activate-multi'])) : ?>
	<div id="message" class="updated notice is-dismissible"><p><?php _e('Selected modules <strong>activated</strong>.'); // phpcs:ignore?></p></div>
<?php elseif (isset($_GET['deactivate'])) : ?>
	<div id="message" class="updated notice is-dismissible"><p><?php _e('Module <strong>deactivated</strong>.'); // phpcs:ignore?></p></div>
<?php elseif (isset($_GET['deactivate-multi'])) : ?>
	<div id="message" class="updated notice is-dismissible"><p><?php _e('Selected modules <strong>deactivated</strong>.'); // phpcs:ignore?></p></div>
<?php elseif ('update-selected' == $action) : ?>
	<div id="message" class="updated notice is-dismissible"><p><?php _e('All selected modules are up to date.'); // phpcs:ignore?></p></div>
<?php elseif (isset($_GET['resume'])) : ?>
	<div id="message" class="updated notice is-dismissible"><p><?php _e('Module <strong>resumed</strong>.'); // phpcs:ignore?></p></div>
<?php endif; ?>

<div class="wrap">
<h1 class="wp-heading-inline">
<?php
echo esc_html($title);
?>
</h1>

<?php
if (( ! is_multisite() || is_network_admin() ) && current_user_can('install_plugins')) {
	?>
	<button id="osdxp-module-upload-button" class="button-secondary">
		<?php echo esc_html_x('Upload Module ', 'module'); ?>
	</button>
	<?php
}

if (strlen($s)) {
	/* translators: %s: search keywords */
	printf(
		'<span class="search-results subtitle">' . esc_html__('Search results for &#8220;%s&#8221;') . '</span>',
		esc_html(urldecode($s))
	);
}
?>

<hr class="wp-header-end">

<?php

/*
 * Fires before the modules list table is rendered.
 *
 * This hook also fires before the modules list table is rendered in the Network Admin.
 *
 * Please note: The 'active' portion of the hook name does not refer to whether the current
 * view is for active modules, but rather all modules actively-installed.
 *
 * @param array[] $modules_all An array of arrays containing information on all installed modules.
 */
do_action('pre_current_active_plugins', $modules['all']);
?>

<?php $osdxp_modules_list_table->views(); ?>

<form class="search-form search-plugins" method="get">
<?php $osdxp_modules_list_table->search_box(esc_html__('GO'), 'module'); ?>
</form>

<form method="post" id="bulk-action-form">

<input type="hidden" name="module_status" value="<?php echo esc_attr($status); ?>" />
<input type="hidden" name="paged" value="<?php echo esc_attr($page); ?>" />

<?php $osdxp_modules_list_table->display(); ?>
</form>

	<span class="spinner"></span>
</div>
<?php
//ajax login modal - not used rn
wp_print_request_filesystem_credentials_modal();
//ajax script updating nodes for installing/updating/upload/delete - not used rn
wp_print_admin_notice_templates();
//ajax html updating nodes for installing/updating/upload/delete - not used rn
wp_print_update_row_templates();
// include( ABSPATH . '/wp-admin/admin-footer.php' );
