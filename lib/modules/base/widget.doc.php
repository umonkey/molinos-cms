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
      'description' => 'Выводит запрошенный пользователем документ.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/DocWidget',
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
  public static function getConfigOptions()
  {
    return array(
      'mode' => array(
        'type' => 'EnumControl',
        'label' => t('Режим работы'),
        'required' => true,
        'options' => array(
          'view' => t('Просмотр'),
          'edit' => t('Редактирование'),
          ),
        ),
      'fixed' => array(
        'type' => 'NumberControl',
        'label' => t('Фиксированный документ'),
        'description' => t("Документ с указанным здесь кодом будет возвращён "
          ."если из адреса запрошенной страницы достать код документа "
          ."не удалось (он не указан или так настроена страница)."),
        ),
      'showneighbors' => array(
        'type' => 'BoolControl',
        'label' => t('Возвращать информацию о соседях'),
        ),
      );
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
    $output = '';

    if (null !== ($node = $this->getDocument($options))) {
      if (in_array($node->class, array('tag', 'config')))
        throw new PageNotFoundException();

      if (!$node->published)
        throw new ForbiddenException(t('Документ не опубликован.'));

      if (!$node->checkPermission('r'))
        throw new ForbiddenException(t('У вас нет доступа к этому документу.'));

      $output .= $node->getXML('document');
      $output .= $node->getActionLinksXML();

      if (count($sids = $node->linkListParents('tag', true))) {
        $tmp = '';
        foreach (Node::find(array('class' => 'tag', 'id' => $sids, 'published' => 1)) as $tag)
          $tmp .= $tag->getXML('section');
        $output .= html::em('sections', $tmp);
      }

      /*
      $result['schema'] = $node->getSchema();
      */

      if ($this->showneighbors and $this->ctx->section->id and in_array($this->ctx->section->id, $sections)) {
        if (null !== ($n = $node->getNeighbors($this->ctx->section->id))) {
          $tmp = '';

          if (!empty($n['right']))
            $tmp .= $n['right']->getXML('right');
          if (!empty($n['left']))
            $tmp .= $n['left']->getXML('left');

          if (!empty($tmp))
            $output .= html::em('neighbors', $tmp);
        }
      }
    }

    return $output;
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

    return $form->getHTML($node);
  }

  private function getDocument(array $options)
  {
    if ($options['root'] instanceof Node)
      return $options['root']->id
        ? $options['root']
        : null;

    elseif (!empty($options['root']))
      return Node::load($options['root']);

    return null;
  }
};
