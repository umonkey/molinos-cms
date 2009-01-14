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

    if (null === mcms::config('ftpfolder'))
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

  public function set($value, Node &$node)
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
      if (!empty($value['id']))
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
};
