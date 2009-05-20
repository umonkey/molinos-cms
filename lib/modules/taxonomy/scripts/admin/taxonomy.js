$(function () {
	/* Сворачивание/разворачивание списка разделов
	------------------------------------------------------------------------------------------------------------------- */
	$('form#nodeList ul.nodes').click(function(e) {
		var target = $(e.target);
		if (target.is('span.expand-collapse') ) {
			target.toggleClass('collapsed').parents('li:eq(0)').find('>ul').toggle();
			return false;
		}
	}).addClass('expand-collapse').find('li ul li span.expand-collapse').addClass('collapsed');
	if ($('form#nodeList ul.nodes').length) {
		if ($.cookies.get('expandedTags') ) {
			var expandedTags = $.cookies.get('expandedTags').split('-');
			$.each(expandedTags, function(k, v) {
				$('form#nodeList ul.nodes input[name="nodes[]"][value="' + v + '"]').parents('li:eq(0)').find('>ul').show().end().find('>span.expand-collapse').removeClass('collapsed');
			});
		}
		$(window).unload(function() {
			var expandedTags = [];
			$('form#nodeList ul.nodes li:has(>ul:visible)').each(function() {
				expandedTags.push($(this).find('>span.container>span.actions input[name="nodes[]"]').val() );
			});
			$.cookies.set('expandedTags', expandedTags.join('-'), { path:'/admin/structure/', hoursToLive:8760 });
		});
	}
	/* ---------------------------------------------------------------------------------------------------------------- */
});
