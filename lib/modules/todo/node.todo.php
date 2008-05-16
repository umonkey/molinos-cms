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

    $url = "http://{$_SERVER['HTTP_HOST']}/todo/". ($this->rel ? "{$this->rel}/" : "");

    $message = t('<p>Напоминание: %text.</p><p><a href=\'@url\'>Полный список</a></p>', array(
      '%text' => rtrim($this->name, '.'),
      '@url' => $url,
      ));

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

  public function render($prefix = null)
  {
    $name = l("/node/comments/{$this->id}/", mcms_plain($this->name, false));
    $mine = ($this->to == mcms::user()->id);

    if (!$mine and !empty($this->to)) {
      try {
        $to = Node::load(array('class' => 'user', 'id' => $this->to));
        $name .= t(' (<a class=\'userlink\' href=\'@url\'>%name</a>)', array(
          '@url' => "/node/{$to->id}/",
          '%name' => empty($to->fullname) ? $to->name : $to->fullname,
          ));
      } catch (ObjectNotFoundException $e) {
      }
    }

    $output = mcms::html('input', array(
      'type' => 'checkbox',
      'value' => $mine ? $this->id : null,
      'class' => 'checkbox' . ($mine ? '' : ' disabled'),
      'checked' => empty($this->closed) ? null : 'checked',
      'disabled' => $mine ? '' : 'disabled',
      ));
    $output .= mcms::html('span', array(
      'class' => 'delete',
      ));
    $output .= mcms::html('span', array(
      'class' => 'description',
      ), $name);

    return mcms::html('div', array(
      'class' => 'todo',
      'id' => ltrim($prefix .'-item-'. $this->id, '-'),
      ), $output);
  }
}
