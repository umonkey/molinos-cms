<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AttachmentControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Файл'),
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));

    $this->description .= '<p>'. t("Максимальный размер файла: %size.", array('%size' => ini_get('upload_max_filesize'))) .'</p>';
  }

  public function getHTML(array $data)
  {
    $data = empty($data[$this->value]) ? array() : $data[$this->value];

    $output = mcms::html('input', array(
      'type' => 'file',
      'name' => $this->value,
      'id' => $this->id .'-input'
      ));
    $output .= mcms::html('input', array(
      'type' => 'hidden',
      'name' => $this->value .'[id]',
      'value' => empty($data['id']) ? null : $data['id'],
      'id' => $this->id .'-hidden',
      ));

    return $this->wrapHTML($output);
  }
};
