/**
 * jQuery mcms.tabber plugin
 * 
 * @projectDescription Lightweight plugin to create tabbed navigation
 * @requirements html structure must be smth like this: <div class="tabs">{<div class="tab"><h3>Header</h3>Content</div>}*n</div>. 
 * Elements and classes are customizable, refer to main func description.
 * @version 0.21(05/05/2008)
 * @copyright 2008 Molinos CMS Development Team, Dmitriy Chekanov
 * @example
 * 
 * with default options: 
	$('.tabbed').tabber(); 
 * 
 * custom options: 
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
 * 
 * Licensed under the GNU General Public License v2 :
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

(function($){
	/**
	 * Main func
	 * 
	 * @param {Object} options object with params
	 * @param {Bool} options.active defines which tab will be activated by default. Reference point is zero
	 * @param {Bool} options.selectors.tab defines selector for element which will be treated as tab
	 * @param {Bool} options.selectors.header defines selector for element which will be treated as tab's header
	 * @param {Bool} options.classes defines various classes to append to generated elements
	 * @return jQuery
	 */
	 $.fn.tabber = function(options){
		// extending default params
		var opts = $.extend({}, $.fn.tabber.defaults, options);
		// cycle through each matched element
		return this.each(function(){
			$$ = $(this);
			// Metadata plugin support
			var o = $.meta ? $.extend({}, opts, $$.data()) : opts;
			// do nothing if there is only 1 tab
			if ($$.find(' > ' + o.selectors.tab).length == 1) return;
			$$.addClass(o.classes.container)
				.prepend('<ul class="' + o.classes.controls + '"></ul>')
				.find(o.selectors.tab)
				.each(function(j){ // for each found tab
					$tab = $(this);
					// save header text and remove header element
					var headerText = $tab.find(o.selectors.header).text();
					$tab.addClass(o.classes.tab).find(o.selectors.header).remove();
					// находим список вкладок в текущей форме; добавляем в список текст легенды текущего филдсета; ставим обработчик на ссылку в списке;
					$$.find('.' + o.classes.controls)
						.append('<li><a href="'+ window.location.href +'"><span>' + headerText + '</span></a></li>')
						.find('li:last a')
						.click(function(){
							$this = $(this);
							$container = $this.parents('ul:eq(0)').parent();
							var index = $container.find('.' + o.classes.controls + ' li').index($this.parent().get(0));
							// прячем все филдсэты в текущей форме
							$container.find(o.selectors.tab).hide();
							// показываем нужный филдсет
							$container.find(o.selectors.tab + ':eq(' + index + ')').show();
							// убираем классы у соседних с родительским по отн. к ссылке li эл-ов, добавляем ему класс
							$this.parent().siblings().removeAttr('class').end().addClass('active');
							return false;
						});
					
					// прячем неактивные табы, назначаем класс для ссылки, соот. активному 	
					if (o.active != j){
						$tab.hide();
					} else {
						$$.find('.' + o.classes.controls + ' li:last').addClass('active');
					};		
				});
		});
	};

	/**
	 * Default params
	 */
	$.fn.tabber.defaults = {
		active: 0,
		selectors: {
			tab: 'div.tab',
			header: 'h5'
		},
		classes: {
			tab: 'tabber-tab',
			controls: 'tabber-controls',
			container: 'tabber-container'
		}
	};
	
})(jQuery);
