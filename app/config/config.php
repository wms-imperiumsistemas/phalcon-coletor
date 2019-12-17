<?php
/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');
date_default_timezone_set('America/Fortaleza');

return new \Phalcon\Config([
    'database' => [
        'adapter'     => 'Oracle',
        'host'        => 'localhost',
        'username'    => 'wms_lojao',
        'password'    => 'wms_adm',
        'dbname'      => '(DESCRIPTION = (ADDRESS_LIST =(LOAD_BALANCE=ON) (FAILOVER=ON) (ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1521)))(CONNECT_DATA =(SID  = xe)(FAILOVER_MODE = (TYPE=SELECT)(METHOD=PRECONNECT))))',
        'charset'     => 'utf8',
    ],
    'application' => [
        'appDir'         => APP_PATH . '/',
        'controllersDir' => APP_PATH . '/controllers/',
        'modelsDir'      => APP_PATH . '/models/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'viewsDir'       => APP_PATH . '/views/',
        'pluginsDir'     => APP_PATH . '/plugins/',
        'libraryDir'     => APP_PATH . '/library/',
        'servicesDir'    => APP_PATH . '/services/',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => '/',
    ]
]);
