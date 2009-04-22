<?php

class UserAdmin
{
  public static function on_get_list(Context $ctx)
  {
    $tmp = new AdminListHandler($ctx);
    return $tmp->getHTML('users', array(
      'edit' => $ctx->user->hasAccess('u', 'user'),
      '#raw' => true,
      'self' => $ctx->user->id,
      ));
  }
}
