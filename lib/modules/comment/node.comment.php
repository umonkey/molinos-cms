<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentNode extends Node
{
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
    else
      $this->linkAddParent($this->node);

    return $res;
  }

  public function getDefaultSchema()
  {
    return array(
      'name' => array(
        'label' => t('Заголовок'),
        'type' => 'TextLineControl',
        'description' => t('Отображается в списках документов как в административном интерфейсе, так и на самом сайте.'),
        'required' => true,
        ),
      'text' => array(
        'type' => 'TextHTMLControl',
        'label' => t('Текст'),
        ),
    );
  }

  public function checkPermission($perm)
  {
    if ('d' == $perm and mcms::user()->id == $this->uid)
      return true;

    return parent::checkPermission($perm);
  }
};
