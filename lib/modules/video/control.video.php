<?php

class VideoControl extends URLControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Видео'),
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['description']))
      $form['description'] = t('Можно использовать готовый код для вставки проигрывателя или ссылку на страницу с клипом.');
    parent::__construct($form, array('value'));
  }

  public function format(Node $node, $em)
  {
    if (is_array($value = $node->{$this->value})) {
      $data = $value;
      $embed = $data['embed'];
      unset($data['embed']);

      return html::wrap($em, html::cdata($embed), $data);
    }
  }

  /**
   * Предварительный просмотр.
   */
  public function preview($node)
  {
    if (is_array($value = $node->{$this->value}))
      return html::em('value', array(
        'html' => true,
        ), html::cdata($value['embed']));
  }

  private function parse($url, $options = array())
  {
    $options = array_merge(array('width' => 425, 'height' => 318), $options);

    $nothing = 'You need Adobe Flash for this.';
    $link = array(
      'url' => $url,
      );

    if (preg_match('%^http://vimeo.com/([0-9]+)%', $url, $m1)) {
      $link['type'] = 'video/flv';
      $link['embed'] = '<object width="'. $options['width'] .'" height="'. $options['height'] .'">'
        .'<param name="allowfullscreen" value="true" />'
        .'<param name="allowscriptaccess" value="always" />'
        .'<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id='. $m1[1] .'&server=vimeo.com&show_title=0&show_byline=0&show_portrait=0&color=00ADEF&fullscreen=1" />'
        .'<embed src="http://vimeo.com/moogaloop.swf?clip_id='. $m1[1] .'&server=vimeo.com&show_title=0&show_byline=0&show_portrait=0&color=00ADEF&fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="'. $options['width'] .'" height="'. $options['height'] .'">'
        .'</embed></object>';
      $link['host'] = 'Vimeo';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://video\.google\.com/videoplay\?docid=([0-9\-]+)%i', $url, $m1)) {
      $link['type'] = 'video/flv';
      $link['embed'] = '<object width="'.$options['width'].'" height="'.$options['height'].'"><param name="movie" value="http://video.google.com/googleplayer.swf?docId='.$m1[1].'&amp;hl=en"></param><param name="wmode" value="transparent"></param><embed src="http://video.google.com/googleplayer.swf?docId='.$m1[1].'&amp;hl=en" type="application/x-shockwave-flash" wmode="transparent" width="'.$options['width'].'" height="'.$options['height'].'"></embed></object>';
      $link['host'] = 'Google Video';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://www\.vesti\.ru/videos\?vid=(\d+)%', $url, $m1)) {
      $link['type'] = 'video/flv';
      $link['host'] = 'vesti.ru';
      $link['vid'] = $m1[1];
      try {
        $xml = http::fetch('http://www.vesti.ru/v.xml?adv=1&z=2&vid=' . $link['vid'], http::CONTENT);
        $s = new SimpleXMLElement($xml);
        $link['thumbnail'] = (string)$s->picture;
      } catch (Exception $e) { }
      $link['embed'] = t('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,29,0" width="408" height="356" id="flvplayer" align="middle"><param name="allowScriptAccess" value="always" /><param name="allowFullScreen" value="true" /><param name="movie" value="http://www.vesti.ru/i/flvplayer.swf?vid=%vid&amp;autostart=false" /><param name="quality" value="high" /><param name="wmode" value="transparent" /><param name="devicefont" value="true" /><param name="bgcolor" value="#000000" /><param name="vid" value="%vid" /><a href="@url"><img src="@thumbnail" alt="preview" /></a></object>', array(
        '%width' => $options['width'],
        '%height' => $options['height'],
        '%vid' => $link['vid'],
        '@thumbnail' => $link['thumbnail'],
        '@url' => $url,
        ));

    } elseif (preg_match('%^http://([a-z0-9]+\.){0,1}youtube\.com/(?:watch\?v=|v/)([^&]+)%i', $url, $m1)) {
      $link['thumbnail'] = 'http://img.youtube.com/vi/' . $m1[2] . '/2.jpg';
      $link['type'] = 'video/flv';
      $link['host'] = 'YouTube';
      $link['vid'] = $m1[2];
      $link['embed'] = t("<object width='%width' height='%height'><param name='movie' value='http://www.youtube.com/v/%vid' /><param name='wmode' value='transparent' /><a href='http://www.youtube.com/watch?v=%vid'><img src='@thumbnail' alt='preview' /></a></object>", array(
        '%width' => $options['width'],
        '%height' => $options['height'],
        '%vid' => $m1[2],
        '@thumbnail' => $link['thumbnail'],
        ));
    } elseif (preg_match('%^http://vids\.myspace\.com/index.cfm\?fuseaction=[^&]+\&(?:amp;){0,1}videoID=([0-9]+)%i', $url, $m1)) {
      $link['type'] = 'video/flv';
      $link['embed'] = '<embed src="http://lads.myspace.com/videos/vplayer.swf" flashvars="m='.$m1[1].'&type=video" type="application/x-shockwave-flash" width="'.$options['width'].'" height="'.$options['height'].'"></embed>';
      $link['host'] = 'MySpace';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://vision\.rambler\.ru/users/(.+)$%i', $url, $m1)) {
      $link['type'] = 'video/flv';
      $link['embed'] = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="'.$options['width'].'" height="'.$options['height'].'"><param name="wmode" value="transparent"></param><param name="movie" value="http://vision.rambler.ru/i/e.swf?id='.$m1[1].'&logo=1" /><embed src="http://vision.rambler.ru/i/e.swf?id='.$m1[1].'&logo=1" width="'.$options['width'].'" height="'.$options['height'].'" type="application/x-shockwave-flash" wmode="transparent"/></object>';
      $link['host'] = 'Rambler';
      $link['vid'] = $m1[1];
    } elseif (preg_match('%^http://video\.mail\.ru/([a-z]+)/([^/]+)/([0-9]+)/([0-9]+)\.html*%i', $url, $m1)) {
      $link['type'] = 'video/flv';
      $link['embed'] = '<object width="'.$options['width'].'" height="'.$options['height'].'"><param name="flashvars" value="imaginehost=video.mail.ru&perlhost=my.video.mail.ru&alias='.$m1[1].'&username='.$m1[2].'&albumid='.$m1[3].'&id='.$m1[4].'&catalogurl=http://video.mail.ru/catalog/music/" /><param name="movie" value="http://img.mail.ru/r/video/player_full_size.swf?par=http://video.mail.ru/'.$m1[1].'/'.$m1[2].'/'.$m1[3].'/$'.$m1[4].'$0$248"></param><embed src="http://img.mail.ru/r/video/player_full_size.swf?par=http://video.mail.ru/'.$m1[1].'/'.$m1[2].'/'.$m1[3].'/$'.$m1[4].'$0$248" type="application/x-shockwave-flash" width="'.$options['width'].'" height="'.$options['height'].'" flashvars="imaginehost=video.mail.ru&perlhost=my.video.mail.ru&alias='.$m1[1].'&username='.$m1[2].'&albumid='.$m1[3].'&id='.$m1[4].'&catalogurl=http://video.mail.ru/catalog/music/"></embed></object>';
      $link['host'] = 'Mail.Ru';
      $link['vid'] = $m1[1].'/'.$m1[2].'/'.$m1[3].'/'.$m1[4];
    } elseif (preg_match('%^http://rutube\.ru/tracks/(\d+).html\?v=(.+)$%i', $url, $m1)) {
      $link['type'] = 'video/flv';
      $link['embed'] = '<OBJECT width="'.$options['width'].'" height="'.$options['height'].'"><PARAM name="movie" value="http://video.rutube.ru/'.$m1[2].'" /><PARAM name="wmode" value="transparent" /><EMBED src="http://video.rutube.ru/'.$m1[2].'" type="application/x-shockwave-flash" wmode="transparent" width="'.$options['width'].'" height="'.$options['height'].'" /></OBJECT>';
      $link['host'] = 'RuTube';
      $link['vid'] = $m[2];
    } else {
      return false;
    }

    if (0 === strpos($link['type'], 'video/') and empty($link['thumbnail'])) {
      mcms::debug($url, $link);
      $link['thumbnail'] = 'lib/modules/base/video.png';
    }

    return $link;
  }

  public function set($value, &$node)
  {
    $this->validate($value);

    if (false !== ($video = $this->parse($value)))
      $node->{$this->value} = $this->parse($value);
  }

  public function getXML($data)
  {
    $this->addClass('form-text');

    return parent::wrapXML(array(
      'value' => $this->getValue($data),
      'maxlength' => 255,
      ));
  }

  protected function getValue($data)
  {
    $data = $data->{$this->value};

    if (is_array($data))
      $data = isset($data['url'])
        ? $data['url']
        : null;

    return $data;
  }
}
