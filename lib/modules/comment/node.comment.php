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
      'name' => 'comment',
      'title' => t('Комментарий'),
      'lang' => 'ru',
      'adminmodule' => 'comment',
      'notags' => 1,
      'fields' => array(
        'name' => array(
          'label' => 'Заголовок',
          'type' => 'TextLineControl',
          'description' => 'Отображается в списках документов как в административном интерфейсе, так и на самом сайте.',
          'required' => '1',
          ),
        'author' => array(
          'label' => 'Автор',
          'type' => 'TextLineControl',
          ),
        'text' => array(
          'label' => 'Текст',
          'type' => 'TextHTMLControl',
          ),
      ),
    );
  }

  public function checkPermission($perm)
  {
    if ('d' == $perm and mcms::user()->id == $this->uid)
      return true;

    return parent::checkPermission($perm);
  }

  public function schema()
  {
    $schema = parent::schema();

    $schema['fields']['uid'] = array(
      'type' => 'NodeLinkControl',
      'label' => t('Автор'),
      'dictionary' => 'user',
      );

    return $schema;
  }
};
