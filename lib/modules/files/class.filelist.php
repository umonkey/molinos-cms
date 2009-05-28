<?php
/**
 * Вывод списка файлов.
 */

class FileList extends AdminListHandler implements iAdminList
{
  private $type;

  public function __construct(Context $ctx, $type = null)
  {
    $this->type = $type;
    parent::__construct($ctx, 'file');
  }

  /**
   * Возвращает фильтр для выборки файлов.
   */
  protected function getNodeFilter()
  {
    $filter = parent::getNodeFilter();

    switch ($this->type) {
    case 'multimedia':
      $filter['filetype?|'] = array(
        'audio/%',
        'video/%',
        );
      break;
    case 'picture':
      $filter['filetype?|'] = 'image/%';
      break;
    case 'office':
      $filter['filename?|'] = array(
        '.doc%',
        '.xsl%',
        '.pdf%',
        );
      break;
    }

    return $filter;
  }

  /**
   * Вывод списка файлов.
   */
  public static function on_get_list(Context $ctx)
  {
    $options = array(
      '#raw' => true,
      'name' => 'list',
      'title' => t('Файловый архив'),
      'path' => os::webpath(MCMS_SITE_FOLDER, $ctx->config->get('modules/files/storage')),
      'advsearch' => true,
      'canedit' => true,
      'mode' => $ctx->get('mode', 'table'),
      'type' => $ctx->get('type', 'all'),
      );

    $tmp = new FileList($ctx, $options['type']);
    return $tmp->getHTML('files', $options);
  }
}
