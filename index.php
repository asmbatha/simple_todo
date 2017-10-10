<?php

require_once('./init.php');

define('TEMPLATEPATH', APP_PATH. 'themes/'.Config::get('template').'/');

require(TEMPLATEPATH. 'index.php');

?>