<?php
/**
 * Контрол для редактирования поля типа документа.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для редактирования поля типа документа.
 *
 * Скрытый контрол для внутреннего использования.
 *
 * @package mod_base
 * @subpackage Controls
 */
class FieldControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Поле типа данных (редактор)'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getXML($data)
  {
    $output = $this->getTypes();
    $output .= $this->getDictionaries();

    if (is_array($data->{$this->value}))
      foreach ($data->{$this->value} as $k => $v)
        $output .= html::em('field', array('name' => $k) + $v);

    for ($idx = 1; $idx <= 5; $idx++) {
      $output .= html::em('field', array(
        'name' => 'newfield' . $idx,
        'isnew' => true,
        'type' => 'textlinecontrol',
        ));
    }

    return parent::wrapXML(array(), $output);
  }

  private function getTypes($current = null)
  {
    $types = array();

    foreach ($tmp = Loader::getImplementors('iFormControl') as $class) {
      if (class_exists($class)) {
        if ('control' != $class) {
          $info = call_user_func(array($class, 'getInfo'));
          if (empty($info['hidden']))
            $types[$class] = $info['name'];
        }
      }
    }

    asort($types);

    $output = '';

    foreach ($types as $k => $v)
      $output .= html::em('type', array(
        'name' => $k,
        'label' => $v,
        ));

    return $output;
  }

  // FIXME: переделать так, чтобы отображались только справочники.
  private function getDictionaries($current = null)
  {
    if ((null !== $current) and (false !== strpos($current, '.')))
      $current = substr($current, 0, strpos($current, '.'));

    $options = Node::getSortedList('type', 'title', 'name');

    $output = '';

    foreach ($options as $k => $v)
      $output .= html::em('dictionary', array(
        'name' => $k,
        'label' => $v,
        ));

    return $output;
  }

  public function set($value, Node &$node)
  {
    if (empty($value['__reset']))
      return;

    $node->{$this->value} = $this->extractFields($value);
  }

  protected function validate($value)
  {
    if (empty($value['__reset']))
      return true;

    if (!count($fields = $this->extractFields($value)))
      throw new ValidationException(t('Тип документа должен содержать хотя бы одно поле.'));

    foreach ($this->extractFields($value) as $name => $info) {
      if (strspn(mb_strtolower($name), '0123456789abcdefghijklmnopqrstuvwxyz_') != strlen($name))
        throw new ValidationException('Имя поля может содержать только цифры, буквы и прочерк.');
    }

    return true;
  }

  private function extractFields(array $data)
  {
    $fields = array();

    foreach ($data as $idx => $f) {
      if ($idx != '__reset' and !empty($f['name']) and empty($f['delete'])) {
        $name = $f['name'];
        unset($f['name']);

        foreach ($f as $k => $v)
          if ('' === $v)
            unset($f[$k]);

        $fields[$name] = $f;
      }
    }

    return $fields;
  }
};
