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
 * A widget to list todo items.
 *
 * This class is much like the standard ListWidget, but specific
 * to the "todo" nodes.  It returns open/closed object in separate
 * lists, and uses a handwritten query, optimized for performance.
 *
 * @package mod_todo
 */
class TodoListWidget extends Widget
{
  private $options;

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Список задач',
      'description' => 'Выводит общий список задач или задачи по конкретному объекту.',
      );
  }

  public static function getConfigOptions()
  {
    return array(
      'linktpl' => array(
        'type' => 'TextLineControl',
        'label' => t('Шаблон ссылки'),
        'default' => 'node/$nid',
        'required' => true,
        ),
      );
  }

  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['uid'] = $ctx->user->id;
    $options['mode'] = $this->get('mode');

    if (null === ($options['rel'] = $ctx->document->id))
      $options['relname'] = null;
    elseif ('user' == $ctx->document->class)
      $options['relname'] = empty($ctx->document->fullname) ? $ctx->document->name : $ctx->document->fullname;
    else
      $options['relname'] = $ctx->document->name;

    return $this->options = $options;
  }

  public function onGet(array $options)
  {
    $result = array(
      'list' => array(),
      'count' => 0,
      'rel' => $this->options['rel'],
      'relname' => $this->options['relname'],
      'linktpl' => $this->linktpl,
      );

    $list = $this->getList($this->ctx);

    foreach ($list as $node) {
      $tmp = $node->getRaw();
      $result['list'][empty($node->closed) ? 'open' : 'closed'][] = $tmp;
      $result['count']++;
    }

    $result['users'] = $this->getUsers();

    return $result;
  }
  
  private function getUsers()
  {
    $result = array();
    
    foreach (Node::find($this->ctx->db, array('class' => 'user')) as $node)
      $result[$node->id] = empty($node->fullname) ? $node->name : $node->fullname;
      
    asort($result);
    
    return $result;
  }

  private function getList(Context $ctx)
  {
    $filter = array(
      'class' => 'todo',
      '#sort' => 'created',
      );

    if (!empty($this->options['rel'])) {
      $rel = $this->options['rel'];
      $filter['id'] = $ctx->db->getResultsV("id", "SELECT n.id AS `id` "
        ."FROM node n "
        ."WHERE n.class = 'todo' AND n.id IN "
        ."(SELECT tid FROM node__rel WHERE nid = ? "
        ."UNION SELECT nid FROM node__rel WHERE tid = ? "
        ."UNION SELECT rel FROM node__idx_todo WHERE rel = ?)",
          array($rel, $rel, $rel));
    }

    return Node::find($ctx->db, $filter);
  }
}
