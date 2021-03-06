<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModeratorModule
{
  /**
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function hookNodeUpdate(Context $ctx, Node $node, $op)
  {
    $config = $ctx->config->get('modules/moderator');

    if (!empty($config['skip_types']) and in_array($node->class, $config['skip_types']))
      return;

    // Некоторые известные типы не модерируем.
    if (in_array($node->class, array('domain', 'widget', 'user', 'group', 'field', 'cronstats')))
      return;

    // Пользователь сам себе публикатор.
    if ($ctx->user->hasAccess(ACL::PUBLISH, $node->class))
      return;

    switch ($op) {
    case 'update':
      $prepend = 'Пользователь %user <strong>изменил</strong> документ типа «%type»:';
      break;
    case 'create':
      $prepend = 'Пользователь %user <strong>создал</strong> документ типа «%type»:';
      break;
    case 'delete':
      $prepend = 'Пользователь %user <strong>удалил</strong> (в корзину) документ типа «%type»:';
      break;
    case 'restore':
      $prepend = 'Пользователь %user <strong>восстановил из корзины</strong> документ типа «%type»:';
      break;
    case 'publish':
      $prepend = 'Пользователь %user просит <strong>опубликовать</strong> документ типа «%type»:';
      $node->onSave("UPDATE `node` SET `published` = 0 WHERE `id` = %ID%");
      break;
    case 'unpublish':
      $prepend = 'Пользователь %user просит <strong>скрыть</strong> документ типа «%type»:';
      $node->onSave("UPDATE `node` SET `published` = 1 WHERE `id` = %ID%");
      break;
    default:
      return;
    }

    $user = $ctx->user->getNode();

    $body = '<p>'. t($prepend, array(
      '%user' => $user ? $user->getName() : null,
      '%type' => isset($schema['title']) ? $schema['title'] : $node->class,
      )) .'</p>'. self::getNodeBody($node);

    if (count($to = self::getRecipients($ctx))) {
      $rc = BebopMimeMail::send(
        null,
        $to,
        t('Редакторская активность на сайте %site',
          array('%site' => MCMS_HOST_NAME)),
        $body
        );
    }
  }

  private static function getNodeBody(Node $node)
  {
    $body = '<dl>';

    $schema = $node->getSchema();

    foreach ($schema as $k => $v) {
      if (isset($node->$k)) {
        if ($v instanceof PasswordControl)
          $value = null;
        elseif ($v instanceof EmailControl)
          $value = html::em('a', array(
            'href' => 'mailto:' . $node->$k,
            ), $node->$k);
        elseif (!is_object($node->$k))
          $value = $node->$k;

        if (null !== $value) {
          $body .= '<dt>'. html::plain($v->label) .':</dt>';
          $body .= '<dd>'. $value .'</dd>';
        }
      }
    }

    $body .= '</dl>';

    $body .= '<p>' . html::link('admin/node/' . $node->id . '?destination=admin', t('Открыть в админке')) . '</p>';

    return $body;
  }

  private static function getRecipients(Context $ctx)
  {
    $config = $ctx->config->get('modules/moderator');
    $list = isset($config['super']) ? preg_split('/, */', $config['super']) : array();

    if (Context::last()->user->id) {
      try {
        $tmp = Node::load(array('class' => 'user', 'id' => Context::last()->user->id));

        if (!empty($tmp->publisher) and is_numeric($tmp->publisher)) {
          $tmp = Node::load(array('class' => 'user', 'id' => $tmp->publisher));
          if (!empty($tmp->email))
            $list[] = $tmp->email;
        }
      } catch (ObjectNotFoundException $e) {
      }
    }

    // Добавляем в список адреса, указанные в свойствах домена.
    if ($ctx = Context::last())
      if (isset($ctx->moderatoremail))
        $list += preg_split('/, */', $ctx->moderatoremail);

    return array_unique($list);
  }

  /**
   * @mcms_message ru.molinos.cms.module.settings.moderator
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'from' => array(
        'type' => 'EmailControl',
        'label' => t('Отправитель сообщений'),
        'description' => t('С этого адреса будут приходить сообщения на тему модерации.'),
        'default' => 'no-reply@' . MCMS_HOST_NAME,
        ),
      'super' => array(
        'type' => 'EmailControl',
        'label' => t('Супервизоры'),
        'description' => t('Список почтовых адресов, на которые всегда приходят все сообщения о модерации, независимо от привязки пользователя к выпускающему редактору.'),
        ),
      'skip_types' => array(
        'type' => 'SetControl',
        'label' => t('Немодерируемые документы'),
        'options' => Node::getSortedList('type', 'title', 'name'),
        ),
      ));
  }
};
