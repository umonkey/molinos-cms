/**
 * Специфические действия для IE6
 */
if ($.browser.msie && $.browser.version < 7 ){
	// действия при готовности DOM
	 $(function(){
	 	// корректирует ширину эл-та body (min-width)
	 	controlBodyWidth();
	 });
	 
	 // действия при ресайзе окна
	$(window).resize(function(){
	 	// корректирует ширину эл-та body (min-width)
	 	controlBodyWidth();
	 });
	 
	 /**
	  * Фиксирование ширины документа на 1000px (min-width)
 	  * @todo было бы неплохо делать это без JS, пример как это можно сделать - http://www.cssplay.co.uk/boxes/width2.html
	  */
	 function controlBodyWidth(){
	 	($(window).width() < 1000) ?  $('body').css('width', '1000px') : $('body').css('width', '100%');
	 };
}

/**
 * Действия при готовности DOM
 */
$(document).ready(function () {
	
	$('input[name="node_content_files[__bebop][]"]').MultiFile();

	// Превращение филдсетов в табы
	if ($('form.tabbed').length != 0) {
    $('.tabbed').tabber({
      active: 0,
      selectors: {
        tab: 'fieldset.tabable',
        header: '>legend'
      },
      classes: {
        tab: 'tab-content',
        controls: 'ftabber-tabs',
        container: 'ftabber-form'
      }
    });
	}
	
	$('.control-AccessControl-wrapper th').click(function(){
		recheck($(this).parents('table:eq(0)').find('input[value="'+$(this).text().toLowerCase()+'"]'));
	});
	
	$('.control-AccessControl-wrapper th').mousedown(function(){
		return false;
	});
	
	$('.control-AccessControl-wrapper th').bind('selectstart', function() {
		return false;
	});
	
	$('.control-AccessControl-wrapper tr td:first-child').click(function(){
		recheck($(this).parent().find('input'));
	});
	
	$('.control-AccessControl-wrapper tr td:first-child').mousedown(function(){
		return false;
	});
	
	$('.control-AccessControl-wrapper tr td:first-child').bind('selectstart', function() {
		return false;
	});
	
	var win = window.opener ? window.opener : window.dialogArguments, c;
  	if (win) { tinyMCE = win.tinyMCE; }
		
	$("input:checked").parent().parent().addClass("current");

	$(":checkbox").change(function(){
		$(this).parent().parent().toggleClass("current");
	});
	
	$("input[name='select_all']").change(function () {
		$(this).parent().parent().parent().find("tr").toggleClass("current");
	});

  $('form.node-domain-create-form input.form-radio').change(function () {
    if ($(this).attr('value') == 'domain') {
      $('#domain-aliases-wrapper').removeClass('hidden');
      $('#domain-parent-wrapper').addClass('hidden');
    } else {
      $('#domain-aliases-wrapper').addClass('hidden');
      $('#domain-parent-wrapper').removeClass('hidden');
    }
  });
  $('#domain-parent-wrapper').addClass('hidden');

  // Скрываем выбор раздела по умолчанию.
  $('form.node-domain-edit-form #control-node-params-wrapper select').change(bebop_fix_domain_defaultsection);
  bebop_fix_domain_defaultsection();

  $('form.node-file-create-form input[name="__file_mode"]').change(function () { bebop_fix_file_mode_selection($(this).val()); });
  bebop_fix_file_mode_selection('local');

  $('.control-FieldControl-wrapper .selector').click(function () {
    var field = $(this).attr('href').replace(/.*#/, '');

    $('.control-FieldControl-wrapper table').addClass('hidden');
    $('#field-'+ field +'-editor table').removeClass('hidden');

    return false;
  });

  // обработчик формы выхода
  $('#user-logout-form').submit(function () {
    $.ajax({
      type: "POST",
      url: $(this).attr('action'),
      data: $(this).serialize(),
      dataType: "json",

      success: function (data) {
        if (data.status = 'ok') {
          $('#center').html(data.message);
          $('#top_menu').hide('slow');
          $('#left_sidebar').hide('slow');
          $('#user-profile').hide('slow');
          $('#top_toolbar .greeting').hide('slow');
        }
      }
    });

    return false;
  });

  $('#center .ctrl_filter').click(function () {
    $.ajax({
      type: "GET",
      url: $(this).attr('href') + '?widget=BebopContentFilterSettings',

      success: function (data) {
        $('#widget-BebopContentList').after(data);
        $('#form-content-filter-wrapper').hide();

        $('#widget-BebopContentList').hide();
        $('#form-content-filter-wrapper').show();
      }
    });

    return false;
  });

  $('.jsonly').css('display', 'block');
  $('.nojs').css('display', 'none');

  // Массовые операции над документами.
  $('.select-all').click(function () {
    $('table.nodelist :checkbox').attr('checked', 'checked');
  });
  $('.select-none').click(function () {
    $('table.nodelist :checkbox').attr('checked', '');
  });
  $('.select-published').click(function () {
    $('table.nodelist :checkbox').attr('checked', '');
    $('table.nodelist tr.published :checkbox').attr('checked', 'checked');
  });
  $('.select-unpublished').click(function () {
    $('table.nodelist :checkbox').attr('checked', '');
    $('table.nodelist tr.unpublished :checkbox').attr('checked', 'checked');
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

/**
 * Вспомогательные функции
 */

/**
 * Управляет состоянием чекбоксов.
 * Все чекбоксы в колонке включаются (если есть выключенные) или выключаются (если всё включено).
 */
function recheck($inputs){
	if ($inputs.length > $inputs.filter(':checked').length && $inputs.filter(':checked').length != 0 || $inputs.filter(':checked').length == 0){
		$inputs.attr('checked', 'checked');
	} else {
		$inputs.removeAttr('checked');
	}
}
	
/**
 * Карусель в осн. навигации: цепляем действия на контролы
 * @param {Object} carousel
 */
function bebop_dashboard_init(carousel){
	// ссылка на все контролы
	var $carouselControls = $('.carousel-control a');
	// действия при клике
	$carouselControls.click(function(){
		// ссылка на контрол, по которому кликнули
		var $$ = $(this);
		// убираем класс со всех контролов
		$carouselControls.removeClass('active');
		// добавляем класс к контролу, по которому кликнули
		$$.addClass('active');
		// убираем классы со всех элементов карусели
		carousel.container.find('li').removeClass('choosen');
		// добавляем класс группе элементов карусели, сопоставленных этому контролу
		carousel.container.find('li.group-' + parseInt($carouselControls.index(this) + 1)).addClass('choosen');
		// вращаем карусель
		carousel.scroll(jQuery.jcarousel.intval($$.attr('href').replace('#', '') ) );
		return false;
	});
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

function bebop_fix_domain_defaultsection()
{
  switch ($('form.node-domain-edit-form #control-node-params-wrapper select').attr('value')) {
  case 'sec':
  case 'sec+doc':
    $('form.node-domain-edit-form #control-node-defaultsection-wrapper').removeClass('hidden');
    break;
  default:
    $('form.node-domain-edit-form #control-node-defaultsection-wrapper').addClass('hidden');
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

/**
 * Функция предназначена для отладки. Shortcut console.log'a.
 * Записывает в консоль Firebug'a передаваемые в нее данные. 
 * 
 * @param {String} str - строка/массив для отображения в консоли
 */
function log(str) {
	window.console && window.console.log(str);
};
