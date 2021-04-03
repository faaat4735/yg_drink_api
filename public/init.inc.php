<?php

define('ROOT_DIR', dirname(__DIR__)  . '/');
define('CONFIG_DIR', ROOT_DIR  . 'config/');
define('LOG_DIR', ROOT_DIR . 'log/');
define('VENDOR_DIR', ROOT_DIR . 'vendor/');
define('APP_DIR', ROOT_DIR  . 'app/');
define('CORE_DIR', APP_DIR  . 'core/');
define('CERT_DIR', ROOT_DIR  . 'cert/');



/**
 * load the private configure
 */
if (file_exists(CONFIG_DIR . 'config.private.php')) {
    include CONFIG_DIR . "config.private.php";
}


!defined('DB_HOST') && define('DB_HOST', 'localhost');
!defined('DB_PORT') && define('DB_PORT', 3306);
!defined('DB_USERNAME') && define('DB_USERNAME', 'root');
!defined('DB_PASSWORD') && define('DB_PASSWORD', '123456');
!defined('DB_DATABASE') && define('DB_DATABASE', 'rz_walk');

!defined('ADMIN_USER') && define('ADMIN_USER', 'admin');
!defined('ADMIN_PASSWORD') && define('ADMIN_PASSWORD', '');

!defined('OSS_HOST') && define('OSS_HOST', 'https://oss.shyonggui.com/');

!defined('ENV_PRODUCTION') && define('ENV_PRODUCTION', FALSE);
!defined('DEBUG_MODE') && define('DEBUG_MODE', FALSE);

!defined('ALI_KEYID') && define('ALI_KEYID', '');
!defined('ALI_KEYSECRET') && define('ALI_KEYSECRET', '');
!defined('OSS_ENDPOINT') && define('OSS_ENDPOINT', '');
!defined('OSS_BUCKET') && define('OSS_BUCKET', '');

!defined('PAY_MODE') && define('PAY_MODE', FALSE);

!defined('WECHAT_APPID') && define('WECHAT_APPID', '');
!defined('WECHAT_ID') && define('WECHAT_ID', '');
!defined('WECHAT_KEY') && define('WECHAT_KEY', '');

require VENDOR_DIR . 'autoload.php';