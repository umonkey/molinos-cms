<?php

class UebAPI
{
  /**
   * Выводит <link>
   * @mcms_message ru.molinos.cms.page.head
   */
  public static function on_get_head(Context $ctx, array $pathinfo, $param)
  {
    $attrs = array(
      'rel' => 'alternate',
      'type' => 'application/x-wiki',
      );

    if ($param) {
      $attrs['title'] = t('Редактировать');
      $attrs['href'] = 'admin/node/' . $param;
    } else {
      $attrs['title'] = t('Создать документ');
      $attrs['href'] = 'admin/create';
    }

    $attrs['href'] = $ctx->url()->getBase($ctx) . $attrs['href'] . '?destination=' . urlencode($_SERVER['REQUEST_URI']);

    $output = html::em('link', $attrs);

    return html::em('head', array(
      'module' => 'ueb',
      ), html::cdata($output));
  }
}
