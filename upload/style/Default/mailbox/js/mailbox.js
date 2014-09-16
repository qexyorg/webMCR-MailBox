function CheckAll()
{
	var i;
	for(i=0; i<document.action.elements.length; i++)
	{
		if(document.action.check.checked==true){
			document.action.elements[i].checked=true;
		}else{
			document.action.elements[i].checked=false;
		}
	}
}

function insertbb(obj, leftcode, rightcode)
{
	if(document.selection)
	{ // Для IE
		var s = document.selection.createRange();
		if (s.text){ s.text = leftcode + s.text + rightcode; }
	}else{ // Opera, FireFox, Chrome

		var start = obj.selectionStart;
		var end = obj.selectionEnd;
		s = obj.value.substr(start,end-start);
		obj.value = obj.value.substr(0, start) + leftcode + s + rightcode + obj.value.substr(end);
	}
}

window.addEventListener('DOMContentLoaded', function() {
	$(".del-box").click(function() {
		if(!confirm('Удаление папки приведет к удалению всех писем, содержащихся в ней.\nВы действительно хотите удалить папку?')){return false;}
	});
});

window.addEventListener('DOMContentLoaded', function() {
	$(".del-mes").click(function() {
		if(!confirm('Вы действительно хотите удалить это сообщение?')){return false;}
	});
});

// <![CDATA[
window.addEventListener('DOMContentLoaded', function() {
	$('.qx-spl-body').hide()
 
	$('.qx-spl').click(function(){
		$(this).toggleClass("qx-close").toggleClass("qx-open").next().slideToggle();
	})
});
// ]]>