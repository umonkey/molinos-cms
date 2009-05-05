<?php

class WidgetAdmin
{
  const listurl = 'admin/structure/widgets';

  public static function on_get_list(Context $ctx)
  {
    $list = '';
    $types = Widget::list_types($ctx);
    $orphan = self::getOrphanWidgets($ctx);

    foreach (Widget::loadWidgets($ctx) as $k => $v) {
      $v['name'] = $k;

      if (isset($types[strtolower($v['classname'])]))
        $inside = html::em('info', $types[strtolower($v['classname'])]);
      else
        $inside = null;

      if (in_array($k, $orphan))
        $v['orphan'] = true;

      $list .= html::em('widget', $v, $inside);
    }

    return html::wrap('content', $list, array(
      'name' => 'widgets',
      'base' => self::listurl,
      'title' => t('Виджеты'),
      ));
  }

  public static function on_get_add(Context $ctx)
  {
    if (!$ctx->get('type'))
      return self::on_get_add_new($ctx);

    if (!array_key_exists($type = $ctx->get('type'), $types = Widget::list_types($ctx)))
      throw new PageNotFoundException();

    $form = self::get_schema($ctx, $type)->getForm(array(
      'action' => self::listurl . '/save?type=' . urlencode($type),
      'title' => t('Новый виджет «%type»', array(
        '%type' => mb_strtolower($types[$type]['name']),
        )),
      ));
    $form->addControl(new SubmitControl(array(
      'text' => t('Создать'),
      )));

    return html::wrap('content', $form->getXML(Control::data()), array(
      'name' => 'edit',
      'title' => t('Добавление виджета'),
      ));
  }

  public static function on_get_add_new(Context $ctx)
  {
    $types = array();
    foreach (Widget::list_types($ctx) as $k => $v)
      $types[$k] = $v['name'];
    asort($types);

    $form = new Form(array(
      'method' => 'get',
      'action' => self::listurl . '/add',
      'title' => t('Добавление виджета'),
      ));
    $form->addControl(new EnumControl(array(
      'label' => t('Тип виджета'),
      'required' => true,
      'options' => $types,
      'default' => 'listwidget',
      'value' => 'type',
      )));
    $form->addControl(new SubmitControl(array(
      'text' => t('Продолжить'),
      )));

    $html = $form->getXML(Control::data(array(
      'type' => 'listwidget',
      )));

    return html::wrap('content', $html, array(
      'name' => 'edit',
      'title' => t('Новый виджет'),
      ));
  }

  public static function on_get_edit(Context $ctx)
  {
    $list = Widget::loadWidgets($ctx);

    if (!array_key_exists($name = $ctx->get('name'), $list))
      throw new PageNotFoundException();

    if (empty($list[$name]['classname']))
      throw new RuntimeException(t('Не указан тип виджета %name.', array(
        '%name' => $name,
        )));

    if (!class_exists($list[$name]['classname']))
      throw new RuntimeException(t('Используемый виджетом «%name» класс (%class) отсутствует.', array(
        '%name' => $name,
        '%class' => $list[$name]['classname'],
        )));

    $info = $list[$name];
    $info['name'] = $name;
    $info['pages'] = self::get_pages_for($name);

    $form = self::get_form($ctx, $info['classname'], $name);
    $html = $form->getXML(Control::data($info));

    return html::wrap('content', $html, array(
      'name' => 'edit',
      ));
  }

  public static function on_get_delete(Context $ctx)
  {
    $list = '';
    foreach ((array)$ctx->get('delete') as $name)
      $list .= html::em('widget', array(
        'name' => $name,
        ));

    if (empty($list))
      throw new PageNotFoundException();

    return html::wrap('content', $list, array(
      'name' => 'delete-widgets',
      'title' => t('Виджеты'),
      'base' => self::listurl,
      ));
  }

  public static function on_post_save(Context $ctx)
  {
    self::process($ctx, $ctx->get('type'));
    return $ctx->getRedirect(self::listurl);
  }

  public static function on_post_edit(Context $ctx)
  {
    self::process($ctx);
    return $ctx->getRedirect(self::listurl);
  }

  public static function on_post_delete(Context $ctx)
  {
    if ($ctx->post('confirm')) {
      $delete = (array)$ctx->post('delete');
      $widgets = Widget::loadWidgets($ctx);

      foreach ((array)$ctx->post('delete') as $k) {
        if (array_key_exists($k, $widgets))
          unset($widgets[$k]);
        else
          throw new PageNotFoundException();
      }

      Widget::save($widgets);
      BaseRoute::save(self::remove_dead_widgets(BaseRoute::load(), array_keys($widgets)));
    }

    return $ctx->getRedirect(self::listurl);
  }

  private static function get_pages()
  {
    $result = array();
    foreach (BaseRoute::load() as $k => $v) {
      $name = $key = substr($k, 4);
      if (!empty($v['title']))
        $name .= ' (' . $v['title'] . ')';
      $result[$key] = $name;
    }
    return $result;
  }

  private static function get_schema(Context $ctx, $type)
  {
    if (!class_exists($type))
      throw new PageNotFoundException();

    $schema = call_user_func(array($type, 'getConfigOptions'), $ctx);

    $weight = 10;
    foreach ($schema as $k => $v) {
      $schema[$k]['weight'] = $weight++;
      if (empty($v['group']))
        $schema[$k]['group'] = t('Настройки');
    }

    $schema['name'] = array(
      'type' => 'TextLineControl',
      'label' => t('Внутреннее имя'),
      'description' => t('Может содержать только маленькие латинские буквы и цифры.'),
      'weight' => 1,
      'required' => true,
      );
    $schema['title'] = array(
      'type' => 'TextLineControl',
      'label' => t('Заголовок'),
      'description' => t('Помогает ориентироваться в виджетах, может использоваться шаблонами.'),
      'weight' => 2,
      );
    $schema['disabled'] = array(
      'type' => 'BoolControl',
      'label' => t('Отключить виджет'),
      'weight' => 3,
      );

    $schema['pages'] = array(
      'type' => 'SetControl',
      'group' => t('Страницы'),
      'options' => self::get_pages(),
      );

    return new Schema($schema);
  }

  private static function get_form(Context $ctx, $type, $name = null)
  {
    $schema = self::get_schema($ctx, $type);

    if (null !== $name)
      $schema['name']->readonly = true;

    $form = $schema->getForm(array(
      'action' => self::listurl . '/edit',
      'method' => 'post',
      ));

    if (null !== $name)
      $form->title = t('Настройка виджета «%name»', array('%name' => $name));
    else
      $form->title = t('Добавление виджета');

    $form->addControl(new SubmitControl());

    return $form;
  }

  private static function process(Context $ctx, $type = null)
  {
    if (null === $type) {
      $list = Widget::loadWidgets($ctx);

      if (!array_key_exists($name = $ctx->post('name'), $list))
        throw new PageNotFoundException();

      $type = $list[$name]['classname'];
    }

    $schema = self::get_schema($ctx, $type);

    foreach ($data = $schema->getFormData($ctx)->dump() as $k => $v)
      if (empty($v))
        unset($data[$k]);

    $name = $data['name'];
    unset($data['name']);

    $data['classname'] = $type;

    self::update($ctx, $name, $data);
  }

  private static function update(Context $ctx, $name, array $info)
  {
    $widgets = Widget::loadWidgets($ctx);

    if (isset($info['pages'])) {
      $pages = $info['pages'];
      unset($info['pages']);
    } else {
      $pages = null;
    }

    // Список виджетов без текущего.
    $reduced = array_diff(array_keys($widgets), array($name));

    // Удаляем лишнее из списка маршрутов.
    $routes = self::remove_dead_widgets(BaseRoute::load(), $reduced);

    // Цепляем текущий виджет к выбранным страницам.
    if (null !== $pages) {
      foreach ($pages as $page) {
        if (!array_key_exists($route = 'GET/' . $page, $routes))
          throw new PageNotFoundException(t('Страница %page перестала существовать.', array(
            '%page' => $page,
            )));

        $pwlist = isset($routes[$route]['widgets'])
          ? explode(',', $routes[$route]['widgets'])
          : array();
        $pwlist[] = $name;

        sort($pwlist);
        $routes[$route]['widgets'] = implode(',', array_unique($pwlist));
      }
    }

    $widgets[$name] = $info;

    Widget::save($widgets);
    BaseRoute::save($routes);
  }

  private static function get_pages_for($name)
  {
    $pages = array();

    foreach (BaseRoute::load() as $k => $v) {
      if (isset($v['widgets']) and in_array($name, explode(',', $v['widgets'])))
        $pages[] = substr($k, 4);
    }

    return $pages;
  }

  private static function remove_dead_widgets(array $route, array $widget_names)
  {
    foreach ($route as $k => $v)
      if (isset($v['widgets']))
        $route[$k]['widgets'] = implode(',', array_intersect(explode(',', $v['widgets']), $widget_names));

    return $route;
  }

  private static function getOrphanWidgets(Context $ctx)
  {
    $used = array();
    $route = BaseRoute::load();

    foreach (BaseRoute::load() as $route) {
      if (!empty($route['widgets'])) {
        foreach (explode(',', $route['widgets']) as $w)
          if (!in_array($w, $used))
            $used[] = $w;
      }
    }

    return array_diff(array_keys(Widget::loadWidgets($ctx)), $used);
  }
}
