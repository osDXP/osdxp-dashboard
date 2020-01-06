# Changelog

A historical record of notable changes to `osdxp-dashboard` will be documented in this file.

## [v1.1.1 (2019-12-27)](https://github.com/osDXP/osdxp-dashboard/releases/tag/v1.1.1)
- Apply proper colspan to module update messages
- Refactor dashboard templates logic
- Refactor redirect path for user logins that are using osDXP
- Account for osDXP dashboard status when deciding redirect path when checking for updates
- Update default available modules json file
- Reimplement available modules in regular admin on a multisite
- Fix checking for new modules on a single site in a multisite instance
- Fix proper path when switching to regular WordPress interface

## [v1.1.0 (2019-12-05)](https://github.com/osDXP/osdxp-dashboard/releases/tag/v1.1.0)
- Update repository meta files
- Make plugin agnostic of integrations
- Register assets by default and only enqueue them conditionally
- Use SCRIPT_DEBUG instead of WP_DEBUG when deciding what type of assets to load (production or development)

## [v1.0.3 (2019-11-05)](https://github.com/osDXP/osdxp-dashboard/releases/tag/v1.0.3)
- Update default available modules `json`
- Add slug filtering for modules
- Update `yahnis-elsts/plugin-update-checker` dep to `~4.8.0`
- Shift parent updater initialization logic to a point before member access
- Files with effects now loaded by main plugin
- Autoload file now loaded only if exists

## [v1.0.2 (2019-11-01)](https://github.com/osDXP/osdxp-dashboard/releases/tag/v1.0.2)
- Change osDXP logo from `png` to `svg`
- Change Dashboard action icons to be inline with the sidebar
- Fix incorrect instances of osDXP title
- Fix dashboard title position

## [v1.0.1 (2019-10-01)](https://github.com/osDXP/osdxp-dashboard/releases/tag/v1.0.1)
- Add classmap autoloading via composer
- Cleanup available modules endpoint

## [v1.0 (2019-10-01)](https://github.com/osDXP/osdxp-dashboard/releases/tag/v1.0)
- Initial release