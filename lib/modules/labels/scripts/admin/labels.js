$(function() {
		
	function getFieldLabels(fieldValue) {
		var labels = {
			list: fieldValue.split(','),
			cleanList: [],
			positions: []
		}, startPosition = 0;
		$.each(labels.list, function(k, v) {
			var cleanLabel = $.trim(v);
			labels.list[k] = v;
			labels.positions[k] = {
				start: startPosition,
				end: startPosition + v.length
			};
			labels.cleanList[k] = cleanLabel;
			startPosition = startPosition + v.length + 1;
		});
		return labels;
	}
	
	function getCaretPosition(element) {
		var caretPosition;
		if (document.selection) {
			var r = document.selection.createRange().duplicate();
			r.moveEnd('character', element.value.length);
			if (r.text == '') {
				caretPosition = element.value.length;
			} else {
				caretPosition = element.value.lastIndexOf(r.text);
			}
		} else {
			caretPosition = element.selectionStart;
		}
		return caretPosition;
	}
	
	function getModifiedLabel(fieldValue, caretPosition) {
		var labels = getFieldLabels(fieldValue), label;
		$.each(labels.positions, function(k, v) {
			if (caretPosition >= v.start && caretPosition <= v.end) {
				label = labels.cleanList[k];
			}
		});
		return label;
	}
	
	$('input[name="labels"]').each(function() {
		var that = this, t, c;
		var listContainer =  $('<div class="suggestions" />').appendTo('body').click(function(e) {
			var target = $(e.target);
			if (target.is('span') ) {
				var labels = getFieldLabels(that.value);
				$.each(labels.positions, function(k, v) {
					if (c >= v.start && c <= v.end) {
						labels.cleanList[k] = $(e.target).text();
					}
				});
				$(that).val(labels.cleanList.join(', ') );
				$(this).empty().hide();
			}
		});
		var f = function() {
			var label = getModifiedLabel(that.value, c);
			$.get('./api/labels/suggest.xml', {
				search: label
			}, function(response) {
				response = $(response);
				var length = response.find('label').length;
				if (length > 0) {
					response.find('label').each(function(k) {
						var text = '<span class="fakelink">' + $(this).text() +  '</span>';
						if (k < length - 1) {
							text += ', '
						}
						listContainer.append(text);
					});
					listContainer.css({
						left: position.left,
						top: position.top,
						width: $(that).outerWidth() - 20
					}).show();
				}
			});
		};
		var position = {
			left: $(this).offset().left,
			top: $(this).offset().top + $(this).outerHeight() + 1
		};
		$(this).keyup(function(e) {
			if (e.which < 37 || e.which > 40) {
				c = getCaretPosition(that);
				clearTimeout(t);
				listContainer.empty().hide();
				var label = getModifiedLabel(that.value, c);
				if (label && label.length > 3) {
					t = setTimeout(f, 500);
				}
			}
		});
	});
	
});
