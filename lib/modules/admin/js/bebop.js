// работает как алерт если нет консоли, иначе перенаправляет данные в нее
log = (function() {
	var handler;
	if (window.console && console.firebug) {
		handler = function(e) {
			console.log(e);
		};
	} else {
		handler = function(e) {
			alert(e);
		};
	}
	return handler;
})();

// namespace
var mcms = {};

/* Работа с формами
------------------------------------------------------------------------------------------------------------------- */
mcms.forms = {};
// работа с формой доступа
mcms.forms.crud = {};
// все чекбоксы в колонке включаются (если есть выключенные) или выключаются (если всё включено).
mcms.forms.crud.recheck = function($inputs){
	if ($inputs.filter(':checked').length != $inputs.length) {
		$inputs.attr('checked', 'checked');
	} else {
		$inputs.removeAttr('checked');
	}
}
/* ---------------------------------------------------------------------------------------------------------------- */

/* Специфические действия для IE6
------------------------------------------------------------------------------------------------------------------- */
if ($.browser.msie && $.browser.version < 7 ) {
	mcms.ie = {};
	// фиксирование ширины документа на 1000px (min-width)
	mcms.ie.controlBodyWidth = function() {
	 	if ($(window).width() < 1000) {
			document.body.style.width = '1000px';
		} else {
			document.body.style.width = 'auto';
		}
	 }
	 
	// действия при готовности DOM
	 $(function() {
	 	// корректирует ширину эл-та body (min-width)
	 	mcms.ie.controlBodyWidth();
	 });
	 
	 // действия при ресайзе окна
	$(window).resize(function() {
	 	// корректирует ширину эл-та body (min-width)
	 	mcms.ie.controlBodyWidth();
	 });
}
/* ---------------------------------------------------------------------------------------------------------------- */	

/**
 * Действия при готовности DOM
 */
$(document).ready(function () {
	// Превращение филдсетов в табы
	if ($('form.tabbed').length != 0) {
		$('.tabbed').tabber({
			selectors: {
				tabContainer: 'fieldset.tabable',
				tabTitle: '>legend'
			}
		});
	}
	
	/* CRUDP
	------------------------------------------------------------------------------------------------------------------- */
	/* клик по CRUDP */
	$('.control.access-wrapper th').click(function(){
		mcms.forms.crud.recheck($(this).parents('table:eq(0)').find('input.perm-'+$(this).text().toLowerCase()) );
	});
	
	/* клик по названию группы */
	$('.control.access-wrapper tr td:first-child').click(function(){
		mcms.forms.crud.recheck($(this).parent().find('input'));
	});
	
	/* запрет выделения текста заголовка */
	$('.control.access-wrapper th').mousedown(function(){ return false; });
	$('.control.access-wrapper th').bind('selectstart', function(){ return false; });
	$('.control.access-wrapper tr td:first-child').mousedown(function(){ return false; });
	$('.control.access-wrapper tr td:first-child').bind('selectstart', function() { return false; });
	/* ---------------------------------------------------------------------------------------------------------------- */	
	
	
	
	var win = window.opener ? window.opener : window.dialogArguments, c;
  	if (win) { tinyMCE = win.tinyMCE; }
		
	$("input:checked").parent().parent().addClass("current");

	$(":checkbox").change(function(){
		$(this).parent().parent().toggleClass("current");
	});
	
	$("input[name='select_all']").change(function () {
		$(this).parent().parent().parent().find("tr").toggleClass("current");
	});

  $('form.node-file-create-form input[name="__file_mode"]').change(function () { bebop_fix_file_mode_selection($(this).val()); });
  bebop_fix_file_mode_selection('local');

  $('.control-FieldControl-wrapper .selector').click(function () {
    var field = $(this).attr('href').replace(/.*#/, '');

    $('.control-FieldControl-wrapper table').addClass('hidden');
    $('#field-'+ field +'-editor table').removeClass('hidden');

    return false;
  });

  $('.jsonly').css('display', 'block');
  $('.nojs').css('display', 'none');

  // Массовое выделение объектов.
  $('.selink').click(function () {
    var c = $(this).attr('class').replace(/.* select-/, '');
    switch (c) {
    case 'all':
      $('table.nodelist :checkbox').attr('checked', 'checked');
      break;
    case 'none':
      $('table.nodelist :checkbox').attr('checked', '');
      break;
    default:
      $('table.nodelist :checkbox').attr('checked', '');
      $('table.nodelist tr.' + c + ' :checkbox').attr('checked', 'checked');
    }
  });

  // Массовые операции над объектами.
  $('.actionlink').click(function () {
    if ($('#nodeList :checked').size()) {
      var action = $(this).attr('class').replace(/.*action-/, '');
      $('#nodeListCommand').attr('value', action);
      $('#nodeList').submit();
    }
  });

  // Бэкап и восстановление.
	if ($('form#mod_exchange').length != 0) {
    $('form#mod_exchange :radio').change(fix_backup_mode);
    fix_backup_mode();
  }

	$('#top_menu_controls ul li.current').addClass('current_strong');

  	$('#top_menu_controls ul li').mouseover(function(){
		$('#top_menu_controls ul li').removeClass('current');
		$(this).addClass('current');
	});
	
	$('#top_menu_controls ul li ul li').mouseover(function(){
		$(this).parent().parent().addClass('current');
	});
	
	$('#top_menu_controls ul li ul').mouseout(function(){
		$('#top_menu_controls ul li').removeClass('current');
		$('#top_menu_controls ul li.current_strong').addClass('current');
	});

	$('#top_menu_controls_bottom').mouseout(function(){
		$('#top_menu_controls ul li').removeClass('current');
		$('#top_menu_controls ul li.current_strong').addClass('current');
	});
});

function fix_backup_mode()
{
  var mode = $('form#mod_exchange :radio:checked').val();

  $('form#mod_exchange :file').parent().css('display', mode == 'import' ? 'block' : 'none');
  $('form#mod_exchange [name="expprofiledescr"]').parent().css('display', mode == 'export' ? 'block' : 'none');
  $('form#mod_exchange .control-TextLineControl-wrapper').css('display', mode == 'upgradetoMySQL' ? 'block' : 'none');
  $('form#mod_exchange .control-PasswordControl-wrapper').css('display', mode == 'upgradetoMySQL' ? 'block' : 'none');
}

function bebop_fix_file_mode_selection(sel)
{
  var map = { local: "Attachment", remote: "URL", ftp: "Set" };

  for (i in map) {
    var id = 'form.node-file-create-form .control-'+map[i]+'Control-wrapper';
    if (sel == i)
      $(id).show();
    else
      $(id).hide();
  }
}

function bebop_select(table, mode)
{
  switch (mode) {
  case 'all':
    $('#'+ table +' tr.data').addClass('current').find('input[type="checkbox"]').attr('checked', 'checked');
    break;

  case 'none':
    $('#'+ table +' tr.data input[type="checkbox"]').attr('checked', '');
    $('#'+ table +' tr.data').removeClass('current');
    break;

  case 'published':
    $('#'+ table +' tr.data.unpublished input[type="checkbox"]').attr('checked', '');
    $('#'+ table +' tr.data.unpublished').removeClass('current');
    $('#'+ table +' tr.data.published input[type="checkbox"]').attr('checked', 'checked');
    $('#'+ table +' tr.data.published').addClass('current');
    break;

  case 'unpublished':
    $('#'+ table +' tr.data.published input[type="checkbox"]').attr('checked', '');
    $('#'+ table +' tr.data.published').removeClass('current');
    $('#'+ table +' tr.data.unpublished input[type="checkbox"]').attr('checked', 'checked');
    $('#'+ table +' tr.data.unpublished').addClass('current');
    break;
  }
}

function bebop_content_action(name, title)
{
  if (!confirm('Вы уверены, что хотите '+ title +' выделенные объекты?'))
    return;

  var url = $('#contentForm').attr('action');
  $('#contentForm').attr('action', '/admin/node/'+ name +'/'+ url);
  $('#contentForm').submit();
}

function bebop_selected_action(action)
{
  if (!$('.nodelist :checked').size()) {
    alert('Документы не выбраны.');
  } else {
    $('.action_select option[value="'+ action +'"]').attr('selected', 'selected');
    $('form#nodelist-form').submit();
  }
}
