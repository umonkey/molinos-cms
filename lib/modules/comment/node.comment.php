<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentNode extends Node
{
  public function save()
  {
    if ($this->anonymous)
      unset($this->uid);
    else
      $this->uid = Context::last()->user->getNode();

    parent::save();
    $this->publish();
    return $this;
  }

  public function getName()
  {
    $name = $this->uid
      ? t('Комментарий к ноде %id', array('%id' => $this->node))
      : t('Анонимный комментарий к ноде %id', array('%id' => $this->node));

    return $name;
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Редактирование комментария')
      : t('Добавление комментария');
  }

  public function formProcess(array $data)
  {
    if (empty($this->node))
      $this->node = $data['comment_node'];

    if (!empty($data['anonymous']))
      $this->anonymous = true;

    $res = parent::formProcess($data);

    if (empty($this->node))
      throw new ValidationException(t('Не указан документ, '
        .'к которому добавлен комментарий.'));
    else {
      $this->onSave("DELETE FROM `node__rel` WHERE `nid` = %ID%");
      $this->onSave("INSERT INTO `node__rel` (`tid`, `nid`) VALUES (?, %ID%)", array($this->node));
    }

    return $res;
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

  public function getFormFields()
  {
    $schema = parent::getFormFields();

    $schema['anonymous'] = new BoolControl(array(
      'value' => 'anonymous',
      'label' => t('Оставить анонимный комментарий'),
      'weight' => 100,
      ));

    return $schema;
  }
};
