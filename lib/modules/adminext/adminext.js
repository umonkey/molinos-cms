/*
 */

(function($){

  $.fn.mcmsNodeActions = function(options){
    return this.each(function() {
      var $this = $(this);
      $this.wrap('<span class="mcms-node-actions-wrapper" />');
      var $control = $('<img class="mcms-node-actions" src="lib/modules/adminext/wand.png" alt="" />');
      $control.insertBefore(this);

      $control.click(function(){
        $('.mcms-node-actions-list-wrapper').remove();
        $.getJSON(mcms_path + '/', {
          q: 'adminext.rpc',
          action: 'getlinks',
          url: $control.siblings('a').attr('href'),
          from: location.pathname
        }, function(response){
          $(response.content).css({
            left: $control.parent().offset().left + 'px',
            top: $control.parent().offset().top + 19 + 'px'
          }).prependTo($('body'));
        });
        return false;
      });
    });
  }

  $().click(function(){
    $('.mcms-node-actions-list-wrapper').remove();
  });

})(jQuery);

/*
 * On DOM ready
 */
jQuery(function($){

  $('a.mcms-node-link').mcmsNodeActions();

});
