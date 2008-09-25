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
    if (!empty($_GET['nocache']))
      return null;

    $key = 'adminmenu:'. $this->getCurrentGroup();

    if (!empty($_GET['__cleanurls']))
      $key .= ':cleanurls';

    return mcms::config($key, $value);
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
      );

    return array_key_exists($name, $trans)
      ? $trans[$name]
      : $name;
  }

  public function getHTML()
  {
    $cgroup = $this->getCurrentGroup();

    if (is_string($tmp = $this->cache()))
      return $tmp;

    $menu = $this->getIcons();

    $output = '<ul>';

    foreach ($menu as $group => $icons) {
      if (array_key_exists('href', $icons[0])) {
        $url = $icons[0]['href'];

        if ($group == $cgroup)
          $output .= '<li class=\'current\'>';
        else
          $output .= '<li>';

        $output .= mcms::html('a', array(
          'href' => $url,
          ), self::getGroupName($group));

        $output .= '<ul>';

        foreach ($icons as $icon) {
          $tmp = mcms::html('a', array(
            'href' => $icon['href'],
            'title' => empty($icon['description']) ? null : $icon['description'],
            ), $icon['title']);
          $output .= mcms::html('li', $tmp);
        }

        $output .= '</ul></li>';
      }
    }

    $output .= '</ul>';

    mcms::cache('adminmenu:'. $cgroup, $output);

    return $output;
  }

  private function getIcons()
  {
    $result = array();

    $classes = mcms::getClassMap();
    $rootlen = strlen(dirname(dirname(dirname(dirname(__FILE__)))));

    foreach (mcms::getImplementors('iAdminMenu') as $class) {
      $icons = call_user_func(array($class, 'getMenuIcons'));

      if (is_array($icons) and !empty($icons)) {
        foreach ($icons as $icon) {
          if (array_key_exists('href', $icon)) {
            /*
            $url = new url($icon['href']);
            $url->setarg('cgroup', $icon['group']);
            $icon['href'] = strval($url);
            */
          }

          $result[$icon['group']][] = $icon;
        }
      }
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
        'message' => t('Управление разделами'),
        'description' => t('Управление разделами сайта.'),
        'weight' => -1,
        );

    if ($user->hasAccess('u', 'type'))
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin/structure/list/schema',
        'title' => t('Типы документов'),
        'message' => t('<a href=\'@url1\'>Типы документов</a>, <a
          href=\'@url2\'>справочники</a>', array(
            '@url1' => '?q=admin/structure/list/schema',
            '@url2' => '?q=admin/structure/list/dictlist',
            )),
        );

    if (count($user->getAccess('u'))) {
      $icons[] = array(
        'group' => 'content',
        'href' => '?q=admin/content/list&columns=name,class,uid,created',
        'title' => t('Документы'),
        'message' => t('Список всех документов'),
        'description' => t('Поиск, редактирование, добавление документов.'),
        );
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
          'message' => t('Очередь модерации'),
          'description' => t('Поиск, редактирование, добавление документов.'),
          );
    }

    if ($user->hasAccess('u', 'domain')) {
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin/structure/tree/pages',
        'title' => t('Страницы'),
        'message' => t('Домены и страницы'),
        'description' => t('Управление доменами, страницами и виджетами.'),
        );
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin/structure/list/widgets',
        'title' => t('Виджеты'),
        'message' => t('Виджеты'),
        );
    }

    if ($user->hasAccess('u', 'moduleinfo')) {
      $icons[] = array(
        'group' => 'structure',
        'href' => '?q=admin/structure/modules',
        'title' => t('Модули'),
        'message' => t('Модули'),
        );
    }

    if ($user->hasAccess('u', 'user'))
      $icons[] = array(
        'group' => 'access',
        'href' => '?q=admin/access/list/users',
        'title' => t('Пользователи'),
        'message' => t('Пользователи'),
        'description' => t('Управление профилями пользователей.'),
        );
    if ($user->hasAccess('u', 'group'))
      $icons[] = array(
        'group' => 'access',
        'href' => '?q=admin/access/list/groups',
        'title' => t('Группы'),
        'message' => t('Группы пользователей'),
        'description' => t('Управление группами пользователей.'),
        );

    if ($user->hasAccess('u', 'file'))
      $icons[] = array(
        'group' => 'content',
        'href' => '?q=admin/content/list/files',
        'title' => t('Файлы'),
        'message' => t('Файловый архив'),
        'description' => t('Просмотр, редактирование и добавление файлов.'),
        );

    if (count($user->getAccess('u')) and Node::count(array('deleted' => 1, '-class' => TypeNode::getInternal())))
      $icons[] = array(
        'group' => 'content',
        'href' => '?q=admin/content/list/trash',
        'title' => t('Корзина'),
        'message' => t('Удалённые объекты'),
        'description' => t('Просмотр и восстановление удалённых файлов.'),
        'weight' => 10,
        );

    if (mcms::user()->hasAccess('u', 'type') and mcms::db()->fetch("SELECT COUNT(*) FROM `node__fallback`"))
      $icons[] = array(
        'group' => 'content',
        'message' => t('Отсутствующие страницы'),
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

    foreach ($this->getIcons() as $grname => $gritems) {
      $items = array();

      foreach ($gritems as $item) {
        if (array_key_exists('message', $item)) {
          $text = $item['message'];

          if (false === strpos($text, '<') and array_key_exists('href', $item))
            $text = l($item['href'], $text);

          $items[] = $text;
        }
      }

      if (!empty($items)) {
        $content = mcms::html('legend', self::getGroupName($grname));

        if (count($items) > 1)
          $content .= mcms::html('ul', '<li>'. join('</li><li>', $items) .'</li>');
        else
          $content .= $items[0];

        if (!array_key_exists($idx, $columns))
          $columns[$idx] = '';

        $columns[$idx] .= mcms::html('fieldset', $content);

        $idx = ($idx + 1) % 2;

        // $result .= mcms::html('fieldset', $content);
      }
    }

    if (!empty($columns)) {
      $result = '';

      foreach ($columns as $idx => $col)
        $result .= mcms::html('div', array(
          'id' => 'desktop-column-'. ($idx + 1),
          'class' => 'column',
          ), $col);
    }

    if (empty($result))
      return null;

    return mcms::html('div', array(
      'id' => 'desktop',
      ), $result);
  }
};
