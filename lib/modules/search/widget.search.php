<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SearchWidget extends Widget implements iWidget
{
  /**
   * @mcms_message ru.molinos.cms.widget.enum
   */
  public static function getWidgetInfo()
  {
    return array(
      'class' => __CLASS__,
      'name' => 'Поиск по сайту',
      'description' => 'Контекстный морфологический поиск по сайту.',
      );
  }

  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['q'] = $this->get('query');
    $options['page'] = $this->get('page', 1);
    $options['limit'] = $this->per_page;
    $options['#cache'] = false;

    return $options;
  }

  public function onGet(array $options)
  {
    $config = $this->ctx->modconf('search');

    if (empty($config['engine']))
      return null;

    return $this->dispatch(array($config['engine']), $options);
  }

  protected function onGetMg(array $options)
  {
    $result = array(
      'form' => parent::formRender('search-form'),
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
    $config = $this->ctx->modconf('search');

    if (empty($config['gas_key']))
      return "<!-- GAS disabled: site key not defined -->";
    elseif (empty($this->gas_ctl))
      return "<!-- GAS disabled: form parent not defined -->";
    elseif (empty($this->gas_root))
      return "<!-- GAS disabled: result container not defined -->";

    if (null === ($host = $this->gas_host))
      $host = $_SERVER['HTTP_HOST'];

    return html::em('search', array(
      'mode' => 'gas',
      'apikey' => $config['gas_key'],
      'hostname' => $host,
      'root' => $this->gas_root,
      'formctl' => $this->gas_ctl,
      'onlyform' => (bool)strcasecmp($this->gas_page, trim($this->ctx->query(), '/')),
      'resultpage' => $this->gas_page,
      'query' => $options['q'],
      ));
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

    $config = $this->ctx->modconf('search');

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
        'method' => 'get',
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

  public static function getConfigOptions(Context $ctx)
  {
    $schema = array();
    $config = $ctx->modconf('search');

    switch ($config['engine']) {
    case 'mg':
      $schema['action'] = array(
        'type' => 'TextLineControl',
        'label' => t('Страница с результатами поиска'),
        'description' => t('По умолчанию поиск производится на текущей странице.&nbsp; Если нужно при поиске перебрасывать пользователя на другую страницу, например &mdash; /search/, введите её имя здесь.'),
        'class' => 'settings-mg',
        );
      $schema['per_page'] = array(
        'type' => 'NumberControl',
        'label' => t('Количество результатов на странице'),
        'class' => 'settings-mg',
        );
      $schema['btngo'] = array(
        'type' => 'TextLineControl',
        'label' => t('Текст кнопки поиска'),
        'class' => 'settings-mg',
        );
      break;

    case 'gas':
      $schema['gas_ctl'] = array(
        'type' => 'TextLineControl',
        'label' => t('Блок с формой поиска'),
        'class' => 'settings-gas',
        'description' => t('Введите id элемента, в который нужно помещать форму поиска.'),
        );
      $schema['gas_root'] = array(
        'type' => 'TextLineControl',
        'label' => t('Блок с результатами Google Ajax Search'),
        'class' => 'settings-gas',
        'description' => t('Введите id элемента, в который нужно помещать результаты поиска.  Обычно это — пустой div, скрытый по умолчанию.'),
        );
      $schema['gas_page'] = array(
        'type' => 'TextLineControl',
        'label' => t('Страница с результатами поиска'),
        );
      $schema['gas_host'] = array(
        'type' => 'TextLineControl',
        'label' => t('Искать в домене'),
        'default' => $_SERVER['HTTP_HOST'],
        );
      break;
    }

    return $schema;
  }

  private static function getNodeUrl(Node $node)
  {
    return 'http://'. $_SERVER['HTTP_HOST'] .'/node/'. $node->id .'/';

    $tag = $node->getDB()->getResults("SELECT `id`, `code` FROM `node` `n` "
      ."INNER JOIN `node__rel` `r` ON `r`.`tid` = `n`.`id` "
      ."WHERE `r`.`nid` = :nid AND `n`.`class` = 'tag' AND `n`.`deleted` = 0 AND `n`.`published` = 1", array(
        ':nid' => $node->id,
        ));

    $tag = $tag[0]['id'];

    $url = 'http://'. $_SERVER['HTTP_HOST'] .'/'. $tag .'/'. $node->id .'/';

    return $url;
  }
};
