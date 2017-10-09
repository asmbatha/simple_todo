<?php
set_error_handler('myErrorHandler');
set_exception_handler('myExceptionHandler');

require_once('./init.php');

$db = DBConnection::instance();

if(isset($_GET['loadLists']))
{
	
	$sqlWhere = '';
	$t = array();
	$t['total'] = 0;
	$q = $db->dq("SELECT * FROM {$db->prefix}lists $sqlWhere ORDER BY ow ASC, id ASC");
	while($r = $q->fetch_assoc($q))
	{
		$t['total']++;
		$t['list'][] = prepareList($r);
	}
	jsonExit($t);
}
elseif(isset($_GET['loadTasks']))
{
	stop_gpc($_GET);
	$listId = (int)_get('list');
	check_read_access($listId);

	$sqlWhere = $inner = '';
	if($listId == -1) {
		$userLists = getUserListsSimple();
		$sqlWhere .= " AND {$db->prefix}todolist.list_id IN (". implode(array_keys($userLists), ','). ") ";
	}
	else $sqlWhere .= " AND {$db->prefix}todolist.list_id=". $listId;
	

	$s = trim(_get('s'));
	if($s != '') $sqlWhere .= " AND (title LIKE ". $db->quoteForLike("%%%s%%",$s). " OR note LIKE ". $db->quoteForLike("%%%s%%",$s). ")";
	$sort = (int)_get('sort');
	$sqlSort = "ORDER BY compl ASC, ";

	$t = array();
	$t['total'] = 0;
	$t['list'] = array();
	$q = $db->dq("SELECT *, duedate IS NULL AS ddn FROM {$db->prefix}todolist $inner WHERE 1=1 $sqlWhere $sqlSort");
	while($r = $q->fetch_assoc($q))
	{
		$t['total']++;
		$t['list'][] = prepareTaskRow($r);
	}
	if(_get('setCompl') && have_write_access($listId)) {
		$bitwise = (_get('compl') == 0) ? 'taskview & ~1' : 'taskview | 1';
		$db->dq("UPDATE {$db->prefix}lists SET taskview=$bitwise WHERE id=$listId");
	}
	jsonExit($t);
}
elseif(isset($_GET['newTask']))
{
	stop_gpc($_POST);
	$listId = (int)_post('list');
	check_write_access($listId);
	$t = array();
	$t['total'] = 0;
	$title = trim(_post('title'));
	if(Config::get('smartsyntax') != 0)
	{
		$a = parse_smartsyntax($title);
		if($a === false) {
			jsonExit($t);
		}
		$title = $a['title'];
	}
	if($title == '') {
		jsonExit($t);
	}
	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=0");
	$db->ex("BEGIN");
	$db->dq("INSERT INTO {$db->prefix}todolist (uuid,list_id,title,d_created,d_edited,ow) VALUES (?,?,?,?,?,?)",
				array(generateUUID(), $listId, $title, time(), time(), $ow) );
	$id = $db->last_insert_id();
	$db->ex("COMMIT");
	$r = $db->sqa("SELECT * FROM {$db->prefix}todolist WHERE id=$id");
	$t['list'][] = prepareTaskRow($r);
	$t['total'] = 1;
	jsonExit($t);
}
elseif(isset($_GET['fullNewTask']))
{
	stop_gpc($_POST);
	$listId = (int)_post('list');
	check_write_access($listId);
	$title = trim(_post('title'));
	$note = str_replace("\r\n", "\n", trim(_post('note')));
	$duedate = parse_duedate(trim(_post('duedate')));
	$t = array();
	$t['total'] = 0;
	if($title == '') {
		jsonExit($t);
	}
	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=0");
	$db->ex("BEGIN");
	$db->dq("INSERT INTO {$db->prefix}todolist (uuid,list_id,title,d_created,d_edited,ow,note,duedate) VALUES(?,?,?,?,?,?,?,?)",
				array(generateUUID(), $listId, $title, time(), time(), $ow, $note, $duedate) );
	$id = $db->last_insert_id();
	$db->ex("COMMIT");
	$r = $db->sqa("SELECT * FROM {$db->prefix}todolist WHERE id=$id");
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
	check_write_access();
	$id = (int)_post('id');
	$compl = _post('compl') ? 1 : 0;
	$listId = (int)$db->sq("SELECT list_id FROM {$db->prefix}todolist WHERE id=$id");
	if($compl) 	$ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=1");
	else $ow = 1 + (int)$db->sq("SELECT MAX(ow) FROM {$db->prefix}todolist WHERE list_id=$listId AND compl=0");
	$dateCompleted = $compl ? time() : 0;
	$db->dq("UPDATE {$db->prefix}todolist SET compl=$compl,ow=$ow,d_completed=?,d_edited=? WHERE id=$id",
				array($dateCompleted, time()) );
	$t = array();
	$t['total'] = 1;
	$r = $db->sqa("SELECT * FROM {$db->prefix}todolist WHERE id=$id");
	$t['list'][] = prepareTaskRow($r);
	jsonExit($t);
}
elseif(isset($_GET['addList']))
{
	check_write_access();
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
elseif(isset($_GET['parseTaskStr']))
{
	check_write_access();
	stop_gpc($_POST);
	$t = array(
		'title' => trim(_post('title')),
	);
	if(Config::get('smartsyntax') != 0 && (false !== $a = parse_smartsyntax($t['title'])))
	{
		$t['title'] = $a['title'];
	}
	jsonExit($t);
}
elseif(isset($_GET['clearCompletedInList']))
{
	check_write_access();
	stop_gpc($_POST);
	$t = array();
	$t['total'] = 0;
	$listId = (int)_post('list');
	$db->ex("BEGIN");
	$db->ex("DELETE FROM {$db->prefix}todolist WHERE list_id=$listId and compl=1");
	$t['total'] = $db->affected();
	$db->ex("COMMIT");
	jsonExit($t);
}

###################################################################################################

function prepareTaskRow($r)
{
	$lang = Lang::instance();

	return array(
		'id' => $r['id'],
		'title' => escapeTags($r['title']),
		'listId' => $r['list_id'],
		'compl' => (int)$r['compl'],
		'ow' => (int)$r['ow'],
	);
}

function check_read_access($listId = null)
{
	$db = DBConnection::instance();
	if($listId !== null)
	{
		$id = $db->sq("SELECT id FROM {$db->prefix}lists WHERE id=? AND published=1", array($listId));
		if($id) return;
	}
	jsonExit( array('total'=>0, 'list'=>array(), 'denied'=>1) );
}

function have_write_access($listId = null)
{
	if(is_readonly()) return false;
	// check list exist
	if($listId !== null)
	{
		$db = DBConnection::instance();
		$count = $db->sq("SELECT COUNT(*) FROM {$db->prefix}lists WHERE id=?", array($listId));
		if(!$count) return false;
	}
	return true;
}

function check_write_access($listId = null)
{
	if(have_write_access($listId)) return;
	jsonExit( array('total'=>0, 'list'=>array(), 'denied'=>1) );
}

function inputTaskParams()
{
	$a = array(
		'id' => _post('id'),
		'title'=> trim(_post('title')),
		'listId' => (int)_post('list'),

	);
	return $a;
}

function parse_smartsyntax($title)
{
	$a = array();
	if(!preg_match("|^(/([+-]{0,1}\d+)?/)?(.*?)(\s+/([^/]*)/$)?$|", $title, $m)) return false;
	$a['title'] = isset($m[3]) ? trim($m[3]) : '';
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
	check_write_access();
	$db = DBConnection::instance();
	$db->ex("BEGIN");
	//TODO: delete unused tags?
	$db->dq("DELETE FROM {$db->prefix}todolist WHERE id=$id");
	$affected = $db->affected();
	$db->ex("COMMIT");
	return $affected;
}

function prepareList($row)
{
	$taskview = (int)$row['taskview'];
	return array(
		'id' => $row['id'],
		'name' => htmlarray($row['name']),
		'sort' => (int)$row['sorting'],
		'published' => $row['published'] ? 1 :0,
		'showCompl' => $taskview & 1 ? 1 : 0,
		'showNotes' => $taskview & 2 ? 1 : 0,
		'hidden' => $taskview & 4 ? 1 : 0,
	);
}
?>