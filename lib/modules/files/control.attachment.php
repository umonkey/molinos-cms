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
      'class' => __CLASS__,
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
    $dt = array(
      'name' => null,
      'filetype' => null,
      'updated' => null,
      'created' => null,
      'filepath' => null,
      );

    if (($tmp = $data->{$this->value}) instanceof Node) {
      $dt = $tmp->getRaw();
    } elseif (is_numeric($dt)) {
      try {
        $dt = Node::load($dt)->getRaw();
      } catch (ObjectNotFoundException $e) {
      }
    }

    $dt['filename'] = $dt['name'];
    $dt['name'] = $this->value;

    return parent::wrapXML($dt + array(
      'newfile' => $this->newfile,
      'unzip' => $this->unzip,
      'fetch' => $this->fetch,
      'archive' => $this->archive,
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

  public function format($value, $em)
  {
    if (is_object($value) and 'file' == $value->class) {
      $ctx = Context::last();
      $url = os::path($ctx->config->getPath('modules/files/storage', 'files'), $value->filepath);

      if (!file_exists($url))
        return;

      switch ($value->filetype) {
      case 'video/flv':
      case 'video/x-flv':
      case 'video/mp4':
        $result = html::em($em, html::cdata($this->getPlayer($url, array(
          'width' => $value->width,
          'height' => $value->height,
          ))));
        break;
      case 'audio/mpeg':
        $result = html::em($em, html::cdata($this->getPlayer($url, array(
          'width' => 300,
          'height' => 20,
          ))));
        break;
      default:
        $inside = '';
        foreach ((array)$value->versions as $k => $v)
          $inside .= html::em('version', array(
            'id' => $k,
            'width' => $v['width'],
            'height' => $v['height'],
            'url' => MCMS_SITE_FOLDER . '/' . $v['path'],
            ));

        $result = html::em($em, array(
          'url' => $url,
          'filename' => $value->filename,
          'type' => $value->filetype,
          'size' => $value->filesize,
          'width' => $value->width,
          'height' => $value->height,
          ), $inside);
      }

      return $result;
    }

    return html::wrap($em, html::cdata($value));
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
      'path' => $base . 'lib/modules/attachment/player.swf',
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
};
