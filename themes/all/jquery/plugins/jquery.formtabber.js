/**
 * jQuery formtabber plugin
 * @version 0.1(21/01/2008)
 * @author Errant
 * @example $('.tabbed').formtabber(); $('.tabbed').formtabber({active: 2, wrap: true}); 
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */

(function($){
	
	/**
	 * Основная функция
	 * 
	 * @param {Object} options объект с параметрами
	 * @param {String} options.wrap определяет, нужно ли обрамлять форму в блок. По умолчанию обрамление не делается.
	 * @param {Bool} options.active определяет, какая из вкладок будет текущей. По умолчанию - первая. Нумерация ведется с 0.
	 * @return jQuery
	 */
	
	 $.fn.formtabber = function(options) {
		// получение параметров плагина. если при вызове плагина пар-ры не указаны, берутся пар-ры из объекта formtabber.defaults
		var opts = $.extend({}, $.fn.formtabber.defaults, options);
		
		// цикл обрабатывает каждый найденный элемент
		return this.each(function(i) {
      if ($(this).find(' > fieldset').size() == 1) return;

			// сохраняем ссылку на текущую форму - пригодится позднее
			$$ = $(this);
			// в теории эта строчка дает поддержку плагина Metadata. не проверял
			var o = $.meta ? $.extend({}, opts, $this.data()) : opts;
		
			// начало непоср. обработки
			// обрамляем форму в блок, если при вызове плагина дано соот. указание
			if (o.wrap == true) $$.wrap('<div class="ftabber-container" id="ftabber-container-' + i + '"></div>');
			
			// добавляем к форме класс; добавляем пустой список вкладок; находим все филдсэты в форме;
			$$.addClass('ftabber-form-' + i)
				.addClass('ftabber-form')
				.prepend('<ul class="ftabber-tabs"></ul>')
				.find('fieldset.tabable')
				
				.each(function(j){
					// сохраняем ссылку на текущий филдсет
					$fieldset = $(this);
					// добавляем класс (по сути, id, но id уже может быть указан, поэтому класс)
					$fieldset.addClass('tab-content ftabber-fieldset-' + i + '-' + j);
					// сохраняем текст легенды и убираем ее
					var legendtext = $fieldset.find('legend').text();
					$fieldset.find('legend').remove()
					
					// находим список вкладок в текущей форме; добавляем в список текст легенды текущего филдсета; ставим обработчик на ссылку в списке;
					$$.find('.ftabber-tabs')
						.append('<li><a href="'+ window.location.href +'">' + legendtext + '</a></li>')
						.find('li:last a')
						.click(function(){
							// прячем все филдсэты в текущей форме
							$('.ftabber-form-' + i + ' fieldset').hide();
							// показываем нужный филдсет
							$('.ftabber-fieldset-' + i + '-' + j).show();
							// убираем классы у соседних с родительским по отн. к ссылке li эл-ов, добавляем ему класс
							$(this).parent('li').siblings().removeAttr('class').end().addClass('active');
							return false;
						});
					
					// прячем неактивные филдсэты, назначаем класс для ссылки, соот. активному 	
					if (o.active != j){
						$fieldset.hide();
					} else {
						$$.find('.ftabber-tabs li:last').addClass('active');
					};		
			});
		});
	};

	/**
	 * Параметры по умолчанию
	 */
	$.fn.formtabber.defaults = {
		active: 0,
		wrap: false
	};
	
})(jQuery);
