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
    parent::__construct($form, array('value'));

    $parts = array();

    if (!empty($this->extensions))
      $parts[] = t('Допустимые типы файлов: %list.', array('%list' => $this->extensions));

    $this->description .= '<p>'. join('  ', $parts) .'</p>';
  }

  public function getHTML(array $data)
  {
    if (!empty($data[$this->value])) {
      if (($dt = $data[$this->value]) instanceof Node)
        $dt = $dt->getRaw();
      elseif (is_numeric($dt))
        $dt = Node::load($dt)->getRaw();
    } else {
      $dt = array(
        'name' => null,
        'filetype' => null,
        'updated' => null,
        'created' => null,
        'filepath' => null,
        );
    }

    return $this->wrapHTML($this->render($dt), false);
  }
};
