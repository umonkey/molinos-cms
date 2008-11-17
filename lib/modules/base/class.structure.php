<?php
/**
 * Класс для работы со структурой сайта.
 *
 * Пример использования:
 *
 * $s = new Structure();
 * $p = $s->findPage($_SERVER['HTTP_HOST'], $_GET['q']);
 *
 * Возвращает FALSE, если подходящая страница не найдена, или
 * массив с ключами: (string)name, (array)page, (array)args.
 *
 * Justin Forest, 2008-11-15.
 */

class Structure
{
  private static $instance = null;

  protected $widgets = array();
  protected $aliases = array();
  protected $domains = array();

  public static function getInstance()
  {
    if (null === self::$instance)
      self::$instance = new Structure();
    return self::$instance;
  }

  public function __construct()
  {
    $this->load();
  }

  private function load()
  {
    if (null !== ($xml = $this->getXML())) {
      $this->parseWidgets($xml);
      $this->parseAliases($xml);
      $this->parsePages($xml);
    } else {
      $ma = new StructureMA();

      if (is_array($data = $ma->import())) {
        foreach (array('widgets', 'aliases', 'domains') as $k)
          if (array_key_exists($k, $data))
            $this->$k = $data[$k];

        $this->save();
      } else {
        throw new RuntimeException(t('Не удалось прочитать структуру сайта из XML файла.'));
      }
    }
  }

  private function parseWidgets(SimpleXMLElement $xml)
  {
    foreach ($xml->xpath('/config/widgets/widget') as $em) {
      $w = array();

      // Basic attributes.
      foreach ($em->attributes() as $k => $v)
        $w[$k] = strval($v);

      // Parameters.
      foreach ($em->xpath('param') as $p) {
        $a = $p->attributes();

        $name = strval($a['name']);

        if ('list' == strval($a['type']))
          $value = explode(',', strval($a['value']));
        else
          $value = strval($a['value']);

        $w['params'][$name] = is_numeric($value)
          ? intval($value)
          : $value;
      }

      // Access.
      foreach ($em->xpath('access') as $p) {
        $a = $p->attributes();
        $w['groups'][] = intval($a['gid']);
      }

      $k = $w['name'];
      unset($w['name']);

      $this->widgets[$k] = $w;
    }
  }

  private function parseAliases(SimpleXMLElement $xml)
  {
    foreach ($xml->xpath('/config/aliases/alias') as $alias) {
      $a = array();

      foreach ($alias->attributes() as $k => $v)
        $a[strval($k)] = strval($v);

      $source = $a['source'];
      unset($a['source']);

      $this->aliases[$source] = $a;
    }
  }

  private function parsePages(SimpleXMLElement $xml)
  {
    foreach ($xml->xpath('/config/domains/domain') as $domain) {
      $dname = $this->getElementAttributes($domain, 'name');

      foreach ($domain->xpath('page') as $page) {
        $p = $this->getElementAttributes($page);

        foreach ($page->xpath('region') as $region) {
          $rname = $this->getElementAttributes($region, 'name');

          foreach ($region->xpath('widget') as $widget)
            $p['widgets'][$rname][] = $this->getElementAttributes($widget, 'name');
        }

        $pname = $p['name'];
        unset($p['name']);

        $this->domains[$dname][$pname] = $p;
      }
    }
  }

  /**
   * Возвращает либо все атрибуты элемента в виде массива, либо один конкретный.
   */
  private function getElementAttributes(SimpleXMLElement $em, $key = null)
  {
    $a = array();

    foreach ($em->attributes() as $k => $v)
      $a[strval($k)] = strval($v);

    return (null === $key)
      ? $a
      : $a[$key];
  }

  /**
   * Возвращает SimpleXMLElement, содержащий конфигурацию из XML файла.
   */
  private function getXML()
  {
    if (file_exists($path = $this->getXMLName()))
      return new SimpleXMLElement(file_get_contents($path));

    return null;
  }

  /**
   * Возвращает имя файла, содержащего конфигурацию в XML.
   */
  protected function getXMLName()
  {
    return substr(mcms::config('fullpath'), 0, -4) . '.xml';
  }

  /**
   * Возвращает информацию о подходящей странице.
   */
  public function findPage($domain, $path)
  {
    if (null === ($domain = $this->findDomain($domain)))
      return false;

    $path = '/' . rtrim($path, '/');
    $args = array();

    $match = '/';

    foreach ($this->domains[$domain] as $page => $meta) {
      if (strlen($page) > strlen($match)) {
        // Точное совпадение.
        if ($path == $page) {
          $match = $page;
          break;
        }

        if (0 === strpos($path, $page)) {
          if ('/' == substr($path, strlen($page), 1)) {
            $match = $page;
            $args = explode('/', trim(substr($path, strlen($page) + 1), '/'));
          }
        }
      }
    }

    $result = array(
      'name' => ('/' == $match)
        ? 'index'
        : str_replace('/', '-', ltrim($match, '/')),
      'page' => $this->domains[$domain][$match],
      'args' => $args,
      );

    if (false === ($result['args'] = $this->findPageParameters($result['page'], $args)))
      return false;

    return $result;
  }

  /**
   * Возвращает параметры страницы в виде массива или false в случае ошибки.
   */
  private function findPageParameters(array $page, array $path_args)
  {
    $keys = explode('+', $page['params']);

    // Параметров в урле больше, чем должно быть => мусор => 404.
    if (count($path_args) > count($keys))
      return false;

    $result = array();

    foreach ($keys as $k => $v)
      $result[$v] = isset($path_args[$k])
        ? $path_args[$k]
        : null;

    return $result;
  }

  /**
   * Возвращает домен с учётом алиасов.
   */
  private function findDomain($host)
  {
    if (array_key_exists($host, $this->aliases))
      return $this->aliases[$host]['target'];

    if (array_key_exists($host, $this->domains))
      return $host;

    if (!empty($this->domains))
      return array_shift(array_keys($this->domains));

    return null;
  }

  /**
   * Возвращает информацию об указанных виджетах.
   */
  public function findWidgets(array $names)
  {
    $result = array();

    foreach ($names as $name)
      if (array_key_exists($name, $this->widgets))
        $result[$name] = $this->widgets[$name];

    return $result;
  }

  /**
   * Запись структуры в XML файл.
   */
  public function save()
  {
    $result = "<?xml version='1.0' encoding='utf-8'?>\n";
    $result .= "<config>\n";
    $result .= $this->dumpWidgets();
    $result .= $this->dumpAliases();
    $result .= $this->dumpDomains();
    $result .= "</config>\n";

    file_put_contents($this->getXMLName(), $result);
  }

  private function dumpWidgets()
  {
    $result = "  <widgets>\n";

    foreach ($this->widgets as $k => $v) {
      $attrs = array(
        'name' => $k,
        'title' => $v['title'],
        'class' => $v['class'],
        );

      $result .= "    <widget" . mcms::htmlattrs($attrs) . ">\n";

      if (!empty($v['config'])) {
        foreach ($v['config'] as $k => $v) {
          if (!empty($v)) {
            $a = array(
              'name' => $k,
              'value' => $v,
              );

            if (is_array($v)) {
              $a['value'] = join(',', $v);
              $a['type'] = 'list';
            }

            $result .= "      " . mcms::html('param', $a) . "\n";
          }
        }
      }

      $result .= "    </widget>\n";
    }

    $result .= "  </widgets>\n";

    return $result;
  }

  private function dumpAliases()
  {
    $result = "  <aliases>\n";

    foreach ($this->aliases as $k => $v) {
      $v = array('source' => $k) + $v;
      $result .= "    " . mcms::html('alias', $v) . "\n";
    }

    $result .= "  </aliases>\n";

    return $result;
  }

  private function dumpDomains()
  {
    $result = "  <domains>\n";

    foreach ($this->domains as $domain => $pages) {
      $result .= "    <domain" . mcms::htmlattrs(array(
        'name' => $domain,
        )) . ">\n";

      foreach ($pages as $name => $data) {
        $a = array('name' => $name) + $data;
        unset($a['widgets']);

        $result .= "      <page" . mcms::htmlattrs($a) . ">\n";

        if (!empty($data['widgets'])) {
          foreach ($data['widgets'] as $region => $widgets) {
            $result .= "        <region" . mcms::htmlattrs(array('name' => $region)) . ">\n";

            foreach ($widgets as $w)
              $result .= "          " . mcms::html('widget', array('name' => $w)) . "\n";

            $result .= "        </region>\n";
          }
        }

        $result .= "      </page>\n";
      }

      $result .= "    </domain>\n";
    }

    $result .= "  </domains>\n";

    return $result;
  }
}
