<?php

class SchemaRPC extends RPCHandler
{
  public static function on_rpc(Context $ctx)
  {
    return parent::hookRemoteCall($ctx, __CLASS__);
  }

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
