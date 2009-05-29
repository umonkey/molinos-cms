<?php

class RouteAdmin
{
  const listurl = 'admin/structure/routes';

  public static function on_get_list(Context $ctx)
  {
    $routes = '';

    if (is_array($ini = BaseRoute::load($ctx))) {
      ksort($ini);

      foreach ($ini as $k => $v) {
        $v['name'] = $k;
        $v['id'] = urlencode($v['name']);

        if (false === strpos($v['name'], '*')) {
          $url = $v['name'];
          if (0 === strpos($url, 'localhost/'))
            $url = MCMS_HOST_NAME . substr($url, 9);
          $v['url'] = 'http://' . $url;
        }

        $routes .= html::em('route', $v);
      }
    }

    return html::em('content', array(
      'name' => 'routes',
      'title' => t('Маршруты'),
      ), $routes);
  }

  public static function on_get_add(Context $ctx)
  {
    $form = self::getSchema($ctx)->getForm(array(
      'title' => t('Добавление маршрута'),
      'action' => self::listurl . '/save',
      ));
    $form->addControl(new SubmitControl(array(
      'text' => t('Добавить'),
      'weight' => 1000,
      )));

    return html::em('content', array(
      'name' => 'edit',
      ), $form->getXML(Control::data()));
  }

  public static function on_get_edit(Context $ctx)
  {
    if (!array_key_exists($key = $ctx->get('id'), $map = BaseRoute::load($ctx)))
      throw new PageNotFoundException();

    $form = self::getSchema($ctx)->getForm(array(
      'title' => t('Настройка маршрута'),
      'action' => self::listurl . '/save?id=' . urlencode($ctx->get('id')),
      ));
    $form->addControl(new SubmitControl(array(
      'text' => t('Сохранить'),
      'weight' => 1000,
      )));

    return html::em('content', array(
      'name' => 'edit',
      ), $form->getXML(self::getFormData($key, $map[$key])));
  }

  public static function on_get_delete(Context $ctx)
  {
    $pages = '';

    foreach ((array)$ctx->get('check') as $id)
      $pages .= html::em('page', urldecode($id));

    if (empty($pages))
      throw new ForbiddenException(t('Не выбраны пути для удаления.'));

    return html::wrap('content', $pages, array(
      'name' => 'route-delete',
      ));
  }

  public static function on_post_save(Context $ctx)
  {
    $data = self::getSchema($ctx)->getFormData($ctx)->dump();

    $key = rtrim($data['host'] . '/' . $data['path'], '/');
    if (null === $data['path'])
      $key .= '/';
    unset($data['host']);
    unset($data['path']);

    if (!empty($data['widgets'])) {
      sort($data['widgets']);
      $data['widgets'] = $data['widgets'];
    }

    $data['call'] = 'BaseRoute::serve';

    $map = BaseRoute::load($ctx);

    if (null !== ($old = $ctx->get('id'))) {
      if (array_key_exists($old, $map))
        unset($map[$old]);
      else
        ; // throw new PageNotFoundException(t('Редактируемый путь перестал существовать.'));
    }

    $map[$key] = $data;
    BaseRoute::save($map);

    return $ctx->getRedirect(self::listurl);
  }

  public static function on_post_delete(Context $ctx)
  {
    $map = BaseRoute::load($ctx);

    foreach ((array)$ctx->post('delete') as $path) {
      if (array_key_exists($key = $path, $map))
        unset($map[$key]);
      else
        throw new PageNotFoundException(t('Путь <tt>%path</tt> не найден.', array(
          '%path' => $path,
          )));
    }

    BaseRoute::save($map);

    return $ctx->getRedirect(self::listurl);
  }

  /**
   * Возвращает схему для редактирования маршрута.
   */
  private static function getSchema(Context $ctx)
  {
    $widgets = array();
    foreach (Widget::loadWidgets($ctx) as $k => $v)
      $widgets[$k] = sprintf("%s (%s)", $v['title'], $v['classname']);
    asort($widgets);

    $themes = array();
    foreach (glob(os::path(MCMS_SITE_FOLDER, 'themes', '*'), GLOB_ONLYDIR) as $dir)
      $themes[] = basename($dir);

    return new Schema(array(
      'host' => array(
        'type' => 'TextLineControl',
        'label' => t('Домен'),
        'weight' => 1,
        'required' => true,
        'description' => t('Имя «localhost» используется как домен по умолчанию.'),
        'group' => t('Адрес'),
        'default' => 'localhost',
        ),
      'path' => array(
        'type' => 'TextLineControl',
        'label' => t('Путь'),
        'weight' => 2,
        'description' => t('Звёздочка используется для передачи произвольного параметра. Она может быть только одна. Пустой путь означает главную страницу домена.'),
        'group' => t('Адрес'),
        ),
      'title' => array(
        'type' => 'TextLineControl',
        'label' => t('Заголовок'),
        'weight' => 3,
        'group' => t('Внешний вид'),
        ),
      'language' => array(
        'type' => 'TextLineControl',
        'label' => t('Язык по умолчанию'),
        'weight' => 4,
        'required' => true,
        'default' => 'ru',
        'group' => t('Внешний вид'),
        ),
      'content_type' => array(
        'type' => 'TextLineControl',
        'label' => t('Тип содержимого'),
        'required' => true,
        'default' => 'text/html',
        'group' => t('Внешний вид'),
        'weight' => 5,
        'description' => t('Обычно используют text/html, реже — text/xml.'),
        ),
      'theme' => array(
        'type' => 'TextLineControl',
        'label' => t('Шкура'),
        'group' => t('Внешний вид'),
        'weight' => 6,
        'description' => t('Доступные шкуры: %list.', array(
          '%list' => join(', ', $themes),
          )),
        'default' => empty($themes) ? null : $themes[0],
        ),
      'optional' => array(
        'type' => 'BoolControl',
        'label' => t('Параметры не обязательны'),
        'group' => t('Параметризация'),
        'weight' => 7,
        ),
      'defaultsection' => array(
        'type' => 'EnumControl',
        'label' => t('Раздел по умолчанию'),
        'options' => Node::getSortedList('tag'),
        'group' => t('Параметризация'),
        'weight' => 8,
        ),
      'widgets' => array(
        'type' => 'SetControl',
        'options' => $widgets,
        'group' => t('Виджеты'),
        'label' => t('Виджеты'),
        'weight' => 9,
        ),
      'cache' => array(
        'type' => 'NumberControl',
        'group' => t('Производительность'),
        'weight' => 10,
        'label' => t('Время жизни страницы в кэше'),
        'description' => t('Указывается в секундах, по умолчанию кэш отключен.'),
        ),
      ));
  }

  private static function getFormData($key, array $data)
  {
    list($data['host'], $data['path']) = explode('/', $key, 2);
    return Control::data($data);
  }
}
