<?php
/* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2: */

function smarty_function_bebop_render_file($params, &$smarty)
{
  $message = $output = null;

  if (empty($params['file']))
    $message = "Не получен массив с описанием файла, воспользуйтесь параметром file.";
  elseif (empty($params['file']['filetype']))
    $message = "Полученный массив не похож на описание файла (отсутствует поле filetype).";
  else {
    switch ($params['file']['filetype']) {
    case 'image/jpeg':
    case 'image/pjpeg':
    case 'image/png':
    case 'image/gif':
      $file = $params['file'];
      $src = 'attachment/'. $file['id'];

      $output = '<img';

      if (!empty($params['class']))
        $output .= ' class=\''. mcms_plain($params['class']) .'\'';

      $width = $file['width'];
      $height = $file['height'];

      if (!empty($params['width']) or !empty($params['height'])) {
        $src .= ',';

        if (!empty($params['width'])) {
          $width = $params['width'];
          $src .= $width;
          $output .= " width='{$width}'";
        }

        $src .= ',';

        if (!empty($params['height'])) {
          $height = $params['height'];
          $src .= $height;
          $output .= " height='{$height}'";
        }

        $src .= ',9';

        if (!empty($params['scale'])) {
          if ($params['scale'] == 'crop')
            $src .= 'c';
          elseif ($params['scale'] == 'downsize') {
            $src .= 'cd';

            if (!empty($params['background']))
              $src .= 'w';
          }
        }
      }

      $alt = mcms_plain(empty($file['name']) ? $file['filename'] : $file['name']);

      $output .= " src='{$src}' alt='{$alt}' />";
      break;
     
    case 'application/x-shockwave-flash':
      // получаем параметры файла
      $file = $params['file'];
      $src = 'attachment/'. $file['id'];

      // определяем высоту и ширину - либо по данным, выданным виджетом, либо по данным, указанным в вызове функции. последнее приоритетней
      (empty($params['height'])) ? $height = $file['height'] : $height = $params['height'];
      (empty($params['width'])) ? $width = $file['width'] : $width = $params['width'];

      // задаем значение переменной для вывода результирующего кода
      $output = '<!--[if !IE]> -->'
        .'<object type="application/x-shockwave-flash" data="'.$src.'" width="'.$width.'" height="'.$height.'">'
        .'<!-- <![endif]-->'
        .'<!--[if IE]>'
        .'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="'.$width.'" height="'.$height.'">'
        .'<param name="movie" value="'.$src.'" />'
        .'<!--><!--dgx-->'
        .'<param name="loop" value="true" />'
        .'</object>'
        .'<!-- <![endif]-->';
      break;
      
    default:
      $message = "Неподдерживаемый тип файла: ". $params['file']['filetype'];
    }
  }

  if ($output !== null) {
    if (empty($params['assign']))
      return $output;
    else
      $smarty->assign($params['assign'], $output);
  }

  elseif ($message !== null) {
    return "<p><strong>". $message ."</strong></p>";
  }
}
