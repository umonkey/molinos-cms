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
 * The remote call handler for the Todo module.
 *
 * The RPC handler does two things: (1) creates a todo node,
 * and (2) toggles its finished state.  This is controlled with
 * the "action" argument of the RPC call, e.g.: ?q=todo.rpc&action=add
 *
 * @package mod_todo
 */
class TodoRPC
{
  public static function hookRemoteCall(Context $ctx)
  {
    switch ($ctx->get('action')) {
    case 'add':
      $ctx->user->checkAccess(ACL::CREATE, 'todo');

      $node = Node::create('todo', array(
        'name' => $ctx->post('name'),
        'uid' => $ctx->user->id,
        'to' => $ctx->post('user', $ctx->user->id),
        'published' => 1,
        'rel' => $ctx->post('rel'),
        ));

      if (empty($node->name)) {
        $msg = t('не указан текст задачи.');

        bebop_on_json(array(
          'status' => 'error',
          'message' => $msg,
          ));

        throw new InvalidArgumentException($msg);
      }

      $node->save();

      bebop_on_json(array(
        'status' => 'created',
        'id' => $node->id,
        'html' => $node->render(),
        ));

      break;

    case 'toggle':
      try {
        $node = Node::load(array('class' => 'todo', 'id' => $ctx->get('id')));
      } catch (ObjectNotFoundException $e) {
        bebop_on_json(array(
          'status' => 'error',
          'message' => 'todo '. $ctx->get('id') .' not found',
          ));
        throw new PageNotFoundException();
      }

      if (empty($node->closed))
        $node->closed = date('Y-m-d H:i:s', time() - date('Z', time())); // mcms::now();
      else
        $node->closed = null;

      $node->save();

      if ($ctx->method('POST') and null !== ($comment = $ctx->post('comment'))) {
        $tmp = Node::create('comment', array(
          'uid' => $ctx->user->id,
          'author' => $ctx->user->name,
          'name' => t('Комментарий к напоминанию'),
          'text' => $comment,
          ));
        $tmp->save();
        $tmp->linkAddParent($node->id);
      }

      $state = $node->closed ? 'closed' : 'open';

      bebop_on_json(array(
        'status' => 'ok',
        'state' => $state,
        ));

      break;
    }
  }
};
