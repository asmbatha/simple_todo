<?php
set_exception_handler('myExceptionHandler');

# Check old config file (prior v1.3)
require_once('./db/config.php');
if(!isset($config['db']))
{
	$config['db'] = 'sqlite';
}

if($config['db'] != '') 
{
	require_once('./init.php');
	
	$dbtype = 'sqlite';
}
else
{
	if(!defined('APP_PATH')) define('APP_PATH', dirname(__FILE__) .'/');
	require_once(APP_PATH. 'common.php');
	Config::loadConfig($config);
	unset($config); 

	$db = 0;
	$dbtype = '';
}

echo '<html><head><meta name="robots" content="noindex,nofollow"><title>'. Config::get('title') .' Todo Setup</title></head><body>'; 
echo "<big><b>". Config::get('title') ." Setup</b></big><br><br>";

# determine current installed version
$ver = get_ver($db, $dbtype);

if(!$ver)
{
	$dbtype = 'sqlite';
	if(Config::get('db') != 'sqlite'){
		Config::set('db', $dbtype);
		if(!testConnect($error)) {
			exitMessage("Database connection error: $error");
		}
		if(!is_writable('./db/config.php')) {
			exitMessage("Config file ('db/config.php') is not writable.");
		}
		Config::save();
		exitMessage("This will create todo database <form method=post><input type=hidden name=install value=1><input type=submit value=' Install '></form>");
	}
	# install database
	
	try
	{
		$db->ex(
		"CREATE TABLE {$db->prefix}todo (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			`task` TEXT NOT NULL,
			`completed` TINYINT UNSIGNED NOT NULL default 0
		) ");

	} catch (Exception $e) {
		exitMessage("<b>Error:</b> ". htmlarray($e->getMessage()));
	} 

}
echo "Done <a href=index.php>Click here to return to the Homepage.</a><br><br> <b>Attention!</b> Delete this file for security reasons.";
printFooter();


function get_ver($db, $dbtype)
{
	if(!$db || $dbtype == '') return '';
	if(!$db->table_exists($db->prefix.'todo')) return '';
	return true;
}

function exitMessage($s)
{
	echo $s;
	printFooter();
	exit;
}

function printFooter()
{
	echo "</body></html>"; 
}

function testConnect(&$error)
{
	try
	{
		if(false === $f = @fopen(APP_PATH. 'db/todo.db', 'a+')) throw new Exception("database file is not readable/writable");
		else fclose($f);

		if(!is_writable(APP_PATH. 'db/')) throw new Exception("database directory ('db') is not writable");

		require_once(APP_PATH. 'class.db.sqlite3.php');
		$db = new Database_Sqlite3;
		$db->connect(APP_PATH. 'db/todo.db');
	} catch(Exception $e) {
		$error = $e->getMessage();
		return 0;
	}
	return 1;
}

function myExceptionHandler($e)
{
	echo '<br><b>Fatal Error:</b> \''. $e->getMessage() .'\' in <i>'. $e->getFile() .':'. $e->getLine() . '</i>'.
		"\n<pre>". $e->getTraceAsString() . "</pre>\n";
	exit;
}

?>