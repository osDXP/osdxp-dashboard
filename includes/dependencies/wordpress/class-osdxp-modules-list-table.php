<?php
/**
 * List Table API: OSDXP_Modules_List_Table class
 *
 * @see  class-wp-plugins-list-table.php
 *
 * @package osdxp-dashboard
 */
namespace OSDXP_Dashboard;

/** WP_List_Table class */
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Core class used to implement displaying installed modules in a list table.
 *
 * @see OSDXP_List_Table
 */
class OSDXP_Modules_List_Table extends \WP_List_Table
{

	/**
	 * Constructor.
	 *
	 * @see OSDXP_List_Table::__construct() for more information on default arguments.
	 *
	 * @global string $status
	 * @global int    $page
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct($args = array())
	{
		global $status, $page;

		parent::__construct(
			array(
				'plural'    =>  'modules',
			)
		);

		$status = 'all';
		if (isset($_REQUEST['module_status']) && in_array($_REQUEST['module_status'], array( 'active', 'inactive', 'recently_activated', 'upgrade', 'mustuse', 'dropins', 'search', 'paused' ))) {
			$status = $_REQUEST['module_status'];
		}

		if (isset($_REQUEST['s'])) {
			$_SERVER['REQUEST_URI'] = add_query_arg('s', wp_unslash($_REQUEST['s']));
		}

		$page = $this->get_pagenum();
	}

	/**
	 * @return array
	 */
	protected function get_table_classes()
	{
		return array( 'widefat', 'plugins' ); //wp default styling
	}

	/**
	 * @return bool
	 */
	public function ajax_user_can()
	{
		return current_user_can('activate_plugins');
	}

	/**
	 * @global string $status
	 * @global array  $modules
	 * @global array  $totals
	 * @global int    $page
	 * @global string $orderby
	 * @global string $order
	 * @global string $s
	 */
	public function prepare_items()
	{
		global $status, $modules, $totals, $page, $orderby, $order, $s;

		//@TODO: see https://codex.wordpress.org/Function_Reference/WP_List_Table#Using_within_Meta_Boxes for why this is here
		$this->_column_headers = array(
			 $this->get_columns(),      // columns
			 array(),           // hidden
			 $this->get_sortable_columns(), // sortable
		);
		wp_reset_vars(array( 'orderby', 'order' ));

		/**
		 * Filters the full array of modules to list in the Modules list table.
		 *
		 * @see get_plugins()
		 *
		 * @see  filter_modules() in utils.php
		 * @param array $all_modules An array of modules to display in the list table.
		 */
		$all_modules = apply_filters('osdxp_get_modules', get_plugins());

		$modules = array(
			'all'                => $all_modules,
			'search'             => array(),
			'active'             => array(),
			'inactive'           => array(),
			'recently_activated' => array(),
			'upgrade'            => array(),
			'mustuse'            => array(),
			'dropins'            => array(),
			'paused'             => array(),
		);

		$screen = $this->screen;

		if (! is_multisite() || ( $screen->in_admin('network') && current_user_can('manage_network_plugins') )) {

			/**
			 * Filters whether to display the advanced modules list table.
			 *
			 * There are two types of advanced modules - must-use and drop-ins -
			 * which can be used in a single site or Multisite network.
			 *
			 * The $type parameter allows you to differentiate between the type of advanced
			 * modules to filter the display of. Contexts include 'mustuse' and 'dropins'.
			 *
			 * @param bool   $show Whether to show the advanced modules for the specified
			 *                     module type. Default true.
			 * @param string $type The module type. Accepts 'mustuse', 'dropins'.
			 */
			if (apply_filters('show_advanced_plugins', true, 'mustuse')) {
				$modules['mustuse'] = get_mu_plugins();
			}

			/** This action is documented in wp-admin/includes/class-wp-plugins-list-table.php */
			if (apply_filters('show_advanced_plugins', true, 'dropins')) {
				$modules['dropins'] = get_dropins();
			}

			if (current_user_can('update_plugins')) {
				$current = get_site_transient('update_plugins');
				foreach ((array) $modules['all'] as $module_file => $module_data) {
					if (isset($current->response[ $module_file ])) {
						$modules['all'][ $module_file ]['update'] = true;
						$modules['upgrade'][ $module_file ]       = $modules['all'][ $module_file ];
					}
				}
			}
		}

		if (! $screen->in_admin('network')) {
			$show = current_user_can('manage_network_plugins');
			/**
			 * Filters whether to display network-active modules alongside modules active for the current site.
			 *
			 * This also controls the display of inactive network-only modules (modules with
			 * "Network: true" in the module header).
			 *
			 * Modules cannot be network-activated or network-deactivated from this screen.
			 *
			 * @param bool $show Whether to show network-active modules. Default is whether the current
			 *                   user can manage network modules (ie. a Super Admin).
			 */
			$show_network_active = apply_filters('show_network_active_plugins', $show);
		}

		set_transient('plugin_slugs', array_keys($modules['all']), DAY_IN_SECONDS);

		if ($screen->in_admin('network')) {
			$recently_activated = get_site_option('recently_activated', array());
		} else {
			$recently_activated = get_option('recently_activated', array());
		}

		foreach ($recently_activated as $key => $time) {
			if ($time + WEEK_IN_SECONDS < time()) {
				unset($recently_activated[ $key ]);
			}
		}

		if ($screen->in_admin('network')) {
			update_site_option('recently_activated', $recently_activated);
		} else {
			update_option('recently_activated', $recently_activated);
		}

		$module_info = get_site_transient('update_plugins');

		foreach ((array) $modules['all'] as $module_file => $module_data) {
			// Extra info if known. array_merge() ensures $module_data has precedence if keys collide.
			if (isset($module_info->response[ $module_file ])) {
				$module_data                    = array_merge((array) $module_info->response[ $module_file ], $module_data);
				$modules['all'][ $module_file ] = $module_data;
				// Make sure that $modules['upgrade'] also receives the extra info since it is used on ?module_status=upgrade
				if (isset($modules['upgrade'][ $module_file ])) {
					$modules['upgrade'][ $module_file ] = $module_data;
				}
			} elseif (isset($module_info->no_update[ $module_file ])) {
				$module_data                    = array_merge((array) $module_info->no_update[ $module_file ], $module_data);
				$modules['all'][ $module_file ] = $module_data;
				// Make sure that $modules['upgrade'] also receives the extra info since it is used on ?module_status=upgrade
				if (isset($modules['upgrade'][ $module_file ])) {
					$modules['upgrade'][ $module_file ] = $module_data;
				}
			}

			// Filter into individual sections
			if (is_multisite() && ! $screen->in_admin('network') && is_network_only_plugin($module_file) && ! is_plugin_active($module_file)) {
				if ($show_network_active) {
					// On the non-network screen, show inactive network-only modules if allowed
					$modules['inactive'][ $module_file ] = $module_data;
				} else {
					// On the non-network screen, filter out network-only modules as long as they're not individually active
					unset($modules['all'][ $module_file ]);
				}
			} elseif (! $screen->in_admin('network') && is_plugin_active_for_network($module_file)) {
				if ($show_network_active) {
					// On the non-network screen, show network-active modules if allowed
					$modules['active'][ $module_file ] = $module_data;
				} else {
					// On the non-network screen, filter out network-active modules
					unset($modules['all'][ $module_file ]);
				}
			} elseif (( ! $screen->in_admin('network') && is_plugin_active($module_file) )
				|| ( $screen->in_admin('network') && is_plugin_active_for_network($module_file) ) ) {
				// On the non-network screen, populate the active list with modules that are individually activated
				// On the network-admin screen, populate the active list with modules that are network activated
				$modules['active'][ $module_file ] = $module_data;

				if (! $screen->in_admin('network') && is_plugin_paused($module_file)) {
					$modules['paused'][ $module_file ] = $module_data;
				}
			} else {
				if (isset($recently_activated[ $module_file ])) {
					// Populate the recently activated list with modules that have been recently activated
					$modules['recently_activated'][ $module_file ] = $module_data;
				}
				// Populate the inactive list with modules that aren't activated
				$modules['inactive'][ $module_file ] = $module_data;
			}
		}

		if (strlen($s)) {
			$status            = 'search';
			$modules['search'] = array_filter($modules['all'], array( $this, '_search_callback' ));
		}

		$totals = array();
		foreach ($modules as $type => $list) {
			$totals[ $type ] = count($list);
		}

		if (empty($modules[ $status ]) && ! in_array($status, array( 'all', 'search' ))) {
			$status = 'all';
		}

		$this->items = array();
		foreach ($modules[ $status ] as $module_file => $module_data) {
			// Translate, Don't Apply Markup, Sanitize HTML
			$this->items[ $module_file ] = _get_plugin_data_markup_translate($module_file, $module_data, false, true);
		}

		$total_this_page = $totals[ $status ];

		if (! $orderby) {
			$orderby = 'Name';
		} else {
			$orderby = ucfirst($orderby);
		}

		$order = strtoupper($order);

		uasort($this->items, array( $this, '_order_callback' ));

		//@TODO: set screen options or leave default 20 items/per page, removing unused parent class function
		$modules_per_page = $this->get_items_per_page(str_replace('-', '_', $screen->id . '_per_page'), 999);

		$start = ( $page - 1 ) * $modules_per_page;

		if ($total_this_page > $modules_per_page) {
			$this->items = array_slice($this->items, $start, $modules_per_page);
		}

		$this->set_pagination_args(
			array(
				'total_items' => $total_this_page,
				'per_page'    => $modules_per_page,
			)
		);
	}

	/**
	 * @global string $s URL encoded search term.
	 *
	 * @param array $module
	 * @return bool
	 */
	public function _search_callback($module)
	{
		global $s;

		foreach ($module as $value) {
			if (is_string($value) && false !== stripos(strip_tags($value), urldecode($s))) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @global string $orderby
	 * @global string $order
	 * @param array $module_a
	 * @param array $module_b
	 * @return int
	 */
	public function _order_callback($module_a, $module_b)
	{
		global $orderby, $order;

		$a = $module_a[ $orderby ];
		$b = $module_b[ $orderby ];

		if ($a == $b) {
			return 0;
		}

		if ('DESC' === $order) {
			return strcasecmp($b, $a);
		} else {
			return strcasecmp($a, $b);
		}
	}

	/**
	 * @global array $modules
	 */
	public function no_items()
	{
		global $modules;

		if (! empty($_REQUEST['s'])) {
			$s = esc_html(wp_unslash($_REQUEST['s']));

			/* translators: %s: module search term */
			printf(esc_html__('No modules found for &#8220;%s&#8221;.', 'osdxp-dashboard'), esc_html($s));

			// We assume that somebody who can install modules in multisite is experienced enough to not need this helper link.
			if (! is_multisite() && current_user_can('install_plugins')) {
				echo ' <a href="https://opensourcedxp.com">' . esc_html__('Browse for more modules on the OSDXP Website!', 'osdxp-dashboard') . '</a>';
			}
		} elseif (! empty($modules['all'])) {
			esc_html_e('No modules found.', 'osdxp-dashboard');
		} else {
			esc_html_e('You do not appear to have any modules available at this time.', 'osdxp-dashboard');
		}
	}

	/**
	 * Displays the search box.
	 *
	 * @since 4.6.0
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 */
	public function search_box($text, $input_id)
	{
		if (empty($_REQUEST['s']) && ! $this->has_items()) {
			return;
		}

		$input_id = $input_id . '-search-input';

		if (! empty($_REQUEST['orderby'])) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
		}
		if (! empty($_REQUEST['order'])) {
			echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_attr($text); ?>:</label>
			<input type="hidden" name="page" value="dxp-modules-installed">
			<input type="search" id="<?php echo esc_attr($input_id); ?>" class="wp-filter-search" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php esc_attr_e('Search installed modules...'); ?>"/>
			<?php
			//@TODO:hidden ajax button
			  // submit_button( $text, 'hide-if-js', '', false, array( 'id' => 'search-submit' ) );
			?>
			<?php submit_button($text, '', '', false, array( 'id' => 'search-submit' )); ?>
		</p>
		<?php
	}

	/**
	 * @global string $status
	 * @return array
	 */
	public function get_columns()
	{
		global $status;

		return array(
			'cb'          => ! in_array($status, array( 'mustuse', 'dropins' )) ? '<input type="checkbox" />' : '',
			'name'        => __('Module'),
			'description' => __('Description'),
			'status' => __('Status'),
		);
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns()
	{
		return array();
	}

	/**
	 * @global array $totals
	 * @global string $status
	 * @return array
	 */
	protected function get_views()
	{
		global $totals, $status;

		$status_links = array();
		foreach ($totals as $type => $count) {
			if (! $count) {
				continue;
			}

			switch ($type) {
				case 'all':
					/* translators: %s: module count */
					$text = _nx('All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $count, 'modules');
					break;
				case 'active':
					/* translators: %s: module count */
					$text = _n('Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $count);
					break;
				case 'recently_activated':
					/* translators: %s: module count */
					$text = _n('Recently Active <span class="count">(%s)</span>', 'Recently Active <span class="count">(%s)</span>', $count);
					break;
				case 'inactive':
					/* translators: %s: module count */
					$text = _n('Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $count);
					break;
				case 'mustuse':
					/* translators: %s: module count */
					$text = _n('Must-Use <span class="count">(%s)</span>', 'Must-Use <span class="count">(%s)</span>', $count);
					break;
				case 'dropins':
					/* translators: %s: module count */
					$text = _n('Drop-in <span class="count">(%s)</span>', 'Drop-ins <span class="count">(%s)</span>', $count);
					break;
				case 'paused':
					/* translators: %s: module count */
					$text = _n('Paused <span class="count">(%s)</span>', 'Paused <span class="count">(%s)</span>', $count);
					break;
				case 'upgrade':
					/* translators: %s: module count */
					$text = _n('Update Available <span class="count">(%s)</span>', 'Update Available <span class="count">(%s)</span>', $count);
					break;
			}

			if ('search' !== $type) {
				$status_links[ $type ] = sprintf(
					"<a href='%s'%s>%s</a>",
					add_query_arg('module_status', $type, 'admin.php?page=dxp-modules-installed&'),
					( $type === $status ) ? ' class="current" aria-current="page"' : '',
					sprintf($text, number_format_i18n($count))
				);
			}
		}

		return $status_links;
	}


	/**
	 * Display the list of views available on this table.
	 *
	 * @since 3.1.0
	 */
	public function views()
	{
		$views = $this->get_views();
		/**
		 * Filters the list of available list table views.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @since 3.5.0
		 *
		 * @param string[] $views An array of available list table views.
		 */
		$views = apply_filters("views_{$this->screen->id}", $views);

		if (empty($views)) {
			return;
		}

		echo '<h2 class="screen-reader-text">Filter modules list</h2>';

		echo "<ul class='subsubsub'>\n";
		foreach ($views as $class => $view) {
			$views[ $class ] = "\t<li class='$class'>$view";
		}
		echo implode(" |</li>\n", $views) . "</li>\n";
		echo '</ul>';
	}

	/**
	 * @global string $status
	 * @return array
	 */
	protected function get_bulk_actions()
	{
		global $status;

		$actions = array();

		if ('active' != $status) {
			$actions['activate-selected'] = $this->screen->in_admin('network') ? esc_html__('Network Activate', 'osdxp-dashboard') : esc_html__('Activate', 'osdxp-dashboard');
		}

		if ('inactive' != $status && 'recent' != $status) {
			$actions['deactivate-selected'] = $this->screen->in_admin('network') ? esc_html__('Network Deactivate', 'osdxp-dashboard') : esc_html__('Deactivate', 'osdxp-dashboard');
		}

		if (! is_multisite() || $this->screen->in_admin('network')) {
			if (current_user_can('update_plugins')) {
				$actions['update-selected'] = esc_html__('Update', 'osdxp-dashboard');
			}
			if (current_user_can('delete_plugins') && ( 'active' != $status )) {
				$actions['delete-selected'] = esc_html__('Delete', 'osdxp-dashboard');
			}
		}

		return $actions;
	}

	/**
	 * @global string $status
	 * @param string $which
	 */
	public function bulk_actions($which = '')
	{
		global $status;

		if (in_array($status, array( 'mustuse', 'dropins' ))) {
			return;
		}

		parent::bulk_actions($which);
	}

	/**
	 * @global string $status
	 * @param string $which
	 */
	protected function extra_tablenav($which)
	{
		global $status;

		if (! in_array($status, array( 'recently_activated', 'mustuse', 'dropins' ))) {
			return;
		}

		echo '<div class="alignleft actions">';

		if ('recently_activated' == $status) {
			submit_button(esc_html__('Clear List', 'osdxp-dashboard'), '', 'clear-recent-list', false);
		} elseif ('top' === $which && 'mustuse' === $status) {
			echo '<p>' . sprintf(
				/* translators: %s: mu-plugins directory name */
				esc_html__('Files in the %s directory are executed automatically.', 'osdxp-dashboard'),
				'<code>' . esc_html(str_replace(ABSPATH, '/', WPMU_PLUGIN_DIR)) . '</code>'
			) . '</p>';
		} elseif ('top' === $which && 'dropins' === $status) {
			echo '<p>' . sprintf(
				/* translators: %s: wp-content directory name */
				esc_html__('Drop-ins are advanced modules in the %s directory that replace OSDXP functionality when present.', 'osdxp-dashboard'),
				'<code>' . esc_url_raw(str_replace(ABSPATH, '', WP_CONTENT_DIR)) . '</code>'
			) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * @return string
	 */
	public function current_action()
	{
		if (isset($_POST['clear-recent-list'])) {
			return 'clear-recent-list';
		}

		return parent::current_action();
	}

	/**
	 * @global string $status
	 */
	public function display_rows()
	{
		global $status;

		if (is_multisite() && ! $this->screen->in_admin('network') && in_array($status, array( 'mustuse', 'dropins' ))) {
			return;
		}

		foreach ($this->items as $module_file => $module_data) {
			$this->single_row(array( $module_file, $module_data ));
		}
	}

	/**
	 * @global string $status
	 * @global int $page
	 * @global string $s
	 * @global array $totals
	 *
	 * @param array $item
	 */
	public function single_row($item)
	{
		global $status, $page, $s, $totals;

		list( $module_file, $module_data ) = $item;
		$context                           = $status;
		$screen                            = $this->screen;

		// Pre-order.
		$actions = array(
			'deactivate' => '',
			'activate'   => '',
			'details'    => '',
			'delete'     => '',
		);

		// Do not restrict by default
		$restrict_network_active = false;
		$restrict_network_only   = false;

		if ('mustuse' === $context) {
			$is_active = true;
		} elseif ('dropins' === $context) {
			$dropins     = _get_dropins();
			$module_name = $module_file;
			if ($module_file != $module_data['Name']) {
				$module_name .= '<br/>' . $module_data['Name'];
			}
			if (true === ( $dropins[ $module_file ][1] )) { // Doesn't require a constant
				$is_active   = true;
				$description = '<p><strong>' . $dropins[ $module_file ][0] . '</strong></p>';
			} elseif (defined($dropins[ $module_file ][1]) && constant($dropins[ $module_file ][1])) { // Constant is true
				$is_active   = true;
				$description = '<p><strong>' . $dropins[ $module_file ][0] . '</strong></p>';
			} else {
				$is_active   = false;
				$description = '<p><strong>' . $dropins[ $module_file ][0] . ' <span class="error-message">' . esc_html__('Inactive:', 'osdxp-dashboard') . '</span></strong> ' .
					sprintf(
						/* translators: 1: drop-in constant name, 2: wp-config.php */
						__('Requires %1$s in %2$s file.'),
						"<code>define('" . $dropins[ $module_file ][1] . "', true);</code>",
						'<code>wp-config.php</code>'
					) . '</p>';
			}
			if ($module_data['Description']) {
				$description .= '<p>' . $module_data['Description'] . '</p>';
			}
		} else {
			if ($screen->in_admin('network')) {
				$is_active = is_plugin_active_for_network($module_file);
			} else {
				$is_active               = is_plugin_active($module_file);
				$restrict_network_active = ( is_multisite() && is_plugin_active_for_network($module_file) );
				$restrict_network_only   = ( is_multisite() && is_network_only_plugin($module_file) && ! $is_active );
			}

			if ($screen->in_admin('network')) {
				if ($is_active) {
					if (current_user_can('manage_network_plugins')) {
						/* translators: %s: module name */
						$actions['deactivate'] = '<a href="' . wp_nonce_url('admin.php?page=dxp-modules-installed&action=deactivate&module=' . urlencode($module_file) . '&module_status=' . $context . '&paged=' . $page . '&s=' . $s, 'deactivate-module_' . $module_file) . '" aria-label="' . esc_attr(sprintf(_x('Network Deactivate %s', 'module'), $module_data['Name'])) . '">' . __('Network Deactivate') . '</a>';
					}
				} else {
					if (current_user_can('manage_network_plugins')) {
						/* translators: %s: module name */
						$actions['activate'] = '<a href="' . wp_nonce_url('admin.php?page=dxp-modules-installed&action=activate&module=' . urlencode($module_file) . '&module_status=' . $context . '&paged=' . $page . '&s=' . $s, 'activate-module_' . $module_file) . '" class="edit" aria-label="' . esc_attr(sprintf(_x('Network Activate %s', 'module'), $module_data['Name'])) . '">' . __('Network Activate') . '</a>';
					}
					if (current_user_can('delete_plugins') && ! is_plugin_active($module_file)) {
						/* translators: %s: module name */
						$actions['delete'] = '<a href="' . wp_nonce_url('admin.php?page=dxp-modules-installed&action=delete-selected&checked[]=' . urlencode($module_file) . '&module_status=' . $context . '&paged=' . $page . '&s=' . $s, 'bulk-modules') . '" class="delete" aria-label="' . esc_attr(sprintf(_x('Delete %s', 'module'), $module_data['Name'])) . '">' . __('Delete') . '</a>';
					}
				}
			} else {
				if ($restrict_network_active) {
					$actions = array(
						'network_active' => __('Network Active'),
					);
				} elseif ($restrict_network_only) {
					$actions = array(
						'network_only' => __('Network Only'),
					);
				} elseif ($is_active) {
					if (current_user_can('deactivate_plugin', $module_file)) {
						/* translators: %s: module name */
						$actions['deactivate'] = '<a href="' . wp_nonce_url('admin.php?page=dxp-modules-installed&action=deactivate&module=' . urlencode($module_file) . '&module_status=' . $context . '&paged=' . $page . '&s=' . $s, 'deactivate-module_' . $module_file) . '" aria-label="' . esc_attr(sprintf(_x('Deactivate %s', 'module'), $module_data['Name'])) . '">' . __('Deactivate') . '</a>';
					}
					if (current_user_can('resume_plugin', $module_file) && is_plugin_paused($module_file)) {
						/* translators: %s: module name */
						$actions['resume'] = '<a class="resume-link" href="' . wp_nonce_url('admin.php?page=dxp-modules-installed&action=resume&module=' . urlencode($module_file) . '&module_status=' . $context . '&paged=' . $page . '&s=' . $s, 'resume-module_' . $module_file) . '" aria-label="' . esc_attr(sprintf(_x('Resume %s', 'module'), $module_data['Name'])) . '">' . __('Resume') . '</a>';
					}
				} else {
					if (current_user_can('activate_plugin', $module_file)) {
						/* translators: %s: module name */
						$actions['activate'] = '<a href="' . wp_nonce_url('admin.php?page=dxp-modules-installed&action=activate&module=' . urlencode($module_file) . '&module_status=' . $context . '&paged=' . $page . '&s=' . $s, 'activate-module_' . $module_file) . '" class="edit" aria-label="' . esc_attr(sprintf(_x('Activate %s', 'module'), $module_data['Name'])) . '">' . __('Activate') . '</a>';
					}

					if (! is_multisite() && current_user_can('delete_plugins')) {
						/* translators: %s: module name */
						$actions['delete'] = '<a href="' . wp_nonce_url('admin.php?page=dxp-modules-installed&action=delete-selected&checked[]=' . urlencode($module_file) . '&module_status=' . $context . '&paged=' . $page . '&s=' . $s, 'bulk-modules') . '" class="delete" aria-label="' . esc_attr(sprintf(_x('Delete %s', 'module'), $module_data['Name'])) . '">' . __('Delete') . '</a>';
					}
				} // end if $is_active
			} // end if $screen->in_admin( 'network' )
		} // end if $context

		$actions = array_filter($actions);

		if ($screen->in_admin('network')) {

			/**
			 * Filters the action links displayed for each module in the Network Admin Modules list table.
			 *
			 * @param string[] $actions     An array of module action links. By default this can include 'activate',
			 *                              'deactivate', and 'delete'.
			 * @param string   $module_file Path to the module file relative to the modules directory.
			 * @param array    $module_data An array of module data. See `get_plugin_data()`.
			 * @param string   $context     The module context. By default this can include 'all', 'active', 'inactive',
			 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
			 */
			$actions = apply_filters('network_admin_plugin_action_links', $actions, $module_file, $module_data, $context);

			/**
			 * Filters the list of action links displayed for a specific module in the Network Admin Modules list table.
			 *
			 * The dynamic portion of the hook name, `$module_file`, refers to the path
			 * to the module file, relative to the modules directory.
			 *
			 * @param string[] $actions     An array of module action links. By default this can include 'activate',
			 *                              'deactivate', and 'delete'.
			 * @param string   $module_file Path to the module file relative to the modules directory.
			 * @param array    $module_data An array of module data. See `get_plugin_data()`.
			 * @param string   $context     The module context. By default this can include 'all', 'active', 'inactive',
			 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
			 */
			$actions = apply_filters("network_admin_plugin_action_links_{$module_file}", $actions, $module_file, $module_data, $context);
		} else {

			/**
			 * Filters the action links displayed for each module in the Modules list table.
			 *
			 * @param string[] $actions     An array of module action links. By default this can include 'activate',
			 *                              'deactivate', and 'delete'. With Multisite active this can also include
			 *                              'network_active' and 'network_only' items.
			 * @param string   $module_file Path to the module file relative to the modules directory.
			 * @param array    $module_data An array of module data. See `get_plugin_data()`.
			 * @param string   $context     The module context. By default this can include 'all', 'active', 'inactive',
			 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
			 */
			$actions = apply_filters('plugin_action_links', $actions, $module_file, $module_data, $context);

			/**
			 * Filters the list of action links displayed for a specific module in the Modules list table.
			 *
			 * The dynamic portion of the hook name, `$module_file`, refers to the path
			 * to the module file, relative to the modules directory.
			 *
			 * @param string[] $actions     An array of module action links. By default this can include 'activate',
			 *                              'deactivate', and 'delete'. With Multisite active this can also include
			 *                              'network_active' and 'network_only' items.
			 * @param string   $module_file Path to the module file relative to the modules directory.
			 * @param array    $module_data An array of module data. See `get_plugin_data()`.
			 * @param string   $context     The module context. By default this can include 'all', 'active', 'inactive',
			 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
			 */
			$actions = apply_filters("plugin_action_links_{$module_file}", $actions, $module_file, $module_data, $context);
		}

		$requires_php   = isset($module['requires_php']) ? $module['requires_php'] : null;
		$compatible_php = is_php_version_compatible($requires_php);
		$class          = $is_active ? 'active' : 'inactive';
		$checkbox_id    = 'checkbox_' . md5($module_data['Name']);
		if ($restrict_network_active || $restrict_network_only || in_array($status, array( 'mustuse', 'dropins' )) || ! $compatible_php) {
			$checkbox = '';
		} else {
			/* translators: %s: module name */
			$checkbox = "<label class='screen-reader-text' for='" . $checkbox_id . "' >" . sprintf(__('Select %s'), $module_data['Name']) . '</label>'
				. "<input type='checkbox' name='checked[]' value='" . esc_attr($module_file) . "' id='" . $checkbox_id . "' />";
		}
		if ('dropins' != $context) {
			$description = '<p>' . ( $module_data['Description'] ? $module_data['Description'] : '&nbsp;' ) . '</p>';
			$module_name = $module_data['Name'];
		}

		if (! empty($totals['upgrade']) && ! empty($module_data['update'])) {
			$class .= ' update';
		}

		$paused = ! $screen->in_admin('network') && is_plugin_paused($module_file);

		if ($paused) {
			$class .= ' paused';
		}

		$module_slug = isset($module_data['slug']) ? $module_data['slug'] : sanitize_title($module_name);
		printf(
			'<tr class="%s" data-slug="%s" data-plugin="%s">',
			esc_attr($class),
			esc_attr($module_slug),
			esc_attr($module_file)
		);

		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
		foreach ($columns as $column_name => $column_display_name) {
			$extra_classes = '';
			if (in_array($column_name, $hidden)) {
				$extra_classes = ' hidden';
			}

			switch ($column_name) {
				case 'status':
					$classes = 'column-status desc';

					echo "<td style='width:15%' class='column-primary $classes{$extra_classes}'>";

					/**
					 * Display license information template
					 *
					 * @see  documentation on filter usage
					 * @package  osdxp-dashboard
					 */
					$license_display = array();
					$license_data = [];

					//get module filename without .php at the end and replace spaces with dashes
					$plugin_slug = explode('/', $module_file)[0];
					$plugin_slug = explode('.php', $plugin_slug)[0];
					$plugin_slug = str_replace(' ', '-', $plugin_slug);

					/**
					 * Filter the license data.
					 *
					 * @param null|array $license_data License data array, which has the following elements:
					 *                                 - 'expiry' (int) -> UNIX timestamp
					 *                                 - 'status' (string) -> E.g.: Active, Inactive, Expired.
					 * @param string $plugin_slug Plugin slug.
					 */
					$license_data = apply_filters('osdxp_dashboard_license_data', null, $plugin_slug);

					/**
					 * Filter the license data for a specific plugin.
					 *
					 * @param null|array $license_data License data array, which has the following elements:
					 *                                 - 'expiry' (int) -> UNIX timestamp
					 *                                 - 'status' (string) -> E.g.: Active, Inactive, Expired.
					 */
					$license_data = apply_filters('osdxp_dashboard_license_data_' . $plugin_slug, $license_data);

					if ($license_data && is_array($license_data)) {
						if (!empty($license_data['expiry'])) {
							$timestamp = $license_data['expiry'];
							//check if timestamp
							if ((int) $timestamp) {
								if ($timestamp > time()) {
									$timestamp = human_time_diff($timestamp) . ' from now';
									$license_data['status'] = isset($license_data['status']) ? $license_data['status'] : 'active';
								} else {
									$timestamp = human_time_diff($timestamp) . ' ago';
									$license_data['status'] = isset($license_data['status']) ? $license_data['status'] : 'expired';
								}
							} else {
								$timestamp = 'unavailable';
							}
							/* translators: %s: module license expiry date */
							echo '<p><b>' . esc_html__('Valid through', 'osdxp-dashboard') . '</b><br>' . esc_html($timestamp) . '</p>';
						}

						if (!empty($license_data['status'])) {
							/* translators: %s: module license status */
							echo '<p><b>' . esc_html__('License:', 'osdxp-dashboard') . '</b><br>' . esc_html($license_data['status']) . '</p>';
						}
					}

					echo '<div class="module-actions">';
					$button_text = apply_filters('osdxp_manage_button_module_' . $plugin_slug, 'Manage License');
					echo '<a target="_blank" class="button-primary" href="' . $module_data['AuthorURI'] . '">' . esc_html__($button_text, 'osdxp-dashboard') . '</a>';
					/**
					 * Filters the array of row meta for each module in the Modules list table.
					 *
					 * @param string[] $module_meta An array of the module's metadata,
					 *                              including the version, author,
					 *                              author URI, and module URI.
					 * @param string   $module_file Path to the module file relative to the modules directory.
					 * @param array    $module_data An array of module data.
					 * @param string   $status      Status of the module. Defaults are 'All', 'Active',
					 *                              'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
					 *                              'Drop-ins', 'Search', 'Paused'.
					 */
					$module_meta = '';
					$module_meta = apply_filters('osdxp_module_column_status', $module_meta, $module_file, $module_data, $status);
					echo $module_meta;

					echo '</div>'; // Close .module-actions.

					echo '</div>';

					echo '</td>';
					break;
				case 'cb':
					echo "<th scope='row' class='check-column'>$checkbox</th>";
					break;
				case 'name':
					echo '<td style="width:35%" class="plugin-title">';

					// Output module logo.
					if (!empty($module_data['logo'])) {
						echo wp_kses(
							$module_data['logo'],
							[
								'img' => [
									'alt' => [],
									'src' => [],
									'srcset' => [],
									'title' => [],
								],
								'picture' => [],
								'source' => [
									'media' => [],
									'sizes' => [],
									'srcset' => [],
									'type' => [],
								],
							]
						);
					} else {
						echo '<img src="' . esc_url(OSDXP_DASHBOARD_PLACEHOLDER_IMAGE_URL) . '">';
					}

					// Output module name.
					echo sprintf(
						'<p><strong>%s</strong></p>',
						esc_html($module_name)
					);
					echo "<b>v{$module_data['Version']}</b>";
					echo $this->row_actions($actions, true);
					echo '</td>';
					break;

				case 'description':
					$classes = 'column-description desc';

					echo '<td style="width:50%" class="' . $classes . '"">
						<div class="osdxp-module-description-column">
						<div class="plugin-description">' . $description . '</div>
						<div class="' . $class . ' second plugin-version-author-uri">';



					echo '</div>';

					/**
					 * Display license key information template
					 *
					 * @see  documentation on filter usage
					 * @package  osdxp-dashboard
					 */

					//get module filename without .php at the end and replace spaces with dashes
					$plugin_slug = explode('/', $module_file)[0];
					$plugin_slug = explode('.php', $plugin_slug)[0];
					$plugin_slug = str_replace(' ', '-', $plugin_slug);

					/**
					 * Filter the license key.
					 *
					 * @param null|bool|string $license_key License key. Accepts:
					 * 										1. null to display the license text field;
					 * 										2. a string to display the license key;
					 * 										3. false to disable both the license key and input field.
					 * @param string $plugin_slug Plugin slug.
					 */
					$license_key = apply_filters('osdxp_license_key', false, $plugin_slug);

					/**
					 * Filter the license key for a specific plugin.
					 *
					 * @param null|bool|string $license_key License key. Accepts:
					 * 										1. null to display the license text field;
					 * 										2. a string to display the license key;
					 * 										3. false to disable both the license key and input field.
					 */
					$license_key = apply_filters('osdxp_license_key_' . $plugin_slug, $license_key);

					if (false !== $license_key) {
						echo sprintf(
							'<div class="%s second module-license-key">',
							esc_attr($class)
						);

						$field_id = 'osdxp-license-field-' . $plugin_slug;

						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo esc_license_field_markup(
							/**
							 * Filters the license field markup.
							 *
							 * @param string $license_field_markup License field markup.
							 * @param string $plugin_slug Plugin slug.
							 */
							apply_filters(
								'osdxp_dashboard_license_field_markup',
								sprintf(
									'
									<div class="license-input-wrapper %5$s">
										<label for="%1$s"><strong>%2$s</strong></label>
										<input id="%1$s"
											class="js-osdxp-module-license" type="text" size="40"
											data-module="%3$s"
										>
										<button class="js-osdxp-submit-module-license button-primary">%4$s</button>
									</div>
									',
									sanitize_html_class($field_id),
									esc_html__('Enter License Key:', 'osdxp-dashboard'),
									esc_attr($plugin_slug),
									esc_html__('Submit', 'osdxp-dashboard'),
									is_null($license_key) ? '' : 'hidden'
								),
								$plugin_slug
							)
						);

						if (is_string($license_key)) {
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo esc_license_key_markup(
								/**
								 * Filters the license key markup.
								 *
								 * @param string $license_key License key.
								 * @param string $plugin_slug Plugin slug.
								 */
								apply_filters('osdxp_dashboard_license_key_markup', $license_key, $plugin_slug)
							);
						}

						echo '</div>';
					}

					if ($paused) {
						$notice_text = esc_html__('This module failed to load properly and is paused during recovery mode.');

						printf('<p><span class="dashicons dashicons-warning"></span> <strong>%s</strong></p>', $notice_text);

						$error = wp_get_plugin_error($module_file);

						if (false !== $error) {
							printf('<div class="error-display"><p>%s</p></div>', wp_get_extension_error_description($error));
						}
					}

					echo '</div><!-- .osdxp-module-description-column --></td>';
					break;
				default:
					$classes = "$column_name column-$column_name $class";

					echo "<td class='$classes{$extra_classes}'>";

					/**
					 * Fires inside each custom column of the Modules list table.
					 *
					 * @param string $column_name Name of the column.
					 * @param string $module_file Path to the module file relative to the modules directory.
					 * @param array  $module_data An array of module data.
					 */
					do_action('manage_plugins_custom_column', $column_name, $module_file, $module_data);

					echo '</td>';
			}
		}

		echo '</tr>';

		/**
		 * Fires after each row in the Modules list table.
		 *
		 * @param string $module_file Path to the module file relative to the modules directory.
		 * @param array  $module_data An array of module data.
		 * @param string $status      Status of the module. Defaults are 'All', 'Active',
		 *                            'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
		 *                            'Drop-ins', 'Search', 'Paused'.
		 */
		do_action('after_plugin_row', $module_file, $module_data, $status);

		/**
		 * Fires after each specific row in the Modules list table.
		 *
		 * The dynamic portion of the hook name, `$module_file`, refers to the path
		 * to the module file, relative to the modules directory.
		 *
		 * @param string $module_file Path to the module file relative to the modules directory.
		 * @param array  $module_data An array of module data.
		 * @param string $status      Status of the module. Defaults are 'All', 'Active',
		 *                            'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use',
		 *                            'Drop-ins', 'Search', 'Paused'.
		 */
		do_action("after_plugin_row_{$module_file}", $module_file, $module_data, $status);
	}

	/**
	 * Gets the name of the primary column for this specific list table.
	 *
	 * @return string Unalterable name for the primary column, in this case, 'name'.
	 */
	protected function get_primary_column_name()
	{
		return 'name';
	}
}
