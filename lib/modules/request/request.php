<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class UserErrorException extends Exception
{
  var $description = null;
  var $note = null;

  public function __construct($message, $code, $description = null, $note = null)
  {
    parent::__construct($message, $code);
    $this->description = $description;
    $this->note = $note;
  }

  public function getDescription()
  {
    return $this->description;
  }

  public function getNote()
  {
    return $this->note;
  }
};

class PageNotFoundException extends UserErrorException
{
  public function __construct($page = null, $description = null)
  {
    $description .= 'Попробуйте поискать требуемую информацию на <a href="/">главной странице</a> сайта.';

    parent::__construct(
      'Страница не найдена',
      404,
      'Такой <span class="highlight">страницы нет</span> на этом сайте',
      $description);
  }
};

class UnauthorizedException extends UserErrorException
{
  public function __construct($message = null)
  {
    if (empty($message))
      $message = 'Нет доступа';
    parent::__construct($message, 401, 'В доступе отказано', 'У вас недостаточно прав для обращения к этой странице.&nbsp; Попробуйте представиться системе.');
  }
}

class ForbiddenException extends UserErrorException
{
  public function __construct($description = null)
  {
    if (null === $description)
      $description = t("Ваших полномочий недостаточно для выполнения запрошенной операции.");
    parent::__construct(t("Нет доступа"), 403, $description);
  }
};

class NotInstalledException extends UserErrorException
{
  public function __construct($message = null)
  {
    if ($message === null) {
      $message = count(mcms::db()->getResults("SHOW TABLES LIKE 'node%'"))
        ? "Отсутствует как минимум одна жизненно важная таблица.&nbsp; Это больше всего похоже на то, что &laquo;Molinos.CMS&raquo; не была установлена.&nbsp; Если этот сайт ранее был работоспособен, обратитесь к его администратору за консультацией (возможно, произошло повреждение базы данных или обновления были применены некорректно).&nbsp; Возможно также, что этот сайт был только что проинсталлирован."
        : "Таблицы, используемые &laquo;Molinos.CMS&raquo;, в базе данных отсутствуют.&nbsp; Скорее всего, архив с кодом CMS был развёрнут в каталоге сайта, но инсталляционный скрипт запущен не был.&nbsp; Обратитесь к администратору сайта за объяснением, или, если попробуйте <a href='/install.php?destination=". urlencode($_SERVER['REQUEST_URI']) ."'>запустить инсталляционный скрипт</a> через веб-интерфейс.";
    }

    parent::__construct("Фатальная ошибка", 500, "Система не готова к использованию", $message);
  }
};

// Контекст, в котором выполняется запрос.  Содержит всю параметризацию.
class RequestContext
{
  // Элементы пути, ведущие к текущей странице.
  private $ppath;

  // Дополнительные элементы пути.
  private $apath;

  // Относящиеся к виджету параметры и файлы.
  private $get = null;
  private $post = null;
  private $files = null;

  // Текущий раздел и документ.
  private $section = null;
  private $document = null;

  // Основной раздел для страницы.
  private $root = null;

  // Сохраняем глобальный контекст.
  private static $global = null;

  // Запрещаем создавать объекты напрямую.
  private function __construct()
  {
  }

  // Разгребаем параметры, подставляя текущие значения из пути
  // в локальные свойства $section и $document.
  private function setObjectIds(Node $page)
  {
    switch ($page->params) {
    case 'sec+doc':
      $this->document = empty($this->apath[1]) ? null : $this->apath[1];

    case 'sec':
      if (!empty($page->defaultsection) and is_numeric($page->defaultsection))
        $this->root = $page->defaultsection;

      if (null === ($this->section = empty($this->apath[0]) ? null : $this->apath[0]))
        $this->section = $this->root;

      break;

    case 'doc':
      $this->document = empty($this->apath[0]) ? null : $this->apath[0];
      break;
    }

    // Нормализуем идентификаторы.

    if (null !== $this->document and !is_numeric($this->document))
      if (null === ($this->document = mcms::db()->getResult("SELECT `id` FROM `node` WHERE `code` = :code", array(':code' => $this->document))))
          throw new PageNotFoundException();

    if (null !== $this->section and !is_numeric($this->section))
      if (null === ($this->section = mcms::db()->getResult("SELECT `id` FROM `node` WHERE `code` = :code", array(':code' => $this->section))))
        throw new PageNotFoundException();

    // Запишем информацию о запросе.
    if (mcms::config('log_requests')) {
      try {
        if (null !== $this->document)
          self::logNodeAccess($this->document);
        if (null !== $this->section)
          self::logNodeAccess($this->section);
      } catch (PDOException $e) {
        if ('23000' != $e->getCode())
          throw $e;
      }
    }
  }

  public static function logNodeAccess($nid)
  {
    $params = array(
      'nid' => $nid,
      'ip' => $_SERVER['REMOTE_ADDR'],
      'referer' => empty($_SERVER['HTTP_REFERER']) ? null : $_SERVER['HTTP_REFERER'],
      );

    mcms::db()->exec("INSERT INTO `node__astat` (`timestamp`, `nid`, `ip`, `referer`) "
      ."VALUES (UTC_TIMESTAMP(), :nid, :ip, :referer)", $params);
  }

  // Запрещаем изменять свойства извне.
  public function __set($key, $value)
  {
    throw new InvalidArgumentException("RequestContext is a read-only object.");
  }

  public function __get($key)
  {
    switch ($key) {
      case 'ppath':
      case 'apath':
      case 'get':
      case 'post':
      case 'files':
        if ($this->$key === null)
          return array();
        return $this->$key;

      case 'root':
        return empty($this->root) ? null : $this->root;

      case 'section':
      case 'document':
        $node = &$this->$key;

        if ($node === null)
          return null;
        elseif (is_object($node))
          return $this->$key;
        else {
          $tmp = Node::load($node);

          if ($tmp->class == 'tag' and $key != 'section')
            return null;

          return $tmp;
        }

      case 'section_id':
        return $this->section;

      case 'document_id':
        return $this->document;

      default:
        throw new InvalidArgumentException("Unknown key for RequestContext: ". $key);
    }
  }

  // Упрощённое обращение к параметрам $_GET.
  public function get($key, $default = null)
  {
    if ($this->get !== null and array_key_exists($key, $this->get))
      return $this->get[$key];
    return $default;
  }

  // Упрощённое обращение к параметрам $_POST.
  public function post($key, $default = null)
  {
    if ($this->post !== null and array_key_exists($key, $this->post))
      return $this->post[$key];
    return $default;
  }

  // Формирует глобальный контекст.
  public static function setGlobal(array $ppath, array $apath, Node $page)
  {
    if (self::$global !== null)
      throw new InvalidArgumentException("There is a global request context already.");

    $ctx = new RequestContext();

    $ctx->ppath = $ppath;
    $ctx->apath = $apath;
    $ctx->setObjectIds($page);

    self::$global = $ctx;
  }

  // Возвращает текущий контекст.
  public static function getGlobal()
  {
    if (self::$global === null)
      throw new InvalidArgumentException("There is no global request context yet.");

    return self::$global;
  }

  // Формирует контекст для виджета, из глобального.
  public static function getWidget(array $get, array $post, array $files)
  {
    $ctx = clone(self::getGlobal());

    $ctx->get = $get;
    $ctx->post = $post;
    $ctx->files = $files;

    return $ctx;
  }
};

class RequestController
{
  private $page = null;
  private $widgets = array();
  private $begin = null;

  private $get_vars = array();
  private $post_vars = array();
  private $file_vars = array();

  private $context = null;

  private $root = null;
  private static $killfiles = null;

  public function __construct()
  {
    ob_start();

    self::checkServerSettings();

    set_error_handler('RequestController::errorHandler', substr(BEBOP_VERSION, 0, 6) == 'trunk.' ? E_ALL : E_ERROR|E_WARNING|E_PARSE);

    if (!empty($_SERVER['REQUEST_METHOD'])) {
      // Куки на неделю.
      $session_lifetime = 3600 * 24 * 7;
      ini_set('session.cookie_lifetime', $session_lifetime);
      ini_set('session.gc_maxlifetime', $session_lifetime);
      ini_set('session.cache_expire', $session_lifetime);
      ini_set('session.cookie_domain', mcms::config('basedomain'));

      bebop_session_start();

      if ($_SERVER['REQUEST_METHOD'] != 'POST' and !empty($_SESSION['user']['systemgroups']) and (in_array('CMS Developers', $_SESSION['user']['systemgroups']) or bebop_is_debugger()))
        ini_set('display_errors', 1);

      bebop_session_end();
    }

    $this->run();
  }

  public function __destruct()
  {
    $this->cleanFiles();
  }

  private function run()
  {
    $pdo = null;

    try {
      if (!BebopConfig::getInstance()->isok())
        throw new NotInstalledException("Не удалось найти конфигурационный файл.&nbsp; Скорее всего, CMS ещё не была проинсталлирована.&nbsp; Вы можете <a href='/install.php'>запустить процесс инсталляции</a> прямо сейчас.");

      $this->begin = microtime(true);

      if (null !== ($tmp = $this->parseSpecialPath()))
        return $tmp;

      $this->parseGet();

      if ($_SERVER['REQUEST_METHOD'] == 'POST')
        $this->parsePost();

      $this->parsePath();

      switch ($_SERVER["REQUEST_METHOD"]) {
      case 'GET':
        $this->runGet();

        break;

      case 'POST':
        $this->parseFiles();
        $this->runPost();
        break;

      default:
        throw new UserErrorException("Метод не поддерживается", 405, "Метод {$_SERVER['REQUEST_METHOD']} не поддерживается", "Вы послали запрос, который сервер обработать не может.");
        break;
      }
    } catch (Exception $e) {
      if ($e instanceof UserErrorException)
        $message = $e->getDescription();
      else
        $message = $e->getMessage();

      if (bebop_is_debugger())
        $message = get_class($e) .': '. $message;

      bebop_on_json(array('message' => $message));

      if ($this->renderError($e))
        return;
      throw $e;
    }

    // Сбрасываем кэш, если были запросы.
    mcms::flush(mcms::FLUSH_NOW);

    PDO_Singleton::disconnect();
  }

  // Возвращает нагенерированный обработчиками контент.
  public function getContent()
  {
    return ob_get_clean();
  }

  private function parsePath()
  {
    // Очистка кэша.
    if (!empty($_GET['flush'])) {
      if (bebop_is_debugger()) {
        DBCache::getInstance()->flush(true);
      }

      exit(bebop_redirect(bebop_combine_url(bebop_split_url(), false), 301, false));
    }

    if (mcms::config('path_aliases')) {
      if (null !== ($next = mcms::db()->getResult("SELECT `dst` FROM `node__path` WHERE `src` = :src", array(':src' => $_SERVER['REQUEST_URI']))))
        bebop_redirect($next, 301);
    }

    // Запрашиваем структуру домена.  Если запрошен несуществующий
    // домен, исключение будет брошено автоматически.
    try {
      $map = self::getUrlsForDomain($_SERVER['HTTP_HOST']);
    } catch (PDOException $e) {
      if ($e->getCode() == '42S02')
        throw new NotInstalledException();
      else
        throw $e;
    }

    // Убедимся, что на конце урла есть слэш.
    $req = bebop_split_url();
    if (substr($req['path'], -1) != '/') {
      $req['path'] .= '/';
      exit(bebop_redirect(bebop_combine_url($req)));
    }

    // Начинаем поиск отсюда.
    $this->root = $root = $map;

    // Текущий путь, будем вырезать из него фрагменты по мере обработки.
    $apath = ($req['path'] == '/') ? array() : explode('/', trim($req['path'], '/'));

    // Разобранные элементы пути.
    $ppath = array();

    // Здесь будет последний активный путь.
    $last_active = null;

    // Продвигаемся вглубь урлов, пока не закончится путь.
    while (!empty($apath)) {
      $current = array_shift($apath);

      if (!empty($root['children'])) {
        foreach ($root['children'] as $url) {
          if ($url['name'] == $current) {
            $root = $url;
            $ppath[] = $current;
            $current = null;
          }
        }
      }

      // Если подходящий элемент не был найден -- прекращаем поиск.
      if ($current !== null) {
        array_unshift($apath, $current);
        break;
      }
    }

    // Запомним базовый адрес страницы.
    $root['base'] = rtrim("http://{$_SERVER['HTTP_HOST']}/". ltrim(join('/', $ppath), '/'), '/') .'/';

    // Сохраним информацию о текущей странице.  Список детей не очищаем, если
    // вдруг шаблону взбредёт в голову его использовать -- на здоровье.
    $this->page = Node::create($root['class'], $root);

    // Сохраняем контекст, для виджетов.
    RequestContext::setGlobal($ppath, $apath, $this->page);

    // Если запрошен отдельный виджет, загружаем его.
    if (!empty($_GET['widget'])) {
      try {
        $widgets = array(Node::load(array('class' => 'widget', 'name' => $_GET['widget'])));
      } catch (ObjectNotFoundException $e) {
        throw new UserErrorException("Виджет не найден", 404, "Виджет не найден", "Запрошенного виджета у нас нет.");
      }
    }

    // Загружаем все виджеты для страницы.
    else {
      $key = "page:{$this->page->id}:widgets";

      $widgets = mcms::cache($key);
      if ($widgets === false or (bebop_is_debugger() and !empty($_GET['flush']))) {
        $widgets = Node::find(array('class' => 'widget', 'id' => $this->page->linkListChildren('widget', true)));
        mcms::cache($key, $widgets);
      }
    }

    // Обрабатываем виджеты, превращая их в контроллеры.
    foreach ($widgets as $widget) {
      if (!class_exists($class = $widget->classname))
        continue;

      $obj = new $class($widget);

      // Обрабатываем только виджеты, остальной мусор, если он сюда
      // как-то попал, пропускаем, чтобы не получить исключение.
      if (!($obj instanceof Widget))
        continue;

      // Параметризация виджета.
      $ctx = RequestContext::getWidget(
        array_key_exists($widget->name, $this->get_vars) ? $this->get_vars[$widget->name] : array(),
        array_key_exists($widget->name, $this->post_vars) ? $this->post_vars[$widget->name] : array(),
        array_key_exists($widget->name, $this->file_vars) ? $this->file_vars[$widget->name] : array()
        );

      try {
        $this->widgets[$widget->name] = array(
          'cache_key' => $obj->getCacheKey($ctx),
          'options' => $obj->getRequestOptions($ctx),
          'object' => $obj,
          );
      } catch (WidgetHaltedException $e) { }
    }

    // Готовимся к обработке запроса.
    // $this->page->prepareSmarty(null);
  }

  private function parseSpecialPath()
  {
    $tmp = parse_url($_SERVER['REQUEST_URI']);

    if (!empty($tmp['path']) and preg_match_all('@^/playlist/[0-9,]+\.xspf$@i', $tmp['path'], $m)) {
      $nids = array_unique(explode(',', substr($tmp['path'], 10, -5)));
      print mcms::mediaGetPlaylist($nids);
      return true;
    }
  }

  // Разбивает параметры GET-запроса на модули.
  private function parseGet()
  {
    $this->get_vars = array();

    $urlinfo = bebop_split_url();

    foreach ($urlinfo['args'] as $k => $v) {
      if (is_array($v))
        $this->get_vars[$k] = $v;
    }
  }

  private function parsePost()
  {
    $this->post_vars = array();

    foreach ($_POST as $k => $v) {
      $point = strpos($k, '_', 1);
      if ($point !== false) {
        $module_name = substr($k, 0, $point);
        $var_name = substr($k, $point + 1);

        if (!array_key_exists($module_name, $this->post_vars))
          $this->post_vars[$module_name] = array();

        $this->post_vars[$module_name][$var_name] = $v;
      }
    }
  }

  private function parseFiles()
  {
    $this->file_vars = array();

    foreach ($_FILES as $k => $v) {
      $point = strpos($k, '_', 1);

      if ($point !== false) {
        $module_name = substr($k, 0, $point);
        $var_name = substr($k, $point + 1);

        if (!array_key_exists($module_name, $this->file_vars))
          $this->file_vars[$module_name] = array();

        $this->file_vars[$module_name][$var_name] = array();

        if (is_array($v['error'])) {
          foreach ($v['error'] as $index => $error) {
            if ($error > UPLOAD_ERR_OK) {
              // место для первичной обработки ошибок
            }

            if (!is_uploaded_file($v['tmp_name'][$index])) {
              // возможно хакерская атака!
              // место для обработки ошибки
              continue;
            }

            $arr = array(
              'name' => $v['name'][$index],
              'type' => $v['type'][$index],
              'size' => $v['size'][$index],
              'tmp_name' => $v['tmp_name'][$index],
              'error' => $error
            );
            $this->file_vars[$module_name][$var_name][] = $arr;
          }
        } else {
          if ($v['error'] > UPLOAD_ERR_OK) {
            // место для первичной обработки ошибок
          }

          if (!is_uploaded_file($v['tmp_name'])) {
            // возможно хакерская атака!
            // место для обработки ошибки
            continue;
          }

          $this->file_vars[$module_name][$var_name][] = $v;
        }
      }
    }
  }

  private function cleanFiles()
  {
    foreach ($this->file_vars as $module => $files) {
      foreach ($files as $group) {
        foreach ($group as $file) {
          if (file_exists($file['tmp_name'])) {
            unlink($file['tmp_name']);
          }
        }
      }
    }

    if (is_array(self::$killfiles)) {
      foreach (self::$killfiles as $f) {
        if (file_exists($f) and is_writable($dir = dirname($f)))
          unlink($f);
      }
    }
  }

  public static function killfile($filename)
  {
    self::$killfiles[] = $filename;
  }

  private function runGet()
  {
    $pdo = mcms::db();

    // Сюда складываем время выполнения виджетов.
    $profile = array('__total' => microtime(true));

    // Сюда будем складывать блоки для смарти.
    $blocks = array('widgets' => array());

    // Эта штука будет нас кэшировать.
    $cache = new DBCache($this->page->language);

    // Загружаем закэшированные виджеты.
    if (empty($_GET['widget']))
      $this->getCachedWidgets($cache, $blocks, $profile);

    // Обрабатываем оставшиеся виджеты.
    foreach ($this->widgets as $name => $info) {
      $time = microtime(true);

      if (bebop_is_debugger() and !empty($_GET['profile']))
        $pdo->log("--- {$name}.onGet() ---");

      $pdo->beginTransaction();

      try {
        $data = $info['object']->onGet($info['options']);
        $pdo->commit();
      } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
      }

      $blocks[$name] = $data;

      // Пытаемся отрендерить.
      if (is_string($data))
        $blocks['widgets'][$name] = $data;

      elseif (!empty($data['html']) and is_string($data['html'])) {
        $blocks['widgets'][$name] = $data['html'];
        $data = $data['html'];
      }

      elseif (($html = $info['object']->render($this->page, $data)) !== null) {
        $blocks['widgets'][$name] = $html;
        $data = $html;
      }

      // Кэшируем, если можно.
      if (!empty($info['cache_key']) and (empty($_GET['nocache']) or !bebop_is_debugger())) {
        $cache->$info['cache_key'] = $data;
      }

      $profile[$name] = microtime(true) - $time;
    }

    if (!empty($_GET['widget'])) {
      if (!array_key_exists($_GET['widget'], $blocks['widgets'])) {
        if (bebop_is_json() and array_key_exists($_GET['widget'], $blocks) and !empty($blocks[$_GET['widget']]))
          bebop_on_json($blocks[$_GET['widget']]);

        bebop_debug("Widget {$_GET['widget']} not found.", $blocks);
        throw new PageNotFoundException();
      }

      $data = $blocks['widgets'][$_GET['widget']];
      header('Content-Type: text/html; charset=utf-8');
      header('Content-Length: '. strlen($data));
      die($data);
    }

    // Рендерим страницу.
    $time = microtime(true);
    $output = $this->page->renderSmarty($blocks);
    $profile['__smarty'] = microtime(true) - $time;

    if (!empty($_GET['widget_debug']))
      bebop_debug($blocks);

    $profile['__total'] = microtime(true) - $profile['__total'];
    $profile['__request'] = microtime(true) - $this->begin;

    if (!empty($_GET['profile']) and bebop_is_debugger()) {
      $this->printProfileData($profile);
      exit();
    }

    $output = str_replace('$execution_time', $profile['__request'], $output);

    print $output;
  }

  private function printProfileData(array $data = null)
  {
    header('Content-Type: text/html; charset=utf-8');

    $link = bebop_split_url();
    $link['args']['profile'] = null;
    $url = bebop_combine_url($link);

    print "<html><head><title>Profile Info &mdash; Molinos.CMS</title><style type='text/css'>body { font-family: monospace } td { padding-right: 10px } li { margin-bottom: .5em }</style></head><body><p><em>You see this information because your IP address is added to the configuration file.</em></p>";
    print "<p><a href='". htmlspecialchars($url) ."'>Disable profiling</a>.</p>";

    if ($data !== null) {
      asort($data);
      print "<h1>Widget Timing</h1><table><tr><th>Widget</th><th>Time</th><th>Cache</th></tr>";
      foreach ($data as $k => $v) {
        if (substr($k, 0, 2) == '__')
          continue;
        print "<tr><td>{$k}</td><td style='text-align: left'>{$v}</td><td>no</td></tr>";
      }
      print "<tr style='background-color: #ddd'><td>smarty</td><td style='text-align: left'>{$data['__smarty']}</td><td>&nbsp;</td></tr>";
      print "<tr style='background-color: #ddd'><td>page total</td><td style='text-align: left'>{$data['__total']}</td><td>&nbsp;</td></tr>";
      print "<tr style='background-color: #ddd'><td>request total</td><td style='text-align: left'>{$data['__request']}</td><td>&nbsp;</td></tr>";
      print "<tr style='background-color: #ddd'><td>SQL queries</td><td style='text-align: left'>". mcms::db()->getLogSize() ."</td><td>&nbsp;</td></tr>";
      print "</table>";
    }

    $log = mcms::db()->getLog();
    if (!empty($log)) {
      print "<h1>SQL Query Log</h1><ol>";
      foreach ($log as $query)
        print "<li>". mcms_plain($query) ."</li>";
      print "</ol>";
    }

    print "</body></html>";
  }

  private function runPost()
  {
    $redirect = null;

    // Проверяем, есть ли у нас обработчик формы.
    if (array_key_exists('form_handler', $_POST))
      if (!array_key_exists($_POST['form_handler'], $this->widgets))
        throw new PageNotFoundException(t("Обработчик формы (%widget) не найден.", array('%widget' => $_POST['form_handler'])));
      elseif (empty($_POST['form_id']))
        throw new InvalidArgumentException(t("Не указан идентификатор формы."));
      else
        $form_handler = $_POST['form_handler'];
    else
      $form_handler = null;

    $pdo = mcms::db();

    foreach ($this->widgets as $name => $info) {
      $pdo->log("--- {$name}.onGet() ---");

      $post = empty($this->post_vars[$name]) ? array() : $this->post_vars[$name];
      $files = empty($this->file_vars[$name]) ? array() : $this->file_vars[$name];

      try {
        $res = null;
        $this->decodeInputButtons($post);

        $pdo->beginTransaction();

        if ($name == $form_handler) {
          if (null !== ($form = $info['object']->formGet($_POST['form_id']))) {
            if ($form->validate($_POST)) {
              $data = $_POST;

              unset($data['form_id']);
              unset($data['form_handler']);

              foreach ($_FILES as $field => $fileinfo) {
                if (is_array($fileinfo['name'])) {
                  foreach (array_keys($fileinfo) as $key) {
                    foreach ($fileinfo[$key] as $k => $v)
                      $data[$field][$k][$key] = $v;
                  }
                }

                else {
                  foreach ($fileinfo as $k => $v)
                    $data[$field][$k] = $v;
                }
              }

              $res = $info['object']->formProcess($_POST['form_id'], $data);
            } else {
              throw new InvalidArgumentException(t("Form did not validate."));
            }
          }
        } else {
          $res = $info['object']->onPost($info['options'], $post, $files);
        }

        if (!empty($res) and is_string($res))
          $redirect = $res;
        $pdo->commit();
      } catch (WidgetHaltedException $e) {
        $pdo->rollback();
      } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
      }
    }

    $this->cleanFiles();

    if (is_array($res) and array_key_exists('redirect', $res)) {
      $redirect = $res['redirect'];
    } elseif (is_array($res)) {
      bebop_on_json($res);
    }

    if (empty($redirect)) {
      if (!empty($_GET['destination']))
        $redirect = $_GET['destination'];
      elseif (!empty($_SERVER['HTTP_REFERER']))
        $redirect = $_SERVER['HTTP_REFERER'];
      else
        $redirect = $_SERVER['REQUEST_URI'];
    }

    // bebop_debug($redirect);

    if (bebop_is_debugger() and !empty($_GET['postprofile']))
      exit($this->printProfileData());

    bebop_redirect($redirect);
  }

  // Декодирование кнопок.  Проблема в том, что значение элемента <input> -- его текст,
  // который и приходит на сервер при нажатии на кнопку.  Этот текст, как правило,
  // локализован, и приходит на непонятном языке.  Чтобы эту проблему обойти, мы
  // добавляем к каждой кнопке скрытый элемент (hidden), формирующий масив вида:
  //
  //   текст => реальное значение
  //
  // Таким образом, при обработке формы мы можем однозначно выяснить, какая кнопка
  // была нажата пользователем, в удобном для нас виде.
  private function decodeInputButtons(array &$post)
  {
    // Декодируем кнопки.
    if (array_key_exists('button_values', $post) and is_array($post['button_values'])) {
      foreach ($post['button_values'] as $button => $values) {
        if (empty($post[$button])) {
          unset($post['button_values'][$button]);
        }

        else {
          foreach ($values as $k => $v) {
            if ($post[$button] == $k) {
              $post[$button] = $v;
            }
          }
        }
      }

      unset($post['button_values']);
    }
  }

  // Вовзаращает дерево урлов для текущего домена.
  private function getUrlsForDomain($domain)
  {
    $tree = DomainNode::getSiteMap();

    if (is_array($tree)) {
      foreach ($tree as $nid => $branch) {
        // Точное совпадение, возвращаем.
        if ($branch['name'] == $domain)
          return $branch;

        // Найден алиас -- редиректим.
        if (in_array($domain, $branch['aliases'])) {
          $url = bebop_split_url();
          $url['host'] = $branch['name'];
          exit(bebop_redirect(bebop_combine_url($url)));
        }
      }
    }

    throw new UserErrorException("Домен не найден", 404, "Домен &laquo;{$domain}&raquo; не обслуживается", "Запрошенное доменное имя не обслуживается этим сервером.&nbsp; Попробуйте повторить запрос через какое-то время.");
  }

  // Загрузка виджетов из кэша.
  private function getCachedWidgets(DBCache $cache, array &$blocks, array &$profile)
  {
    // Засекаем время.
    $time = microtime(true);

    try {
      if (empty($_GET['nocache']) or !bebop_is_debugger()) {
        // Список всех идентификаторов кэша.
        $keys = array();

        // Формируем список идентификаторов.
        foreach ($this->widgets as $k => $v) {
          if (!empty($v['cache_key']))
            $keys[] = $v['cache_key'];
        }

        // Разгребаем данные, найденные в кэше.
        foreach (mcms::db()->getResultsKV("cid", "data", "SELECT `cid`, `data` FROM `node__cache` WHERE `lang` = :lang AND `cid` IN ('". join("', '", $keys) ."') -- RequestController::getCachedWidgets()", array(':lang' => $this->page->language)) as $cid => $data) {
          $data = unserialize($data);

          foreach ($this->widgets as $name => $info) {
            // Виджет найден в кэше.  Добавляем его в отрендеренные и удаляем из списка виджетов.
            if ($info['cache_key'] == $cid) {
              // Готовый HTML код.
              if (is_string($data)) {
                $blocks['widgets'][$name] = $data;
                unset($this->widgets[$name]);
              }

              // Массив данных.
              else {
                $blocks[$name] = $data;
              }
            }
          }
        }
      }
    } catch (PDOException $e) {
      if ($e->getCode() == '42S02')
        throw new NotInstalledException();
      else
        throw $e;
    }

    // Добавляем информацию в профайлер.
    $profile['__cache'] = microtime(true) - $time;
  }

  private function renderError(Exception $e)
  {
    if (bebop_is_debugger() and mcms::config('pass_exceptions') and $e->getCode() != 401 and $e->getCode() != 403)
      return false;

    if (null !== $this->page)
      $theme = $this->page->theme;
    elseif (null !== $this->root)
      $theme = $this->root->theme;
    else
      $theme = 'all';

    $output = bebop_render_object("error", $e->getCode(), $theme, array('error' => array(
      'code' => $e->getCode(),
      'type' => get_class($e),
      'message' => $e->getMessage(),
      'description' => method_exists($e, 'getDescription') ? $e->getDescription() : 'Внутренняя ошибка',
      'note' => method_exists($e, 'getNote') ? $e->getNote() : $e->getMessage(),
      )));

    if (!is_numeric($code = $e->getCode()))
      $code = 500;

    header("HTTP/1.1 {$code} Error");
    header("Content-Type: text/html; charset=utf-8");
    header("Content-Length: ". strlen($output));
    die($output);
  }

  private function findErrorTemplate($theme, $code)
  {
    if (file_exists($fname = "htdocs/themes/{$theme}/templates/error.{$code}.tpl"))
      return $fname;
    if (file_exists($fname = "htdocs/themes/{$theme}/templates/error.default.tpl"))
      return $fname;
    return null;
  }

  private function findErrorPage($name)
  {
    // Ищем в текущем домене.
    if (!empty($this->root['children'])) {
      foreach ($this->root['children'] as $page) {
        if ($page['name'] == $name)
          return $page;
      }
    }

    return null;
  }

  private static function checkServerSettings()
  {
    $htreq = array(
      'register_globals' => 0,
      'magic_quotes_gpc' => 0,
      'magic_quotes_runtime' => 0,
      'magic_quotes_sybase' => 0,
      );

    $errors = $messages = array();

    foreach ($htreq as $k => $v) {
      ini_set($k, $v);

      if ($v != ($current = ini_get($k)))
        $errors[] = $k;
    }

    if (ini_get($k = 'session.gc_maxlifetime') < 7 * 24 * 60 * 60)
      ini_set($k, 30 * 24 * 60 * 60);

    if (!is_writable(mcms::config('tmpdir')))
      $messages[] = t('Каталог для временных файлов (<tt>%dir</tt>) закрыт для записи. Очень важно, чтобы в него можно было писать.', array('%dir' => mcms::config('tmpdir')));
    if (!is_writable(mcms::config('filestorage')))
      $messages[] = t('Каталог для загружаемых пользователями файлов (<tt>%dir</tt>) закрыт для записи. Очень важно, чтобы в него можно было писать.', array('%dir' => mcms::config('filestorage')));

    if (!empty($errors) or !empty($messages)) {
      $output = "<html><head><title>Setup Error</title></head><body>";

      if (!empty($errors)) {
        $output .= '<h1>'. t('Нарушение безопасности') .'</h1>';
        $output .= "<p>The following <a href='http://php.net/'>PHP</a> settings are incorrect and could not be <a href='http://php.net/ini_set'>changed</a>:</p>";
        $output .= "<table border='1'><tr><th>Setting</th><th>Value</th><th>Required</th></tr>";

        foreach ($errors as $key)
          $output .= "<tr><td>{$key}</td><td>". ini_get($key) ."</td><td>{$htreq[$key]}</td></tr>";

        $output .= "</table>";
      }

      if (!empty($messages)) {
        $output .= '<h1>'. t('Ошибка настройки') .'</h1>';
        $output .= '<ol><li>'. join('</li><li>', $messages) .'</li></ol>';
      }

      $output .= '<p>'. t('Свяжитесь с администратором вашего хостинга для исправления этих проблем.&nbsp; <a href=\'http://code.google.com/p/molinos-cms/\'>Molinos.CMS</a> на данный момент не может работать.') .'</p>';
      $output .= "</body></html>";

      header('HTTP/1.1 500 Security Error');
      header('Content-Type: text/html; charset=utf-8');
      header('Content-Length: '. strlen($output));
      die($output);
    }
  }

  // Обработчик ошибок.
  public static function errorHandler($errno, $errstr, $errfile, $errline, array $context)
  {
    if ($errno == 2048)
      return;

    if (bebop_is_debugger()) {
      $output = "\nError {$errno}: {$errstr}.\n";
      $output .= "File: {$errfile} at line {$errline}.\n";
      $output .= "\nDon't panic.  You see this message because you're listed as a debugger.\n";
      $output .= "Regular web site visitors don't see this message.\n";
      $output .= "They most likely see a White Screen Of Death. ;)\n\n";

      $output .= "--- backtrace ---\n";
      ob_start();
      debug_print_backtrace();
      $output .= ob_get_clean();

      header('Content-Type: text/plain; charset=utf-8');
      header('Content-Length: '. strlen($output));
      die($output);
    }
  }
}
