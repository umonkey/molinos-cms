<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TinyMceModule
{
  /**
   * Возвращает список скриптов и стилей для использования на сайте.
   *
   * @mcms_message ru.molinos.cms.compressor.enumXXX
   */
  public static function on_compressor_enum(Context $ctx, $mode = 'website')
  {
    $result = array();
    $conf = $ctx->config->get('modules/tinymce');

    $conf['gzip'] = false;

    if (empty($conf['gzip']))
      $result[] = array('script', 'lib/modules/tinymce/editor/tiny_mce.js');
    else
      $result[] = array('script', 'lib/modules/tinymce/editor/tiny_mce_gzip.js');

    $initializer = empty($conf['initializer']) ? '' : $conf['initializer'] . ', ';
    $initializer .= 'document_base_url: "http://' . MCMS_HOST_NAME . $ctx->folder() . '/", ';
    $initializer .= 'tiny_mce_path: "lib/modules/tinymce/editor"';
    $text = 'tinyMCE_initializer = {' . $initializer . '};';
    os::write($path = os::path($ctx->config->getPath('main/tmpdir'), 'tinymce_initializer.js'), $text);
    $result[] = array('script', os::localpath($path));

    $theme = empty($conf['theme'])
      ? 'simple'
      : $conf['theme'];

    if (!empty($conf['gzip']))
      if (file_exists($path = os::path('lib', 'modules', 'tinymce', 'editor', 'template_' . $theme . '_gzip.js')))
        $result[] = array('script', os::webpath($path));
    if (file_exists($path = os::path('lib', 'modules', 'tinymce', 'editor', 'template_' . $theme . '.js')))
      $result[] = array('script', os::webpath($path));

    return $result;
  }

  /*
   * @mcms_message ru.molinos.cms.compressor.enum.adminXXX
   */
  public static function on_compressor_admin(Context $ctx)
  {
    return self::on_compressor_enum($ctx, 'admin');
  }

  /**
   * @mcms_message ru.molinos.cms.page.head
   */
  public static function on_get_head(Context $ctx)
  {
    if (self::isPageEnabled($ctx)) {
      $result = '';

      $base = $ctx->url()->getBase($ctx);
      $result .= html::em('script', array(
        'type' => 'text/javascript',
        ), "var mcms_url = '{$base}';");

      if ($picker = $ctx->get('tinymcepicker')) {
        $result .= html::em('script', "var tinyMcePicker = '{$picker}';");
        $result .= html::em('script', array(
          'src' => $base . 'lib/modules/tinymce/editor/tiny_mce_popup.js',
          'type' => 'text/javascript',
          ));
      } elseif (preg_match('%^(admin/edit/|admin/create/)%', $ctx->query())) {
        foreach (self::on_compressor_admin($ctx) as $script)
          if (file_exists($script[1]))
            $result .= html::em('script', array(
              'src' => $base . $script[1],
              'type' => 'text/javascript',
              ));
      }

      return html::wrap('head', html::cdata($result), array(
        'module' => 'tinymce',
        ));
    }
  }

  /**
   * Проверяет, разрешено ли использовать редактор на странице.
   */
  private static function isPageEnabled(Context $ctx)
  {
    $pages = $ctx->config->getArray('modules/tinymce/routes', array(
      'admin/edit/',
      'admin/create/',
      ));

    if ($ctx->get('tinymcepicker'))
      $pages[] = 'admin/content/files';

    $query = $ctx->query();

    foreach ($pages as $prefix)
      if (0 === strpos($query, $prefix))
        return true;

    return false;
  }
}
