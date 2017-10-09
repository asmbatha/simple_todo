(function(){

var taskList = new Array(), taskOrder = new Array();
var selTask = 0;
var flag = { needAuth:false, isLogged:false, tagsChanged:true, readOnly:false, editFormChanged:false };
var taskCnt = { total:0, past: 0, today:0, soon:0 };
var tabLists = {
	_lists: {},
	_length: 0,
	_order: [],
	_alltasks: {},
	clear: function(){
		this._lists = {}; this._length = 0; this._order = [];
		this._alltasks = { id:-1, showCompl:0, sort:3 }; 
	},
	length: function(){ return this._length; },
	exists: function(id){ if(this._lists[id] || id==-1) return true; else return false; },
	add: function(list){ this._lists[list.id] = list; this._length++; this._order.push(list.id); },
	replace: function(list){ this._lists[list.id] = list; },
	get: function(id){ if(id==-1) return this._alltasks; else return this._lists[id]; },
	getAll: function(){ var r = []; for(var i in this._order) { r.push(this._lists[this._order[i]]); }; return r; },
	reorder: function(order){ this._order = order; }
};
var curList = 0;
var tagsList = [];

var todo = window.todo = _td = {

	theme: {
		newTaskFlashColor: '#ffffaa',
		editTaskFlashColor: '#bbffaa',
		msgFlashColor: '#ffffff'
	},

	actions: {},
	menus: {},
	appUrl: '',
	templateUrl: '',

	// procs
	init: function(options)
	{
		jQuery.extend(this.options, options);

		// handlers
		$('#newtask_form').submit(function(){
			submitNewTask(this);
			return false;
		});
		
		$('#newtask_submit').click(function(){
			$('#newtask_form').submit();
		});
		
		// tasklist handlers
		$("#tasklist").bind("click", tasklistClick);

		// AJAX Errors
		$('#msg').ajaxSend(function(r,s){
			$("#msg").hide().removeClass('mtt-error mtt-info').find('.msg-details').hide();
			$("#loading").show();
		});

		$('#msg').ajaxStop(function(r,s){
			$("#loading").fadeOut();
		});

		$('#msg').ajaxError(function(event, request, settings){
			var errtxt;
			if(request.status == 0) errtxt = 'Bad connection';
			else if(request.status != 200) errtxt = 'HTTP: '+request.status+'/'+request.statusText;
			else errtxt = request.responseText;
			flashError(_td.lang.get('error'), errtxt); 
		}); 


		// Error Message details
		$("#msg>.msg-text").click(function(){
			$("#msg>.msg-details").toggle();
		});

		$(window).bind('beforeunload', function() {
			if(_td.pages.current.page == 'taskedit' && flag.editFormChanged) {
				return _td.lang.get('confirmLeave');
			}
		});


		return this;
	},

	log: function(v)
	{
		console.log.apply(this, arguments);
	},

};

function loadTasks(opts)
{
	if(!curList) return false;
	opts = opts || {};
	if(opts.clearTasklist) {
		$('#tasklist').html('');
		$('#total').html('0');
	}

	_td.db.request('loadTasks', { }, function(json){
		taskList.length = 0;
		taskCnt.total = taskCnt.past = taskCnt.today = taskCnt.soon = 0;
		var tasks = '';
		$.each(json.list, function(i,item){
			tasks += prepareTaskStr(item);
			taskList[item.id] = item;
			changeTaskCnt(item, 1);
		});
		if(opts.beforeShow && opts.beforeShow.call) {
			opts.beforeShow();
		}
		refreshTaskCnt();
		$('#tasklist').html(tasks);
	});
};


function prepareTaskStr(item)
{
	return `<li id="taskrow_">${item.id} ${item.title}</li>`;
};


function submitNewTask(form)
{
	if(form.task.value == '') return false;
	_td.db.request('newTask', { list:curList.id, title: form.task.value }, function(json){
		
	}); 
	flag.tagsChanged = true;
	return false;
};

function deleteTask(id)
{
	if(!confirm(_td.lang.get('confirmDelete'))) {
		return false;
	}
	_td.db.request('deleteTask', {id:id}, function(json){=
	});
	flag.tagsChanged = true;
	return false;
};

function completeTask(id, ch)
{
	if(!taskList[id]) return; //click on already removed from the list while anim. effect
	var compl = 0;
	if(ch.checked) compl = 1;
	_td.db.request('completeTask', {id:id, compl:compl, list:curList.id}, function(json){
	});
	return false;
};

})();