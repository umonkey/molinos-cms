<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

interface iDashboard
{
  // Возвращает массив элементов с ключами: img, href, title.
  public static function getDashboardIcons();
};

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
    $tmp = bebop_split_url();
    if ('/admin/' == $tmp['path'])
      bebop_redirect('/admin/content/?cgroup=content');

    $options = array_merge(parent::getRequestOptions($ctx), array(
      'groups' => $this->user->getGroups(true),
      'cgroup' => empty($_GET['cgroup']) ? 'content' : $_GET['cgroup'],
      '#nocache' => true,
      ));

    if (empty($options['cgroup']))
      $options['cgroup'] = 'content';

    return $this->options = $options;
  }

  // Обработка запросов.  Возвращает список действий, предоставляемых административными виджетами.
  public function onGet(array $options)
  {
    if (is_array($result = $this->getIcons()) and !empty($result))
      return $this->getHTML($result);
  }

  private function getIcons()
  {
    $cache = BebopCache::getInstance();
    $key = 'dashboard:'. $this->options['cgroup'];

    if (false and is_array($result = $cache->$key))
      return $result;

    $result = array();

    foreach (bebop_get_module_map() as $module => $info) {
      if (!empty($info['interface']['iDashboard'])) {
        foreach ($info['interface']['iDashboard'] as $class) {
          if (class_exists($class) and is_array($items = call_user_func(array($class, 'getDashboardIcons')))) {
            foreach ($items as $item) {
              if (isset($item['img'])) {
                if (!file_exists($img = 'lib/modules/'. $module .'/'. $item['img']))
                  unset($item['img']);
                else
                  $item['img'] = '/'. $img;
              }

              $item['module'] = $module;

              if (empty($item['group']))
                $group = t('Misc');
              else {
                $group = $item['group'];
                unset($item['group']);
              }

              $result[$group][] = $item;
            }
          }
        }
      }
    }

    if (empty($result))
      return null;

    ksort($result);

    $cache->$key = $result;

    return $result;
  }

  public function getHTML(array $menu)
  {
    $cgroup = $this->options['cgroup'];

    $trans = array(
      'access' => t('Доступ'),
      'content' => t('Наполнение'),
      'developement' => t('Разработка'),
      'statistics' => t('Статистика'),
      'structure' => t('Структура'),
      );

    $output = '<ul>';

    foreach ($menu as $group => $icons) {
      $group = strtolower($group);

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
        $url = bebop_split_url($icon['href']);
        $url['args']['cgroup'] = $group;

        $tmp = mcms::html('a', array(
          'href' => bebop_combine_url($url, false),
          'title' => empty($icon['description']) ? null : $icon['description'],
          ), $icon['title']);
        $output .= mcms::html('li', array(), $tmp);
      }

      $output .= '</ul></li>';
    }

    $output .= '</ul>';

    BebopCache::getInstance()->{'adminmenu:'. $cgroup} = $output;

    return $output;
  }

  private function usort(array $a, array $b)
  {
    if (0 !== ($tmp = $a['weight'] - $b['weight']))
      return $tmp;

    return strcmp($a['title'], $b['title']);
  }
};
