(function(){

var app;

function todoStorageAjax(aapp) 
{
	this.app = app = aapp;
}

window.todoStorageAjax = todoStorageAjax;

todoStorageAjax.prototype = 
{
	/* required method */
	request:function(action, params, callback)
	{
		if(!this[action]) throw "Unknown storage action: "+action;

		this[action](params, function(json){
			if(json.denied) app.errorDenied();
			if(callback) callback.call(app, json)
		});
	},


	loadTasks: function(params, callback)
	{
		$.getJSON(this.app.appUrl+'ajax.php?loadTasks', callback);
	},


	newTask: function(params, callback)
	{
		$.post(this.app.appUrl+'ajax.php?newTask',
			{ list:params.list, title: params.title, tag:params.tag }, callback, 'json');
	},
	
	completeTask: function(params, callback)
	{
		$.post(this.app.appUrl+'ajax.php?completeTask='+params.id, { id:params.id, compl:params.compl }, callback, 'json');
	},


	deleteTask: function(params, callback)
	{
		$.post(this.app.appUrl+'ajax.php?deleteTask='+params.id, { id:params.id }, callback, 'json');
	},
};

})();