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
   * При формировании списка доступных типов виджетов используются классы,
   * реализующие интерфейс iWidget.
   *
   * @see iWidget
   *
   * @param bool $simple true, если форма не должна содержать дополнительные
   * вкладки (историю изменений, файлы итд).
   *
   * @return Form описание формы.
   */
  public function formGet($simple = true)
  {
    if (null === $this->id) {
      $classes = self::listWidgets();

      $form = new Form(array(
        'title' => t('Добавление виджета'),
        ));
      $form->addControl(new HiddenControl(array(
        'value' => 'class',
        )));
      $form->addControl(new TextLineControl(array(
        'value' => 'name',
        'label' => t('Внутреннее имя'),
        'description' => t('Может содержать только цифры, латинские буквы и символ подчёркивания.'),
        'required' => true,
        'class' => 'form-title',
        )));
      $form->addControl(new TextLineControl(array(
        'value' => 'title',
        'label' => t('Видимое название'),
        'description' => t('Может содержать произвольный текст.&nbsp; Иногда используется не только в админке, но и на сайте.'),
        'required' => true,
        )));
      $form->addControl(new EnumControl(array(
        'value' => 'classname',
        'label' => t('Тип'),
        'required' => true,
        'options' => $classes,
        )));
      $form->addControl(new SubmitControl(array(
        'text' => t('Создать'),
        )));
    } else {
      $form = parent::formGet($simple);
      $form->title = t('Редактирование виджета "%name"', array('%name' => $this->name));

      // FIXME: переписать работу с настройками виджетов!
      if (!empty($this->config))
        foreach ($this->config as $k => $v)
          $this->{'config_' . $k} = $v;

      if (mcms::class_exists($this->classname))
        if (null !== ($tab = call_user_func(array($this->classname, 'formGetConfig'), $this))) {
          $tab->intro = t('Подробную информацию о настройке этого виджета можно <a href=\'@link\'>найти в документации</a>.', array(
            '@link' => 'http://code.google.com/p/molinos-cms/wiki/'. $this->classname,
            ));

          $form->addControl($tab);
        }

      $form->addClass($this->classname .'-config');

      if (null !== ($tmp = $form->findControl('classname'))) {
        $tmp->label = 'Тип';
        $tmp->readonly = true;
        $tmp->required = false;
      }

      $form->hideControl('config');
    }

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

    $config = array();

    foreach ($data as $k => $v)
      if (substr($k, 0, 7) == 'config_') {
        if ($k == 'config_types')
          unset($v['__reset']);
        $config[substr($k, 7)] = $v;
      }

    $this->config = $config;

    if (mcms::class_exists($this->classname)) {
      $w = new $this->classname($this);
      $w->formHookConfigSaved();
    }

    /* $next = */ parent::formProcess($data)->save();

    if ($isnew) {
      $next = "?q=admin&mode=edit&cgroup=structure&id={$this->id}"
        ."&destination=". urlencode($_GET['destination']);
      mcms::redirect($next);
    } else if (!empty($_GET['destination'])) {
      mcms::redirect($_GET['destination']);
    } else {
      mcms::redirect("admin");
    }
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
    return array(
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
        'type' => 'TextLineControl',
        'required' => true,
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
        ),
      );
  }

  public function schema()
  {
    $schema = parent::schema();

    if (isset($schema['config']))
      unset($schema['config']);

    return $schema;
  }
};
