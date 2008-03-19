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
	
	// Превращение филдсетов в табы
	if ($('form.tabbed').length != 0) {
		$('form.tabbed').formtabber({
			active: 0
		});
	}
	
	// Включение авторастягивания textarea
	if ($('form textarea.resizable').length != 0) {
	  	$('form textarea.resizable').autogrow();
	}
	
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

  bebop_fix_files();

  $('.returnHref a').click(function () {
    var win = window.opener ? window.opener : window.dialogArguments, c;
    if (win) { tinyMCE = win.tinyMCE; }

    if (tinyMCE) {
      var tmp_win = tinyMCE.getWindowArg('window');
      if (tmp_win) {
        tmp_win.document.getElementById('href').value = $(this).attr('href');
      }
      window.close();
      return false;
    }
  });

  $('a.returnHref').click(function () {
    return mcms_picker_return($(this).attr('href'));
  });

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
});


/**
 * Вспомогательные функции
 */

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

function bebop_fix_files()
{
  $('#center :file').each(function (i) {
    var f = $('#center :file').eq(i);
    var id = f.attr('id').replace('-input', '');

    var html = ""
      +"<label style='display:inline'><input type='checkbox' value='1' id='"+id+"-delete' name='"+$('#'+id+'-input').attr('name')+"[delete]' /> удалить</label>"
      +" или <a href='javascript:mcms_file_pick(\""+ id +"\");'>подобрать</a>"
      ;

    f.after("<p class='attctl'>"+ html +'</p>');

    var current = $('#center :hidden#'+ id +'-hidden').attr('value');
    if (current)
      f.before("<img id='"+ id +"-preview' src='/attachment/"+ current +",100,100,d' alt='preview' style='margin: 0 4px 4px 0; float: left;' />");

    $('#center #'+ id +'-input').parent().after("<div style='clear: both;'></div>");
  });
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

function mcms_file_pick(id)
{
  window.open('/admin/files/picker/?BebopFiles.picker='+ id, '_blank');
}

function mcms_picker_return(href)
{
  var fileid = href.replace('/attachment/', '');

  if (mcms_picker_id == 'src') {
    var wod = window.opener.document;
    var em = wod.getElementById(mcms_picker_id);

    if (em) {
      em.value = href;
    } else if (em = wod.getElementById('mce_0_ifr')) {
      var src = jQuery(em.contentDocument).find('#'+ mcms_picker_id);
      if (src.length == 0) {
        alert('Error accessing the parent dialog.');
        return false;
      }

      src.val(href);
      window.close();

      return false;
    } else {
      alert('Target control not found');
      return false;
    }
  } else {
    // Заменяем старый предпросмотр новым.
    window.opener.jQuery('#'+ mcms_picker_id +'-preview').remove();
    window.opener.jQuery('#'+ mcms_picker_id +'-input').before("<img id='"+ mcms_picker_id +"-preview' src='/attachment/"+ fileid +",100,100,d' alt='preview' style='margin: 0 4px 4px 0; float: left;' />");

    // Заменяем скрытое значение.
    window.opener.jQuery('#'+ mcms_picker_id +'-hidden').attr('value', fileid);

    // Сбрасываем отметку об удалении.
    window.opener.jQuery('#center #'+ mcms_picker_id +'-delete').attr('checked', '');
  }

  window.close();
  return false;
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


function bebopImageBrowser(field_name, url, type, win)
{
    if (field_name == 'src')
      field_name += '&BebopFiles.search=image%2F';
    window.open('/admin/files/picker/?BebopFiles.picker='+field_name, '_blank');
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
