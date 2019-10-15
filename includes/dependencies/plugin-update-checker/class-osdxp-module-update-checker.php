<?php
/**
 * Module update checker extended from vendor PUC
 *
 * @see   Puc_v4p7_Plugin_UpdateChecker
 */
namespace OSDXP_Dashboard;

// phpcs:disable
require OSDXP_DASHBOARD_DIR . 'includes/dependencies/plugin-update-checker/class-osdxp-module-update-checker-ui.php';
// phpcs:enable

class OsdxpModuleUpdateChecker extends \Puc_v4p7_UpdateChecker
{
	protected $updateTransient = 'update_plugins';
	protected $translationType = 'plugin';

	public $pluginAbsolutePath = ''; //Full path of the main module file.
	public $pluginFile = '';  //Module filename relative to the modules directory.
	public $muPluginFile = ''; //For MU modules, the module filename relative to the mu-plugins directory.

		/**
		 * @var Puc_v4p7_Plugin_Package
		 */
	protected $package;

	private $extraUi = null;
		// phpcs:disable
		/**
		 * Class constructor.
		 *
		 * @param string $metadataUrl The URL of the module's metadata file.
		 * @param string $pluginFile Fully qualified path to the main module file.
		 * @param string $slug The module's 'slug'. If not specified, the filename part of $pluginFile sans '.php' will be used as the slug.
		 * @param integer $checkPeriod How often to check for updates (in hours). Defaults to checking every 12 hours. Set to 0 to disable automatic update checks.
		 * @param string $optionName Where to store book-keeping info about update checks. Defaults to 'external_updates-$slug'.
		 * @param string $muPluginFile Optional. The module filename relative to the mu-plugins directory.
		 */
		// phpcs:enable
	public function __construct(
		$metadataUrl,
		$pluginFile,
		$slug = '',
		$checkPeriod = 12,
		$optionName = '',
		$muPluginFile = ''
	) {
		$this->pluginAbsolutePath = $pluginFile;
		$this->pluginFile = plugin_basename($this->pluginAbsolutePath);
		$this->muPluginFile = $muPluginFile;

		//If no slug is specified, use the name of the main module file as the slug.
		//For example, 'my-cool-module/cool-module.php' becomes 'cool-module'.
		if (empty($slug)) {
			$slug = basename($this->pluginFile, '.php');
		}

		//Module slugs must be unique.
		$slugCheckFilter = 'puc_is_slug_in_use-' . $slug;
		$slugUsedBy = apply_filters($slugCheckFilter, false);
		if ($slugUsedBy) {
			$this->triggerError(sprintf(
				'Module slug "%s" is already in use by %s. Slugs must be unique.',
				htmlentities($slug),
				htmlentities($slugUsedBy)
			), E_USER_ERROR);
		}
		add_filter($slugCheckFilter, array($this, 'getAbsolutePath'));

		//Backwards compatibility: If the module is a mu-plugin but no $muPluginFile is specified, assume
		//it's the same as $pluginFile given that it's not in a subdirectory (WP only looks in the base dir).
		if ((strpbrk($this->pluginFile, '/\\') === false) && $this->isUnknownMuPlugin()) {
			$this->muPluginFile = $this->pluginFile;
		}

		//To prevent a crash during module uninstallation, remove updater hooks when the user removes the module.
		//Details: https://github.com/YahnisElsts/plugin-update-checker/issues/138#issuecomment-335590964
		add_action('uninstall_' . $this->pluginFile, array($this, 'removeHooks'));

		parent::__construct($metadataUrl, dirname($this->pluginFile), $slug, $checkPeriod, $optionName);

		$this->extraUi = new OsdxpModuleUpdateCheckerUi($this);
	}

		/**
		 * Create an instance of the scheduler.
		 *
		 * @param int $checkPeriod
		 * @return Puc_v4p7_Scheduler
		 */
	protected function createScheduler($checkPeriod)
	{
		$scheduler = new \Puc_v4p7_Scheduler($this, $checkPeriod, array('load-plugins.php'));
		register_deactivation_hook($this->pluginFile, array($scheduler, 'removeUpdaterCron'));
		return $scheduler;
	}

		/**
		 * Install the hooks required to run periodic update checks and inject update info
		 * into WP data structures.
		 *
		 * @return void
		 */
	protected function installHooks()
	{
		//Override requests for plugin information
		add_filter('plugins_api', array($this, 'injectInfo'), 20, 3);

		parent::installHooks();
	}

		/**
		 * Remove update checker hooks.
		 *
		 * The intent is to prevent a fatal error that can happen if the module has an uninstall
		 * hook. During uninstallation, WP includes the main module file (which creates a PUC instance),
		 * the uninstall hook runs, WP deletes the module files and then updates some transients.
		 * If PUC hooks are still around at this time, they could throw an error while trying to
		 * autoload classes from files that no longer exist.
		 *
		 * The "site_transient_{$transient}" filter is the main problem here, but let's also remove
		 * most other PUC hooks to be safe.
		 *
		 * @internal
		 */
	public function removeHooks()
	{
		parent::removeHooks();
		$this->extraUi->removeHooks();
		$this->package->removeHooks();

		remove_filter('plugins_api', array($this, 'injectInfo'), 20);
	}

		/**
		 * Retrieve module info from the configured API endpoint.
		 *
		 * @uses wp_remote_get()
		 *
		 * @param array $queryArgs Additional query arguments to append to the request. Optional.
		 * @return Puc_v4p7_Plugin_Info
		 */
	public function requestInfo($queryArgs = array())
	{
		list($pluginInfo, $result) = $this->requestMetadata('Puc_v4p7_Plugin_Info', 'request_info', $queryArgs);

		if ($pluginInfo !== null) {
			/** @var Puc_v4p7_Plugin_Info $pluginInfo */
			$pluginInfo->filename = $this->pluginFile;
			$pluginInfo->slug = $this->slug;
		}

		$pluginInfo = apply_filters($this->getUniqueName('request_info_result'), $pluginInfo, $result);
		return $pluginInfo;
	}

		/**
		 * Retrieve the latest update (if any) from the configured API endpoint.
		 *
		 * @uses PluginUpdateChecker::requestInfo()
		 *
		 * @return Puc_v4p7_Update|null An instance of Plugin_Update, or NULL when no updates are available.
		 */
	public function requestUpdate()
	{
		//For the sake of simplicity, this function just calls requestInfo()
		//and transforms the result accordingly.
		$pluginInfo = $this->requestInfo(array('checking_for_updates' => '1'));
		if ($pluginInfo === null) {
			return null;
		}
		$update = \Puc_v4p7_Plugin_Update::fromPluginInfo($pluginInfo);

		$update = $this->filterUpdateResult($update);

		return $update;
	}

		/**
		 * Intercept plugins_api() calls that request information about our module and
		 * use the configured API endpoint to satisfy them.
		 *
		 * @see plugins_api()
		 *
		 * @param mixed $result
		 * @param string $action
		 * @param array|object $args
		 * @return mixed
		 */
	public function injectInfo($result, $action = null, $args = null)
	{
		$relevant = ($action == 'plugin_information') && isset($args->slug) && (
				($args->slug == $this->slug) || ($args->slug == dirname($this->pluginFile))
			);
		if (!$relevant) {
			return $result;
		}

		$pluginInfo = $this->requestInfo();
		$pluginInfo = apply_filters($this->getUniqueName('pre_inject_info'), $pluginInfo);
		if ($pluginInfo) {
			return $pluginInfo->toWpFormat();
		}

		return $result;
	}

	protected function shouldShowUpdates()
	{
		//No update notifications for mu-plugins unless explicitly enabled. The MU module file
		//is usually different from the main module file so the update wouldn't show up properly anyway.
		return !$this->isUnknownMuPlugin();
	}

		/**
		 * @param stdClass|null $updates
		 * @param stdClass $updateToAdd
		 * @return stdClass
		 */
	protected function addUpdateToList($updates, $updateToAdd)
	{
		if ($this->package->isMuPlugin()) {
			//WP does not support automatic update installation for mu-plugins, but we can
			//still display a notice.
			$updateToAdd->package = null;
		}
		return parent::addUpdateToList($updates, $updateToAdd);
	}

		/**
		 * @param stdClass|null $updates
		 * @return stdClass|null
		 */
	protected function removeUpdateFromList($updates)
	{
		$updates = parent::removeUpdateFromList($updates);
		if (!empty($this->muPluginFile) && isset($updates, $updates->response)) {
			unset($updates->response[$this->muPluginFile]);
		}
		return $updates;
	}

		/**
		 * For modules, the update array is indexed by the modules filename relative to the "modules"
		 * directory. Example: "module-name/module.php".
		 *
		 * @return string
		 */
	protected function getUpdateListKey()
	{
		if ($this->package->isMuPlugin()) {
			return $this->muPluginFile;
		}
		return $this->pluginFile;
	}

		/**
		 * Alias for isBeingUpgraded().
		 *
		 * @deprecated
		 * @param WP_Upgrader|null $upgrader The upgrader that's performing the current update.
		 * @return bool
		 */
	public function isPluginBeingUpgraded($upgrader = null)
	{
		return $this->isBeingUpgraded($upgrader);
	}

		/**
		 * Is there an update being installed for this module, right now?
		 *
		 * @param WP_Upgrader|null $upgrader
		 * @return bool
		 */
	public function isBeingUpgraded($upgrader = null)
	{
		return $this->upgraderStatus->isPluginBeingUpgraded($this->pluginFile, $upgrader);
	}

		/**
		 * Get the details of the currently available update, if any.
		 *
		 * If no updates are available, or if the last known update version is below or equal
		 * to the currently installed version, this method will return NULL.
		 *
		 * Uses cached update data. To retrieve update information straight from
		 * the metadata URL, call requestUpdate() instead.
		 *
		 * @return Puc_v4p7_Plugin_Update|null
		 */
	public function getUpdate()
	{
		$update = parent::getUpdate();
		if (isset($update)) {
			/** @var Puc_v4p7_Plugin_Update $update */
			$update->filename = $this->pluginFile;
		}
		return $update;
	}

		/**
		 * Get the translated module title.
		 *
		 * @deprecated
		 * @return string
		 */
	public function getPluginTitle()
	{
		return $this->package->getPluginTitle();
	}

		/**
		 * Check if the current user has the required permissions to install updates.
		 *
		 * @return bool
		 */
	public function userCanInstallUpdates()
	{
		return current_user_can('update_plugins');
	}

		/**
		 * Check if the module file is inside the mu-plugins directory.
		 *
		 * @deprecated
		 * @return bool
		 */
	protected function isMuPlugin()
	{
		return $this->package->isMuPlugin();
	}

		/**
		 * MU modules are partially supported, but only when we know which file in mu-plugins
		 * corresponds to this module.
		 *
		 * @return bool
		 */
	protected function isUnknownMuPlugin()
	{
		return empty($this->muPluginFile) && $this->package->isMuPlugin();
	}

		/**
		 * Get absolute path to the main module file.
		 *
		 * @return string
		 */
	public function getAbsolutePath()
	{
		return $this->pluginAbsolutePath;
	}

		/**
		 * Register a callback for filtering query arguments.
		 *
		 * The callback function should take one argument - an associative array of query arguments.
		 * It should return a modified array of query arguments.
		 *
		 * @uses add_filter() This method is a convenience wrapper for add_filter().
		 *
		 * @param callable $callback
		 * @return void
		 */
	public function addQueryArgFilter($callback)
	{
		$this->addFilter('request_info_query_args', $callback);
	}

		/**
		 * Register a callback for filtering arguments passed to wp_remote_get().
		 *
		 * The callback function should take one argument - an associative array of arguments -
		 * and return a modified array or arguments. See the WP documentation on wp_remote_get()
		 * for details on what arguments are available and how they work.
		 *
		 * @uses add_filter() This method is a convenience wrapper for add_filter().
		 *
		 * @param callable $callback
		 * @return void
		 */
	public function addHttpRequestArgFilter($callback)
	{
		$this->addFilter('request_info_options', $callback);
	}

		/**
		 * Register a callback for filtering the module info retrieved from the external API.
		 *
		 * The callback function should take two arguments. If the module info was retrieved
		 * successfully, the first argument passed will be an instance of  PluginInfo. Otherwise,
		 * it will be NULL. The second argument will be the corresponding return value of
		 * wp_remote_get (see WP docs for details).
		 *
		 * The callback function should return a new or modified instance of PluginInfo or NULL.
		 *
		 * @uses add_filter() This method is a convenience wrapper for add_filter().
		 *
		 * @param callable $callback
		 * @return void
		 */
	public function addResultFilter($callback)
	{
		$this->addFilter('request_info_result', $callback, 10, 2);
	}

	protected function createDebugBarExtension()
	{
		return new \Puc_v4p7_DebugBar_PluginExtension($this);
	}

		/**
		 * Create a package instance that represents this plugin or theme.
		 *
		 * @return Puc_v4p7_InstalledPackage
		 */
	protected function createInstalledPackage()
	{
		return new \Puc_v4p7_Plugin_Package($this->pluginAbsolutePath, $this);
	}

		/**
		 * @return Puc_v4p7_Plugin_Package
		 */
	public function getInstalledPackage()
	{
		return $this->package;
	}
	/* -------------------------------------------------------------------
	 * JSON-based update API
	 * -------------------------------------------------------------------
	 */

	/**
	 * Retrieve plugin or theme metadata from the JSON document at $this->metadataUrl.
	 *
	 * @param string $metaClass Parse the JSON as an instance of this class. It must have a static fromJson method.
	 * @param string $filterRoot
	 * @param array $queryArgs Additional query arguments.
	 * @return array [Puc_v4p7_Metadata|null, array|WP_Error]
	 * A metadata instance and the value returned by wp_remote_get().
	 */
	protected function requestMetadata($metaClass, $filterRoot, $queryArgs = array())
	{
		//Query args to append to the URL.
		//Plugins can add their own by using a filter callback (see addQueryArgFilter()).
		$queryArgs = array_merge(
			array(
				'installed_version' => strval($this->getInstalledVersion()),
				'php' => phpversion(),
				'locale' => get_locale(),
			),
			$queryArgs
		);
		$queryArgs = apply_filters($this->getUniqueName($filterRoot . '_query_args'), $queryArgs);

		//Various options for the wp_remote_get() call. Plugins can filter these, too.
		$options = array(
			'timeout' => 10, //seconds
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			),
		);
		$options = apply_filters($this->getUniqueName($filterRoot . '_options'), $options);

		//The metadata file should be at 'http://your-api.com/url/here/$slug/info.json'
		$url = $this->metadataUrl;
		if (!empty($queryArgs)) {
			$url = add_query_arg($queryArgs, $url);
		}

		$result = wp_safe_remote_post($url, $options);

		$result = apply_filters($this->getUniqueName('request_metadata_http_result'), $result, $url, $options);

		//Try to parse the response
		$status = $this->validateApiResponse($result);
		$metadata = null;
		if (!is_wp_error($status)) {
			$metadata = call_user_func(array($metaClass, 'fromJson'), $result['body']);
		} else {
			do_action('puc_api_error', $status, $result, $url, $this->slug);
			$this->triggerError(
				sprintf('The URL %s does not point to a valid metadata file. ', $url)
				. $status->get_error_message(),
				E_USER_WARNING
			);
		}

		return array($metadata, $result);
	}
}
