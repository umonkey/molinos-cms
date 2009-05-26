<?php

class LabelNode extends Node
{
  /**
   * Дополнительные действия для предварительного просмотра.
   */
  public function getActionLinks()
  {
    return array_merge(parent::getActionLinks(), array(
      'find' => array(
        'title' => t('Показать документы'),
        'href' => 'admin/content/list?search=tags%3A' . $this->id,
        ),
      ));
  }

  /**
   * Добавляет в предварительный просмотр количество отмеченных документов.
   */
  public function getPreviewXML(Context $ctx)
  {
    $result = parent::getPreviewXML($ctx);

    $count = $ctx->db->fetch("SELECT COUNT(*) FROM node WHERE deleted = 0 AND id IN "
      . "(SELECT nid FROM node__rel WHERE tid = ?) AND class IN "
      . "(SELECT name FROM node WHERE class = 'type' AND deleted = 0 AND published = 1)",
      array($this->id));

    if ($count) {
      $message = t('%count (<a href="@url">список</a>)', array(
        '%count' => $count,
        '@url' => 'admin/content/list?search=tags%3A' . $this->id,
        ));

      $result .= html::em('field', array(
        'title' => t('Отмечено документов'),
        ), html::em('value', html::cdata($message)));
    }

    return $result;
  }
}
