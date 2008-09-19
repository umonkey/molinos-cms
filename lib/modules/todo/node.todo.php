<?php
/**
 * This file contains the RPC handler for the todo module.
 *
 * This class contains frequently used functions and shortcuts
 * to functions provider by different modules.
 *
 * PHP version 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @package mod_todo
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * The "todo" doctype handler.
 *
 * @package mod_todo
 */
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

    if (!empty($message)) {
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

  public function getActionLinks()
  {
    $links = parent::getActionLinks();

    if ($this->checkPermission('u'))
      $links['todoaction'] = array(
        'href' => '?q=todo.rpc&action=toggle&id='. $this->id
          .'&destination=CURRENT',
        'title' => $this->closed ? t('Реактивировать') : t('Выполнено'),
        'icon' => 'todoaction',
        );

    return $links;
  }
}
