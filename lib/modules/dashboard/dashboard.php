<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class BebopDashboard extends Widget implements iAdminWidget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => t('Панель управления'),
      'description' => t("Возвращает описание основных разделов админки."),
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = array_merge(parent::getRequestOptions($ctx), array(
      'groups' => $this->user->getGroups(true),
      ));
    return $options;
  }

  // Обработка запросов.  Возвращает список действий, предоставляемых административными виджетами.
  public function onGet(array $options)
  {
    $result = array();

    $user = AuthCore::getInstance()->getUser();

    if ($user->getName() == 'anonymous')
      throw new UnauthorizedException();

    $tree = Tagger::getInstance()->getObjectTree(key(Node::find(array('class' => 'domain', 'name' => array('DOMAIN', 'www.DOMAIN')))), 0);

    $perms = array(
      'taxonomy' => 'Structure Managers',
      'schema' => 'Schema Managers',
      'builder' => 'Developers',
      'users' => 'User Managers',
      'subscription' => 'Subscription Managers',
      'logs' => 'Access Managers',
      );

    foreach ($tree['children'] as $path) {
      if ($path['name'] == 'admin') {
        foreach ($path['children'] as $url) {
          if (!empty($url['hidden']))
            continue;

          $pass = true;

          if (array_key_exists($url['name'], $perms)) {
            $pass = false;
            $list = (array)$perms[$url['name']];

            foreach ($list as $k => $v)
              if ($this->user->hasGroup($v)) {
                $pass = true;
                break;
              }
          }

          if ($pass) {
            $result['list'][] = array(
              'name' => $url['title'],
              'class' => $url['name'],
              'link' => "/admin/{$url['name']}/",
              'description' => $url['description'],
              );
          }
        }
      }
    }

    if (empty($result['list']))
      throw new UnauthorizedException();

    if (preg_match('/^((.*)\.([0-9]+))$/', BEBOP_VERSION, $m)) {
      $result['version_major'] = $m[2];
      $result['version_minor'] = $m[3];
    } else {
      $result['version_major'] = '?';
      $result['version_minor'] = '?';
    }


    return $result;
  }
};
