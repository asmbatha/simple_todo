<!DOCTYPE HTML>
<html>
<head>
	<title><?php appinfo('title'); ?></title>
	<link rel="stylesheet" type="text/css" href="<?php appinfo('template_url'); ?>css/style.css" media="all" />
</head>
<body>
	<div class="wrap">
		<h1 id="title"><?php appinfo('title'); ?> </h1>
		<div id="msg"><span class="msg-text"></span><div class="msg-details"></div></div>
		<div id="task-list">
			<div id="loading"></div>
			<ul>

			</ul>
			<button id="complete-all">Complete all tasks</button>		
		</div>
		<form id="add-new-task" autocomplete="off">
			<input type="text" name="task" id="task" placeholder="Add a new task..." />
		</form>
	</div><!-- #wrap -->
</body>
	<!-- JavaScript Files Go Here -->
	<script src="http://code.jquery.com/jquery-latest.min.js"></script>

    <script type="text/javascript" src="<?php appinfo('app_url'); ?>js/todo.js"></script>
    <script type="text/javascript" src="<?php appinfo('app_url'); ?>js/todo_ajax_storage.js"></script>

    <script type="text/javascript">
        $().ready(function () {
            todo.appUrl = "<?php appinfo('app_url'); ?>";
            todo.templateUrl = "<?php appinfo('template_url'); ?>";
            todo.db = new todoStorageAjax(todo);
            todo.init({}).loadTasks();
        });
    </script>
</html>