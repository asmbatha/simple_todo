(function(){


var todo = window.todo = _td = {

	// procs
	init: function(options)
	{
		jQuery.extend(this.options, options);

		// handlers
		$('#add-new-task').submit(function(e){
			e.preventDefault();
			submitNewTask(this);
			return completeTask;  
		});
		
		
		$('#task-list').on('click', '.complete-button', function(e){
			e.preventDefault();
			completeTask(this)
			return false;  
		});
		
		$('#complete-all').click(function(e){
			e.preventDefault();
			$('.complete-button').each(function() { completeTask(this) })
			return false;  
		});

		// AJAX Errors
		$( document ).ajaxSend(function(r,s){
			$("#msg").hide().removeClass('msg-error msg-info').find('.msg-details').hide();			
			$("#loading").show();
		});

		$( document ).ajaxStop(function(r,s){
			$("#loading").fadeOut();
		});

		$( document ).ajaxError(function(event, request, settings){
			var errtxt;
			if(request.status == 0) errtxt = 'Bad connection';
			else if(request.status != 200) errtxt = 'HTTP: '+request.status+'/'+request.statusText;
			else errtxt = request.responseText;

			flashError("Error processing your request", errtxt); 			
		}); 

		return this;
	},
	loadTasks,
	log: function(v)
	{
		console.log.apply(this, arguments);
	},

};

function loadTasks()
{
	_td.db.request('loadTasks', { }, function(json){
		var tasks = '';
		$.each(json.list, function(i,item){
			tasks += prepareTaskStr(item);
		});
		$('#task-list ul').html(tasks);
	});
};


function prepareTaskStr(item)
{
	return `<li class="${item.compl ? 'completed' : 'pending'}">
			<span>${item.task}</span>
			<img id="${item.id}" class="${item.compl ? 'delete' : 'complete'}-button" width="10px" src="images/close.svg" />
		</li>`;
};


function submitNewTask(form)
{
	if(form.task.value == '') return false;
	_td.db.request('newTask', { task: form.task.value }, function(json){
		$.each(json.list, function(i,item){
			$('#task-list ul').append(prepareTaskStr(item));
			form.task.value = '';
		});
	}); 
	return false;
};

function deleteTask(id)
{
	if(!confirm('Are you sure you want to permanently delete this task>')) {
		return false;
	}
	_td.db.request('deleteTask', {id:id}, function(json){
	});
	return false;
};

function completeTask(el)
{
	var id = $(el).attr('id');
	var compl = 1;
	_td.db.request('completeTask', {id:id, compl:compl}, function(json){
		$(el).parent().fadeOut("fast", function() { $(el).parent().remove();})
	});
	return false;
};

/*
	Errors and Info messages
*/

function flashError(str, details)
{
	$("#msg>.msg-text").text(str)
	$("#msg>.msg-details").text(details);
	$("#loading").hide();
	$("#msg").addClass('msg-error');
}

function flashInfo(str, details)
{
	$("#msg>.msg-text").text(str)
	$("#msg>.msg-details").text(details);
	$("#loading").hide();
	$("#msg").addClass('msg-info');
}

function toggleMsgDetails()
{
	var el = $("#msg>.msg-details");
	if(!el) return;
	if(el.css('display') == 'none') el.show();
	else el.hide()
}

})();