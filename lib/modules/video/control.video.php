<?php

class VideoControl extends TextLineControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Видео'),
      'class' => __CLASS__,
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['description']))
      $form['description'] = t('Можно использовать готовый код для вставки проигрывателя или ссылку на страницу с клипом.');
    parent::__construct($form, array('value'));
  }

  public function format($value)
  {
    if (0 === strpos($value, 'http://')) {
      $info = $this->parse($value);
      if (!empty($info['embed']))
        return $info['embed'];
      mcms::flog('dunno how to embed: ' . $value);
      return null;
    }

    return $value;
  }

  private function parse($url, $options = array())
  {
    $options = array_merge(array('width' => 425, 'height' => 318), $options);

    $nothing = 'You need Adobe Flash for this.';

    if (preg_match('%^http://vimeo.com/([0-9]+)%', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<object width="'. $options['width'] .'" height="'. $options['height'] .'">'
        .'<param name="allowfullscreen" value="true" />'
        .'<param name="allowscriptaccess" value="always" />'
        .'<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id='. $m1[1] .'&server=vimeo.com&show_title=0&show_byline=0&show_portrait=0&color=00ADEF&fullscreen=1" />'
        .'<embed src="http://vimeo.com/moogaloop.swf?clip_id='. $m1[1] .'&server=vimeo.com&show_title=0&show_byline=0&show_portrait=0&color=00ADEF&fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="'. $options['width'] .'" height="'. $options['height'] .'">'
        .'</embed></object>';
      $link['is_video'] = true;
      $link['host'] = 'Vimeo';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://video\.google\.com/videoplay\?docid=([0-9\-]+)%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<object width="'.$options['width'].'" height="'.$options['height'].'"><param name="movie" value="http://video.google.com/googleplayer.swf?docId='.$m1[1].'&amp;hl=en"></param><param name="wmode" value="transparent"></param><embed src="http://video.google.com/googleplayer.swf?docId='.$m1[1].'&amp;hl=en" type="application/x-shockwave-flash" wmode="transparent" width="'.$options['width'].'" height="'.$options['height'].'"></embed></object>';
      $link['is_video'] = true;
      $link['host'] = 'Google Video';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://www\.vesti\.ru/videos\?vid=(\d+)%', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['is_video'] = true;
      $link['host'] = 'vesti.ru';
      $link['vid'] = $m1[1];
      $link['embed'] = str_replace('$ID$', $m1[1], '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,29,0" width="408" height="356" id="flvplayer" align="middle"><param name="allowScriptAccess" value="always" /><param name="allowFullScreen" value="true" /><param name="movie" value="http://www.vesti.ru/i/flvplayer.swf?vid=$ID$&autostart=false" /><param name="quality" value="high" /><param name="wmode" value="transparent" /><param name="devicefont" value="true" /><param name="bgcolor" value="#000000" /><param name="vid" value="$ID$" /><embed src="http://www.vesti.ru/i/flvplayer.swf?vid=$ID$&autostart=false" quality="high" devicefont="true" bgcolor="#000000" width="408" height="356" name="flvplayer" align="middle" allowScriptAccess="always" allowFullScreen="true" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /></object>');
    } elseif (preg_match('%^http://([a-z0-9]+\.){0,1}youtube\.com/(?:watch\?v=|v/)([^&]+)%i', $url, $m1)) {
      $o = html::em('param', array(
        'name' => 'movie',
        'value' => 'http://www.youtube.com/v/'. $m1[2],
        ));
      $o .= html::em('param', array(
        'name' => 'wmode',
        'value' => 'transparent',
        ));
      $o .= html::em('embed', array(
        'src' => 'http://www.youtube.com/v/'. $m1[2],
        'type' => 'application/x-shockwave-flash',
        'wmode' => 'transparent',
        'width' => $options['width'],
        'height' => $options['height'],
        ), $nothing);
      $link['embed'] = html::em('object', array(
        'width' => $options['width'],
        'height' => $options['height'],
        ), $o);
      $link['type'] = 'video/x-flv';
      $link['is_video'] = true;
      $link['host'] = 'YouTube';
      $link['vid'] = $m1[2];
      $link['thumbnail'] = 'http://img.youtube.com/vi/' . $m1[2] . '/2.jpg';
    } elseif (preg_match('%^http://vids\.myspace\.com/index.cfm\?fuseaction=[^&]+\&(?:amp;){0,1}videoID=([0-9]+)%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<embed src="http://lads.myspace.com/videos/vplayer.swf" flashvars="m='.$m1[1].'&type=video" type="application/x-shockwave-flash" width="'.$options['width'].'" height="'.$options['height'].'"></embed>';
      $link['is_video'] = true;
      $link['host'] = 'MySpace';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://vision\.rambler\.ru/users/(.+)$%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="'.$options['width'].'" height="'.$options['height'].'"><param name="wmode" value="transparent"></param><param name="movie" value="http://vision.rambler.ru/i/e.swf?id='.$m1[1].'&logo=1" /><embed src="http://vision.rambler.ru/i/e.swf?id='.$m1[1].'&logo=1" width="'.$options['width'].'" height="'.$options['height'].'" type="application/x-shockwave-flash" wmode="transparent"/></object>';
      $link['is_video'] = true;
      $link['host'] = 'Rambler';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://video\.mail\.ru/([a-z]+)/([^/]+)/([0-9]+)/([0-9]+)\.html*%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<object width="'.$options['width'].'" height="'.$options['height'].'"><param name="flashvars" value="imaginehost=video.mail.ru&perlhost=my.video.mail.ru&alias='.$m1[1].'&username='.$m1[2].'&albumid='.$m1[3].'&id='.$m1[4].'&catalogurl=http://video.mail.ru/catalog/music/" /><param name="movie" value="http://img.mail.ru/r/video/player_full_size.swf?par=http://video.mail.ru/'.$m1[1].'/'.$m1[2].'/'.$m1[3].'/$'.$m1[4].'$0$248"></param><embed src="http://img.mail.ru/r/video/player_full_size.swf?par=http://video.mail.ru/'.$m1[1].'/'.$m1[2].'/'.$m1[3].'/$'.$m1[4].'$0$248" type="application/x-shockwave-flash" width="'.$options['width'].'" height="'.$options['height'].'" flashvars="imaginehost=video.mail.ru&perlhost=my.video.mail.ru&alias='.$m1[1].'&username='.$m1[2].'&albumid='.$m1[3].'&id='.$m1[4].'&catalogurl=http://video.mail.ru/catalog/music/"></embed></object>';
      $link['is_video'] = true;
      $link['host'] = 'Mail.Ru';
      $link['vid'] = $m1[1].'/'.$m1[2].'/'.$m1[3].'/'.$m1[4];
    } elseif (preg_match('%^http://rutube\.ru/tracks/(\d+).html\?v=(.+)$%i', $url, $m1)) {
      $link['type'] = 'video/x-flv';
      $link['embed'] = '<OBJECT width="'.$options['width'].'" height="'.$options['height'].'"><PARAM name="movie" value="http://video.rutube.ru/'.$m1[2].'" /><PARAM name="wmode" value="transparent" /><EMBED src="http://video.rutube.ru/'.$m1[2].'" type="application/x-shockwave-flash" wmode="transparent" width="'.$options['width'].'" height="'.$options['height'].'" /></OBJECT>';
      $link['is_video'] = true;
      $link['host'] = 'RuTube';
      $link['vid'] = $m[2];
    }

    if (!empty($link['is_video']) and empty($link['thumbnail']))
      $link['thumbnail'] = 'lib/modules/base/video.png';

    return $link;
  }
}
