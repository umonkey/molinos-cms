<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModuleAdminWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Управление модулями',
      'description' => 'Позволяет включать, выключать и настраивать модули..',
      'hidden' => true,
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    /*
    $form->addControl(new TextLineControl(array(
      'value' => 'config_varname',
      'label' => t('Название настройки'),
      )));
    */

    return $form;
  }

  public function formHookConfigData(array &$data)
  {
    // $data['xyz'] = 123;
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    if (null !== ($options['edit'] = $ctx->get('edit')))
      $options['#nocache'] = true;

    return $this->options = $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return parent::formRender($options['edit'] ? 'module-edit' : 'module-list');
  }

  public function formGet($id)
  {
    $form = null;

    switch ($id) {
    case 'module-list':
      $form = new Form(array(
        'title' => t('Настройка модулей'),
        'class' => 'tabbed',
        'intro' => t('Информацию о конкретном <a href=\'@modurl\'>модуле</a> можно найти в документаци (по ссылке с имени модуля), или в <a href=\'@listurl\'>общем списке модулей</a>.', array(
          '@modurl' => 'http://code.google.com/p/molinos-cms/wiki/Modules',
          '@listurl' => 'http://code.google.com/p/molinos-cms/w/list?q=label:Module',
          )),
        ));

      foreach ($this->getModuleGroups() as $group) {
        $tab = new FieldSetControl(array(
          'name' => $group,
          'label' => $group,
          ));
        $tab->addControl(new ModuleListControl(array(
          'value' => 'module_list',
          'group' => $group,
          )));
        $form->addControl($tab);
      }

      $form->addControl(new SubmitControl(array(
        'text' => t('Применить'),
        )));

      break;

    case 'module-edit':
      $map = mcms::getModuleMap();
      $modname = $this->options['edit'];

      if (!array_key_exists($modname, $map['modules']))
        throw new PageNotFoundException();

      $classes = $map['modules'][$modname]['classes'];
      $implementors = mcms::getImplementors('iModuleConfig');

      $classname = null;

      foreach ($implementors as $tmp) {
        if (in_array(strtolower($tmp), $classes)) {
          $classname = $tmp;
          break;
        }
      }

      if (null === $classname)
        throw new PageNotFoundException();

      $rator = $classname; // $map[$this->options['edit']]['interface']['iModuleConfig'][0];

      if (!mcms::class_exists($rator))
        throw new InvalidArgumentException(t('Настройка модуля %name невозможна: класс %class не загружен.', array(
          '%name' => $this->options['edit'],
          '%class' => $rator,
          )));

      if (null !== ($form = call_user_func(array($rator, 'formGetModuleConfig')))) {
        $form->intro = t('Подробности об этом модуле можно найти в <a href=\'@url\'>документации</a>.', array('@url' => 'http://code.google.com/p/molinos-cms/wiki/mod_'. str_replace('-', '_', $this->options['edit'])));
        if (!isset($form->title))
          $form->title = t('Настройка модуля %module', array('%module' => $this->options['edit']));

        $form->addControl(new SubmitControl(array(
          'text' => t('Применить'),
          )));

        return $form;
      } else {
        throw new PageNotFoundException();
      }
    }

    return $form;
  }

  public function formGetData($id)
  {
    $data = null;

    switch ($id) {
    case 'module-list':
      $data = array();
      $enabled = array();

      foreach (Node::find(array('class' => 'moduleinfo', 'published' => 1)) as $tmp)
        $enabled[] = $tmp->name;

      $mmap = mcms::getModuleMap();

      foreach ($mmap['modules'] as $name => $info) {
        if ('Core' == $info['group'])
          $enabled = true;
        else
          $enabled = !empty($info['enabled']);

        $mod = array(
          'title' => empty($info['name']['ru']) ? 'n/a' : $info['name']['ru'],
          'enabled' => $enabled,
          'docurl' => empty($info['docurl']) ? null : $info['docurl'],
          );

        if (!empty($mod['enabled']) and !empty($info['interface']['iModuleConfig']))
          $mod['config'] = true;

        $data['module_list'][$info['group']][$name] = $mod;
      }

      break;

    case 'module-edit':
      try {
        $node = Node::load(array('class' => 'moduleinfo', 'name' => $this->options['edit']));

        if (is_array($tmp = $node->config))
          foreach ($tmp as $k => $v)
            $data['config_'. $k] = $v;
      } catch (ObjectNotFoundException $e) {
        $data = array();
      }

      break;
    }

    return $data;
  }

  public function formProcess($id, array $data)
  {
    switch ($id) {
    case 'module-list':
      $all = array();

      // Собираем полный список модулей.
      foreach (Node::find(array('class' => 'moduleinfo')) as $mi)
        $all[$mi->name] = $mi;

      // Включаем отмеченные.
      if (!empty($data['module_list']) and is_array($data['module_list'])) {
        foreach ($data['module_list'] as $k => $v) {
          if (!empty($v)) {
            if (!array_key_exists($k, $all)) {
              $all[$k] = Node::create('moduleinfo', array(
                'name' => $k,
                'published' => true,
                ));
              $all[$k]->save();
            }
            $all[$k]->publish();
          }
        }
      }

      // Выключаем неотмеченные.  При этом выключатся и «ядерные», но это не страшно.
      foreach ($all as $k => $v)
        if (empty($data['module_list'][$k]))
          $v->unpublish();

      mcms::invoke('iModuleConfig', 'hookPostInstall');
      break;

    case 'module-edit':
      $config = array();

      foreach ($data as $k => $v)
        if (substr($k, 0, 7) == 'config_')
          $config[substr($k, 7)] = $v;

      try {
        $node = Node::load($f = array('class' => 'moduleinfo', 'name' => $this->options['edit']));
      } catch (ObjectNotFoundException $e) {
        $node = Node::create('moduleinfo', array(
          'name' => $this->options['edit'],
          'published' => true,
          ));
      }

      $node->config = $config;
      $node->save();

      break;
    }
  }

  private function getModuleGroups()
  {
    static $groups = null;

    if (null === $groups) {
      $groups = array();
      $map = mcms::getModuleMap();

      foreach ($map['modules'] as $v)
        if (!in_array($v['group'], $groups))
          $groups[] = $v['group'];
    }

    sort($groups);

    return $groups;
  }
};
