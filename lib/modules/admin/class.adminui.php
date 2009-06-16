<?php

class AdminUI
{
  public static function submenu(Context $ctx, $query, array $pathinfo)
  {
    AdminPage::checkperm($ctx, $pathinfo);

    $router = new Router();
    $router->poll($ctx);

    $menu = new AdminMenu($router->getStatic());

    if (false === ($submenu = $menu->getSubMenu($ctx)))
      throw new PageNotFoundException();

    if (false === ($content= $submenu->getXML($ctx, 'content', array('type' => 'submenu'))))
      throw new PageNotFoundException();

    $page = new AdminPage($content);
    return $page->getResponse($ctx);
  }
}
