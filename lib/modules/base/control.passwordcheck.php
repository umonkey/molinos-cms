<?php

class PasswordCheckControl extends TextLineControl
{
  public static function getInfo()
  {
    return array(
      'name' => t('Поле для ввода пароля'),
      'hidden' => true,
      );
  }

  public function getHTML($data)
  {
    if (null === $this->class)
      $this->class = 'form-text';
    else
      $this->class = array_merge(array('form-text'), (array)$this->class);

    $value = $this->getValue($data);

    $output = html::em('input', array(
      'type' => 'password',
      'id' => $this->id,
      'class' => $this->class,
      'name' => $this->value,
      'value' => $value,
      'readonly' => $this->readonly ? 'readonly' : null,
      'maxlength' => 255,
      ));

    return $this->wrapHTML($output);
  }

  public function set($value, Node &$node)
  {
    if ($node instanceof UserNode) {
      $old = Node::load($node->id);
      if (!$old->checkpw($value))
        throw new ValidationException($this->value, t('Вы неверно ввели текущий пароль.'));
    }
  }
}
