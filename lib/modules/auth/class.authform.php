<?php

class AuthForm
{
  /**
   * Возвращает форму авторизации.
   * 
   * @param Context $ctx 
   * @return string
   * @mcms_message ru.molinos.cms.auth.form
   */
  public static function getXML(Context $ctx)
  {
    if (!($providers = $ctx->registry->poll('ru.molinos.cms.auth.enum')))
      return null;

    $form = self::getForm($providers)->getXML(Control::data());

    return $form;
  }

  private static function getForm(array $list)
  {
    $types = array();

    $form = new Form(array(
      'title' => t('Требуется авторизация'),
      ));

    $class = '';
    foreach ($list as $provider) {
      list($name, $title, $schema) = $provider['result'];

      $types[$name] = $title;

      $tab = $form->addControl(new FieldSetControl(array(
        'name' => $name,
        'id' => $name,
        'label' => $title,
        'class' => 'authmode' . $class,
        'mode' => $name,
        )));

      foreach ($schema as $k => $v) {
        $v->group = $title;
        $v->value = $name . '_' . $k;
        $tab->addControl($v);
      }

      $class = ' hidden';
    }

    $form->addControl(new SubmitControl(array(
      'text' => t('Продолжить'),
      )));

    if (1 == count($types)) {
      list($type) = array_keys($types);
      $form->addControl(new HiddenControl(array(
        'value' => 'auth_type',
        'default' => $type,
        )));
    } else {
      $form->addControl(new EnumRadioControl(array(
        'value' => 'auth_type',
        'label' => t('Режим входа'),
        'required' => true,
        'options' => $types,
        )));
    }

    $form->action = 'auth/login.rpc?destination=CURRENT';

    return $form;
  }

  public static function on_get_groups(Context $ctx)
  {
    $nodes = Node::find(array(
      'class' => 'group',
      'deleted' => 0,
      ), $ctx->db);

    $counts = $ctx->db->getResultsKV('id', 'count', "SELECT tid AS id, COUNT(*) AS count FROM node__rel r INNER JOIN node g ON g.id = r.tid INNER JOIN node u ON u.id = r.nid WHERE g.deleted = 0 AND u.deleted = 0 AND g.class = 'group' AND u.class = 'user' GROUP BY tid");

    $html = '';
    foreach ($nodes as $node) {
      $count = isset($counts[$node->id])
        ? $counts[$node->id]
        : 0;
      $html .= html::em('node', array(
        'id' => $node->id,
        'name' => $node->getName(),
        'created' => $node->created,
        'users' => $count,
        'editable' => $node->checkPermission('u'),
        'published' => true,
        ));
    }

    $html = html::wrap('data', $html);

    return html::em('content', array(
      'name' => 'list',
      'preset' => 'groups',
      'title' => t('Группы пользователей'),
      'nosearch' => true,
      'create' => 'admin/create/group',
      ), $html);
  }

  /**
   * Возвращает маршрут для вывода формы авторизации.
   * @mcms_message ru.molinos.cms.route.poll
   */
  public static function on_route_poll()
  {
    return array(
      'GET//login' => array(
        'call' => __CLASS__ . '::on_get_login_form'
        ),
      );
  }

  /**
   * Вывод формы авторизации.
   */
  public static function on_get_login_form(Context $ctx)
  {
    if ($ctx->user->id and !$ctx->get('stay'))
      return $ctx->getRedirect();

    if (class_exists('APIStream'))
      APIStream::init($ctx);

    $handler = array(
      'theme' => $ctx->config->get('modules/auth/login_theme'),
      );

    $content = '';
    foreach ((array)$ctx->registry->poll('ru.molinos.cms.page.head', array($ctx, $handler, null), true) as $block)
      if (!empty($block['result']))
        $content .= $block['result'];

    $content .= self::getXML($ctx);

    $xml = html::em('page', array(
      'status' => 401,
      'base' => $ctx->url()->getBase($ctx),
      'host' => MCMS_HOST_NAME,
      'prefix' => os::webpath(MCMS_SITE_FOLDER, 'themes'),
      'back' => urlencode(MCMS_REQUEST_URI),
      'next' => $ctx->get('destination'),
      'api' => APIStream::getPrefix(),
      'query' => $ctx->query(),
      ), $content);

    if (file_exists($xsl = os::path(MCMS_SITE_FOLDER, 'themes', $handler['theme'], 'templates', 'login.xsl'))) {
      try {
        return xslt::transform($xml, $xsl);
      } catch (Exception $e) { }
    }

    return xslt::transform($xml, 'lib/modules/auth/xsl/login.xsl');
  }
}
