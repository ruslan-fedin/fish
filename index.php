<?php

define('VG_ACCESS',true);
header('Content-type: text/html; charset=utf-8');
session_start();

require_once 'config.php';
require_once 'core/base/settings/internal_setting.php';
require_once 'libraries/function.php';

use core\base\controller\RouteController;
use core\base\exceptions\RoteException;



try {
 RouteController::getInstance()->route();
}
catch (RoteException $e){
    exit($e->getMessage());
}


//phpinfo();
