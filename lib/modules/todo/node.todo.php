<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class TodoNode extends Node
{
  public function save()
  {
    $isnew = empty($this->id);

    parent::save();

    $this->linkSetParents($this->rel ? array($this->rel) : array());

    $this->notify($isnew);
  }

  private function notify($isnew)
  {
    $url = "http://{$_SERVER['HTTP_HOST']}/todo/". ($this->rel ? "{$this->rel}/" : "");

    $message = t('<p>Задача: %text.</p><p><a href=\'@url\'>Полный список</a></p>', array(
      '%text' => rtrim($this->name, '.'),
      '@url' => $url,
      ));

    if ($isnew and $this->rel) {
      mcms::mail(null, $this->rel, t('Новая задача'), $message);
    }

    elseif (!$isnew) {
      if ($this->closed)
        mcms::mail(null, $this->uid, t('Задача закрыта'), $message);
      else
        mcms::mail(null, $this->rel, t('Задача открыта повторно'), $message);
    }
  }

  public function render($prefix = null)
  {
    $output = mcms::html('input', array(
      'type' => 'checkbox',
      'value' => $this->id,
      'class' => 'checkbox',
      'checked' => empty($this->closed) ? null : 'checked',
      ));
    $output .= mcms::html('span', array(
      'class' => 'description',
      ), $this->name);

    return mcms::html('div', array(
      'class' => 'todo',
      'id' => ltrim($prefix .'-item-'. $this->id, '-'),
      ), $output);
  }
}
