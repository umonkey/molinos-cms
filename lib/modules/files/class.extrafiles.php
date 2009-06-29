<?php
/**
 * Прикрепление множественных файлов к документам.
 *
 * Этот класс содержит весь код, необходимый для прикрепления
 * произвольного количества файлов к любому документу.  Файлы
 * привязываются безымянными ссылками (key=NULL), в XML выводятся
 * массивом files/file.
 *
 * @author Justin Forest <justin.forest@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html
 */

class ExtraFiles
{
  /**
   * Добавление действия ко всем документам.
   * @mcms_message ru.molinos.cms.node.actions
   */
  public static function on_get_actions(Context $ctx, Node $node)
  {
    if ($node instanceof FileNode or !$node->checkPermission('u'))
      return;

    $count = Node::count(array(
      'class' => 'file',
      'deleted' => 0,
      'tags' => $node->id,
      ), $node->getDB());

    if ($count)
      return array(
        'attach' => array(
          'href' => "admin/node/attach?id={$node->id}&destination=CURRENT",
          'title' => t('Управление файлами'),
          'scope' => 'edit',
          ),
        );
    else
      return array(
        'attach' => array(
          'href' => "admin/create/file?sendto={$node->id}&destination=CURRENT",
          'title' => t('Добавить файлы'),
          'scope' => 'edit',
          ),
        );
  }

  /**
   * Возвращает список файлов, прикреплённых к документу.
   */
  public static function on_get_list(Context $ctx)
  {
    $ids = $ctx->db->getResultsV("nid", "SELECT `nid` FROM `node__rel` WHERE `tid` = ? AND `key` IS NULL AND `nid` IN (SELECT `id` FROM `node` WHERE `class` = 'file')", array($ctx->get('id')));

    $output = Node::findXML(array(
      'id' => $ctx->get('id'),
      'deleted' => 0,
      ));
    $output .= html::wrap('files', Node::findXML(array(
      'class' => 'file',
      'deleted' => 0,
      'id' => $ids,
      ), $ctx->db));

    return html::em('content', array(
      'name' => 'extrafiles',
      ), $output);
  }

  /**
   * Добавление произвольных файлов в XML ноды.
   * @mcms_message ru.molinos.cms.node.xml
   */
  public static function on_get_node_xml(Node $node)
  {
    if ($node instanceof FileNode)
      return;

    return html::wrap('files', Node::findXML(array(
      'class' => 'file',
      'deleted' => 0,
      'id' => $node->getDB()->getResultsV("nid", "SELECT `nid` FROM `node__rel` WHERE `tid` = ? AND `key` IS NULL", array($node->id)),
      ), $node->getDB()));
  }

  /**
   * Открепление файлов от ноды.
   */
  public static function on_post_detach(Context $ctx)
  {
    if (is_array($ids = $ctx->post('remove'))) {
      $ctx->db->beginTransaction();
      $params = array();
      Node::load($ctx->get('id'), $ctx->db)->touch('u')->onSave('DELETE FROM `node__rel` WHERE `tid` = %ID% AND `key` IS NULL AND `nid` ' . sql::in($ids, $params), $params)->save();
      $ctx->db->commit();
    }

    return $ctx->getRedirect();
  }
}
