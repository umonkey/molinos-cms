<?php
/**
 * Тип документа "file" — файл.
 *
 * @package mod_base
 * @subpackage Types
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Тип документа "file" — файл.
 *
 * @package mod_base
 * @subpackage Types
 */
class FileNode extends Node implements iContentType
{
  private $nosave = false;

  /**
   * Сохранение объекта.
   *
   * При сохранении картинки определяет её размеры, копирует
   * в свойства "width" и "height".  После успешного сохранения
   * (если не возникло исключение) удаляет из файлового кэша
   * отмасштабированные версии картинки (по маске attachment/id*).
   *
   * @return void
   */
  public function save()
  {
    if (!empty($this->nosave))
      return $this;

    if (empty($this->filepath))
      throw new RuntimeException(t('Ошибка загрузки файла.'));

    $path = mcms::config('filestorage');
    $path .= '/'. $this->filepath;

    if ('image/' == substr($this->filetype, 0, 6)) {
      if (is_readable($path) and ($info = @getimagesize($path))) {
        $this->width = $info[0];
        $this->height = $info[1];
      }
    }

    elseif ('.swf' == strtolower(substr($this->filename, -4))) {
      if (class_exists('ID3Tools')) {
        list($x, $y) = ID3Tools::getFlashSize($this);

        $this->width = $x;
        $this->height = $y;
      }
    }

    $res = parent::save();

    $this->purge();

    return $res;
  }

  /**
   * Удаление версий файла из кэша.
   */
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

  /**
   * Перемещение файла в корзину.
   *
   * Перед удалением файла из удаляет его версии из файлового кэша,
   * т.к. файл более не будет доступен извне — его отмасштабированные
   * версии не нужны.
   *
   * @return void
   */
  public function delete()
  {
    $this->purge();
    parent::delete();
  }

  /**
   * Полное удаление файла из корзины.
   *
   * Удаляет отмасштабированные версии из файлового кэша и из основного
   * файлового хранилища.
   *
   * @return void
   */
  public function erase()
  {
    parent::erase();

    $this->purge();

    if (file_exists($filename = mcms::config('filestorage') .'/'. $this->filepath))
      unlink($filename);
  }

  /**
   * Залушка: кидает ForbiddenException с сообщением о невозможности
   * клонирования файлов.
   *
   * @return void
   */
  public function duplicate()
  {
    throw new ForbiddenException(t('Клонирование файлов невозможно.'));
  }

  /**
   * Импорт файла из внешнего источника.
   *
   * Используется как для обработки полученных от браузера файлов, так и для
   * ручного добавления файлов в архив.  Путь к файловому архиву определяется
   * конфигурационным файлом (параметр filestorage).
   *
   * Внутреннее имя файла при копировании в архив формируется с использованием
   * md5-суммы его содержимого, поэтому в архив нельзя два раза добавить один
   * файл.  При обнаружении попытки повторной загрузки файла (с таким же
   * filepath) метод прозрачно подменяет содержимое текущей ноды содержимым
   * уже существующей, новую не создаёт.
   *
   * При невозможности скопировать файл в архив возникает UserErrorException.
   *
   * @return void
   * @param array $file описание файла
   * @param bool $uploaded проверять, действительно ли файл пришёл от браузера.
   *
   * Обязательные ключи: tmp_name (полный путь к фалу, который нужно
   * скопировать в архив), опциональные: type, name, size, parent_id.  При
   * отсутствии type, тип файла определяется эвристически.
   *
   * При указании parent_id файл автоматически прикрепляется к указанной в этом
   * параметре ноде, с помощью Node::linkAddParent().
   */
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

    if ($this->id === null and FileNode::isUnzipable($file)) {
      if (null === ($node = $this->unzip($file['tmp_name'])))
        throw new InvalidArgumentException("ZIP file was empty");
      $this->data = $node->getRaw();
      return $this;
    }

    // Заполняем метаданные.
    $this->filename = $this->name = $file['name'];
    $this->filetype = $file['type'];
    $this->filesize = $file['size'];

    // Формируем внутреннее имя файла.
    $intname = md5_file($file['tmp_name']);
    $this->filepath = substr($intname, 0, 1) .'/'. substr($intname, 1, 1) .'/'. $intname;

    $existing = null;

    // Находим существующий файл.
    try {
      $node = Node::find(array(
        'class' => 'file',
        'filepath' => $this->filepath,
        ), 1);

      if (empty($node))
        throw new ObjectNotFoundException();

      // Исправление для Issue 300: файла физически нет, и заменить ноду нельзя.
      if (!file_exists($storage .'/'. $this->filepath))
        throw new ObjectNotFoundException();

      $existing = array_shift($node)->data;
    }

    // Теоретически это должно возникать только при добавлении
    // нового файла в архив, когда мы возвращаем укороченную
    // форму.
    catch (NoIndexException $e) {
    }

    catch (ObjectNotFoundException $e) {
    }

    if (null !== $existing)
      $this->data = $existing;

    // Файл не найден, создаём новый.
    else {
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

    return $this;
  }

  /**
   * Импорт нескольких файлов из архива.
   *
   * Вытаскивает из архива все файлы, добавляет их в файловый архив.
   *
   * @return Node последний добавленный в архив файл
   * @param string $zipfile путь к ZIP-архиву.
   */
  public static function unzip($zipfile, $folder = null, $parent_id = null)
  {
    $node = null;
    $tmpdir = mcms::mkdir(mcms::config('tmpdir') .'/upload');

    if (function_exists('zip_open')) {
      if (file_exists($zipfile)) {
        $zip = zip_open($zipfile);
        while ($zip_entry = zip_read($zip)) {
          zip_entry_open($zip, $zip_entry);

          if (substr(zip_entry_name($zip_entry), -1) == '/') {
            /*
            mcms::debug(zip_entry_name($zip_entry));

            $zdir = substr(zip_entry_name($zip_entry), 0, -1);
            if (file_exists($zdir))
              throw new Exception('Directory "<b>' . $zdir . '</b>" exists');

            mcms::mkdir($zdir);
            */
          } else {
            $name = basename(zip_entry_name($zip_entry));
            $tmpname = tempnam($tmpdir, 'unzip');

            file_put_contents($tmpname, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));

            $node = Node::create('file');
            $node->import($a = array(
              'parent_id' => $parent_id,
              'tmp_name' => $tmpname,
              'name' => $name,
              'filename' => $name,
              'type' => bebop_get_file_type($name),
              ), false);

            $node->save();

            unlink($tmpname);
          }
          zip_entry_close($zip_entry);
        }
        zip_close($zip);
      } else {
        throw new RuntimeException("No such file {$zipfile}");
      }
    } else {
      throw new RuntimeException('zlib extension is not available');
    }

    return $node;
  }

  public function getFormTitle()
  {
    return $this->id
      ? t('Редактирование файла «%name»', array('%name' => $this->filename))
      : t('Добавление нового файла');
  }

  public function getFormSubmitText()
  {
    return $this->id
      ? parent::getFormSubmitText()
      : t('Добавить');
  }

  /**
   * Обработка пришедшей формы.
   *
   * Вызывается из NodeApiModule, вручную вызывать не нужно.
   *
   * Обработка замены содержимого файла.  Порядок действий именно такой потому,
   * что parent::formProcess() обновляет все поля, а нам нужно изменить некоторые
   * из них вручную.  После импорта файл снова сохраняется.
   *
   * Для помещения файла в архив использует FileNode::import().
   *
   * @return void
   */
  public function formProcess(array $data)
  {
    parent::formProcess($data);

    // Замена файла.
    if ($this->replace instanceof Node) {
      foreach (array('name', 'filename', 'filetype', 'filesize', 'filepath') as $k)
        $this->$k = $this->replace->$k;
      unset($this->replace);
    }

    return ($this->file instanceof FileNode)
      ? $this->file
      : $this;
  }

  // РАБОТА С FTP.

  /**
   * Возвращает путь к FTP папке.
   *
   * Папка FTP используется для загрузки больших файлов, которые проблематично
   * загрузить через браузер.  Путь указывается в конфигурационном файле,
   * параметром ftpfolder; если такого параметра нет — используется
   * подпапка "ftp" в обычном файловом хранилище.
   *
   * @todo вынести из конфига в настройки модуля base.
   *
   * @return string
   */
  public static function getFTPRoot()
  {
    if (null === ($path = mcms::config('ftpfolder')))
      $path = mcms::config('filestorage') . DIRECTORY_SEPARATOR . 'ftp';
    return $path;
  }

  /**
   * Возвращает список файлов в FTP папке.
   *
   * @return array массив с именами файлов, содержащихся в корневой папке FTP.
   * Вложенные папки не обрабатываются.  Имена отсортированы в алфавитном
   * порядке.  Пустой массив означает, что файлов нет.
   */
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

  /**
   * Импортирует файлы из FTP.
   *
   * Импортирует указанные файлы, находящиеся в FTP папке, в файловый архив.
   *
   * @return void
   *
   * @param array $files имена файлов для добавления в архив.  Если указанных
   * файлов в папке не окажется — ничего не произойдёт, они просто не добавятся
   * в архив.
   *
   * @param integer $parent_id идентификатор объекта, к которому следует
   * прикрепить добавленные файлы.
   */
  public static function getFilesFromFTP(array $files, $parent_id = null)
  {
    $path = self::getFTPRoot();
    $available = self::listFilesOnFTP();

    foreach ($files as $file) {
      $file = basename($file);

      if (in_array($file, $available) and is_readable($filename = $path .'/'. $file)) {
        $node = Node::create('file')->import($i = array(
          'filename' => $file,
          'tmp_name' => $filename,
          'parent_id' => $parent_id,
          ), false);

        $node->save();
      }

      Context::killFile($filename);
    }
  }

  public function importFromFTP($filename)
  {
    $path = self::getFTPRoot() . DIRECTORY_SEPARATOR . basename($filename);

    if (!file_exists($path))
      throw new RuntimeException(t('Файл %path не найден в папке FTP.', array(
        '%path' => $filename,
        )));

    return $this->import(array(
      'tmp_name' => $path,
      'name' => basename($path),
      ), false);
  }

  /**
   * Возвращает базовую структуру файла.
   *
   * @return array структура типа file, используемая если в базе данных
   * структура не обнаружена (хранится в виде ноды с типом "type" и
   * именем "file").
   */
  public static function getDefaultSchema()
  {
    return array(
      'name' => array (
        'label' => t('Название файла'),
        'type' => 'TextLineControl',
        'description' => t('Человеческое название файла, например: «Финансовый отчёт за 2007-й год»'),
        'required' => true,
        ),
      'filename' => array (
        'label' => t('Оригинальное имя'),
        'type' => 'TextLineControl',
        'description' => t('Имя, которое было у файла, когда пользователь добавлял его на сайт.  Под этим же именем файл будет сохранён, если пользователь попытается его сохранить.  Рекомендуется использовать только латинский алфавит: Internet Explorer некорректно обрабатывает кириллицу в именах файлов при скачивании файлов.'),
        'required' => true,
        'indexed' => true,
        'volatile' => true,
        ),
      'filetype' => array (
        'label' => t('Тип MIME'),
        'type' => 'TextLineControl',
        'description' => t('Используется для определения способов обработки файла.  Проставляется автоматически при закачке.'),
        'required' => true,
        'indexed' => true,
        'volatile' => true,
        ),
      'filesize' => array (
        'label' => t('Размер в байтах'),
        'type' => 'NumberControl',
        'required' => true,
        'indexed' => true,
        'volatile' => true,
        ),
      'filepath' => array (
        'label' => t('Локальный путь к файлу'),
        'type' => 'TextLineControl',
        'required' => true,
        'indexed' => true,
        'volatile' => true,
        ),
      'width' => array (
        'label' => t('Ширина'),
        'type' => 'NumberControl',
        'description' => t('Проставляется только для картинок и SWF объектов.'),
        'volatile' => true,
        ),
      'height' => array (
        'label' => t('Высота'),
        'type' => 'NumberControl',
        'description' => t('Проставляется только для картинок и SWF объектов.'),
        'volatile' => true,
        ),
      );
  }

  /**
   * Спецобработка добавления нового файла в архив.
   */
  public function getFormFields()
  {
    if ($this->id) {
      $fields = parent::getFormFields();

      $fields['replace'] = new AttachmentControl(array(
        'value' => 'replace',
        'label' => t('Заменить другим файлом'),
        'archive' => false,
        'unzip' => false,
        ));

      return $fields;
    }

    return new Schema(array(
      'file' => array(
        'type' => 'AttachmentControl',
        'required' => true,
        'newfile' => true,
        'unzip' => true,
        'archive' => false,
        'fetch' => true,
        ),
      ));
  }

  /**
   * Проверяет, можно ли файл распаковать.
   */
  public static function isUnzipable(array $finfo)
  {
    if (empty($finfo['unzip']))
      return false;

    switch ($finfo['type']) {
      case 'application/zip':
      case 'application/x-zip-compressed':
        return true;
    }

    switch (strtolower(substr($finfo['name'], strrpos($finfo['name'], '.')))) {
    case '.zip':
      return true;
    }

    return false;
  }

  public function getRaw()
  {
    $result = parent::getRaw();
    $result['_url'] = $this->_url;
    return $result;
  }

  public function getActionLinks()
  {
    $list = parent::getActionLinks();

    if (array_key_exists('locate', $list)) {
      $list['locate']['href'] = $this->_url;
      $list['locate']['icon'] = 'download';
      $list['locate']['title'] = t('Скачать');
    }

    if (array_key_exists('clone', $list))
      unset($list['clone']);

    return $list;
  }

  public function __get($key)
  {
    if ('_url' == $key)
      return empty($_GET['__cleanurls'])
        ? '?q=attachment/'. $this->id .'/'. urlencode($this->filename)
        : 'attachment/'. $this->id .'/'. urlencode($this->filename);
    return parent::__get($key);
  }

  public static function fromURL($url)
  {
    $node = Node::create('file');

    $u = parse_url($url);
    $name = empty($u['path'])
      ? 'unnamed'
      : basename($u['path']);

    if (false === ($headers = get_headers($url, 1)))
      throw new RuntimeException(t('Не удалось определить тип файла: %url', array('%url' => $url)));
    else
      $type = $headers['Content-Type'];

    $node = $node->import($i = array(
      'tmp_name' => http::fetch($url),
      'name' => $name,
      'type' => $type,
      ), false);

    return $node;
  }
};
