<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class MessageNode extends Node
{
  public function save()
  {
    if (empty($this->id)) {
      try {
        $dst = Node::load(array('class' => 'user', 'id' => $this->re));

        if (!empty($dst->email))
          $email = $dst->email;
        elseif (false !== strstr($dst->name, '@'))
          $email = $dst->name;

        if (!empty($email) and class_exists('BebopMimeMail')) {
          BebopMimeMail::send(null, $email, $this->name, $this->text);
          $this->data['sent'] = 1;
        }

        // Сохраняем в базе только если пользователь найден.
        // Чтобы можно было спокойно вызывать mcms::mail() для
        // любых объектов, не парясь с проверкой на class=user.
        return parent::save();
      } catch (ObjectNotFoundException $e) {
      }
    }
  }

  public function getDefaultSchema()
  {
    return array(
      'uid' => array(
        'label' => t('Отправитель'),
        'type' => 'NodeLinkControl',
        'required' => true,
        'values' => 'user.fullname',
        ),
      're' => array(
        'label' => t('Получатель'),
        'type' => 'NodeLinkControl',
        'required' => true,
        'values' => 'user.fullname',
        'indexed' => true,
        ),
      'name' => array(
        'label' => ('Заголовок'),
        'type' => 'TextLineControl',
        'required' => true,
        ),
      'created' => array(
        'label' => t('Дата отправления'),
        'type' => 'DateTimeControl',
        'required' => true,
        ),
      'sent' => array(
        'label' => t('Отправлено по почте'),
        'type' => 'BoolControl',
        'required' => false,
        'indexed' => true,
        ),
      'received' => array(
        'label' => t('Дата прочтения'),
        'type' => 'DateTimeControl',
        'required' => false,
        'indexed' => true,
        ),
      'text' => array(
        'label' => t('Текст'),
        'type' => 'TextHTMLControl',
        'required' => true,
        ),
      );
  }

  // Доступ к сообщению имеют только отправитель и получатель.
  public function checkPermission($perm)
  {
    $user = mcms::user();

    mcms::debug($this->uid, $this->re);

    if ($this->cmp($this->uid, $user->id))
      return true;

    if ($this->cmp($this->re, $user->id))
      return true;

    return false;
  }

  private function cmp($a, $b)
  {
    if ($a instanceof Node)
      return $a->id == $b;
    else
      return $a == $b;
  }
}
