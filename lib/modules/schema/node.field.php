<?php

class FieldNode extends Node implements iContentType
{
  public function save()
  {
    parent::checkUnique('name', t('Поле со внутренним именем %name уже есть.', array(
      '%name' => $this->name,
      )));

    parent::save();

    $this->publish();

    return $this;
  }

  public function checkPermission($perm)
  {
    return true;
  }

  public function getFormSubmitText()
  {
    return empty($this->type)
      ? t('Продолжить')
      : parent::getFormSubmitText();
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Свойства поля %name', array('%name' => $this->name))
      : t('Добавление нового поля');
  }

  public function getFormFields()
  {
    $fields = array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Внутреннее имя'),
        'required' => true,
        're' => '@^[a-z0-9]+$@',
        'description' => t('Используется внутри системы и в шаблонах. Только латинские буквы и арабские цифры.'),
        'readonly' => !empty($this->id),
        ),
      'label' => array(
        'type' => 'TextLineControl',
        'label' => t('Отображаемое имя'),
        'required' => true,
        'description' => t('Используется в формах редактирования документов.'),
        ),
      'type' => array(
        'type' => 'EnumControl',
        'label' => t('Тип'),
        'options' => Control::getKnownTypes(),
        'required' => true,
        'default' => 'TextLineControl',
        ),
      'description' => array(
        'type' => 'TextAreaControl',
        'label' => t('Подсказка'),
        'description' => t('Помогает пользователю понять, что следует вводить в это поле.'),
        ),
      'required' => array(
        'type' => 'BoolControl',
        'label' => t('Обязательное'),
        ),
      );

    // Дополнительные настройки.
    if ($this->id) {
      // Запрещаем изменение типа существующего поля.
      $fields['type']['readonly'] = true;

      if (class_exists($this->type)) {
        $tmp = new $this->type(array(
          '#nocheck' => true,
          ));

        if (null !== $tmp->getSQL())
          $fields['indexed'] = array(
            'type' => 'BoolControl',
            'label' => t('Используется для поиска и сортировки'),
            );

        foreach ((array)$tmp->getExtraSettings() as $k => $v)
          $fields[$k] = $v;
      }
    }

    $fields['types'] = array(
      'type' => 'SetControl',
      'label' => t('Используется типами'),
      'dictionary' => 'type',
      'field' => 'title',
      'group' => t('Типы'),
      'parents' => true,
      );

    return new Schema($fields);
  }

  public function canEditFields()
  {
    return false;
  }

  public function canEditSections()
  {
    return false;
  }
}
