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

    $parts = array();

    if (!empty($this->extensions))
      $parts[] = t('Допустимые типы файлов: %list.', array('%list' => $this->extensions));

    $this->description .= '<p>'. join('  ', $parts) .'</p>';
  }

  public function getHTML(array $data)
  {
    if (!empty($data[$this->value])) {
      if (($dt = $data[$this->value]) instanceof Node)
        $dt = $dt->getRaw();
    } else {
      $dt = array(
        'name' => null,
        'filetype' => null,
        'updated' => null,
        'created' => null,
        'filepath' => null,
        );
    }

    return $this->wrapHTML($this->render($dt), false);
  }
};
