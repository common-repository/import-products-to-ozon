<?php defined( 'ABSPATH' ) || exit;
require_once IP2OZ_PLUGIN_DIR_PATH . 'common-libs/icopydoc-useful-functions-1-1-8.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'common-libs/wc-add-functions-1-0-1.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'common-libs/class-icpd-feedback-1-0-3.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'common-libs/class-icpd-promo-1-1-0.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'common-libs/class-icpd-set-admin-notices.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'common-libs/backward-compatibility.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'functions.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'extensions.php';

require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/class-ip2oz.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/class-ip2oz-data-arr.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/class-ip2oz-debug-page.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/class-ip2oz-error-log.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/class-ip2oz-interface-hocked.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/pages/extensions-page/class-ip2oz-extensions-page.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/pages/settings-page/class-ip2oz-settings-page.php';

require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/generation/traits/common/trait-ip2oz-t-common-get-catid.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/generation/traits/common/trait-ip2oz-t-common-skips.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/generation/traits/global/traits-ip2oz-global-variables.php';

require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/generation/class-ip2oz-api.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/generation/class-ip2oz-api-helper.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/generation/class-ip2oz-api-helper-simple.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/generation/class-ip2oz-api-helper-variable.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/generation/class-ip2oz-generation-xml.php';

require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/updates/class-ip2oz-plugin-form-activate.php';
require_once IP2OZ_PLUGIN_DIR_PATH . 'classes/system/updates/class-ip2oz-plugin-upd.php';