<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class CommentNode extends Node
{
  public function formGet($simple = false)
  {
    $form = parent::formGet($simple);

    if ($this->id)
      $form->title = t('Редактирование комментария');
    else
      $form->title = t('Добавление комментария');

    return $form;
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
};
