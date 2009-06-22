<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentNode extends Node
{
  public function save()
  {
    if ($this->isNew() and $this->parent_id) {
      $this->node = $this->parent_id;
      $this->parent_id = null;
      $this->onSave("INSERT INTO `node__rel` (`tid`, `nid`) VALUES (?, %ID%)", array($this->node));
    }

    return parent::save();
  }

  public function getName()
  {
    if (!($name = $this->name))
      $name = mb_substr(preg_replace('/\s+/', ' ', strip_tags($this->text)), 0, 40) . '...';
    return $name;
  }

  /**
   * Добавляет в комментарий информацию о ноде.
   */
  public function getExtraXMLContent()
  {
    $db = Context::last()->db;

    $node = $this->node
      ? $this->node
      : $db->fetch("SELECT `tid` FROM `node__rel` WHERE `nid` = ? LIMIT 1", array($this->id));

    if ($node)
      if ($data = $db->getResults("SELECT id, class, name FROM node WHERE id = ?", array($node)))
        return html::em('node', $data[0]);
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Редактирование комментария')
      : t('Добавление комментария');
  }

  public static function getDefaultSchema()
  {
    return array(
      'text' => array(
        'type' => 'TextHTMLControl',
        'label' => t('Текст'),
        ),
    );
  }

  /**
   * Возвращает форму.  Проверяет, установлен ли родитель.
   */
  public function formGet($fieldName = null)
  {
    return parent::formGet($fieldName);
  }
}
