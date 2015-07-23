$(function(){
	$('.checkall').on('click', function(){

		var length = $('.chkd').length;

		for (i=0; i < length; i++){
			if($(this)[0].checked){
				$('.chkd')[i].checked = true;
			}else{
				$('.chkd')[i].checked = false;
			}
		};
	});

	$('body').on('click', '.reply-to', function(){

		if($('.bb-textarea').length<=0){ return false; }

		var login = $(this).text();

		var val = $('.bb-textarea').val();

		$('.bb-textarea').val('[b]'+login+'[/b], ');

		$('html, body').animate({
			scrollTop: $(".reply-form").offset().top
		}, 500);

		return false;
	});

	$('body').on('click', '#accept_move', function(){
		var ids = '';

		var length = $('.chkd:checked').length;

		for (i=0; i < length; i++){
			
			if(i!=(length-1)){
				ids = ids+$('.chkd:checked')[i].value+',';
			}else{
				ids = ids+$('.chkd:checked')[i].value;
			}
		};

		var folder = $('#get_move [name="move_in_folder"]').val();

		$.ajax({
			url: base_url+"?mode=mailbox&do=ajax&op=topic_move",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "&ids="+ids+"&fid="+folder,
			error: function(data){
				console.log(data);
				alert('Произошла ошибка запроса, попробуйте повторить попытку');
				return false;
			},
			success: function(data){
				if(!data.type){ console.log(data); return; }

				location.reload();
				$('#get_move').modal('hide');
				alert('Выбранные элементы успешно перемещены');
			}
		});

		return false;
	});

	$('.close_topic').click(function(){
		var id = $(this).closest('.mailbox-topic-full').attr('id');

		if(!confirm('После закрытия переписки, открыть ее будет невозможно и никто не сможет в ней отвечать. Вы уверены, что хотите закрыть переписку?')){ return false; }

		$.ajax({
			url: base_url+"?mode=mailbox&do=ajax&op=topic_close",
			dataType: "json",
			type: 'POST',
			async: true,
			data: "tid="+id,
			error: function(data){
				alert('Произошла ошибка запроса, попробуйте повторить попытку');
				return false;
			},
			success: function(data){
				if(!data.type){ alert(data.message); return; }

				$('.reply-form').remove();
				$('.mailbox-topic-full').append('<div class="reply-closed"><p class="text-center muted">Вы не можете отвечать в переписке, т.к. она была закрыта</p></div>');
				$('.close_topic').text('Переписка закрыта').prop("disabled", true);
				alert('Переписка была успешно закрыта');

			}
		});

		return false;
	});

	$('.form-actions [type="submit"]').on('click', function(){

		var length = $('.chkd:checked').length;

		if(length<=0){
			alert('Вы не выбрали ни одного элемента');
			return false;
		}

		var action = $('.form-actions [name="action"]').val();

		if(action==null){
			return false;
		}else if(action=='remove'){
			if(confirm('Вы уверены, что хотите переместить выбранные переписки в корзину')){ return true; }
		}else if(action=='edit'){

			var edit_url = $('.form-actions [name="action"]').attr('data-edit-url');

			if(length>1){ alert('Одновременно можно редактировать только один элемент'); return false; }
			location.href = "?mode=mailbox"+edit_url+$('.chkd:checked').val();
			return false;
		}else if(action=='close'){
			if(confirm('После закрытия переписок, открыть их будет невозможно и никто не сможет в них отвечать. Вы уверены, что хотите закрыть выбранные переписки?')){ return true; }
		}else if(action=='move'){

			$.ajax({
				url: base_url+"?mode=mailbox&do=ajax",
				dataType: "json",
				type: 'GET',
				async: true,
				data: "op=get_folders",
				error: function(data){
					alert('Произошла ошибка запроса, попробуйте повторить попытку');
					return false;
				},
				success: function(data){
					if(!data.type){ alert(data); return; }

					if(data.data.length<=0){ alert('Нет доступных папок для перемещения'); return false; }

					$('#get_move [name="move_in_folder"]').text('');

					$.each(data.data, function(i, val){
						$('#get_move [name="move_in_folder"]').append('<option value="'+val.id+'">'+val.title+'</option>');
					});

					$('#get_move').modal('show');
				}
			});
		}

		return false;
	});

	$('#data-get_login').typeahead({
		items: 10,
		minLength: 2,

		source: function(query, process) {
			$.ajax({
				url: base_url+"?mode=mailbox&do=ajax&op=get_login",
				dataType: "json",
				type: 'POST',
				async: true,
				data: "query=" + query,

				success: function(data){
					if(!data.type){ return; }

					process(data.data);
				}
			});
		},

		matcher: function (param){
			return true
		},
	});

	$('.permaLinkMsg').on('click', function(){

		var link = location.href;

		if($(this).attr('id') !== undefined){
			link = link + '#reply-id-' + $(this).attr('id');
		}

		$('#permaLinkInput').val(link);
	});

	$('body').on('click', '.reply-load', function(){

		var id = $(this).attr('id');

		var op = 'get_reply';

		if($(this).hasClass('data-topic')){ op = 'get_topic'; }

		$.ajax({
			url: base_url+"?mode=mailbox&do=ajax&op="+op,
			dataType: "json",
			type: 'POST',
			async: true,
			data: "rid="+id,
			error: function(data){
				alert('Произошла ошибка запроса, попробуйте повторить попытку');
				return false;
			},
			success: function(data){
				if(!data.type){ alert(data.message); return; }

				var val = $('.bb-textarea').val();

				$('.bb-textarea').val(val+'[quote]'+data.data.text+'[/quote]');
			}
		});

		return false;
	});
});