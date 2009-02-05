<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminMenu implements iAdminMenu
{
  private function getCurrentGroup()
  {
    if (!empty($_GET['cgroup']))
      $cgroup = $_GET['cgroup'];
    elseif (count($parts = explode('/', $_GET['q'])) > 1)
      $cgroup = $parts[1];
    else
      $cgroup = 'content';

    return $cgroup;
  }

  private function cache($value = null)
  {
    if (null === ($key = $this->getCacheKey()))
      return null;

    if (null === $value)
      return mcms::cache($key);
    else
      return mcms::cache($key, $value);
  }

  private static function getGroupName($name)
  {
    $trans = array(
      'access' => t('Доступ'),
      'content' => t('Наполнение'),
      'developement' => t('Разработка'),
      'statistics' => t('Статистика'),
      'structure' => t('Структура'),
      'status' => t('Состояние'),
      'system' => t('Система'),
      );

    return array_key_exists($name, $trans)
      ? $trans[$name]
      : $name;
  }

  public function getXML()
  {
    $cgroup = $this->getCurrentGroup();

    if (is_string($tmp = $this->cache()) and false)
      return $tmp;

    $output = '';

    foreach ($this->getIcons() as $group => $icons) {
      $tmp = '';

      if (array_key_exists('href', $icons[0])) {
        $first = null;

        foreach ($icons as $icon) {
          $u = new url($icon['href']);
          $u->setarg('q', 'admin.rpc');
          $u->setarg('cgroup', $group);

          if (null === $first)
            $first = $u->string();

          $tmp .= html::em('link', array(
            'url' => $u->string(),
            'description' => empty($icon['description']) ? null : $icon['description'],
            'title' => $icon['title'],
            ));
        }

        $output .= html::em('tab', array(
          'class' => ($group == $cgroup) ? 'current' : null,
          'url' => $first,
          'name' => $group,
          'title' => self::getGroupName($group),
          ), $tmp);
      }
    }

    $output = html::em('block', array(
      'name' => 'menu',
      ), $output);

    $this->cache($output);

    return $output;
  }

  private function getCacheKey()
  {
    if (!empty($_GET['nocache']))
      return null;

    $key = 'adminmenu:'. $this->getCurrentGroup();

    if (!empty($_GET['__cleanurls']))
      $key .= ':cleanurls';

    return $key;
  }

  private function getIcons()
  {
    $result = array();

    foreach ($i = mcms::invoke('iAdminMenu', 'getMenuIcons') as $tmp) {
      if (is_array($tmp))
        foreach ($tmp as $icon)
          if (array_key_exists('group', $icon))
            $result[$icon['group']][] = $icon;
    }

    ksort($result);

    return $result;
  }

  /**
   * Базовая навигация по CMS.
   */
  public static function getMenuIcons()
  {
    $icons = array();
    $user = Context::last()->user;

    if ($user->hasAccess('u', 'tag'))
      $icons[] = array(
        'group' => 'content',
        'href' => '?action=tree&preset=taxonomy',
        'title' => t('Разделы'),
        'description' => t('Управление разделами сайта.'),
        'weight' => -1,
        );

    if ($user->hasAccess('u', 'type'))
      $icons[] = array(
        'group' => 'structure',
        'href' => '?action=list&preset=schema',
        'title' => t('Типы документов'),
        );

    if (count($user->getAccess('u') + $user->getAccess('c'))) {
      $icons[] = array(
        'group' => 'content',
        'href' => '?action=list&columns=name,class,uid,created',
        'title' => t('Документы'),
        'description' => t('Поиск, редактирование, добавление документов.'),
        );
      if ($user->hasAccess('c', 'type'))
        $icons[] = array(
          'group' => 'content',
          'href' => '?action=list&preset=dictlist',
          'title' => t('Справочники'),
          );
      // FIXME. if (Node::count(array('published' => 0, '-class' => TypeNode::getInternal())))
        $icons[] = array(
          'group' => 'content',
          'href' => '?action=list&preset=drafts',
          'title' => t('В модерации'),
          'description' => t('Поиск, редактирование, добавление документов.'),
          );
    }

    if ($user->hasAccess('u', 'domain')) {
      $icons[] = array(
        'group' => 'structure',
        'href' => '?action=list&preset=pages',
        'title' => t('Домены'),
        'description' => t('Управление доменами, страницами и виджетами.'),
        );
      $icons[] = array(
        'group' => 'structure',
        'href' => '?action=list&preset=widgets',
        'title' => t('Виджеты'),
        );
    }

    if ($user->hasAccess('u', 'user'))
      $icons[] = array(
        'group' => 'access',
        'href' => '?action=list&preset=users',
        'title' => t('Пользователи'),
        'description' => t('Управление профилями пользователей.'),
        );
    if ($user->hasAccess('u', 'group'))
      $icons[] = array(
        'group' => 'access',
        'href' => '?action=list&preset=groups',
        'title' => t('Группы'),
        'description' => t('Управление группами пользователей.'),
        );

    if ($user->hasAccess('u', 'file'))
      $icons[] = array(
        'group' => 'content',
        'href' => '?action=list&preset=files',
        'title' => t('Файлы'),
        'description' => t('Просмотр, редактирование и добавление файлов.'),
        );

    if (count($user->getAccess('u')) /* and Node::count(array('deleted' => 1, '-class' => TypeNode::getInternal())) */)
      $icons[] = array(
        'group' => 'content',
        'href' => '?action=list&preset=trash',
        'title' => t('Корзина'),
        'description' => t('Просмотр и восстановление удалённых файлов.'),
        'weight' => 10,
        );

    if (Context::last()->user->hasAccess('u', 'type') and mcms::db()->fetch("SELECT COUNT(*) FROM `node__fallback`"))
      $icons[] = array(
        'group' => 'statistics',
        'title' => t('404'),
        'href' => '?action=list&preset=404',
        );

    return $icons;
  }

  public function __toString()
  {
    return $this->getHTML();
  }

  public function getDesktop()
  {
    $columns = array();
    $idx = 0;

    $result = '';

    if (is_string($cached = mcms::cache($ckey = 'admin:desktop:status')) and false)
      return $cached;

    foreach ($this->getIcons() as $grname => $gritems) {
      $items = '';

      foreach ($gritems as $item) {
        if (!empty($item['message'])) {
          $text = $item['message'];
          unset($item['group']);
          unset($item['message']);

          if (array_key_exists('link', $item))
            $item['link'] = str_replace('&destination=CURRENT', '&destination=' . urlencode($_SERVER['REQUEST_URI']), $item['link']);
          $items .= html::em('message', $item, html::cdata($text));
        }
      }

      if (!empty($items))
        $result .= html::em('group', array(
          'title' => self::getGroupName($grname),
          ), $items);
    }

    if (!empty($result))
      $result = html::em('block', array(
        'name' => 'messages',
        ), $result);

    mcms::cache($ckey, $result);

    return $result;
  }
};
