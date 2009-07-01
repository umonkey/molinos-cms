$(function(){
  $('a.flashplayer').each(function(){
    if (typeof flowplayer == 'function') {
      var id = $(this).attr('id');
      if (id) {
        var img = $(this).find('img:eq(0)').attr('src'),
            opts = {
              clip: {
                autoPlay: true,
                baseUrl: mcms_url
              }
            };
        if (img)
          opts.playlist = [ img, $(this).attr('href') ];
        flowplayer(id,mcms_url+'lib/modules/flashplayer/flowplayer-3.1.1.swf', opts);
      }
    }
  });

  $('a.flashplayer').click(function() {
    return false;
  });
});
