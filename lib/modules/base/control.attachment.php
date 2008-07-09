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
    $fileinfo = empty($data[$this->value]) ? null : $data[$this->value];

    $dt = array('name'     => '', 'filetype' => '', 'updated'  => '',
                'created'  => '', 'filepath' => '');

    $fileprop = '';
    if (!empty($fileinfo)) {
      $dt   = $fileinfo->getData();
      $fid  = $dt['id'];
      $rid  = $dt['rid'];

      $preview = mcms::html('img', array(
        'src' => "attachment.rpc?fid={$fid},48,48,c&rev={$rid}",
        'width' => 48,
        'height' => 48,
        'alt' => $data['filepath'],
        'onclick' => isset($this->picker)
        ? "return mcms_picker.mySubmit(\"". l('?q=attachment.rpc&fid='. $fid) ."\",{$fid})"
         : null,
        ));

      $preview = mcms::html('a', array(
        'title' => 'Скачать',
        'href' => "attachment.rpc?fid={$fid}",
        'class' => isset($this->picker) ? 'returnHref' : null,
         ), $preview);

      $fileprop =  'Размер: '. sprintf("%d Кб",filesize(mcms::config('filestorage').'/'.$dt['filepath'])/1024).",       создан: ".$dt['created'].  ", обновлён: ".$dt['updated'];

      $delblock = mcms::html('div',array('class' => 'tab tab3'),
                   mcms::html('p',null,
                       mcms::html('label',null,
                         mcms::html('input',array( 'type' => 'checkbox',
                                                   'name' => $this->value .'[deleted]',
                                                   'value' => 1
                                                 )).'Удалить'
                                  )));

      $dellink = mcms::html('u',array('class' => 'tab3'),
                   mcms::html('span',array('class' => 'passive'), t('Удалить')));
    }

    $td1 = mcms::html('td',array('class' => 'preview'),$preview);

    $isnew = empty($data[$this->value]);
    $td2 = mcms::html('td',array('class' => 'properties'),
             mcms::html('div',array('class' => 'tab tab1'),
               mcms::html('label',array('class' => 'filename pad'),
                 mcms::html('span',null,t('Название')).
                   mcms::html('input', array('disabled' => 'true', 'type' => 'text',
                                                 'name' => $this->value .'[name]', 'value' => $dt['name'] ))).
               mcms::html('label',array('class' => 'filetype pad'),
                 mcms::html('span',null,t('Тип')).
                   mcms::html('input', array('disabled' => 'true', 'type' => 'text',
                                                 'name' => $this->value .'[type]', 'value' => $dt['filetype'],
                                                 'class' => "filetype"  ))).$fileprop
                              ).
             mcms::html('div',array('class' => 'tab tab2'),
               mcms::html('label',array('class' => 'filename pad'),
                 mcms::html('input', array('type' => 'file', 'name' => $this->value))
                           ). 'Максимальный размер загружаемого файла: '
                            .ini_get('upload_max_filesize').'. Можно также '.
                              mcms::html('a',array('href'=>
                                '?mode=list&preset=files&cgroup=content&mcmsarchive=1&q=admin&picker='),
                                'выбрать из архива')
                          ).
             $delblock.
             mcms::html('div',array('class' => 'filetabs'),
               mcms::html('u',array('class' => 'tab1'),
                 mcms::html('span',array('class' => 'active'), t('Свойства'))
                                ).
                 mcms::html('u',array('class' => 'tab2'),
                   mcms::html('span',array('class' => 'passive'), $isnew ?
                     t('Загрузить') : t('Заменить'))
                             ). $dellink
                         )
                     );

    $str .= mcms::html('tr',array('class'=>'file'), $td1.$td2 );

    if (!empty($this->label))
      $th = mcms::html('tr', mcms::html('th', array('class' => 'fieldname',
        'colspan' => 2), $this->label));
    else
      $th = '';

    $output = mcms::html('table', array('class' => 'files', 'border' => 0,
      'cellspacing' => 0, 'cellpadding' => 0), $th . $str);

    mcms::extras('lib/modules/base/control.attachment.js');

    return $this->wrapHTML($output, false);
  }

};
