<?php

class TaxonomyAdmin
{
  public static function on_get_list(Context $ctx)
  {
    $tmp = new AdminTreeHandler($ctx);
    return $tmp->getHTML('taxonomy', array(
      'edit' => $ctx->user->hasAccess('u', 'tag'),
      'search' => false,
      ));
  }
}
