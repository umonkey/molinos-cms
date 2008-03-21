<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminMenu implements iAdminMenu
{
  public function getHTML()
  {
    $trans = array(
      'access' => t('Доступ'),
      'content' => t('Наполнение'),
      'developement' => t('Разработка'),
      'statistics' => t('Статистика'),
      'structure' => t('Структура'),
      );

    $menu = $this->getIcons();

    $cgroup = empty($_GET['cgroup']) ? 'content' : $_GET['cgroup'];

    $output = '<ul>';

    foreach ($menu as $group => $icons) {
      $url = bebop_split_url($icons[0]['href']);
      $url['args']['cgroup'] = $group;

      if ($group == $cgroup)
        $output .= '<li class=\'current\'>';
      else
        $output .= '<li>';

      $output .= mcms::html('a', array(
        'href' => bebop_combine_url($url, false),
        ), array_key_exists($group, $trans) ? $trans[$group] : $group);

      $output .= '<ul>';

      foreach ($icons as $icon) {
        $tmp = mcms::html('a', array(
          'href' => $icon['href'],
          'title' => $icon['description'],
          ), $icon['title']);
        $output .= mcms::html('li', array(), $tmp);
      }

      $output .= '</ul></li>';
    }

    $output .= '</ul>';

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
          if (!empty($icon['img'])) {
            $classpath = dirname($classes[strtolower($class)]);
            $icon['img'] = substr($classpath, $rootlen) .'/'. $icon['img'];
          }

          $result[$icon['group']][] = $icon;
        }
      }
    }

    ksort($result);

    return $result;
  }

  public static function getMenuIcons()
  {
    $icons = array();
    $user = mcms::user();

    if ($user->hasGroup('Structure Managers'))
      $icons[] = array(
        'group' => 'structure',
        'img' => 'img/taxonomy.png',
        'href' => '/admin/?mode=tree&preset=taxonomy',
        'title' => t('Карта сайта'),
        'description' => t('Управление разделами сайта.'),
        );

    if ($user->hasGroup('Schema Managers'))
      $icons[] = array(
        'group' => 'structure',
        'img' => 'img/doctype.png',
        'href' => '/admin/?mode=list&preset=schema',
        'title' => t('Типы документов'),
        );

    if ($user->hasGroup('Content Managers')) {
      $icons[] = array(
        'group' => 'content',
        'img' => 'img/content.png',
        'href' => '/admin/?mode=list&columns=name,class,uid,created',
        'title' => t('Документы'),
        'description' => t('Поиск, редактирование, добавление документов.'),
        );
      if (Node::count(array('published' => 0, '-class' => TypeNode::getInternal())))
        $icons[] = array(
          'group' => 'content',
          'img' => 'img/content.png',
          'href' => '/admin/?mode=list&preset=drafts',
          'title' => t('В модерации'),
          'description' => t('Поиск, редактирование, добавление документов.'),
          );
    }

    if ($user->hasGroup('Developers')) {
      $icons[] = array(
        'group' => 'developement',
        'img' => 'img/constructor.png',
        'href' => '/admin/?mode=tree&preset=pages',
        'title' => t('Конструктор'),
        'description' => t('Управление доменами, страницами и виджетами.'),
        );
      $icons[] = array(
        'group' => 'developement',
        'img' => 'img/cms-widget.png',
        'href' => '/admin/?mode=list&preset=widgets',
        'title' => t('Виджеты'),
        );
      $icons[] = array(
        'group' => 'developement',
        'img' => 'img/constructor.png',
        'href' => '/admin/?mode=modules',
        'title' => t('Модули'),
        );
    }

    if ($user->hasGroup('User Managers')) {
      $icons[] = array(
        'group' => 'access',
        'img' => 'img/user.png',
        'href' => '/admin/?mode=list&preset=users',
        'title' => t('Пользователи'),
        'description' => t('Управление профилями пользователей.'),
        );
      $icons[] = array(
        'group' => 'access',
        'img' => 'img/cms-groups.png',
        'href' => '/admin/?mode=list&preset=groups',
        'title' => t('Группы'),
        'description' => t('Управление группами пользователей.'),
        );
    }

    if ($user->hasGroup('Content Managers'))
      $icons[] = array(
        'group' => 'content',
        'img' => 'img/files.png',
        'href' => '/admin/?mode=list&preset=files',
        'title' => t('Файлы'),
        'description' => t('Просмотр, редактирование и добавление файлов.'),
        );

    if ($user->hasGroup('Content Managers') and Node::count(array('deleted' => 1, '-class' => TypeNode::getInternal())))
      $icons[] = array(
        'group' => 'content',
        'img' => 'img/recycle.png',
        'href' => '/admin/?mode=list&preset=trash',
        'title' => t('Корзина'),
        'description' => t('Просмотр и восстановление удалённых файлов.'),
        'weight' => 10,
        );

    return $icons;
  }
};
