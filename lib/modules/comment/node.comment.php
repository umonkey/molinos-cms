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
};
