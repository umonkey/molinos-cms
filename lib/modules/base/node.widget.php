<?php
/**
 * Тип документа «widget» — описание виджета.
 *
 * @package mod_base
 * @subpackage Types
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Тип документа «widget» — описание виджета.
 *
 * @package mod_base
 * @subpackage Types
 */
class WidgetNode extends Node implements iContentType
{
  // Проверяем на уникальность.
  /**
   * Сохранение виджета.
   *
   * Проверяет имя виджета на уникальность, пресекает попытки сделать виджет
   * дочерним объектом.  Проверяет, не содержит ли имя виджета недопустимые
   * символы.  Публикует все создаваемые виджеты.
   *
   * @return Node ссылка на себя (для организации цепочек).
   */
  public function save()
  {
    if ($isnew = (null === $this->id))
      $this->data['published'] = true;

    if ($this->parent_id !== null)
      throw new InvalidArgumentException("Виджет не может быть дочерним объектом.");

    $this->name = trim($this->name);

    if (!empty($_SERVER['HTTP_HOST']) and (empty($this->name) or strspn(mb_strtolower($this->name), "abcdefghijklmnopqrstuvwxyz0123456789_") != strlen($this->name)))
      throw new ValidationException('name', "Имя виджета может содержать "
        ."только буквы латинского алфавита и арабские цифры.&nbsp; "
        ."Пожалуйста, переименуйте виджет.");

    parent::checkUnique('name', t("Виджет с таким именем уже существует."));

    return parent::save();
  }

  /**
   * Клонирование виджета.
   *
   * Добавляет к имени клонируемого объекта немного мусора, для уникальности.
   *
   * @param integer $parent новый код родителя.
   *
   * @return Node новый объект.
   */
  public function duplicate($parent = null)
  {
    $this->name = preg_replace('/_[0-9]+$/', '', $this->name) .'_'. rand();
    return parent::duplicate($parent);
  }

  /**
   * Возвращает форму для редактирования виджета.
   *
   * К стандартной форме добавляется класс с типом виджета.
   *
   * @return Form описание формы.
   */
  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);
    $form->addClass($this->classname .'-config');

    foreach ((array)$this->config as $k => $v)
      $this->{'config_' . $k} = $v;

    return $form;
  }

  /**
   * Обработчик форм.
   *
   * Обновляет конфигурацию виджета, привязку к страницам и типам документов.
   * После обработки формы пользователь перебрасывается на новую страницу, в
   * зависимости от ситуации.
   *
   * @param array $data полученные от пользователя данные.
   *
   * @return void
   */
  public function formProcess(array $data)
  {
    $isnew = (null === $this->id);

    parent::formProcess($data);

    $config = array();

    foreach ($this->data as $k => $v) {
      if (0 === strpos($k, 'config_')) {
        if (!empty($v))
          $config[substr($k, 7)] = $v;
        unset($this->data[$k]);
      }
    }

    if (!empty($config))
      $this->data['config'] = $config;
    elseif (array_key_exists('config', $this->data))
      unset($this->data['config']);

    return $this;
  }

  protected static function listWidgets()
  {
    $classes = array();

    foreach (mcms::getImplementors('iWidget') as $classname) {
      if ($classname != 'widget' and substr($classname, -11) != 'adminwidget') {
        $info = Widget::getInfo($classname);
        if (empty($info['hidden']) and !empty($info['name']))
          $classes[$classname] = $info['name'];
      }
    }

    asort($classes);

    return $classes;
  }

  protected function getDefaultSchema()
  {
    $schema = array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Внутреннее имя'),
        'description' => t('Используется для идентификации виджета внутри шаблонов, а также для поиска шаблонов для виджета.'),
        'required' => true,
        ),
      'title' => array(
        'type' => 'TextLineControl',
        'label' => t('Название'),
        'description' => t('Человеческое название виджета.'),
        'required' => true,
        ),
      'description' => array(
        'label' => t('Описание'),
        'type' => 'TextAreaControl',
        'description' => t('Краткое описание выполняемых виджетом функций и особенностей его работы.'),
        ),
      'classname' => array(
        'label' => t('Используемый класс'),
        'type' => 'EnumControl',
        'required' => true,
        'volatile' => true,
        'options' => self::listWidgets(),
        ),
      'pages' => array(
        'type' => 'SetControl',
        'label' => t('Виджет работает на страницах'),
        'group' => t('Страницы'),
        'volatile' => true,
        'dictionary' => 'domain',
        'required' => false,
        'parents' => true,
        ),
      'perms' => array(
        'type' => 'AccessControl',
        'label' => t('Виджет доступен группам'),
        'group' => t('Доступ'),
        'volatile' => true,
        ),
      );

    // Добавляем настройки виджета.
    if (!empty($this->classname) and class_exists($this->classname)) {
      $result = call_user_func(array($this->classname, 'getConfigOptions'));

      if (is_array($result))
        foreach ($result as $k => $v) {
          $v['value'] = 'config_' . $k;
          $v['volatile'] = true;
          if (!array_key_exists('group', $v))
            $v['group'] = t('Настройки');
          if (array_key_exists('ifmodule', $v) and !mcms::ismodule($v['ifmodule']))
            continue;
          $schema[$v['value']] = $v;
        }
    }

    return $schema;
  }
};
