<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SubscriptionWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Управление подпиской',
      'description' => 'Позволяет пользователям подписываться на новости.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new SetControl(array(
      'value' => 'config_sections',
      'label' => t('Поместить документ в разделы'),
      'options' => TagNode::getTags('select'),
      )));

    return $form;
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    if ('confirm' == ($options['status'] = $ctx->get('status', 'default')))
      $options['code'] = $ctx->get('code');

    $options['sections'] = empty($this->sections)
      ? array() : $this->sections;

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    return $this->dispatch(array($options['status']), $options);
  }

  protected function onGetDefault(array $options)
  {
    return parent::formRender('subscription-form', array());
  }

  protected function onGetWait(array $options)
  {
    $output = '<h2>'. t($this->me->title) .'</h2>';
    $output .= '<p>'. t('Инструкция по активации подписки отправлена на введённый почтовый адрес.') .'</p>';
    return $output;
  }

  protected function onGetConfirm(array $options)
  {
    if (null !== $options['code'] and is_array($data = unserialize(base64_decode($options['code'])))) {
      if (empty($data['sections']))
        throw new InvalidArgumentException("Не указаны разделы.");

      foreach ($data['sections'] as $k => $v)
        if (!is_numeric($v))
          unset($data['sections'][$k]);

      $pdo = mcms::db();

      $last = $pdo->getResult("SELECT MAX(`id`) FROM `node`");

      $pdo->exec("REPLACE INTO `node__subscription_emails` (`email`, `active`, `last`) VALUES (:email, 1, :last)",
        array(':email' => $data['email'], ':last' => $last));
      $sid = $pdo->lastInsertId();

      $pdo->exec("INSERT INTO `node__subscription_tags` (`sid`, `tid`) SELECT :sid, `id` FROM `node` WHERE `id` IN (". join(', ', $data['sections']) .")",
        array(':sid' => $sid));

      $output = '<h2>'. t($this->me->title) .'</h2>';
      $output .= '<p>'. t('Параметры вашей подписки успешно изменены.') .'</p>';
      return $output;
    }

    throw new PageNotFoundException();
  }

  protected function onGetUpdated(array $options)
  {
    $output = '<h2>'. t($this->me->title) .'</h2>';
    $output .= '<p>'. t('Состояние подписки изменено, спасибо!') .'</p>';
    return $output;
  }

  public function formGet($id)
  {
    switch ($id) {
    case 'subscription-form':
      $list = TagNode::getTags('select',
        array('enabled' => $this->options['sections']));

      $form = new Form(array(
        'title' => t($this->me->title),
        ));
      $form->addControl(new EmailControl(array(
        'label' => t('Email'),
        'required' => true,
        'value' => 'email',
        )));
      $form->addControl(new SetControl(array(
        'label' => t('Подписаться на'),
        'options' => $list,
        'value' => 'sections',
        )));
      $form->addControl(new SubmitControl(array(
        'text' => t('Подписаться'),
        )));

      return $form;
    }
  }

  public function formProcess($id, array $data)
  {
    switch ($id) {
    case 'subscription-form':
      if (empty($data['sections']))
        throw new InvalidArgumentException("Не выбраны разделы для подписки.");

      // В массиве могут быть и другие данные, поэтому мы
      // выбираем только то, что нам нужно завернуть.
      $bulk = array(
        'email' => $data['email'],
        'sections' => $data['sections'],
        );

      $catlist = '';

      foreach (Node::find(array('class' => 'tag', 'id' => $data['sections'], '#sort' => array('name' => 'asc'))) as $tmp)
        $catlist .= '<li>'. mcms_plain($tmp->name) .'</li>';

      $link = l(null, array(
        $this->getInstanceName() => array(
          'status' => 'confirm',
          'code' => base64_encode(serialize($bulk)),
          ),
        'absolute',
        ));

      // Формируем текст почтового сообщения.
      $body = t("<p>Здравствуйте! Я — почтовый робот сайта %host, и я хотел бы уточнить, действительно ли "
        ."Вы хотите подписаться на новости нашего сайта в следующих категориях:</p><ol>%list</ol>"
        ."<p>Чтобы активировать подписку, пройдите, пожалуйста, по <a href='@link'>этой ссылке</a>.&nbsp; "
        ."Вы можете проигнорировать это сообщение, тогда подписка на новости изменена не будет.</p>", array(
        '%host' => $_SERVER['HTTP_HOST'],
        '%list' => $catlist,
        '@link' => $link,
        ));

      bebop_mail(null, $data['email'], t('Подписка на новости сайта %host', array('%host' => $_SERVER['HTTP_HOST'])), $body);

      $url = bebop_split_url();
      $url['args'][$this->getInstanceName()] = array('status' => 'wait');
      mcms::redirect($url);
    }
  }
};
