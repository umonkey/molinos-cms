<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TinyMceModule
{
  /**
   * Возвращает список скриптов и стилей для использования на сайте.
   *
   * @mcms_message ru.molinos.cms.compressor.enum
   */
  public static function on_compressor_enum(Context $ctx, $mode = 'website')
  {
    $result = array();
    $conf = $ctx->config->get('modules/tinymce');

    if (empty($conf['pages']) or !in_array($mode, (array)$conf['pages']))
      return $result;

    $conf['gzip'] = false;

    if (empty($conf['gzip']))
      $result[] = array('script', 'lib/modules/tinymce/editor/tiny_mce.js');
    else
      $result[] = array('script', 'lib/modules/tinymce/editor/tiny_mce_gzip.js');

    $result[] = array('script', 'lib/modules/tinymce/file_picker.js');

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
   * @mcms_message ru.molinos.cms.compressor.enum.admin
   */
  public static function on_compressor_admin(Context $ctx)
  {
    return self::on_compressor_enum($ctx, 'admin');
  }
}
