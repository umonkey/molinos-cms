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
   * @mcms_message ru.molinos.cms.widget.enum
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
   * документа, showneighbors = возвращать соседей.
   *
   * @return Form описание формы.
   */
  public static function getConfigOptions(Context $ctx)
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
      'show_sections' => array(
        'type' => 'BoolControl',
        'label' => t('Возвращать информацию о разделах'),
        ),
      'showneighbors' => array(
        'type' => 'BoolControl',
        'label' => t('Возвращать информацию о соседях'),
        ),
      'fixed' => array(
        'type' => 'NumberControl',
        'label' => t('Выводить фиксированный документ'),
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
  protected function getRequestOptions(Context $ctx, array $params)
  {
    $options = parent::getRequestOptions($ctx, $params);
    $options['action'] = 'view';

    if ($this->fixed) {
      $options['document'] = array('id' => $this->fixed);
    } elseif (!($options['document'] = $params['document'])) {
      return $this->halt();
      $options['section'] = $params['section'];

      if (null === ($options['action'] = $this->get('action', $this->mode)))
        $options['action'] = 'view';
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
    if (isset($options['document']['class']) and !$this->ctx->user->hasAccess(ACL::READ, $options['document']['class']))
      throw new PageNotFoundException();

    if (isset($options['document']['xml']))
      $output = $options['document']['xml'];
    else
      $output = Node::findXML(array(
        'id' => $options['document']['id'],
        'deleted' => 0,
        'published' => 1,
        ), $this->ctx->db);

    if ($this->show_sections)
      $sections = Node::findXML($q = array(
        'class' => 'tag',
        'tagged' => $options['document']['id'],
        'published' => 1,
        'deleted' => 0,
        ), $this->ctx->db);
    elseif (isset($options['section']['xml']))
      $sections = $options['section']['xml'];

    if (isset($sections))
        $output .= html::wrap('sections', $sections);

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

    $form = $node->formGet();
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
      return mcms::debug(Node::load(array(
        'id' => $options['root'],
        '#cache' => false,
        )));

    return null;
  }
};
