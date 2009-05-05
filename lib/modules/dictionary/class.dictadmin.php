<?php

class DictAdmin
{
  public static function on_get_list(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('dictlist', array(
      '#raw' => true,
      ));
  }
}
