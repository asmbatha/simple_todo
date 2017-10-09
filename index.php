<?php

require_once('./init.php');

define('TEMPLATEPATH', APPPATH. 'themes/'.Config::get('template').'/');

require(TEMPLATEPATH. 'index.php');

?>