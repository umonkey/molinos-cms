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

  public function getFormTitle()
  {
    return $this->id
      ? t('Настройка виджета «%name»', array('%name' => $this->name))
      : t('Добавление нового виджета');
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

    // Шаг 1: выбор типа.
    if ($isnew and !empty($data['from']) and !empty($data['classname'])) {
      $url = new url($data['from']);
      $url->setarg('node.classname', $data['classname']);
      Context::last()->redirect($url->string());
    }

    if (!empty($data['classname']))
      $this->classname = $data['classname'];

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
        if (empty($info['hidden']) and !empty($info['name'])) {
          $classes[$classname] = $info['name'];
          if (!empty($info['description']))
            $classes[$classname] .= mcms::html('p', array(
              'class' => 'note',
              ), mcms_plain($info['description']));
        }
      }
    }

    asort($classes);

    return $classes;
  }

  public static function getDefaultSchema()
  {
    return array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Внутреннее имя'),
        'description' => t('Используется для идентификации виджета внутри шаблонов, а также для поиска шаблонов для виджета.'),
        'required' => true,
        're' => '/^[a-z0-9_]+$/i',
        'volatile' => true,
        ),
      'title' => array(
        'type' => 'TextLineControl',
        'label' => t('Название'),
        'description' => t('Человеческое название виджета.'),
        'volatile' => true,
        ),
      'description' => array(
        'label' => t('Описание'),
        'type' => 'TextAreaControl',
        'description' => t('Краткое описание выполняемых виджетом функций и особенностей его работы.'),
        'volatile' => true,
        ),
      'classname' => array(
        'label' => t('Используемый класс'),
        'type' => 'TextLineControl',
        'description' => t('Не рекоммендуется изменять это значение, если вы не представляете, чем это грозит.'),
        'required' => true,
        'volatile' => true,
        ),
      'pages' => array(
        'type' => 'SetControl',
        'label' => t('Виджет работает на страницах'),
        'group' => t('Доступ'),
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
        'columns' => array('r'),
        ),
      );
  }

  /**
   * Для новых виджетов возвращается урезанная схема, из одного поля (выбор типа виджета).
   */
  public function getFormFields()
  {
    if ($this->isNew() and empty($this->classname))
      return new Schema(array(
        'classname' => array(
          'type' => 'EnumRadioControl',
          'label' => t('Тип виджета'),
          'options' => self::listWidgets(),
          'description' => t('Виджеты — это блоки, из которых формируются страницы, фрагменты приложения. Каждый виджет выполняет одну конкретную функцию. Выберите, какой виджет вы хотите создать.'),
          'required' => true,
          ),
        'from' => array(
          'type' => 'HiddenControl',
          'default' => Context::last()->url()->string(),
          ),
        ));

    $schema = $this->getSchema();

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

    $schema['classname'] = new HiddenControl(array(
      'value' => 'classname',
      ));

    return $schema;
  }

  public function getFormSubmitText()
  {
    return $this->id
      ? parent::getFormSubmitText()
      : t('Продолжить');
  }
};
