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

    $tmp = Context::last()->get('node');
    if (empty($tmp['classname']))
      $form->addControl(new HiddenControl(array(
        'value' => 'from',
        'default' => MCMS_REQUEST_URI,
        )));

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
    $isnew = $this->isNew();

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

    foreach ($this->getProperties() as $k) {
      if (0 === strpos($k, 'config_')) {
        if (!empty($this->$k))
          $config[substr($k, 7)] = $this->$k;
        unset($this->$k);
      }
    }

    if (!empty($config))
      $this->config = $config;
    else
      unset($this->config);

    return $this;
  }

  protected static function listWidgets()
  {
    $widgets = Context::last()->registry->poll('ru.molinos.cms.widget.enum');

    $result = array();
    foreach ($widgets as $info)
      $result[$info['class']] = $info['result']['name'];

    asort($result);

    return $result;
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
        'weight' => 1,
        ),
      'title' => array(
        'type' => 'TextLineControl',
        'label' => t('Название'),
        'description' => t('Человеческое название виджета.'),
        'volatile' => true,
        'weight' => 2,
        ),
      'description' => array(
        'label' => t('Описание'),
        'type' => 'TextAreaControl',
        'description' => t('Краткое описание выполняемых виджетом функций и особенностей его работы.'),
        'volatile' => true,
        'weight' => 3,
        ),
      'classname' => array(
        'label' => t('Используемый класс'),
        'type' => 'TextLineControl',
        'description' => t('Не рекоммендуется изменять это значение, если вы не представляете, чем это грозит.'),
        'required' => true,
        'volatile' => true,
        'weight' => 4,
        ),
      'pages' => array(
        'type' => 'SetControl',
        'label' => t('Виджет работает на страницах'),
        'group' => t('Доступ'),
        'volatile' => true,
        'dictionary' => 'domain',
        'required' => false,
        'parents' => true,
        'weight' => 10,
        ),
      'perms' => array(
        'type' => 'AccessControl',
        'label' => t('Виджет доступен группам'),
        'group' => t('Доступ'),
        'volatile' => true,
        'columns' => array('r'),
        'weight' => 11,
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
        $weight = 60;

        foreach ($result as $k => $v) {
          $v['value'] = 'config_' . $k;
          $v['volatile'] = true;
          if (!array_key_exists('group', $v))
            $v['group'] = t('Настройки');
          if (array_key_exists('ifmodule', $v) and class_exists('modman') and !modman::isInstalled($v['ifmodule']))
            continue;
          $v['weight'] = $weight++;
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

  public function getXML($em = 'node', $_content = null)
  {
    foreach ($info = Widget::getInfo($this->classname) as $k => $v)
      $this->data['_widget_' . $k] = $v;

    return parent::getXML($em, $_content);
  }
};
