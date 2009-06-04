<?php

class LabelsRPC
{
  public static function on_post_manage(Context $ctx)
  {
    if (!($nid = $ctx->get('id')))
      throw new BadRequestException(t('Не указан идентификатор метки (GET-параметр id).'));

    $params = array($nid);
    $sql = "DELETE FROM `node__rel` WHERE `tid` = ? AND `nid` " . sql::notin($ctx->post('apply'), $params);

    $ctx->db->beginTransaction();
    $ctx->db->exec($sql, $params);
    $ctx->db->commit();

    return $ctx->getRedirect();
  }
}
