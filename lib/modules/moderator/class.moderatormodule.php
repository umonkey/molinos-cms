<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class ModeratorModule implements iModuleConfig, iNodeHook
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array(
      'title' => t('Модерирование (настройка)'),
      ));

    $form->addControl(new EmailControl(array(
      'value' => 'config_from',
      'label' => t('Отправитель сообщений'),
      'description' => t('С этого адреса будут приходить сообщения на тему модерации.'),
      )));

    $form->addControl(new EmailControl(array(
      'value' => 'config_super',
      'label' => t('Супервизоры'),
      'description' => t('Список почтовых адресов, на которые всегда приходят все сообщения о модерации, независимо от привязки пользователя к выпускающему редактору.'),
      )));

    $form->addControl(new TextAreaControl(array(
      'value' => 'config_map',
      'label' => t('Получатели'),
      'description' => t('Каждая строка содержит тип документа иполучателя в форате: type = email.'),
      )));

    $types = array();

    foreach (Node::find(array('class' => 'type')) as $t)
      $types[$t->name] = $t->title;

    $form->addControl(new SetControl(array(
      'value' => 'config_skip_types',
      'label' => t('Немодерируемые документы'),
      'options' => $types,
      )));

    return $form;
  }

  public static function hookPostInstall()
  {
    // Добавляем группы «Выпускающие редакторы»
    if (!Node::count(array('class' => 'group', 'login' => 'Publishers'))) {
      $tmp = Node::create('group', array(
        'name' => t('Выпускающие редакторы'),
        'login' => 'Publishers',
        'description' => t('Пользователи из этой группы могут публиковать документы и изменять опубликованные, изменения других пользователей будут проходить через модератора.'),
        ));
      $tmp->save();
    }

    // Добавляем свойство пользователя.
    $spec = array(
      'label' => t('Выпускающий редактор'),
      'type' => 'NodeLinkControl',
      'dictionary' => 'user',
      'required' => false,
      'description' => t('Все изменения, производимые этим пользователем и потенциально отражаемые на сайте, будут требовать одобрения указанного здесь модератора.  Вводить следует логин (внутреннее имя) пользователя с ролью «Выпускающий редактор».'),
      );

    $type = Node::load(array('class' => 'type', 'name' => 'user'));
    if ($type->fieldSet('publisher', $spec))
      $type->save();
  }

  public static function hookNodeUpdate(Node $node, $op)
  {
    $config = mcms::modconf('moderator');

    if (!empty($config['skip_types']) and in_array($node->class, $config['skip_types']))
      return;

    // Некоторые известные типы не модерируем.
    if (in_array($node->class, array('domain', 'widget', 'user', 'group', 'subscription', 'cronstats')))
      return;

    // Пользователь сам себе публикатор.
    if ($node->checkPermission('p'))
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
      mcms::db()->exec("UPDATE `node` SET `published` = 0 WHERE `id` = :id", array(':id' => $node->id));
      break;
    case 'unpublish':
      $prepend = 'Пользователь %user просит <strong>скрыть</strong> документ типа «%type»:';
      mcms::db()->exec("UPDATE `node` SET `published` = 1 WHERE `id` = :id", array(':id' => $node->id));
      break;
    default:
      return;
    }

    $body = '<p>'. t($prepend, array(
      '%user' => mcms::user()->name,
      '%type' => isset($schema['title']) ? $schema['title'] : $node->class,
      )) .'</p>'. self::getNodeBody($node);

    if (count($to = self::getRecipients($node))) {
      $rc = BebopMimeMail::send(
        null,
        $to,
        t('Редакторская активность на сайте %site',
          array('%site' => $_SERVER['HTTP_HOST'])),
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
        elseif ($v instanceof AttachmentControl)
          $value = l('?q=attachment.rpc&fid=' . $node->$k, 'скачать файл');
        elseif ($v instanceof EmailControl)
          $value = l('mailto:' . $node->$k, $node->$k);
        else
          $value = mcms_plain($node->$k);

        if (null !== $value) {
          $body .= '<dt>'. mcms_plain($v->label) .':</dt>';
          $body .= '<dd>'. $value .'</dd>';
        }
      }
    }

    $body .= '</dl>';

    $body .= '<p>' . l('?q=admin/content/edit/' . $node->id . '&destination=admin', t('Открыть в админке')) . '</p>';

    return $body;
  }

  private static function getRecipients(Node $node)
  {
    $config = mcms::modconf('moderator');
    $list = isset($config['super']) ? preg_split('/, */', $config['super']) : array();

    if (!empty($config['map'])) {
      $map = self::parseEmailMap($config['map']);
      if (isset($map[$node->class]))
        $list[] = $map[$node->class];
    }

    if (mcms::user()->id) {
      try {
        $tmp = Node::load(array('class' => 'user', 'id' => mcms::user()->id));

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

  private static function parseEmailMap($map)
  {
    $result = array();

    foreach (explode("\n", $map) as $line)
      if (2 == count($parts = explode('=', $line)))
        $result[trim($parts[0])] = trim($parts[1]);

    return $result;
  }
};
