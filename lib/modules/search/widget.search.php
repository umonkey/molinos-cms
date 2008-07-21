<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SearchWidget extends Widget implements iModuleConfig, iScheduler, iNodehook
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Поиск по сайту',
      'description' => 'Контекстный морфологический поиск по сайту.',
      );
  }

  public static function formGetConfig()
  {
    $config = mcms::modconf('search');

    $form = parent::formGetConfig();

    switch ($config['engine']) {
    case 'mg':
      $form->addControl(new TextLineControl(array(
        'value' => 'config_action',
        'label' => t('Страница с результатами поиска'),
        'description' => t('По умолчанию поиск производится на текущей странице.&nbsp; Если нужно при поиске перебрасывать пользователя на другую страницу, например &mdash; /search/, введите её имя здесь.'),
        'class' => 'settings-mg',
        )));
      $form->addControl(new NumberControl(array(
        'value' => 'config_per_page',
        'label' => t('Количество результатов на странице'),
        'class' => 'settings-mg',
        )));
      $form->addControl(new TextLineControl(array(
        'value' => 'config_btngo',
        'label' => t('Текст кнопки поиска'),
        'class' => 'settings-mg',
        )));
      break;

    case 'gas':
      $form->addControl(new TextLineControl(array(
        'value' => 'config_gas_ctl',
        'label' => t('Блок с формой поиска'),
        'class' => 'settings-gas',
        'description' => t('Введите id элемента, в который нужно помещать форму поиска.'),
        )));
      $form->addControl(new TextLineControl(array(
        'value' => 'config_gas_root',
        'label' => t('Блок с результатами Google Ajax Search'),
        'class' => 'settings-gas',
        'description' => t('Введите id элемента, в который нужно помещать результаты поиска.  Обычно это — пустой div, скрытый по умолчанию.'),
        )));
      break;
    }

    return $form;
  }

  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['q'] = $ctx->get('q');
    $options['page'] = $ctx->get('page', 1);
    $options['limit'] = $this->per_page;
    $options['#nocache'] = true;

    return $options;
  }

  public function onGet(array $options)
  {
    $config = mcms::modconf('search');

    if (empty($config['engine']))
      return null;

    return $this->dispatch(array($config['engine']), $options);
  }

  protected function onGetMg(array $options)
  {
    $result = array(
      'form' => parent::formRender('search-form', array()),
      );

    if (!empty($options['q'])) {
      $result['results'] = $this->getResults($options);

      $result['pager'] = $this->getPager($result['results']['summary']['total'], $options['page'], $options['limit']);
      if ($result['pager']['pages'] < 2)
        unset($result['pager']);
    }

    return $result;
  }

  protected function onGetGas(array $options)
  {
    $config = mcms::modconf('search');

    if (empty($config['gas_key']))
      return "<!-- GAS disabled: site key not defined -->";
    elseif (empty($this->gas_ctl))
      return "<!-- GAS disabled: form parent not defined -->";
    elseif (empty($this->gas_root))
      return "<!-- GAS disabled: result container not defined -->";

    if (is_readable($filename = dirname(__FILE__) .'/gas.txt')) {
      $options = array(
        '__APIKEY' => $config['gas_key'],
        '__HOSTNAME' => $_SERVER['HTTP_HOST'],
        '__ROOT' => $this->gas_root,
        '__FORMCTL' => $this->gas_ctl,
        );

      $template = file_get_contents($filename);

      foreach ($options as $k => $v)
        $template = str_replace($k, $v, $template);

      return $template;
    }
  }

  private function getResults(array $options)
  {
    $result = array();

    $res = $this->executeSearch($options['q'], $options['page']);

    $result["summary"] = array(
        "total"         => udm_get_res_param($res, UDM_PARAM_FOUND),
        "first"         => udm_get_res_param($res, UDM_PARAM_FIRST_DOC),
        "last"          => udm_get_res_param($res, UDM_PARAM_LAST_DOC),
        "items_on_page" => udm_get_res_param($res, UDM_PARAM_NUM_ROWS),
        "time"          => udm_get_res_param($res, UDM_PARAM_SEARCHTIME),
        "info"          => udm_get_res_param($res, UDM_PARAM_WORDINFO),
        "page"          => $options['page'],
        "query"         => $options['q'],
    );

        if ($result["summary"]["items_on_page"] > 0) {
            if ($result['summary']['first'] == 1) {
                // first page
                $result['summary']['items_per_page'] = $result['summary']['items_on_page'];
                
            } else {
                $result['summary']['items_per_page'] = ($result['summary']['first'] - 1) / $result['summary']['page'];
            }

            $result["summary"]["number_of_pages"] = ceil($result["summary"]["total"] / $result["summary"]["items_per_page"]);
        } else {
            // empty set
            $result['summary']['items_per_page'] = 0;
            $result["summary"]["number_of_pages"] = 0;
        }

        $max = $result["summary"]["last"] - $result["summary"]["first"] + 1;

        $result["results"] = array();
        if ($result["summary"]["total"] > 0) {
            $udm_highlighter = array(chr(2), chr(3));
            $html_highlighter = array('<b style="color:red;">', '</b>');

            for ($i = 0; $i < $max; $i++) {
                //udm_make_excerpt($udm, $res, $i);

                $result["results"][] = array(
                    "title" => str_replace($udm_highlighter, $html_highlighter, udm_get_res_field($res, $i, UDM_FIELD_TITLE)),
                    "url" => udm_get_res_field($res, $i, UDM_FIELD_URL),
                    "type" => udm_get_res_field($res, $i, UDM_FIELD_CONTENT),
                    "date" => strtotime(udm_get_res_field($res, $i, UDM_FIELD_MODIFIED)),
                    "rating" => udm_get_res_field($res, $i, UDM_FIELD_RATING),
                    "context" => str_replace(
                        $udm_highlighter,
                        $html_highlighter,
                        html_entity_decode(udm_get_res_field($res, $i, UDM_FIELD_TEXT), ENT_QUOTES, 'UTF-8')
                    ),
                );
            }
        }

    udm_free_res($res);

    return $result;
  }

  private function executeSearch($query, $page)
  {
    if (!function_exists('udm_alloc_agent'))
      throw new UserErrorException("Поиск не работает", 500, "Поиск временно недоступен", "Функции поиска недоступны серверу, требуется вмешательство администратора сайта.");

    $config = mcms::modconf('search');

    $udm = udm_alloc_agent($config['mg_dsn']);
    if ($udm === false or $udm === null)
      throw new UserErrorException("Поиск не работает", 500, "Поиск временно недоступен", "Не удалось подключиться к серверу MnoGoSearch, требуется вмешательство администратора сайта.");

    $params = array(
      UDM_FIELD_CHARSET => 'UTF8',
      UDM_PARAM_CHARSET => 'UTF8',
      UDM_PARAM_LOCAL_CHARSET => 'UTF8',
      UDM_PARAM_BROWSER_CHARSET => 'UTF8',
      UDM_PARAM_SEARCH_MODE => UDM_MODE_ALL,
      UDM_PARAM_PAGE_SIZE => $this->per_page,
      UDM_PARAM_PAGE_NUM => $page - 1,
      UDM_PARAM_QUERY => $query,
    );

    foreach ($params as $key => $value) {
      if (udm_set_agent_param($udm, $key, $value) == false)
        throw new UserErrorException("Поиск не работает", 500, "Поиск временно недоступен", "Не удалось установить параметр {$key}, требуется вмешательство администратора сайта.&nbsp; Текст ошибки: ". udm_error($udm));
    }

    $params_ex = array(
      's' => 'RPD', // sort by rating
      'ExcerptSize' => 1024, // $this->excerpt_size
    );

    foreach ($params_ex as $key => $value) {
      if (udm_set_agent_param_ex($udm, $key, $value) == false)
        throw new UserErrorException("Поиск не работает", 500, "Поиск временно недоступен", "Не удалось установить параметр {$key}, требуется вмешательство администратора сайта.&nbsp; Текст ошибки: ". udm_error($udm));
    }

		$res = udm_add_search_limit($udm, UDM_LIMIT_URL, $_SERVER['HTTP_HOST']);

    if ($res == false)
      throw new UserErrorException("Поиск не работает", 500, "Поиск временно недоступен", "Не удалось установить привязку к домену, требуется вмешательство администратора сайта.");

    if (!empty($config['mg_ispell']) and is_dir($config['mg_ispell'])) {
      $ispell_langs = array(
        'ru' => array('utf-8', 'russian'),
        'en' => array('iso-8859-1', 'english')
      );

      $i = 0;
      foreach ($ispell_langs as $code => $data) {
        $files_path = $config['mg_ispell'] .'/'. $data[1];

        if (!file_exists($files_path.'.aff') or !file_exists($files_path.'.dict'))
          throw new InvalidArgumentException('Не удалось обнаружить файл со словарём или аффиксами для языка "'.$code.'"');

        $sort = intval(++$i == count($ispell_langs)); // сортировать нужно одновременно с добавлением последнего языка

        if (!udm_load_ispell_data($udm, UDM_ISPELL_TYPE_AFFIX, $code, $data[0], $files_path.'.aff', 0))
          throw new InvalidArgumentException('Ошибка загрузки аффикса "'.$files_path.'.aff": '.udm_error($udm));

        if (!udm_load_ispell_data($udm, UDM_ISPELL_TYPE_SPELL, $code, $data[0], $files_path.'.dict', $sort))
          throw new InvalidArgumentException('Ошибка загрузки словаря "'.$files_path.'.dict": '.udm_error($udm));
      }
    }

    $res = udm_find($udm, $query);

    if ($res === false or $res === null)
      throw new InvalidArgumentException('Ошибка поиска: '.udm_error($udm));

    return $res;
  }

  public function formGet($id)
  {
    switch ($id) {
    case 'search-form':
      $form = new Form(array(
        'action' => empty($this->action) ? null : '/'. trim($this->action, '/') .'/',
        ));

      $form->addControl(new TextLineControl(array(
        'label' => t('Что ищем'),
        'value' => 'search_string',
        )));
      $form->addControl(new SubmitControl(array(
        'text' => $this->btngo,
        )));

      return $form;
    }
  }

  public function formProcess($id, array $data)
  {
    switch ($id) {
    case 'search-form':
      $url = bebop_split_url();
      $url['args'][$this->getInstanceName()]['q'] = $data['search_string'];
      mcms::redirect($url);
    }
  }

  public static function formGetModuleConfig()
  {
    $form = new Form(array());
    $form->addClass('tabbed');

    $tab = new FieldSetControl(array(
      'name' => 'main',
      'label' => t('Режим'),
      ));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_engine',
      'label' => t('Технология поиска'),
      'options' => array(
        'gas' => t('Google Ajax Search'),
        'mg' => t('mnoGoSearch'),
        ),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'gas',
      'label' => t('Google'),
      ));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config_gas_key',
      'label' => t('Ключ Google API'),
      'description' => t('Для работы Google Ajax Search нужно <a href=\'@url\'>получить ключ</a>, уникальный для вашего сайта (это делается бесплатно и быстро).', array('@url' => 'http://code.google.com/apis/ajaxsearch/signup.html')),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'mg',
      'label' => t('mnoGoSearch'),
      ));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config_mg_dsn',
      'label' => t('Параметры подключения к БД'),
      'description' => t('Строка формата mysql://mnogouser:pass@server/mnogodb/?dbmode=multi'),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config_mg_ispell',
      'label' => t('Путь к словарям'),
      'description' => t('Введите полный путь к папке ispell.'),
      )));
    $tab->addControl(new TextLineControl(array(
      'value' => 'config_mg_indexer',
      'label' => t('Путь к индексатору'),
      'description' => t('Введите полный путь к исполняемому файлу индексатора (что-то вроде /usr/local/bin/indexer).'),
      )));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_mg_indexmode',
      'label' => t('Режим индексирования'),
      'required' => true,
      'options' => array(
        'web' => t('Обход сайта (медленно)'),
        'db' => t('По базе данных (быстро)'),
        ),
      )));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_mg_results',
      'label' => t('Страница для результатов'),
      'required' => true,
      'options' => DomainNode::getFlatSiteMap('select'),
      'description' => t('Используется только в режиме индексирования базы данных.  На эту страницу будут вести ссылки, отображаемые в результатах поиска.  При индексировании в режиме обхода сайта этот параметр не используется.'),
      )));
    $form->addControl($tab);

    return $form;
  }

  public static function hookPostInstall()
  {
    $t = new TableInfo('node__searchindex');

    if (!$t->exists()) {
      $t->columnSet('nid', array(
        'type' => 'int',
        'required' => true,
        'key' => 'mul',
        'autoincrement' => true,
        ));
      $t->columnSet('url', array(
        'type' => 'varchar(255)',
        'required' => true,
        ));
      $t->columnSet('html', array(
        'type' => 'mediumblob',
        ));

      $t->commit();
    }
  }

  public static function taskRun()
  {
    // 1. Проиндексировать документы, отсутствующие в индексе.
    // 2. Удалить скрытые и удалённые.
    // 3. Всё остальное нужно делать по hookNode().
    $nids = mcms::db()->getResultsV("id", "SELECT `id` FROM `node` WHERE `deleted` = 0 AND `published` = 1 AND `class` NOT IN ('". join("', '", TypeNode::getInternal()) ."') AND `id` NOT IN (SELECT `nid` FROM `node__searchindex`)");

    foreach (Node::find(array('id' => $nids), 100) as $node)
      self::reindexNode($node);
  }

  private static function reindexNode($node)
  {
    static $schema = null;

    if (null === $schema)
      $schema = TypeNode::getSchema();

    if (!is_object($node))
      $node = Node::load(array('id' => $node));

    if (in_array($node->class, TypeNode::getInternal()))
      return;

    if (array_key_exists($node->class, $schema)) {
      foreach ($schema[$node->class]['fields'] as $k => $v) {
        if (isset($node->$k)) {
          $html .= '<strong>'. mcms_plain($v['label']) .'</strong>';
          $html .= '<div class=\'data\'>'. $node->$k .'</div>';
        }
      }
    }

    $lang = empty($node->lang) ? 'en' : $node->lang;
    $html = "HTTP/1.0 200 OK\nContent-Type: text/html; charset=utf-8\nContent-Language: {$lang}\n\n<html><head><title>{$node->name}</title></head><body><h1>{$node->name}</h1>{$html}</body></html>";

    mcms::db()->exec('DELETE FROM `node__searchindex` WHERE `nid` = :nid', array(':nid' => $node->id));
    mcms::db()->exec('INSERT INTO `node__searchindex` (`nid`, `url`, `html`) VALUES (:nid, :url, :html)', array(':nid' => $node->id, ':url' => self::getNodeUrl($node), ':html' => $html));
  }

  private static function getNodeUrl(Node $node)
  {
    return 'http://'. mcms::config('basedomain') .'/node/'. $node->id .'/';

    $tag = mcms::db()->getResults("SELECT `id`, `code` FROM `node` `n` "
      ."INNER JOIN `node__rel` `r` ON `r`.`tid` = `n`.`id` "
      ."WHERE `r`.`nid` = :nid AND `n`.`class` = 'tag' AND `n`.`deleted` = 0 AND `n`.`published` = 1", array(
        ':nid' => $node->id,
        ));

    $tag = $tag[0]['id'];

    $url = 'http://'. mcms::config('basedomain') .'/'. $tag .'/'. $node->id .'/';

    return $url;
  }

  public static function hookNodeUpdate(Node $node, $op)
  {
    switch ($op) {
    case 'create':
    case 'update':
    case 'publish':
    case 'restore':
      self::reindexNode($node);
      break;
    case 'delete':
    case 'erase':
    case 'unpublish':
      mcms::db()->exec("DELETE FROM `node__searchindex` WHERE `nid` = :nid", array(':nid' => $node->id));
      break;
    }
  }
};
