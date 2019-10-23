<?php
/**
 * File containing hooks and functionality related to menus.
 *
 * @package osdxp-dashboard
 */

namespace OSDXP_Dashboard;

// phpcs:disable
add_action('admin_bar_menu', __NAMESPACE__ . '\\add_switch_to_dxp_button_to_admin_bar_menu', 5);
add_action('admin_bar_menu', __NAMESPACE__ . '\\add_network_menu_items_to_admin_bar_menu', 22);
add_action('admin_bar_menu', __NAMESPACE__ . '\\remove_wp_log_from_admin_bar_menu', 21);
add_action('admin_menu', __NAMESPACE__ . '\\build_dxp_admin_menu', 999);
add_action('admin_head', __NAMESPACE__ . '\\hide_endpoints_from_sidebar', 999) ;

//custom order on dashboard only
if (! is_network_admin()) {
	add_filter('menu_order', __NAMESPACE__ . '\\dxp_reorder_menu');
	add_filter('custom_menu_order', '__return_true');
}
//add network pages
add_action('network_admin_menu', function () {
	build_dxp_admin_menu(true);
});
// phpcs:enable

/**
 * Hide endpoints from the sidebar menu
 *
 * @return  void
 */
function hide_endpoints_from_sidebar()
{
	$dxp_menu_pages = get_dxp_menu_pages();

	if (! $dxp_menu_pages || ! is_array($dxp_menu_pages)) {
		return;
	}

	//hide WP generated subpage on module settings
	remove_submenu_page('dxp-module-settings', 'dxp-module-settings');

	//hide menu settings page if no settings submenus
	global $submenu;
	if (empty($submenu['dxp-module-settings'])) {
		remove_menu_page('dxp-module-settings');
	}

	foreach ($dxp_menu_pages as $menu_page) {
		// Validate and confirm endpoint, check if it's being displayed(has parent_slug)
		if (is_valid_dxp_menu_page($menu_page)
			&& $menu_page['type'] === OSDXP_DASHBOARD_MENU_TYPE_ENDPOINT
			&& isset($menu_page['parent_slug'])
		) {
				remove_submenu_page($menu_page['parent_slug'], $menu_page['menu_slug']);
		}
	}
}

/**
 * Sets desired menu page order
 *
 * @return  array of file names as used by Wordpress
 */
function dxp_reorder_menu()
{
	if (!is_dxp_dashboard()) {
		return [];
	}

	return dxp_accepted_top_pages();
}

/**
 * Method for adding the Switch to DXP button to the WP admin bar.
 *
 * @param \WP_Admin_Bar $wp_admin_bar WP Admin Bar object.
 *
 * @return void
 */
function add_switch_to_dxp_button_to_admin_bar_menu($wp_admin_bar)
{
	if (is_dxp_dashboard_active_for_current_user()) {
		if (!is_network_admin()) {
			$wp_admin_bar->add_menu(
				[
					'id'    => 'switch-to-dxp',
					'title' => sprintf(
						'<div>%s</div>',
						__('<div>Return to<br> WordPress</div>', 'osdxp-dashboard')
					),
					'href'  => '/wp-admin/?dxp=off', // return to wp-admin dashboard
				]
			);
		}
	} else {
		$wp_admin_bar->add_menu(
			[
				'id'    => 'switch-to-dxp-backburger',
				'title' => '<div></div><span class="screen-reader-text">'
							. esc_html__('Switch to DXP', 'osdxp-dashboard')
							. '</span>',
				'href'  => admin_url('?dxp=on'), //set the user meta&init plugin based on dxp param

			]
		);
		$wp_admin_bar->add_menu(
			[
				'parent' => 'switch-to-dxp-backburger',
				'id'    => 'switch-to-dxp',
				'title' => '<span class="screen-reader-text">'
							. esc_html__('Switch to DXP', 'osdxp-dashboard')
							. '</span>',
				'href'  => admin_url('?dxp=on'), //set the user meta&init plugin based on dxp param
			]
		);
	}
}

/**
 * Method to add network menu items to admin bar.
 *
 * @param \WP_Admin_Bar $wp_admin_bar WP Admin Bar object.
 *
 * @return void
 */
function add_network_menu_items_to_admin_bar_menu($wp_admin_bar)
{
	if (is_dxp_dashboard_active_for_current_user() && is_multisite()) {
		edit_my_sites_menu($wp_admin_bar);
	}
}

/**
 * Remove WP menu items and replace them with the DXP menu items.
 *
 * @var  bool $network default false  [check if adding pages on network dashboard]
 *
 * @return void
 */
function build_dxp_admin_menu(bool $network = false)
{
	// Check if we are on a DXP Dashboard page.
	if (is_dxp_dashboard()) {
		// Remove all menu items.
		dxp_admin_remove_menu_items($network);

		// Register the menu items.
		dxp_admin_add_menu_items($network);
	}
}

/**
 * Method to add DXP Dashboard menu pages.
 *
 * @var  bool $network default false  [check if adding pages on network dashboard]
 *
 * @return void
 */
function dxp_admin_add_menu_items(bool $network = false)
{
	$dxp_menu_pages = get_dxp_menu_pages();

	global $menu;
	//change plugins page title to legacy plugins and settings to configurations
	if ($network) {
		$menu[20][0] = 'Legacy ' . $menu[20][0];
		$menu[25][0] = 'OS DXP ' . $menu[25][0];
	} else {
		$menu[5][0] = 'Articles';
		$menu[80][0] = 'OS DXP ' . $menu[80][0];
	}

	if (! $dxp_menu_pages || ! is_array($dxp_menu_pages)) {
		return;
	}

	//get module update count and build pill badge element
	$count = '';
	if (! isset($module_data)) {
		$module_data = osdxp_get_update_data();
	}

	$count = "<span class='update-plugins count-{$module_data}'><span class='module-count'>"
			. number_format_i18n($module_data)
			. '</span></span>';

	foreach ($dxp_menu_pages as $menu_page) {
		// Validate menu page.
		if (!is_valid_dxp_menu_page($menu_page) ||  !is_valid_dxp_network_page($menu_page, $network)) {
			continue;
		}

		if (OSDXP_DASHBOARD_MENU_TYPE_MENU === $menu_page['type']) {
			if (isset($menu_page['position_network']) && $network) {
				$position = $menu_page['position_network'];
			} else {
				$position = isset($menu_page['position']) ? $menu_page['position'] : null;
			}
			// Add menu page.
			add_menu_page(
				$menu_page['page_title'],
				$menu_page['menu_title'] === esc_html__('Modules', 'osdxp-dashboard')
					? esc_html__('Modules ', 'osdxp-dashboard') . $count
					: $menu_page['menu_title'],
				$menu_page['capability'],
				$menu_page['menu_slug'],
				isset($menu_page['function']) ? $menu_page['function'] : '',
				isset($menu_page['icon_url']) ? $menu_page['icon_url'] : '',
				$position
			);
		} elseif (OSDXP_DASHBOARD_MENU_TYPE_SUBMENU === $menu_page['type']) {
			// Add submenu page.
			add_submenu_page(
				$menu_page['parent_slug'],
				$menu_page['page_title'],
				$menu_page['menu_title'],
				$menu_page['capability'],
				$menu_page['menu_slug'],
				isset($menu_page['function']) ? $menu_page['function'] : ''
			);
		} else {
			//add endpoint
			add_submenu_page(
				isset($menu_page['parent_slug']) ? $menu_page['parent_slug'] : '',
				$menu_page['page_title'],
				$menu_page['menu_title'],
				$menu_page['capability'],
				$menu_page['menu_slug'],
				isset($menu_page['function']) ? $menu_page['function'] : ''
			);
		}
	}
}

/**
 * Method to retrieve accepted top-level default pages
 * Returns pages in correct order
 *
 * @return  array of menu slugs
 */
function dxp_accepted_top_pages()
{
	// dashboard, pages and posts
	$menu_list = [
		'index.php',
		'edit.php?post_type=page',
		'edit.php'
	];

	// cpts
	$cpts = get_post_types(['_builtin'  => false], 'names');
	$cpts = apply_filters('osdxp_filter_cpts', $cpts);

	foreach ($cpts as $cpt) {
		$menu_list[] = 'edit.php?post_type=' . $cpt;
	}

	// non-dashboard osDXP top-level pages
	$custom_pages = [];
	$custom_pages = apply_filters('osdxp_add_module_settings_page', $custom_pages);
	foreach ($custom_pages as $custom_page) {
		if (!isset($custom_page['type']) || OSDXP_DASHBOARD_MENU_TYPE_MENU !== $custom_page['type']) {
			continue;
		}
		$menu_list[] = $custom_page['menu_slug'];
	}

	array_push(
		$menu_list,
		'upload.php',
		'themes.php',
		'dxp-modules-installed',
		'dxp-module-settings',
		'options-general.php',
		'separator2',
		'users.php',
		'tools.php'
	);

	return $menu_list;
}

/**
 * Method to remove all menu items.
 *
 * @var  bool $network default false  [check if adding pages on network dashboard]
 *
 * @return void
 */
function dxp_admin_remove_menu_items(bool $network = false)
{
	if ($network) {
		return;
	}

	$menu_list = dxp_accepted_top_pages();

	global $menu;
	foreach ($menu as $menu_item) {
		if (!in_array($menu_item[2], $menu_list)) {
			// $menu_item[2] is the menu slug.
			remove_menu_page($menu_item[2]);
		}
	}
}

/**
 * Render the available modules page.
 *
 * @return void
 */
function dxp_render_available_modules()
{
	$OSDXP_AvailableModules = new OSDXPAvailableModules();
	$OSDXP_AvailableModules->showPage();
}

/**
 * Render the module settings page.
 *
 * @return void
 */
function dxp_render_module_settings()
{
	echo 'Module settings top-level page content';
}

/**
 * Render the installed modules page.
 *
 * @return void
 */
function dxp_render_installed_modules()
{
	require_once(OSDXP_DASHBOARD_DIR . '/templates/modules-list-installed.php');
}

/**
 * Endpoint needed for upload/update modules
 *
 * @return void
 */
function dxp_render_update_modules()
{
	require_once(OSDXP_DASHBOARD_DIR . '/templates/modules-update.php');
}

/**
 * Endpoint needed to display module update information
 *
 * @return void
 */
function dxp_render_update_modules_information()
{
	require_once(OSDXP_DASHBOARD_DIR . '/templates/modules-information.php');
}

/**
 * Updates my-sites to apply to OSDXP admin bar menu
 *
 * @var  object $wp_admin_bar Admin Bar nodes object
 * @return  void
 */
function edit_my_sites_menu($wp_admin_bar)
{

	// Show only when the user has at least one site, or they're a super admin.
	if (count($wp_admin_bar->user->blogs) < 1 && ! current_user_can('manage_network')) {
		return;
	}

	if ($wp_admin_bar->user->active_blog) {
		$my_sites_url = get_admin_url($wp_admin_bar->user->active_blog->blog_id, 'my-sites.php');
	} else {
		$my_sites_url = admin_url('my-sites.php');
	}

	// Remove Legacy Plugins and Settings from the Network Admin Menu
	$wp_admin_bar->remove_menu('network-admin-p');
	$wp_admin_bar->remove_menu('network-admin-o');

	if (current_user_can('manage_network')) {
		$wp_admin_bar->add_group(
			array(
				'parent' => 'site-name',
				'id'     => 'my-sites-super-admin',
			)
		);

		$wp_admin_bar->add_menu(
			array(
				'parent' => 'my-sites-super-admin',
				'id'     => 'network-admin',
				'title'  => esc_html__('Network Admin', 'osdxp-dashboard'),
				'href'   => network_admin_url(),
			)
		);

		$wp_admin_bar->add_menu(
			array(
				'parent' => 'network-admin',
				'id'     => 'network-admin-d',
				'title'  => esc_html__('Dashboard', 'osdxp-dashboard'),
				'href'   => network_admin_url(),
			)
		);

		if (current_user_can('manage_sites')) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-s',
					'title'  => esc_html__('Sites', 'osdxp-dashboard'),
					'href'   => network_admin_url('sites.php'),
				)
			);
		}

		if (current_user_can('manage_network_users')) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-u',
					'title'  => esc_html__('Users', 'osdxp-dashboard'),
					'href'   => network_admin_url('users.php'),
				)
			);
		}

		if (current_user_can('manage_network_themes')) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-t',
					'title'  => esc_html__('Themes', 'osdxp-dashboard'),
					'href'   => network_admin_url('themes.php'),
				)
			);
		}

		if (current_user_can('manage_network_plugins')) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-mi',
					'title'  => esc_html__('Modules', 'osdxp-dashboard'),
					'href'   => network_admin_url('admin.php?page=dxp-modules-installed'),
				)
			);
		}
		if (current_user_can('manage_network_options')) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'network-admin',
					'id'     => 'network-admin-o',
					'title'  => esc_html__('OS DXP Settings', 'osdxp-dashboard'),
					'href'   => network_admin_url('settings.php'),
				)
			);
		}
	}

	// Add site links
	$wp_admin_bar->add_group(
		array(
			'parent' => 'site-name',
			'id'     => 'my-sites-list',
			'meta'   => array(
				'class' => current_user_can('manage_network') ? 'ab-sub-secondary' : '',
			),
		)
	);

	foreach ((array) $wp_admin_bar->user->blogs as $blog) {
		switch_to_blog($blog->userblog_id);

		$blogname = $blog->blogname;

		if (! $blogname) {
			$blogname = preg_replace('#^(https?://)?(www.)?#', '', get_home_url());
		}

		$menu_id = 'blog-' . $blog->userblog_id;

		if (current_user_can('read')) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'my-sites-list',
					'id'     => $menu_id,
					'title'  => $blogname,
					'href'   => admin_url(),
				)
			);

			$wp_admin_bar->add_menu(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-d',
					'title'  => esc_html__('Dashboard', 'osdxp-dashboard'),
					'href'   => admin_url(),
				)
			);
		} else {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'my-sites-list',
					'id'     => $menu_id,
					'title'  => $blogname,
					'href'   => home_url(),
				)
			);
		}

		if (current_user_can(get_post_type_object('post')->cap->create_posts)) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-n',
					'title'  => get_post_type_object('post')->labels->new_item,
					'href'   => admin_url('post-new.php'),
				)
			);
		}

		if (current_user_can('edit_posts')) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => $menu_id,
					'id'     => $menu_id . '-c',
					'title'  => esc_html__('Manage Comments', 'osdxp-dashboard'),
					'href'   => admin_url('edit-comments.php'),
				)
			);
		}

		$wp_admin_bar->add_menu(
			array(
				'parent' => $menu_id,
				'id'     => $menu_id . '-v',
				'title'  => esc_html__('Visit Site', 'osdxp-dashboard'),
				'href'   => home_url('/'),
			)
		);

		restore_current_blog();
	}
}

/**
 * Method to validate a DXP menu page item.
 *
 * @param array $menu_page Menu page array.
 *
 * @return bool
 */
function is_valid_dxp_menu_page($menu_page)
{
	// Check if the variable is a non-empty array.
	if (! $menu_page || ! is_array($menu_page)) {
		return false;
	}

	$required_fields = [
		'type',
		'capability',
		'menu_slug',
	];

	if ($menu_page['type'] !== OSDXP_DASHBOARD_MENU_TYPE_ENDPOINT) {
		array_push($required_fields, 'page_title', 'menu_title');
	}
	if ($menu_page['type'] === OSDXP_DASHBOARD_MENU_TYPE_SUBMENU) {
		array_push($required_fields, 'parent_slug');
	}

	// Check if the required fields are set.
	foreach ($required_fields as $field) {
		if (! isset($menu_page[ $field ])) {
			return false;
		}
	}

	$types = [
		OSDXP_DASHBOARD_MENU_TYPE_MENU,
		OSDXP_DASHBOARD_MENU_TYPE_SUBMENU,
		OSDXP_DASHBOARD_MENU_TYPE_ENDPOINT,
	];

	// Validate menu type.
	if (! in_array($menu_page['type'], $types, true)) {
		return false;
	}

	return true;
}

/**
 * Method to validate a DXP network page item.
 *
 * @param array $menu_page Menu page array.
 * @param bool $network If we're building network menu or not
 *
 * @return bool
 */
function is_valid_dxp_network_page($menu_page, $network)
{
	if (! is_multisite()) {
		return true;
	}

	if (isset($menu_page['network'])) {
		switch ($menu_page['network']) {
			//return true if building network menu and network field is true
			case 'true':
				return $network ? true : false;
			//add page on both
			case 'both':
				return true;
			//accept anything else for regular dashboard only
			default:
				return $network ? false : true;
		}
	}
	//true if building regular menu, false if network menu
	return $network ? false : true;
}

/**
 * Method to remove the WP logo from the admin bar.
 *
 * @param \WP_Admin_Bar $wp_admin_bar WP Admin Bar object.
 *
 * @return void
 */
function remove_wp_log_from_admin_bar_menu($wp_admin_bar)
{
	if (is_dxp_dashboard_active_for_current_user()) {
		$wp_admin_bar->remove_menu('wp-logo');
		if (is_multisite()) {
			$wp_admin_bar->remove_menu('my-sites');
		}
	}
}
