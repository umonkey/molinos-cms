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
    $parts[] = t("Максимальный размер файла: %size.", array('%size' => ini_get('upload_max_filesize')));

    if (!empty($this->extensions))
      $parts[] = t('Допустимые типы файлов: %list.', array('%list' => $this->extensions));

    $this->description .= '<p>'. join('  ', $parts) .'</p>';
  }

  public function getHTML(array $data)
  {
    $fileinfo = empty($data[$this->value]) ? null : $data[$this->value];

    $output = mcms::html('input', array(
      'type' => 'file',
      'name' => $this->value,
      'id' => $this->id .'-input',
      'class' => 'form-file'. ($this->archive ? ' archive' : ''),
      ));

     if (!empty($fileinfo)) {
       $data = $fileinfo->getData();
       $fid  = $data['id'];
       $rid  = $data['rid'];
       $tmp = mcms::html('img', array(
         'src' => "attachment.rpc?fid={$fid},48,48,c&rev={$rid}",
         'width' => 48,
         'height' => 48,
         'alt' => $data['filepath'],
         'onclick' => isset($this->picker)
         ? "return mcms_picker.mySubmit(\"". l('?q=attachment.rpc&fid='. $fid) ."\",{$fid})"
          : null,
         ));

       $tmp = mcms::html('a', array(
         'title' => 'Скачать',
         'href' => "attachment.rpc?fid={$fid}",
         'class' => isset($this->picker) ? 'returnHref' : null,
         ), $tmp);

      $output .= $tmp;
     }

    if ($this->unzip)
      $output .= '<br/>'. mcms::html('input', array(
        'type' => 'checkbox',
        'name' => $this->value .'[unzip]',
        'value' => 1,
        ), 'Распаковать ZIP');

    /*
    $output .= mcms::html('input', array(
      'type' => 'hidden',
      'name' => $this->value .'[id]',
      'value' => empty($data['id']) ? null : $data['id'],
      'id' => $this->id .'-hidden',
      ));
    */

    mcms::extras('lib/modules/base/control.attachment.js');

    return $this->wrapHTML($output);
  }
};
