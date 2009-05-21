/**
 * jQuery mcms.tabber plugin
 * 
 * @projectDescription Lightweight plugin to create tabbed navigation
 * @requirements html structure must be smth like this: <div class="tabs">{<div class="tab"><h3>Header</h3>Content</div>}*n</div>. 
 * Elements and classes are customizable, refer to main func description.
 * @version 0.3(12/01/2009)
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
			tabContainer: 'fieldset.tab',
			tabTitle: '>legend'
		}
	});
 * 
 * Licensed under the GNU General Public License v2 :
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

 (function($) {
	
	$.extend({
		tabber: {
			defaults: {
				selectors: {
					tabContainer: 'div.t-me',
					tabTitle: '>h2'
				},
				removeTitles: true,
				active: 0
			}
		}
	});
	
	var showTab = function(tabs, idx) {
		tabs.each(function(i) {
			if (i != idx) {
				$(this).hide();
			} else {
				$(this).show();
			}
		});
	};
	
	$.fn.tabber = function(options) {
		// extending default params
		var o = $.extend({}, $.tabber.defaults, options);
		
		this.addClass('tabber-container');
	
		return this.each(function() {
			var tabs = $(o.selectors.tabContainer, this).addClass('tabber-tab');
			var titles = $(o.selectors.tabTitle, tabs);
			var navigation = $('<ul class="tabber-navigation" />');
			
			titles.each(function(i) {
				var element = $('<li><a href="#"><span>' + $(this).text() + '</span></a></li>');
				element.find('a').click(function() {
					showTab(tabs, i);
					$(this).parents('li:eq(0)').addClass('active').siblings().removeClass('active');
					return false;
				});
				if (i == o.active) {
					element.addClass('active');
				}
				navigation.append(element);
			});
			
			$(this).prepend(navigation);
			
			if (o.removeTitles == true) {
				titles.remove();
			}

			showTab(tabs, o.active);
		});
		
	};
	
})(jQuery);
