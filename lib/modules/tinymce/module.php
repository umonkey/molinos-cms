<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class TinyMceModule implements iModuleConfig, iPageHook
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());
    $form->addClass('tabbed');

    $tab = new FieldSetControl(array(
      'name' => 'main',
      'label' => t('Основные настройки'),
      ));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_theme',
      'label' => t('Режим работы'),
      'options' => self::listDirs('themes'),
      'required' => true,
      )));
    $tab->addControl(new BoolControl(array(
      'value' => 'config_gzip',
      'label' => t('Использовать компрессию'),
      )));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_toolbar',
      'label' => t('Панель инструментов'),
      'required' => true,
      'options' => array(
        'topleft' => t('Сверху слева'),
        'topcenter' => t('Сверху по центру'),
        'bottomcenter' => t('Снизу по центру'),
        ),
      )));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_path',
      'label' => t('Текущий элемент'),
      'required' => true,
      'options' => array(
        '' => t('Не показывать'),
        'bottom' => t('Снизу'),
        ),
      'description' => t('При отключении пропадает также возможность изменять размер редактора мышью.'),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'plugins',
      'label' => t('Расширения'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'config_plugins',
      'label' => t('Задействовать плагины'),
      'options' => self::listDirs('plugins'),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'pages',
      'label' => t('Страницы'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'config_pages',
      'label' => t('Использовать редактор на страницах'),
      'options' => DomainNode::getFlatSiteMap('select', true),
      )));
    $form->addControl($tab);

    return $form;
  }

  private static function listDirs($path)
  {
    $list = array();

    foreach (glob(dirname(__FILE__) .'/editor/'. $path .'/'.'*', GLOB_ONLYDIR) as $d)
      if (is_dir($d)) {
        $k = basename($d);
        $list[$k] = $k;
      }

    asort($list);

    return $list;
  }

  public static function hookPostInstall()
  {
  }

  public static function hookPage(&$output, Node $page)
  {
    if ('text/html' != $page->content_type)
      return;

    $config = mcms::modconf('tinymce');

    if (empty($config['pages']) or !in_array($page->id, $config['pages']))
      return;

    if (empty($config['gzip'])) {
      $html = '<script type=\'text/javascript\' src=\'/lib/modules/tinymce/editor/tiny_mce.js\'></script>';
    } else {
      $html = '<script type=\'text/javascript\' src=\'/lib/modules/tinymce/editor/tiny_mce_gzip.js\'></script>';
      $html .= self::getInit($config, true);
    }

    $html .= self::getInit($config);

    if (!empty($html))
      $output = str_replace('</head>', $html .'</head>', $output);
  }

  private static function getInit(array $config, $gzip = false)
  {
    $init = array();

    if (!empty($config['plugins']))
      $init[] = 'plugins:"'. join(',', $config['plugins']) .'"';

    if ($gzip) {
      if (!empty($config['theme']))
        $init[] = 'themes:"'. $config['theme'] .'"';

      $init[] = 'skins:"default"';
      $init[] = 'languages:"ru"';
    } else {
      $init[] = 'mode:"textareas"';
      $init[] = 'editor_selector:"visualEditor"';
      $init[] = 'inline_styles:true';
      $init[] = 'extended_valid_elements:"a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]"';

      switch ($config['theme']) {
      case 'advanced':
        $init[] = 'theme_advanced_buttons1:"newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,styleselect,formatselect"';
        $init[] = 'theme_advanced_buttons2:"pastetext,pasteword,|,search,replace,|,outdent,indent,|,undo,redo,|,link,unlink,anchor,image,media,|,charmap,insertdate,|,sub,sup,hr"';
        $init[] = 'theme_advanced_buttons3:"tablecontrols,|,removeformat,cleanup,visualaid,styleprops,code,|,spellchecker"';
        break;
      case 'simple':
        $init[] = 'theme_simple_buttons1:"bold,italic,underline,strikethrough,cleanup,|,bullist,numlist,|,link,unlink,image,|,charmap,hr"';
        break;
      }

      if (!empty($config['toolbar'])) {
        switch ($config['toolbar']) {
        case 'topleft':
          $tmp1 = 'top';
          $tmp2 = 'left';
          break;
        case 'topcenter':
          $tmp1 = 'top';
          $tmp2 = 'center';
          break;
        case 'bottomcenter':
          $tmp1 = 'bottom';
          $tmp2 = 'center';
          break;
        }

        if (isset($tmp1))
          $init[] = 'theme_'. $config['theme'] .'_toolbar_location:"'. $tmp1 .'"';
        if (isset($tmp2))
          $init[] = 'theme_'. $config['theme'] .'_toolbar_align:"'. $tmp2 .'"';
      }

      if (!empty($config['path'])) {
        $init[] = 'theme_'. $config['theme'] .'_path_location:"'. $config['path'] .'"';
        $init[] = 'theme_'. $config['theme'] .'_resizing:true';
      }

      $init[] = 'spellchecker_languages:"English=en,+Русский=ru"';
      $init[] = 'paste_create_paragraphs:false';
      $init[] = 'paste_create_linebreaks:false';
      $init[] = 'paste_use_dialog:true';
      $init[] = 'paste_auto_cleanup_on_paste:true';
      $init[] = 'paste_convert_middot_lists:false';
      $init[] = 'paste_unindented_list_class:"unindentedList"';
      $init[] = 'paste_convert_headers_to_strong:true';

      $init[] = 'language:"ru"';
      $init[] = 'convert_urls:true';
      $init[] = 'relative_urls:false';
      $init[] = 'theme:"'. $config['theme'] .'"';
      $init[] = 'skin:"o2k7"';
      $init[] = 'file_browser_callback:"mcms_file_pick"';
    }

    $html = '<script type=\'text/javascript\'>tinyMCE';
    if ($gzip)
      $html .= '_GZ';
    $html .= '.init({'. join(',', $init) .'})';
    $html .= '</script>';

    return $html;
  }
}
