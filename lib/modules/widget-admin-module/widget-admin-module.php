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
    $options['edit'] = $ctx->get('edit');
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
        'intro' => t('Будьте осторожны, отключая активные модули на работающем сайте: это может нарушить его работоспособность.  Дополнительную информацию о конкретном <a href=\'@modurl\'>модуле</a> можно найти в документаци (по ссылке с имени модуля), или в <a href=\'@listurl\'>общем списке модулей</a>.', array(
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
      $map = bebop_get_module_map();

      if (empty($map[$this->options['edit']]['interface']['iModuleConfig']))
        throw new PageNotFoundException();

      $rator = $map[$this->options['edit']]['interface']['iModuleConfig'][0];

      if (!class_exists($rator))
        throw new InvalidArgumentException(t('Настройка модуля %name невозможна: класс %class не загружен.', array(
          '%name' => $this->options['edit'],
          '%class' => $rator,
          )));

      if (null !== ($form = call_user_func(array($rator, 'formGetModuleConfig')))) {
        $form->intro = t('Подробности об этом модуле можно найти в <a href=\'@url\'>документации</a>.', array('@url' => 'http://code.google.com/p/molinos-cms/wiki/mod_'. str_replace('-', '_', $this->options['edit'])));
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

      foreach ($mmap = bebop_get_module_map() as $name => $info) {
        $mod = array(
          'title' => $info['name']['ru'],
          'enabled' => ($info['group'] == 'core' or in_array($name, $enabled)) ? true : false,
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

      foreach (bebop_get_module_map() as $v)
        if (!in_array($v['group'], $groups))
          $groups[] = $v['group'];
    }

    return $groups;
  }
};

class ModuleListControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список модулей'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array());
  }

  public function getHTML(array $data)
  {
    if (!empty($data[$this->value][$this->group])) {
      $list = array();
      $rows = array();

      foreach ($data[$this->value][$this->group] as $k => $v) {
        $checked = !empty($v['enabled']);
        $disabled = ($this->group == 'core');

        $row = '<td>'. mcms::html('input', array(
          'type' => 'checkbox',
          'value' => 1,
          'name' => $this->value .'['. $k .']',
          'checked' => $checked ? 'checked' : null,
          'disabled' => $disabled ? 'disabled' : null,
          )) .'</td>';
        $row .= '<td>'. mcms::html('a', array(
          'href' => 'http://code.google.com/p/molinos-cms/wiki/mod_'. str_replace('-', '_', $k),
          'target' => '_blank',
          'style' => 'white-space: nowrap',
          ), $k) .'</td>';
        $row .= '<td>'. mcms_plain($v['title']) .'</td>';

        if (!empty($v['config']))
          $row .= '<td>'. mcms::html('a', array(
            'href' => '/admin/builder/modules/?BebopModules.edit='. $k .'&destination='. urlencode($_SERVER['REQUEST_URI']),
            ), t('настроить')) .'</td>';
        else
          $row .= '<td>&nbsp;</td>';

        $rows[] = '<tr>'. $row .'</tr>';
      }

      if  (!empty($rows)) {
        $output = '<table class=\'highlight\'>';
        $output .= '<tr><th>&nbsp;</th><th>Имя</th><th>Описание</th><th>Действия</th></tr>';
        $output .= join('', $rows);
        $output .= '</table>';

        return $this->wrapHTML($output);
      }
    }
  }
};
