<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fenc=utf8 enc=utf8:

class FileNode extends Node implements iContentType
{
  // Определяем размеры.
  public function save()
  {
    $path = mcms::config('filestorage');
    $path .= '/'. $this->filepath;

    if (is_readable($path) and ($info = @getimagesize($path))) {
      $this->width = $info[0];
      $this->height = $info[1];
    }

    parent::save();

    $this->purge();
  }

  // Удаление версий файла из кэша.
  private function purge()
  {
    $path = MCMS_ROOT .'/attachment/'. $this->id .'*';

    if (false !== ($files = glob($path))) {
      foreach ($files as $filename) {
        $parts = explode(',', $filename);
        if (basename($parts[0]) == $this->id)
          unlink($filename);
      }
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

    if (file_exists($filename = mcms::config('filestorage') .'/'. $this->filepath))
      unlink($filename);
  }

  public function duplicate()
  {
    throw new ForbiddenException(t('Клонирование файлов невозможно.'));
  }

  // Импорт файла из массива $post.
  public function import(array $file, $uploaded = true)
  {
    $storage = mcms::config('filestorage');

    // Немного валидации.
    if (empty($file['tmp_name']) or !file_exists($file['tmp_name']))
      throw new Exception(t("Не удалось импортировать исходный файл."));

    // Угадваем значения некоторых полей, для упрощения скриптинга.
    if (!array_key_exists('size', $file))
      $file['size'] = filesize($file['tmp_name']);
    if (!array_key_exists('name', $file))
      $file['name'] = basename($file['tmp_name']);
    if (!array_key_exists('type', $file) or 'application/octet-stream' == $file['type'])
      $file['type'] = bebop_get_file_type($file['tmp_name'], $file['name']);

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

      // Исправление для Issue 300: файла физически нет, и заменить ноду нельзя.
      if (!file_exists($storage .'/'. $this->filepath))
        throw new ObjectNotFoundException();

      $this->data = $node->data;
    }

    // Файл не найден, создаём новый.
    catch (ObjectNotFoundException $e) {
      // Сюда будем копировать файл.
      $dest = $storage .'/'. $this->filepath;

      // Создаём каталог для него.
      mcms::mkdir(dirname($dest), 'Файл не удалось сохранить, т.к. отсутствуют права на запись в каталог, где этот файл должен был бы храниться (%path).  Сообщите об этой проблеме администратору сайта.');

      // Копируем файл.
      if ($uploaded) {
        if (!($c1 = is_uploaded_file($file['tmp_name'])) or !($c2 = move_uploaded_file($file['tmp_name'], $dest))) {
          $debug = sprintf("File could not be uploaded.\nis_uploaded_file: %d\nmove_uploaded_file: %d\ndestination: %s", $c1, $c2, $storage .'/'. $this->filepath);
          mcms::debug($debug, $file, $this);
          throw new UserErrorException("Ошибка загрузки", 400, "Ошибка загрузки", "Не удалось загрузить файл: ошибка {$file['error']}");
        }
      } elseif (!copy($file['tmp_name'], $dest)) {
        throw new UserErrorException("Ошибка загрузки", 400, "Ошибка загрузки", "Не удалось скопировать файл {$file['tmp_name']} в {$dest}.");
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
    $tmpdir = mcms::mkdir(mcms::config('tmpdir') .'/upload');

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
            mcms::mkdir($zdir);
          } else {
            $name = zip_entry_name($zip_entry);
            $tmpname = tempnam($tmpdir, 'unzip');

            file_put_contents($tmpname, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));

            $node = Node::create('file');
            $node->import(array(
              'tmp_name' => $tmpname,
              'name' => $name,
              'filename' => $name,
              ), false);
            $node->save();

            unlink($tmpname);
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
    if (null === $this->id) {
      $form = new Form(array(
        ));

      $ftpfiles = self::listFilesOnFTP();

      $modes = array(
        'local' => t('Со своего компьютера'),
        'ftp' => t('Из тех, что загружены на FTP'),
        'remote' => t('Скачать с другого сайта'),
        );

      if (empty($ftpfiles))
        unset($modes['ftp']);

      $form->addControl(new EnumRadioControl(array(
        'value' => '__file_mode',
        'label' => t('Как вы хотите добавить файл?'),
        'options' => $modes,
        'default' => 'local',
        'required' => true,
        )));

      $form->addControl(new HiddenControl(array(
        'value' => 'node_content_id',
        )));
      $form->addControl(new HiddenControl(array(
        'value' => 'node_content_class',
        )));

      $form->addControl(new AttachmentControl(array(
        'value' => '__file_node_update',
        'label' => t('Загрузите новый файл'),
        'unzip' => true,
        )));

      $form->addControl(new SetControl(array(
        'value' => '__file_from_ftp',
        'label' => t('Загрузить файлы с FTP'),
        'options' => self::listFilesOnFTP(),
        )));

      $form->addControl(new URLControl(array(
        'value' => '__file_url',
        'label' => t('Загрузить файл из интернета'),
        'description' => t('Укажите полный адрес файла, включая префикс http://, и я попытаюсь добавить его в CMS.'),
        )));

      $form->addControl(new SubmitControl(array(
        'text' => t('Загрузить'),
        )));
    } else {
      $form = parent::formGet($simple);

      $form->addControl(new AttachmentControl(array(
        'value' => '__file_node_update',
        'label' => t('Заменить файл'),
        )));
    }

    /*
    $form->addControl(new SubmitControl(array(
      'text' => t('Загрузить'),
      )));
    */

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
    if (null === $this->id) {
      switch ($data['__file_mode']) {
      case 'ftp':
        if (!empty($data['__file_from_ftp']) and is_array($data['__file_from_ftp']))
          self::getFilesFromFTP($data['__file_from_ftp']);
        return;

      case 'remote':
        if (!empty($data['__file_url'])) {
          if (null !== ($tmp = mcms_fetch_file($data['__file_url'], false))) {
            $file = Node::create('file', array(
              'published' => true,
              ));

            $file->import(array(
              'name' => basename($data['__file_url']),
              'tmp_name' => $tmp,
              ), false);

            $file->save();
          } else {
            throw new InvalidArgumentException(t('Не удалось загрузить указанный файл.'));
          }
        }
        return;

      case 'local':
        if (!empty($data['__file_node_update']['error']) or empty($data['__file_node_update']['tmp_name'])) {
          $noException = false;
          $errorMessage = 'При загрузке файла возникла ошибка.';

          switch ($data['__file_node_update']['error']) {
            case UPLOAD_ERR_INI_SIZE:
              $errorMessage = t('Загружаемый файл слишком велик: настройки '
                .'позволяют принимать файлы до %limit.', array(
                  '%limit' => ini_get('upload_max_filesize'),
                  ));
              break;
            case UPLOAD_ERR_FORM_SIZE:
              $errorMessage = 'Загружаемый файл больше установленного лимита в форме';
              break;
            case UPLOAD_ERR_PARTIAL:
              $errorMessage = 'Файл не был загружен полностью';
              break;
            case UPLOAD_ERR_NO_TMP_DIR:
              $errorMessage = 'Отсутствует временный каталог для загрузки файлов';
              break;
            case UPLOAD_ERR_CANT_WRITE:
              $errorMessage = 'Невозможно записать файл на диск';
              break;
            case UPLOAD_ERR_EXTENSION:
              $errorMessage = 'Некое расширение php остановило загрузку файла';
              break;
            // Все в порядке, надо просто обработать ситуацию для исключения исключения. ;)
            case UPLOAD_ERR_OK:
            case UPLOAD_ERR_NO_FILE:
              $noException = true;
              break;
          }

          if (false === $noException)
            throw new InvalidArgumentException(t($errorMessage));
        }

        $this->import($data['__file_node_update']);
        $this->name = $this->filename;
        $this->data['published'] = true;
        $this->save();

        return;
      }
    }

    elseif (!empty($data['__file_node_update']) and empty($data['__file_node_update']['error'])) {
      $this->import($data['__file_node_update']);
      $this->save();

      if (empty($data['node_content_name']))
        $data['node_content_name'] = $this->filename;
    }

    // $data['#node_override'] = array_intersect_key($this->data, array_flip(array('filename', 'filetype', 'filesize', 'filepath')));
    parent::formProcess($data);
  }

  // РАБОТА С FTP.

  public static function getFTPRoot()
  {
    if (null === ($path = mcms::config('filestorage_ftp')))
      $path = mcms::config('filestorage') .'/ftp';
    return $path;
  }

  public static function listFilesOnFTP()
  {
    if (!is_dir($path = self::getFTPRoot()))
      return array();

    $result = array();

    if (false !== ($list = glob($path .'/'.'*'))) {
      foreach ($list as $file) {
        if (is_file($file)) {
          $file = basename($file);
          $result[$file] = $file;
        }
      }

      asort($result);
    }

    return $result;
  }

  public static function getFilesFromFTP(array $files, $parent_id = null)
  {
    $path = self::getFTPRoot();
    $available = self::listFilesOnFTP();

    foreach ($files as $file) {
      $file = basename($file);

      if (in_array($file, $available) and is_readable($filename = $path .'/'. $file)) {
        $node = Node::create('file');
        $node->import(array(
          'filename' => $file,
          'tmp_name' => $filename,
          'parent_id' => $parent_id,
          ), false);
        $node->save();
      }

      if (file_exists($filename) and is_writable($path)) {
        RequestController::killFile($filename);
      }
    }
  }

  public function getDefaultSchema()
  {
    return array(
      'title' => 'Файл',
      'description' => 'Используется для наполнения файлового архива.',
      'notags' => true,
      'fields' => array (
        'name' => array (
          'label' => 'Название файла',
          'type' => 'TextLineControl',
          'description' => 'Человеческое название файла, например: &laquo;Финансовый отчёт за 2007-й год&raquo;',
          'required' => true,
          ),
        'filename' => array (
          'label' => 'Оригинальное имя',
          'type' => 'TextLineControl',
          'description' => 'Имя, которое было у файла, когда пользователь добавлял его на сайт.  Под этим же именем файл будет сохранён, если пользователь попытается его сохранить.  Рекомендуется использовать только латинский алфавит: Internet Explorer некорректно обрабатывает кириллицу в именах файлов при скачивании файлов.',
          'required' => true,
          'indexed' => true,
          ),
        'filetype' => array (
          'label' => 'Тип MIME',
          'type' => 'TextLineControl',
          'description' => 'Используется для определения способов обработки файла.  Проставляется автоматически при закачке.',
          'required' => true,
          'indexed' => true,
          ),
        'filesize' => array (
          'label' => 'Размер в байтах',
          'type' => 'NumberControl',
          'required' => true,
          'indexed' => true,
          ),
        'filepath' => array (
          'label' => 'Локальный путь к файлу',
          'type' => 'TextLineControl',
          'required' => true,
          'indexed' => true,
          ),
        'width' => array (
          'label' => 'Ширина',
          'type' => 'NumberControl',
          'description' => 'Проставляется только для картинок и SWF объектов.',
          ),
        'height' => array (
          'label' => 'Высота',
          'type' => 'NumberControl',
          'description' => 'Проставляется только для картинок и SWF объектов.',
          ),
        ),
      );
  }
};
