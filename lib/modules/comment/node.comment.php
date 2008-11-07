<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentNode extends Node
{
  public function save()
  {
    if (empty($this->name))
      $this->name = t('Комментарий от %name', array('%name' => mcms::user()->name));

    return parent::save();
  }

  public function formGet($simple = false)
  {
    $form = parent::formGet($simple);

    if ($this->id)
      $form->title = t('Редактирование комментария');
    else
      $form->title = t('Добавление комментария');

    return $form;
  }

  public function formProcess(array $data)
  {
    if (empty($this->node))
      $this->node = $data['comment_node'];

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
      'uid' => array(
        'type' => 'NodeLinkControl',
        'label' => t('Автор'),
        'dictionary' => 'user',
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
