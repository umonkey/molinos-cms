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
        'application/x-shockwave-flash',
        );
      break;
    case 'picture':
      $filter['filetype?|'] = 'image/%';
      break;
    case 'office':
      $filter['filename?|'] = array(
        '%.doc',
        '%.xsl',
        '%.pdf',
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
    try {
      $options = array(
        '#raw' => true,
        'name' => 'list',
        'title' => t('Файловый архив'),
        'path' => os::webpath(MCMS_SITE_FOLDER, $ctx->config->get('modules/files/storage')),
        'advsearch' => true,
        'canedit' => true,
        'mode' => $ctx->get('mode', 'table'),
        'scope' => $ctx->get('scope'),
        'type' => 'file',
        );

      $tmp = new FileList($ctx, $options['scope']);
      return $tmp->getHTML('files', $options);
    } catch (TableNotFoundException $e) {
      if ($e->getTableName() != 'node__idx_filetype')
        throw $e;
      throw new Exception(t('Отсутствует индекс по полю filetype, <a href="@url">исправьте это</a> и возвращайтесь.', array(
        '@url' => 'admin/structure/fields/edit?type=file&field=filetype&destination=' . urlencode($_SERVER['REQUEST_URI']),
        )));
    }
  }
}
