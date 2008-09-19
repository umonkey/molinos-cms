<?php
/**
 * Виджет «отдельный документ».
 *
 * Возвращает информацию об отдельном объекте, запрошенном пользователем или
 * указанном администратором, в зависимости от настроек виджета.
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «отдельный документ».
 *
 * Возвращает информацию об отдельном объекте, запрошенном пользователем или
 * указанном администратором, в зависимости от настроек виджета.
 *
 * Результат кэшируется отдельно для каждого залогиненного пользователя и один
 * раз для всех анонимных.
 *
 * @package mod_base
 * @subpackage Widgets
 */
class DocWidget extends Widget implements iWidget
{
  /**
   * Возвращает информацию о виджете.
   *
   * @return array описание виджета, ключи: name, description.
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Отдельный документ',
      'description' => 'Возвращает в переменную «$document» отдельный документ, идентификатор которого может быть передан либо через адресную строку, либо задан жёстко ниже.  Если идентификатор документа не указан, не возвращает ничего; если идентификатор указан, но документа с таким идентификатором нет, или он не опубликован — возникает ошибка 404.',
      );
  }

  /**
   * Возвращает форму для настройки виджета.
   *
   * Используемые параметры: mode = режим работы, fixed = код фиксированного
   * документа, showneighbors = возвращать соседей.  Доступны через
   * mcms::modconf().
   *
   * @return Form описание формы.
   */
  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new EnumControl(array(
      'value' => 'config_mode',
      'label' => t('Режим работы'),
      'required' => true,
      'options' => array(
        'view' => t('Просмотр'),
        'edit' => t('Редактирование'),
        ),
      )));

    $form->addControl(new NumberControl(array(
      'value' => 'config_fixed',
      'label' => t('Фиксированный документ'),
      'description' => t("Документ с указанным здесь кодом будет возвращён "
        ."если из адреса запрошенной страницы достать код документа "
        ."не удалось (он не указан или так настроена страница)."),
      )));

    $form->addControl(new BoolControl(array(
      'value' => 'config_showneighbors',
      'label' => t('Возвращать информацию о соседях'),
      )));

    return $form;
  }

  /**
   * Вытаскивает из контекста параметры виджета.
   *
   * @return array параметры, необходимые виджеты
   *
   * @param Context $ctx контекст запроса.  Используемые GET-параметры:
   * action, код раздела (если используется возврат информации о соседях), код
   * документа (если не используется возврат фиксированного документа).
   */
  protected function getRequestOptions(Context $ctx)
  {
    if (is_array($options = parent::getRequestOptions($ctx))) {
      if (null === ($options['action'] = $ctx->get('action', $this->mode)))
        $options['action'] = 'view';

      if ($uid = mcms::user()->id) {
        $options['cachecontrol'] = $uid;
        $options['uid'] = $uid;
      } else {
        $options['cachecontrol'] = array_keys(mcms::user()->getGroups());
      }

      if ($this->showneighbors)
        $options['section'] = $ctx->section;

      if (empty($this->fixed))
        $options['root'] = $ctx->document;
      else
        $options['root'] = $this->fixed;

      if ('' == strval($options['root']))
        return false;
    }

    return $options;
  }

  /**
   * Диспетчер запросов.
   *
   * Вызывает onGetView() или onGetEdit(), в зависимости от параметра action.
   *
   * @see Widget::dispatch()
   *
   * @return mixed данные для шаблона.
   * @param array $options параметры, которые насобирал getRequestOptions().
   */
  public function onGet(array $options)
  {
    return $this->dispatch(array($options['action']), $options);
  }

  /**
   * Возвращает информацию об объекте.
   *
   * Информация об объекте включает прикреплённые к нему файлы и привязанные
   * документы.  Если у пользователя нет доступа к объекту — кидает
   * ForbiddenException; если объект не опубликован — кидает ForbiddenException
   * (независимо от прав пользователя); если объект является разделом — кидает
   * PageNotFoundException (потому, что для разделов есть TagsWidget).
   *
   * @param array $options параметры, которые насобирал getRequestOptions()
   *
   * @return array Информация об объекте, содержит ключи: "document" (описание
   * объекта, включая прикреплённые файлы и другие объекты), "tags" (полное
   * описание разделов, к которым прикреплён объект), "schema" (описание
   * структуры объекта) и "neighbors" со ссылками на соседей ("prev" и "next"),
   * если настройки виджета велят возвращать эту информацию.
   */
  protected function onGetView(array $options)
  {
    $result = array(
      'document' => array(),
      'tags' => array(),
      'schema' => array(),
      );

    if (null !== ($node = $this->getDocument())) {
      if (in_array($node->class, array('tag', 'config')))
        throw new PageNotFoundException();

      if (!$node->published)
        throw new ForbiddenException(t('Документ не опубликован.'));

      if (!$node->checkPermission('r'))
        throw new ForbiddenException(t('У вас нет доступа к этому документу.'));

      $result['document'] = $node->getRaw();
      $result['document']['_links'] = $node->getActionLinks();

      $sections = array();

      if (count($sids = $node->linkListParents('tag', true))) {
        foreach (Node::find(array('class' => 'tag', 'id' => $sids, 'published' => 1)) as $tag) {
          $sections[] = $tag->id;
          $result['tags'][] = $tag->getRaw();
        }
      }

      $result['schema'] = TypeNode::getSchema($node->class);

      if ($this->showneighbors and $this->ctx->section->id and in_array($this->ctx->section->id, $sections)) {
        if (null !== ($n = $node->getNeighbors($this->ctx->section->id))) {
          $result['neighbors'] = array(
            'prev' => empty($n['right']) ? null : $n['right']->getRaw(),
            'next' => empty($n['left']) ? null : $n['left']->getRaw(),
            );
        }
      }
    }

    if (array_key_exists('document', $result))
      bebop_on_json(array($result['document']));

    return $result;
  }

  /**
   * Возвращает форму для редактирования объекта.
   *
   * Возвращает HTML код формы, достаточный для редактирования объекта.
   * Используется стандартный обработчик форм (nodeapi.rpc), вносить изменения в
   * код формы нельзя — только стилизовать.
   *
   * @param array $options параметры, которые насобирал getRequestOptions().
   *
   * @return string HTML код формы.
   */
  protected function onGetEdit(array $options)
  {
    $node = Node::load($options['root']);

    $form = $node->formGet(false);
    $form->addClass('tabbed');

    return $form->getHTML($node->formGetData());
  }

  private function getDocument()
  {
    if ($this->options['root'] instanceof Node)
      return $this->options['root']->id
        ? $this->options['root']
        : null;

    elseif (!empty($this->options['root']))
      return Node::load($this->options['root']);

    return null;
  }
};
