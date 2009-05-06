<?php

class SchemaMenu
{
  public static function on_get_types(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('schema', array(
      '#raw' => true,
      ));
  }

  public static function on_get_fields(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('fields', array('#raw' => true));
  }
}
