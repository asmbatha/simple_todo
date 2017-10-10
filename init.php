<?php

if(!defined('APP_PATH')) define('APP_PATH', dirname(__FILE__) .'/');

require_once(APP_PATH. 'common.php');
require_once(APP_PATH. 'db/config.php');

ini_set('display_errors', 'On');

if(!isset($config)) global $config;
Config::loadConfig($config);
unset($config);

date_default_timezone_set(Config::get('timezone'));

# Database Connection
if(Config::get('db') == 'sqlite')
{
	require_once(APP_PATH. 'class.db.sqlite3.php');
	$db = DBConnection::init(new Database_Sqlite3);
	$db->connect(APP_PATH. 'db/todo.db');
}
else {
	# It seems not installed
	die("Not installed. Run <a href=setup.php>setup.php</a> first.");
}
$db->prefix = Config::get('prefix');

$_appinfo = array();

function appinfo($v)
{
	global $_appinfo;
	if(!isset($_appinfo[$v])) {
		echo get_appinfo($v);
	} else {
		echo $_appinfo[$v];
	}
}

function get_appinfo($v)
{
	global $_appinfo;
	if(isset($_appinfo[$v])) return $_appinfo[$v];
	switch($v)
	{
		case 'template_url':
			$_appinfo['template_url'] = get_appinfo('app_url'). 'themes/'. Config::get('template') . '/';
			return $_appinfo['template_url'];
		case 'url':
			$_appinfo['url'] = Config::get('url');
			if($_appinfo['url'] == '')
				$_appinfo['url'] = 'http://'.$_SERVER['HTTP_HOST'] .($_SERVER['SERVER_PORT'] != 80 ? ':'.$_SERVER['SERVER_PORT'] : '').
									url_dir(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME']);
			return $_appinfo['url'];
		case 'app_url':
			$_appinfo['app_url'] = Config::get('app_url');
			if($_appinfo['app_url'] == '') $_appinfo['app_url'] = url_dir(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME']);
			return $_appinfo['app_url'];
		case 'title':
			$_appinfo['title'] = (Config::get('title') != '') ? htmlarray(Config::get('title')) : __('Price Check Todolist');
			return $_appinfo['title'];
	}
}

function jsonExit($data)
{
	header('Content-type: application/json; charset=utf-8');
	echo json_encode($data);
	exit;
}

?>