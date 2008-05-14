<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class TodoRPC implements iRemoteCall
{
  public static function hookRemoteCall(RequestContext $ctx)
  {
    switch ($ctx->get('action')) {
    case 'add':
      mcms::user()->checkAccess('c', 'todo');

      $node = Node::create('todo', array(
        'name' => $ctx->post('name'),
        'uid' => mcms::user()->id,
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

      $state = $node->closed ? 'closed' : 'open';

      bebop_on_json(array(
        'status' => 'ok',
        'state' => $state,
        ));

      break;
    }

    mcms::redirect($ctx->get('destination', '/'));
  }
};
