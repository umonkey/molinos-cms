<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SubscriptionAdminWidget extends Widget implements iAdminWidget, iScheduler, iDashboard
{
  public function __construct(Node $node)
  {
    parent::__construct($node);

    $this->groups = array(
      'Subscription Managers',
      );
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Управление подпиской',
      'description' => 'Управление почтовой подпиской: какие разделы задействованы, итд.',
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['mode'] = $ctx->get('mode', 'default');
    $options['download'] = $ctx->get('download');

    if ($options['mode'] == 'download') {
      if (null === ($options['format'] = $ctx->get('format')) or !in_array($options['format'], array('xml')))
        throw new PageNotFoundException();
    }

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['mode']), $options);
  }

  // Показываем дефолтную страницу с активными разделами.
  protected function onGetDefault(array $options)
  {
    return array(
      'html' => parent::formRender('subscription-admin-form', $this->formGetData()),
      );
  }

  protected function onGetDownload(array $options)
  {
    $output = '';
    $pdo = mcms::db();

    switch ($options['format']) {
    case 'xml':
      $emails = $pdo->getResultsV("email", "SELECT `email` FROM `node__subscription_emails` WHERE `active` = 1 ORDER BY `email`");
      $sections = $pdo->getResults("SELECT `e`.`email`, `n`.`id`, `r`.`name` FROM `node__subscription_tags` `t` INNER JOIN `node__subscription_emails` `e` ON `e`.`id` = `t`.`sid` INNER JOIN `node` `n` ON `n`.`id` = `t`.`tid` INNER JOIN `node__rev` `r` ON `r`.`rid` = `n`.`rid` ORDER BY `e`.`email`, `n`.`id`");

      $output .= '<?xml version=\'1.0\'?>';
      $output .= '<subscriptions>';

      foreach ($emails as $email) {
        $items = array();

        foreach ($sections as $sec) {
          if ($sec['email'] == $email) {
            $items[] = "<section id='{$sec['id']}' name='". mcms_plain($sec['name']) ."' />";
          }
        }

        if (!empty($items))
          $output .= "<subscriber email='{$email}'>". join('', $items) ."</subscriber>";
      }

      $output .= '</subscriptions>';

      header('Content-Type: text/xml; charset=utf-8');
      break;
    }

    header('Content-Length: '. strlen($output));
    die($output);
  }

  public function onPost(array $options, array $post, array $files)
  {
    foreach (Node::find(array('class' => 'type')) as $t) {
      if (!empty($post['types']) and is_array($post['types']) and in_array($t->name, $post['types']))
        $sendmail = 1;
      else
        $sendmail = 0;

      if ($sendmail != $t->sendmail) {
        $t->sendmail = $sendmail;
        $t->save();
      }
    }

    foreach (Node::find(array('class' => 'tag')) as $t) {
      if (!empty($post['tags']) and is_array($post['tags']) and in_array($t->id, $post['tags']))
        $subscribe = 1;
      else
        $subscribe = 0;

      if ($subscribe != $t->bebop_subscribe) {
        $t->bebop_subscribe = $subscribe;
        $t->save();
      }
    }
  }

  // Выполнение периодических задач.
  public static function taskRun()
  {
    $pdo = mcms::db();

    // Отправка почты занимает много времени.
    if (!ini_get('safe_mode'))
      set_time_limit(0);

    $types = array();

    foreach (Node::find(array('class' => 'type', 'sendmail' => 1)) as $type)
      $types[] = $type->name;

    if (empty($types)) {
      printf("  subscription: no types.\n");
      return;
    }

    // Обрабатываем активных пользователей.
    foreach ($pdo->getResults("SELECT `id`, `email`, `digest`, `last` FROM `node__subscription_emails` WHERE `active` = 1 ORDER BY `email`") as $row) {
      $last = null;

      // Получаем список разделов, на которые пользователь подписан.
      $tags = $pdo->getResultsV("tid", "SELECT `tid` FROM `node__subscription_tags` WHERE `sid` = :sid", array(':sid' => $row['id']));

      if (empty($tags))
        continue;

      // Получаем список новых документов для пользователя.
      $nids = $pdo->getResultsV("id", "SELECT `n`.`id` FROM `node` `n` "
        ."WHERE `n`.`class` IN ('". join("', '", $types) ."') AND `n`.`id` IN "
        ."(SELECT `nid` FROM `node__rel` WHERE `tid` IN (". join(", ", $tags) .")) "
        ."AND `n`.`id` > :last "
        ."AND `n`.`id` IN (PERMCHECK:R) "
        ."ORDER BY `n`.`id`", array(':last' => $row['last']));

      // Отправляем документы.
      foreach (Node::find(array('id' => $nids)) as $node) {
        bebop_mail(null, trim($row['email']), $node->name, $node->text);
        printf("    sent mail to %s: %s\n", trim($row['email']), $node->name);
        $last = max($last, $node->id);
      }

      // Запоминаем последнее отправленное сообщение.
      if ($last !== null) {
        $pdo->exec("UPDATE `node__subscription_emails` SET `last` = :last WHERE `id` = :sid",
          array(':last' => $last, ':sid' => $row['id']));
      }
    }
  }

  // РАБОТА С ФОРМАМИ.

  public function formGet($id)
  {
    $form = null;

    switch ($id) {
    case 'subscription-admin-form':
      $form = new Form(array(
        'title' => t('Управление подпиской'),
        ));
      $form->addClass('tabbed');

      if (null !== ($tab = $this->formGetTypes()))
        $form->addControl($tab);

      if (null !== ($tab = $this->formGetSections()))
        $form->addControl($tab);

      $form->addControl(new SubmitControl(array(
        'text' => t('Сохранить'),
        )));

      break;
    }

    return $form;
  }

  private function formGetTypes()
  {
    $options = array();

    foreach (Node::find(array('class' => 'type', '#sort' => array('type.title' => 'asc'))) as $t)
      $options[$t->id] = $t->title;

    $tab = new FieldSetControl(array(
      'name' => 'types',
      'label' => t('Типы документов'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'subscription_types',
      'options' => $options,
      'label' => t('Типы документов, рассылаемые по почте'),
      )));

    return $tab;
  }

  private function formGetSections()
  {
    $options = array();

    foreach (TagNode::getTags('flat') as $t) {
      $options[$t['id']] = str_repeat('&nbsp;', 4 * $t['depth']) . $t['name'];
    }

    $tab = new FieldSetControl(array(
      'name' => 'sections',
      'label' => t('Разделы'),
      ));
    $tab->addControl(new SetControl(array(
      'value' => 'subscription_sections',
      'label' => t('Выберите активные разделы'),
      'options' => $options,
      )));

    return $tab;
  }

  public function formGetData()
  {
    $data = array(
      'subscription_types' => array(),
      'subscription_sections' => array(),
      );

    foreach (Node::find(array('class' => 'type')) as $t)
      if (!empty($t->sendmail))
        $data['subscription_types'][] = $t->id;

    foreach (Node::find(array('class' => 'tag')) as $t)
      if (!empty($t->bebop_subscribe))
        $data['subscription_sections'][] = $t->id;

    return $data;
  }

  public function formProcess($id, array $data)
  {
    $flush = false;

    if (empty($data['subscription_types']))
      $data['subscription_types'] = array();

    if (empty($data['subscription_sections']))
      $data['subscription_sections'] = array();

    foreach (Node::find(array('class' => 'type')) as $t) {
      if (in_array($t->id, $data['subscription_types']))
        $sendmail = 1;
      else
        $sendmail = 0;

      if ($t->sendmail != $sendmail) {
        $t->sendmail = $sendmail;
        $t->save();
      }
    }

    foreach (Node::find(array('class' => 'tag')) as $t) {
      if (in_array($t->id, $data['subscription_sections']))
        $subscribe = 1;
      else
        $subscribe = 0;

      if ($t->bebop_subscribe != $subscribe) {
        $t->bebop_subscribe = $subscribe;
        $t->save();
      }
    }

    if ($flush)
      mcms::flush();
  }

  public static function getDashboardIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasGroup('Subscription Managers'))
      $icons[] = array(
        'group' => 'Access',
        'img' => 'img/dashboard-task-subscription.gif',
        'href' => '/admin/subscription/',
        'title' => t('Рассылка'),
        'description' => t('Управление подпиской на новости.'),
        );

    return $icons;
  }
};
