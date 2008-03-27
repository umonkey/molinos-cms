<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class FileNode extends Node implements iContentType
{
  // Определяем размеры.
  public function save($clear = true)
  {
    $path = BebopConfig::getInstance()->filestorage;
    $path .= '/'. $this->filepath;

    if (is_readable($path) and ($info = @getimagesize($path))) {
      $this->width = $info[0];
      $this->height = $info[1];
    }

    if (empty($this->name))
      $this->name = basename($this->filename);

    parent::save($clear);

    // Проставляем права контент-менеджерам.
    PDO_Singleton::getInstance()->exec("REPLACE INTO `node__access` (`nid`, `uid`, `c`, `r`, `u`, `d`) "
      ."SELECT :nid, `n`.`id`, 0, 1, 1, 1 FROM `node` `n` INNER JOIN `node_group` `g` ON `g`.`rid` = `n`.`rid` WHERE `g`.`login` = 'Content Managers'", array(':nid' => $this->id));

    $this->purge();
  }

  // Удаление версий файла из кэша.
  private function purge()
  {
    foreach (glob("{$_SERVER['DOCUMENT_ROOT']}/attachment/{$this->id}*") as $filename) {
      $parts = explode(',', $filename);
      if (basename($parts[0]) == $this->id)
        unlink($filename);
    }
  }

  // При удалении очищаем кэш.
  public function delete()
  {
    $this->purge();
    parent::delete();
  }

  public function erase()
  {
    parent::erase();

    $this->purge();

    if (file_exists($filename = BebopConfig::getInstance()->filestorage .'/'. $this->filepath))
      unlink($filename);
  }

  public function duplicate()
  {
    throw new ForbiddenException(t('Клонирование файлов невозможно.'));
  }

  // Импорт файла из массива $post.
  public function import(array $file, $uploaded = true)
  {
    $storage = BebopConfig::getInstance()->filestorage;

    // Немного валидации.
    if (empty($file['tmp_name']) or !file_exists($file['tmp_name']))
      throw new Exception(t("Не удалось импортировать исходный файл."));

    // Угадваем значения некоторых полей, для упрощения скриптинга.
    if (!array_key_exists('size', $file))
      $file['size'] = filesize($file['tmp_name']);
    if (!array_key_exists('name', $file))
      $file['name'] = basename($file['tmp_name']);
    if (!array_key_exists('type', $file) or 'application/octet-stream' == $file['type'])
      $file['type'] = bebop_get_file_type($file['tmp_name']);

    if ($this->id === null and $file['type'] == 'application/zip' and !empty($file['unzip'])) {
      if (null === ($node = $this->unzip($file['tmp_name'])))
        throw new InvalidArgumentException("ZIP file was empty");
      $this->data = $node->getRaw();
      return;
    }

    // Заполняем метаданные.
    $this->filename = $this->name = $file['name'];
    $this->filetype = $file['type'];
    $this->filesize = $file['size'];

    // Формируем внутреннее имя файла.
    $intname = md5_file($file['tmp_name']);
    $this->filepath = substr($intname, 0, 1) .'/'. substr($intname, 1, 1) .'/'. $intname;

    // Находим существующий файл.
    try {
      $node = Node::load(array('class' => 'file', 'filepath' => $this->filepath));
      $this->data = $node->data;
    }

    // Файл не найден, создаём новый.
    catch (ObjectNotFoundException $e) {
      // Создаём каталог для него.
      if (!is_dir($filedir = dirname($storage .'/'. $this->filepath)))
        @mkdir($filedir, 0775, true);

      // Не удалось создать каталог -- нет прав.
      if (!is_dir($filedir))
        throw new ForbiddenException(t("Файл не удалось сохранить, т.к. отсутствуют права на запись в каталог, где этот файл должен был бы храниться (%dir).&nbsp; Сообщите об этой проблеме администратору сайта.", array('%dir' => $filedir)));

      // Сюда будем копировать файл.
      $dest = $storage .'/'. $this->filepath;

      // Копируем файл.
      if ($uploaded) {
        if (!($c1 = is_uploaded_file($file['tmp_name'])) or !($c2 = move_uploaded_file($file['tmp_name'], $dest))) {
          $debug = sprintf("File could not be uploaded.\nis_uploaded_file: %d\nmove_uploaded_file: %d\ndestination: %s", $c1, $c2, $storage .'/'. $this->filepath);
          bebop_debug($debug, $file, $this);
          throw new UserErrorException("Ошибка загрузки", 400, "Ошибка загрузки", "Не удалось загрузить файл: ошибка {$file['error']}");
        }
      } else {
        copy($file['tmp_name'], $dest);
      }
    }

    // Прикрепляем файл к родительскому объекту.
    if (!empty($file['parent_id']))
      $this->linkAddParent($file['parent_id']);
  }

  // Распаковывает архив, добавляет все файлы в админку,
  // возвращает последний добавленный файл.
  public static function unzip($zipfile)
  {
    $node = null;

    if (function_exists('zip_open')) {
      if (file_exists($zipfile)) {
        $zip = zip_open($zipfile);
        while ($zip_entry = zip_read($zip)) {
          zip_entry_open($zip, $zip_entry);

          if (substr(zip_entry_name($zip_entry), -1) == '/') {
            $zdir = substr(zip_entry_name($zip_entry), 0, -1);
            if (file_exists($zdir)) {
              throw new Exception('Directory "<b>' . $zdir . '</b>" exists');
            }
            mkdir($zdir);
          } else {
            $name = zip_entry_name($zip_entry);
            if (file_exists($name))
              unlink($name);
            $fopen = fopen($name, "w");
            fwrite($fopen, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)), zip_entry_filesize($zip_entry));

            $node = Node::create('file');
            $node->import(array(
              'tmp_name' => $name,
              ), false);
            $node->save();
          }
          zip_entry_close($zip_entry);
        }
        zip_close($zip);
      } else {
        throw new Exception("No such file {$zipfile}");
      }
    } else {
      throw new Exception('zlib extension is not available');
    }

    return $node;
  }

  public function formGet($simple = true)
  {
    $form = parent::formGet($simple);

    $form->addControl(new AttachmentControl(array(
      'value' => '__file_node_update',
      'label' => t('Заменить файл'),
      )));

    $form->title = (null === $this->id)
      ? t('Добавление нового файла')
      : t('Редактирование файла %name', array('%name' => $this->filename));

    return $form;
  }

  // Обработка замены содержимого файла.  Порядок действий именно такой потому,
  // что parent::formProcess() обновляет все поля, а нам нужно изменить некоторые
  // из них вручную.  После импорта файл снова сохраняется.
  public function formProcess(array $data)
  {
    if (!empty($data['__file_node_update']) and empty($data['__file_node_update']['error'])) {
      $this->import($data['__file_node_update']);
      $this->save();
    }

    $data['#node_override'] = array_intersect_key($this->data, array_flip(array('filename', 'filetype', 'filesize', 'filepath')));

    parent::formProcess($data);
  }
};
