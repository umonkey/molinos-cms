<?php
/**
 * Контрол для загрузки файлов.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для загрузки файлов.
 *
 * @package mod_base
 * @subpackage Controls
 */
class AttachmentControl extends Control
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Файл'),
      );
  }

  public function __construct(array $form)
  {
    if (!array_key_exists('archive', $form))
      $form['archive'] = true;

    if (!array_key_exists('fetch', $form))
      $form['fetch'] = true;

    if (null === Context::last()->config->getPath('modules/files/ftp', 'ftp'))
      $form['ftp'] = false;

    parent::__construct($form, array('value'));

    $parts = array();

    if (!empty($this->extensions))
      $parts[] = t('Допустимые типы файлов: %list.', array('%list' => $this->extensions));

    if (!empty($parts))
      $this->description .= '<p>'. join(' ', $parts) .'</p>';
  }

  public function getXML($data)
  {
    return parent::wrapXML(array(
      'type' => 'file',
      ));
  }

  public function set($value, &$node)
  {
    if (empty($value) or !is_array($value))
      return;

    switch ($value['error']) {
    case UPLOAD_ERR_OK:
      $value = Node::create('file')->import($value);
      break;
    case UPLOAD_ERR_INI_SIZE:
      throw new RuntimeException(t('Размер файла превышает установленное в системе ограничение (%size).',
        array('%size' => ini_get('upload_max_filesize'))));
    case UPLOAD_ERR_FORM_SIZE:
      throw new RuntimeException(t('Размер файла превышает установленное в форме ограничение.'));
    case UPLOAD_ERR_PARTIAL:
      throw new RuntimeException(t('Файл получен не полностью.'));
    case UPLOAD_ERR_NO_FILE:
      if (!empty($value['id']) and is_numeric($value['id']))
        $value = Node::load($value['id']);
      elseif (!empty($value['url']))
        $value = FileNode::fromURL($value['url']);
      elseif (!empty($value['ftp']))
        $value = Node::create('file')->importFromFTP(array_shift($value['ftp']));
      elseif (!empty($value['unlink'])) {
        $node->linkRemoveChild(null, $this->value);
        $value = null;
      }
      else
        $value = $node->{$this->value};
    }

    if ($this->required and empty($value))
      throw new ValidationException($this->label, t('Вы не загрузили файл в поле «%name», хотя оно отмечено как обязательное.', array(
        '%name' => mb_strtolower($this->label),
        )));

    $node->{$this->value} = $value;
  }

  public function getLinkId($data)
  {
    if (null !== ($value = $data->{$this->value})) {
      if (is_numeric($value))
        ;
      elseif (is_array($value))
        $value = array_key_exists('id', $value)
          ? $value['id']
          : null;
      elseif ($value instanceof Node)
        $value = $value->id
          ? $value->id
          : $value->save()->id;
    }

    return $value;
  }

  public function format(Node $node, $em)
  {
    $value = $node->{$this->value};

    if (is_object($value) and 'file' == $value->class) {
      if (!($inside = $value->getVersionsXML()))
        $inside = html::cdata($this->getEmbedCode($value));

      if ($value->description)
        $inside .= html::em('description', html::cdata($value->description));

      $ctx = Context::last();

      $url = (0 === strpos($value->filetype, 'image/'))
        ? os::webpath($ctx->config->getPath('modules/files/storage', 'files'), $value->filepath)
        : "download/{$value->id}/{$value->filename}";

      $options = array_merge(array(
        'id' => $value->id,
        'name' => $value->name,
        'filename' => $value->name,
        'filesize' => $value->filesize,
        'filetype' => $value->filetype,
        'width' => $value->width,
        'height' => $value->height,
        'url' => $url,
        ), (array)$value->metadata);

      return html::em($em, $options, $inside);
    }

    return html::wrap($em, html::cdata($value));
  }

  public function preview($value)
  {
    $file = $value->{$this->value};

    if (!($file instanceof FileNode))
      return;

    if (0 === strpos($file->filetype, 'image/')) {
      $path = Context::last()->config->getPath('modules/files/storage');

      if (isset($file->versions['thumbnail'])) {
        $attrs = array(
          'width' => $file->versions['thumbnail']['width'],
          'height' => $file->versions['thumbnail']['height'],
          'src' => os::webpath($path, $file->versions['thumbnail']['filename']),
          );
      } else {
        $attrs = array(
          'width' => $file->width,
          'height' => $file->height,
          'src' => os::webpath($path, $file->filepath),
          );
      }

      $attrs['alt'] = $file->filename;

      $img = html::em('img', $attrs);
      $html = html::em('a', array(
        'href' => 'admin/node/' . $file->id
          .  '?destination=' . urlencode(MCMS_REQUEST_URI),
        ), $img);
    }

    // Не картинки.
    else {
      $html = html::em('a', array(
        'href' => "admin/node/{$file->id}?destination=CURRENT",
        ), html::plain($file->filename));
      // $html = $this->getEmbedCode($file);
    }

    if ($value->checkPermission(ACL::UPDATE))
      $html .= t('<p><a href="@url">Открепить файл</a></p>', array(
        '@url' => "files/unlink.rpc?node={$value->id}&file={$file->id}&field={$this->value}&destination=" . urlencode(MCMS_REQUEST_URI),
        ));

    return html::em('value', array(
      'html' => true,
      ), html::cdata($html));
  }

  /**
   * Возвращает код для встраивания файла, без обёртки.
   */
  protected function getEmbedCode($data)
  {
    if (!($data instanceof Node))
      return;

    $ctx = Context::last();
    $url = "download/{$data->id}/{$data->filename}";
    // $url = os::path($ctx->config->getPath('modules/files/storage', 'files'), $data->filepath);

    switch ($data->filetype) {
    case 'video/flv':
    case 'video/x-flv':
    case 'video/mp4':
      return $this->getPlayer($url, array(
        'width' => $data->width,
        'height' => $data->height,
        ));
    case 'audio/mpeg':
      return $this->getPlayer($url, array(
        'width' => 300,
        'height' => 20,
        ));
    case 'image/jpeg':
    case 'image/pjpeg':
    case 'image/png':
    case 'image/gif':
      return html::em('img', array(
        'src' => $url,
        'width' => $data->width,
        'height' => $data->height,
        'alt' => $data->name,
        ));
    default:
      return t('<a href="@url">Скачать %filename</a>', array(
        '@url' => $url,
        '%filename' => $data->filename,
        ));
    }
  }

  private function getPlayer($_url, array $options = array())
  {
    foreach ($options as $k => $v)
      if (null === $v)
        unset($options[$k]);

    $options = array_merge(array(
      'width' => 400,
      'height' => 300,
      ), $options);

    $ctx = Context::last();
    $base = $ctx->url()->getBase($ctx);
    $_file = $base . $_url;

    $url = new url(array(
      'path' => $base . 'lib/modules/files/player.swf',
      ));
    $url->setarg('file', os::webpath($_file));
    $url->setarg('width', $options['width']);
    $url->setarg('height', $options['height']);

    $params = html::em('param', array(
      'name' => 'movie',
      'value' => $url->string(),
      ));
    $params .= html::em('param', array(
      'name' => 'wmode',
      'value' => 'transparent',
      ));

    $obj = array(
      'type' => 'application/x-shockwave-flash',
      'data' => $url->string(),
      'width' => $options['width'],
      'height' => $options['height'],
      );

    return html::em('object', $obj, $params);
  }

  public function getFieldEditURL(Node $node)
  {
    return 'admin/content/files?sendto=' . $node->id
      . '.' . $this->value
      . '&destination=' . urlencode($_GET['destination']);
  }
};
