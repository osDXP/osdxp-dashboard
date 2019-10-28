<?php
/**
 * OSDXP extended WordPress Administration Update API
 *
 * @see  wp-admin/includes/update.php
 *
 * @package osdxp-dashboard
 */


/**
 * Updates Modules rows with update notice
 *
 * @see wp_plugin_update_rows
 */
function osdxp_module_update_rows()
{
	if (! current_user_can('update_plugins')) {
		return;
	}

	$modules = get_site_transient('update_plugins');
	if (isset($modules->response) && is_array($modules->response)) {
		$modules = array_keys($modules->response);
		foreach ($modules as $module_file) {
			add_action("after_plugin_row_$module_file", 'osdxp_module_update_row', 10, 2);
		}
	}
}

/**
 * Displays update information for a module.
 *
 * @see  wp_plugin_update_row
 * @param string $file        Module basename.
 * @param array  $module_data Module information.
 * @return false|void
 */
function osdxp_module_update_row($file, $module_data)
{
	$current = get_site_transient('update_plugins');
	if (! isset($current->response[ $file ])) {
		return false;
	}

	$response = $current->response[ $file ];

	$modules_allowedtags = array(
		'a'       => array(
			'href'  => array(),
			'title' => array(),
		),
		'abbr'    => array( 'title' => array() ),
		'acronym' => array( 'title' => array() ),
		'code'    => array(),
		'em'      => array(),
		'strong'  => array(),
	);

	$module_name = wp_kses($module_data['Name'], $modules_allowedtags);
	$details_url = esc_url($response->url);

	/** @var OSDXP_Modules_List_Table $osdxp_modules_list_table */
	$osdxp_modules_list_table = new OSDXP_Dashboard\OSDXP_Modules_List_Table();

	if (is_network_admin() || ! is_multisite()) {
		if (is_network_admin()) {
			$active_class = is_plugin_active_for_network($file) ? ' active' : '';
		} else {
			$active_class = is_plugin_active($file) ? ' active' : '';
		}

		$requires_php   = isset($response->requires_php) ? $response->requires_php : null;
		$compatible_php = is_php_version_compatible($requires_php);
		$notice_type    = $compatible_php ? 'notice-warning' : 'notice-error';

		echo '<tr class="plugin-update-tr' . $active_class . '" id="' . esc_attr($response->slug . '-update') . '" data-slug="' . esc_attr($response->slug) . '" data-plugin="' . esc_attr($file) . '"><td colspan="4" class="plugin-update colspanchange"><div class="update-message notice inline ' . $notice_type . ' notice-alt"><p>';

		if (! current_user_can('update_plugins')) {
			/* translators: 1: module name, 2: details URL, 3: additional link attributes, 4: version number */
			printf(
				__('There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.'),
				$module_name,
				esc_url($details_url),
				'target="_blank"',
				esc_attr($response->new_version)
			);
		} elseif (empty($response->package)) {
			/* translators: 1: module name, 2: details URL, 3: additional link attributes, 4: version number */
			printf(
				__('There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>. <em>Automatic update is unavailable for this module.</em>'),
				$module_name,
				esc_url($details_url),
				'target="_blank"',
				esc_attr($response->new_version)
			);
		} else {
			if ($compatible_php) {
				/* translators: 1: module name, 2: details URL, 3: additional link attributes, 4: version number, 5: update URL, 6: additional link attributes */
				printf(
					__('There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s" %6$s>update now</a>.'),
					$module_name,
					esc_url($details_url),
					'target="_blank"',
					esc_attr($response->new_version),
					wp_nonce_url(self_admin_url('admin.php?page=dxp-modules-update&noheader&action=upgrade-module&module=') . $file, 'upgrade-module_' . $file),
					sprintf(
						'class="update-link" aria-label="%s"',
						/* translators: %s: module name */
						esc_attr(sprintf(__('Update %s now'), $module_name))
					)
				);
			} else {
				/* translators: 1: module name, 2: details URL, 3: additional link attributes, 4: version number 5: Update PHP page URL */
				printf(
					__('There is a new version of %1$s available, but it doesn&#8217;t work with your version of PHP. <a href="%2$s" %3$s>View version %4$s details</a> or <a href="%5$s">learn more about updating PHP</a>.'),
					$module_name,
					esc_url($details_url),
					'target="_blank"',
					esc_attr($response->new_version),
					esc_url(wp_get_update_php_url())
				);
				wp_update_php_annotation('<br><em>', '</em>');
			}
		}

		/**
		 * Fires at the end of the update message container in each
		 * row of the modules list table.
		 *
		 * The dynamic portion of the hook name, `$file`, refers to the path
		 * of the modules's primary file relative to the modules directory.
		 *
		 *
		 * @param array $module_data {
		 *     An array of module metadata.
		 *
		 *     @type string $name        The human-readable name of the module.
		 *     @type string $module_uri  Module URI.
		 *     @type string $version     Module version.
		 *     @type string $description Module description.
		 *     @type string $author      Module author.
		 *     @type string $author_uri  Module author URI.
		 *     @type string $text_domain Module text domain.
		 *     @type string $domain_path Relative path to the module's .mo file(s).
		 *     @type bool   $network     Whether the module can only be activated network wide.
		 *     @type string $title       The human-readable title of the module.
		 *     @type string $author_name Module author's name.
		 *     @type bool   $update      Whether there's an available update. Default null.
		 * }
		 * @param array $response {
		 *     An array of metadata about the available module update.
		 *
		 *     @type int    $id          Module ID.
		 *     @type string $slug        Module slug.
		 *     @type string $new_version New module version.
		 *     @type string $url         Module URL.
		 *     @type string $package     Module update package URL.
		 * }
		 */
		do_action("in_plugin_update_message-{$file}", $module_data, $response);

		echo '</p></div></td></tr>';
	}
}
