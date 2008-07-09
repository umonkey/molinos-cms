<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class FileListControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Список прикреплённых файлов'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML(array $data)
  {
    $output = "<tr><th>&nbsp;</th><th>Название файла</th><th>". mcms::html('img', array(
      'src' => 'themes/admin/img/bin.gif',
      'alt' => 'Убрать',
      )) ."</th></tr>";


    foreach ($data as $k => $v) {
      if ($this->value == substr($k, 0, strlen($this->value)) and is_numeric(substr($k, strlen($this->value) + 1, -1))) {
         $dt = $v->getData();

         $preview = mcms::html('img', array(
           'src' => "attachment.rpc?fid={$dt['id']},48,48,c&rev={$dt['rid']}",
           'width' => 48,
           'height' => 48,
           'alt' => $data['filepath'],
           'onclick' => isset($this->picker)
           ? "return mcms_picker.mySubmit(\"". l('?q=attachment.rpc&fid='. $fid) ."\",{$fid})"
          : null,
         ));

         $preview = mcms::html('a', array(
          'title' => 'Скачать',
          'href' => "attachment.rpc?fid={$dt['id']}",
          'class' => isset($this->picker) ? 'returnHref' : null,
         ), $preview);

         $td1 = mcms::html('td',array('class' => 'preview'),$preview);

         $td2 = mcms::html('td',array('class' => 'properties'),
                   mcms::html('div',array('class' => 'tab tab1'),
                     mcms::html('label',array('class' => 'filename pad'),
                       mcms::html('span',null,t('Название')).
                       mcms::html('input', array('disabled' => 'true', 'type' => 'text',
                                                 'name' => $k .'[name]', 'value' => $dt['name'] ))).
                     mcms::html('label',array('class' => 'filetype pad'),
                       mcms::html('span',null,t('Тип')).
                       mcms::html('input', array('disabled' => 'true', 'type' => 'text',
                                                 'name' => $k .'[type]', 'value' => $dt['filetype'],
                                                 'class' => "filetype"  ))).
                                 'Размер: '. sprintf("%d Кб",filesize(mcms::config('filestorage').'/'.$dt['filepath'])/1024).",  создан: ".$dt['created'].
                                 ", обновлён: ".$dt['updated']
                              ).
                   mcms::html('div',array('class' => 'tab tab2'),
                     mcms::html('label',array('class' => 'filename pad'),
                       mcms::html('input', array('type' => 'file', 'name' => "{$k}[replace]"))
                               ). 'Максимальный размер загружаемого файла - 2M. Можно также '.
                                  mcms::html('a',array('href'=>
                                   '?mode=list&preset=files&cgroup=content&mcmsarchive=1&q=admin&picker='),
                                   'выбрать из архива')
                              ).
                   mcms::html('div',array('class' => 'tab tab3'),
                     mcms::html('p',null,
                       mcms::html('label',null,
                         mcms::html('input',array( 'type' => 'checkbox',
                                                         'name' => $k .'[unlink]',
                                                         'value' => 1
                                                        )).'Удалить'
                                  ))).
                   mcms::html('div',array('class' => 'filetabs'),
                     mcms::html('u',array('class' => 'tab1'),
                       mcms::html('span',array('class' => 'active'), t('Свойства'))
                                ).
                     mcms::html('u',array('class' => 'tab2'),
                       mcms::html('span',array('class' => 'passive'), t('Заменить'))
                                ).
                     mcms::html('u',array('class' => 'tab3'),
                       mcms::html('span',array('class' => 'passive'), t('Удалить'))
                                ))
                    );

         $str .= mcms::html('tr',array('class'=>'file'), $td1.$td2 );
      }
    }

    $th = mcms::html('tr',null,
            mcms::html('th', array('class' => 'fieldname', 'colspan' => 2), t('Дополнительные файлы')));

    $output =  mcms::html('table', array('class'=>'files','border' => "0", 'cellspacing'=>"0",
                         'cellpadding'=> "0" ), $th.$str);
    return $this->wrapHTML('<table>'. $output .'</table>');
  }

};
