<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class TodoNode extends Node
{
  public function save()
  {
    $isnew = empty($this->id);

    parent::save();

    $links = array();

    if ($this->rel)
      $links[] = $this->rel;
    if ($this->to)
      $links[] = $this->to;

    $this->linkSetParents($links);

    $this->notify($isnew);
  }

  private function notify($isnew)
  {
    if ($this->to and ($this->to == mcms::user()->id))
      return;

    $data = array(
      'mode' => 'mail',
      'node' => $this->getRaw(),
      );
    $data['node']['uid'] = Node::load(array('class' => 'user', 'id' =>
      $this->uid))->getRaw();

    $message = bebop_render_object('mail', 'todo', null, $data, __CLASS__);

    if (empty($message)) {
      $url = "http://{$_SERVER['HTTP_HOST']}/todo/". ($this->rel ? "{$this->rel}/" : "");

      $message = t('<p>Напоминание: %text.</p><p><a href=\'@url\'>Полный список</a></p>', array(
        '%text' => rtrim($this->name, '.'),
        '@url' => $url,
        ));
    }

    if ($isnew) {
      if ($this->to)
        mcms::mail(null, $this->to, t('Новое напоминание'), $message);
    }

    elseif (!$isnew) {
      if ($this->closed)
        mcms::mail(null, $this->uid, t('Напоминание удалено'), $message);
      elseif ($this->to)
        mcms::mail(null, $this->to, t('Напоминание реактивировано'), $message);
    }
  }
}
