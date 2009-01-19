<?php

class FeedbackNode extends Node
{
  public function save()
  {
    $res = parent::save();

    $this->sendMail();

    return $res;
  }

  public function getFormTitle()
  {
    return t('Обратная связь');
  }

  public function getFormSubmitText()
  {
    return t('Отправить');
  }

  protected function sendMail()
  {
    if (!$this->isNew())
      return;

    $to = array();

    if (($this->email instanceof Node) and !empty($this->email->email))
      $to[] = $this->email->email;

    if ($uid = mcms::modconf('feedback', 'supervisor'))
      $to[] = Node::load($uid)->email;

    $text = mcms::format($this->text);

    if (null === ($from = $this->from))
      $from = mcms::user()->getEmail();

    BebopMimeMail::send($from, $to, $this->name, $text);
  }
}
