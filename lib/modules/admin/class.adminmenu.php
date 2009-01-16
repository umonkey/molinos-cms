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

    if (false && is_string($tmp = $this->cache()))
      return $tmp;

    $output = '';

    foreach ($this->getIcons() as $group => $icons) {
      $tmp = '';

      if (array_key_exists('href', $icons[0])) {
        foreach ($icons as $icon)
          $tmp .= html::em('link', array(
            'url' => $icon['href'],
            'description' => empty($icon['description']) ? null : $icon['description'],
            'title' => $icon['title'],
            ));

        $output .= html::em('tab', array(
          'class' => ($group == $cgroup) ? 'current' : null,
          'url' => $icons[0]['href'],
          'name' => $group,
          'title' => self::getGroupName($group),
          ), $tmp);
      }
    }

    $output = html::em('menu', $output);

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
    $user = mcms::user();

    if ($user->hasAccess('u', 'tag'))
      $icons[] = array(
        'group' => 'content',
        'href' => '?q=admin/content/tree/taxonomy',
        'title' => t('Разделы'),
        'description' => t('Управление разделами сайта.'),
        'weight' => -1,
        );

    if ($user->hasAccess('u', 'type'))
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin/structure/list/schema',
        'title' => t('Типы документов'),
        );

    if (count($user->getAccess('u') + $user->getAccess('c'))) {
      $icons[] = array(
        'group' => 'content',
        'href' => '?q=admin/content/list&columns=name,class,uid,created',
        'title' => t('Документы'),
        'description' => t('Поиск, редактирование, добавление документов.'),
        );
      if ($user->hasAccess('c', 'type'))
        $icons[] = array(
          'group' => 'content',
          'href' => '?q=admin/content/list/dictlist',
          'title' => t('Справочники'),
          );
      if (Node::count(array('published' => 0, '-class' => TypeNode::getInternal())))
        $icons[] = array(
          'group' => 'content',
          'href' => '?q=admin/content/list/drafts',
          'title' => t('В модерации'),
          'description' => t('Поиск, редактирование, добавление документов.'),
          );
    }

    if ($user->hasAccess('u', 'domain')) {
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin/structure/list/pages',
        'title' => t('Домены'),
        'description' => t('Управление доменами, страницами и виджетами.'),
        );
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin/structure/list/widgets',
        'title' => t('Виджеты'),
        );
    }

    if ($user->hasAccess('u', 'user'))
      $icons[] = array(
        'group' => 'access',
        'href' => '?q=admin/access/list/users',
        'title' => t('Пользователи'),
        'description' => t('Управление профилями пользователей.'),
        );
    if ($user->hasAccess('u', 'group'))
      $icons[] = array(
        'group' => 'access',
        'href' => '?q=admin/access/list/groups',
        'title' => t('Группы'),
        'description' => t('Управление группами пользователей.'),
        );

    if ($user->hasAccess('u', 'file'))
      $icons[] = array(
        'group' => 'content',
        'href' => '?q=admin/content/list/files',
        'title' => t('Файлы'),
        'description' => t('Просмотр, редактирование и добавление файлов.'),
        );

    if (count($user->getAccess('u')) and Node::count(array('deleted' => 1, '-class' => TypeNode::getInternal())))
      $icons[] = array(
        'group' => 'content',
        'href' => '?q=admin/content/list/trash',
        'title' => t('Корзина'),
        'description' => t('Просмотр и восстановление удалённых файлов.'),
        'weight' => 10,
        );

    if (mcms::user()->hasAccess('u', 'type') and mcms::db()->fetch("SELECT COUNT(*) FROM `node__fallback`"))
      $icons[] = array(
        'group' => 'statistics',
        'title' => t('404'),
        'href' => '?q=admin/content/list/404',
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

    $result = null;

    if (is_string($cached = mcms::cache($ckey = 'admin:desktop:status')))
      return $cached;

    foreach ($this->getIcons() as $grname => $gritems) {
      $items = '';

      foreach ($gritems as $item) {
        if (!empty($item['message'])) {
          $text = $item['message'];
          unset($item['group']);
          unset($item['message']);
          $items .= html::em('message', $item, html::cdata($text));
        }
      }

      if (!empty($items))
        $result .= html::em('group', array(
          'title' => self::getGroupName($grname),
          ), $items);
    }

    if (!empty($result))
      $result = html::em('messages', $result);

    mcms::cache($ckey, $result);

    return $result;
  }
};
