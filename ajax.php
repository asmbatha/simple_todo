<?php
set_error_handler('myErrorHandler');
set_exception_handler('myExceptionHandler');

require_once('./init.php');

$db = DBConnection::instance();

if(isset($_GET['loadTasks']))
{
	stop_gpc($_GET);

	$t = array();
	$t['total'] = 0;
	$t['list'] = array();
	$q = $db->dq("SELECT * FROM {$db->prefix}todo WHERE completed=0");
	while($r = $q->fetch_assoc($q))
	{
		$t['total']++;
		$t['list'][] = prepareTaskRow($r);
	}
	jsonExit($t);
}
elseif(isset($_GET['newTask']))
{
	stop_gpc($_POST);
	
	$t = array();
	$t['total'] = 0;
	$task = trim(_post('task'));
	if(Config::get('smartsyntax') != 0)
	{
		$a = parse_smartsyntax($task);
		if($a === false) {
			jsonExit($t);
		}
		$task = $a['task'];
	}
	if($task == '') {
		jsonExit($t);
	}

	$db->ex("BEGIN");
	$db->dq("INSERT INTO {$db->prefix}todo (task) VALUES (?)", array($task) );
	$id = $db->last_insert_id();
	$db->ex("COMMIT");
	$r = $db->sqa("SELECT * FROM {$db->prefix}todo WHERE id=$id");
	$t['list'][] = prepareTaskRow($r);
	$t['total'] = 1;
	jsonExit($t);
}
elseif(isset($_GET['deleteTask']))
{
	$id = (int)_post('id');
	$deleted = deleteTask($id);
	$t = array();
	$t['total'] = $deleted;
	$t['list'][] = array('id'=>$id);
	jsonExit($t);
}
elseif(isset($_GET['completeTask']))
{
	$id = (int)_post('id');

	$compl = 1;
	$db->dq("UPDATE {$db->prefix}todo SET completed=? WHERE id=?",
				array($compl, $id) );
	$t = array();
	$t['total'] = 1;
	$t['id'] = $id;
	$r = $db->sqa("SELECT * FROM {$db->prefix}todo WHERE id=$id");
	$t['list'][] = prepareTaskRow($r);
	jsonExit($t);
}
elseif(isset($_GET['addList']))
{
	stop_gpc($_POST);
	$t = array();
	$t['total'] = 0;
	$name = str_replace(array('"',"'",'<','>','&'),array('','','','',''),trim(_post('name')));
	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}lists");
	$db->dq("INSERT INTO {$db->prefix}lists (uuid,name,ow,d_created,d_edited) VALUES (?,?,?,?,?)",
				array(generateUUID(), $name, $ow, time(), time()) );
	$id = $db->last_insert_id();
	$t['total'] = 1;
	$r = $db->sqa("SELECT * FROM {$db->prefix}lists WHERE id=$id");
	$t['list'][] = prepareList($r);
	jsonExit($t);
}

###################################################################################################

function prepareTaskRow($r)
{
	return array(
		'id' => $r['id'],
		'task' => escapeTags($r['task']),
		'compl' => (int)$r['completed'],
	);
}

function inputTaskParams()
{
	$a = array(
		'id' => _post('id'),
		'task'=> trim(_post('task')),
	);
	return $a;
}

function parse_smartsyntax($task)
{
	$a = array();
	if(!preg_match("|^(/([+-]{0,1}\d+)?/)?(.*?)(\s+/([^/]*)/$)?$|", $task, $m)) return false;
	$a['task'] = isset($m[3]) ? trim($m[3]) : '';
	return $a;
}

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
	if($errno==E_ERROR || $errno==E_CORE_ERROR || $errno==E_COMPILE_ERROR || $errno==E_USER_ERROR || $errno==E_PARSE) $error = 'Error';
	elseif($errno==E_WARNING || $errno==E_CORE_WARNING || $errno==E_COMPILE_WARNING || $errno==E_USER_WARNING || $errno==E_STRICT) {
		if(error_reporting() & $errno) $error = 'Warning'; else return;
	}
	elseif($errno==E_NOTICE || $errno==E_USER_NOTICE) {
		if(error_reporting() & $errno) $error = 'Notice'; else return;
	}
	elseif(defined('E_DEPRECATED') && ($errno==E_DEPRECATED || $errno==E_USER_DEPRECATED)) { # since 5.3.0
		if(error_reporting() & $errno) $error = 'Notice'; else return;
	}
	else $error = "Error ($errno)";	# here may be E_RECOVERABLE_ERROR
	throw new Exception("$error: '$errstr' in $errfile:$errline", -1);
}

function myExceptionHandler($e)
{
	try { // to avoid Exception thrown without a stack frame
		if(-1 == $e->getCode()) {
			echo $e->getMessage()."\n". $e->getTraceAsString();
			exit;
		}
		echo 'Exception: \''. $e->getMessage() .'\' in '. $e->getFile() .':'. $e->getLine(); //."\n". $e->getTraceAsString();
	}
	catch(Exception $e) {
		echo 'Exception in ExceptionHandler: \''. $e->getMessage() .'\' in '. $e->getFile() .':'. $e->getLine();
	}
	exit;
}

function deleteTask($id)
{
	$db = DBConnection::instance();
	$db->ex("BEGIN");
	$db->dq("DELETE FROM {$db->prefix}todo WHERE id=$id");
	$affected = $db->affected();
	$db->ex("COMMIT");
	return $affected;
}
?>