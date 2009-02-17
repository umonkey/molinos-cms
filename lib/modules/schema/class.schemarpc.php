<?php

class SchemaRPC extends RPCHandler implements iRemoteCall
{
  public static function rpc_get_reindex(Context $ctx)
  {
    try {
      $field = NodeStub::loadByName($ctx->db, $ctx->get('field'), 'field');
      $field->getObject()->checkIndex();
    } catch (ObjectNotFoundException $e) {
      throw new BadMethodCallException(t('Не указано поле для индексации.'));
    }
  }
}
