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
    if (DIRECTORY_SEPARATOR == substr($this->filepath, 0, 0))
      throw new RuntimeException(t('Путь к файлу должен быть относительным, а не абсолютным.'));

    $path = os::path(MCMS_SITE_FOLDER, Context::last()->config->get('modules/files/storage'), $this->filepath);

    if ($tmp = Imgtr::transform($this)) 
      $this->versions = $tmp;
    else
      unset($this->versions);

    $res = parent::save();

    if ($this->isNew())
      $this->publish();

    return $res;
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

    if (file_exists($filename = os::path(self::getStoragePath(), $this->filepath)))
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
   * конфигурационным файлом (параметр files).
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
    $storage = os::path(MCMS_SITE_FOLDER, Context::last()->config->get('modules/files/storage'));

    // Немного валидации.
    if (empty($file['tmp_name']) or !file_exists($file['tmp_name']))
      throw new Exception(t("Не удалось импортировать исходный файл."));

    // Угадваем значения некоторых полей, для упрощения скриптинга.
    if (!isset($file['size']))
      $file['size'] = filesize($file['tmp_name']);
    if (!isset($file['name']))
      $file['name'] = basename($file['tmp_name']);
    if (!isset($file['type']))
      $file['type'] = os::getFileType($file['tmp_name']);

    if ($this->id === null and FileNode::isUnzipable($file)) {
      if (null === ($node = $this->unzip($file['tmp_name'])))
        throw new InvalidArgumentException("ZIP file was empty");
      $this->data = $node->getRaw();
      return $this;
    }

    $this->filepath = os::mkunique($this->getCleanFileName($file), $storage);
    $this->filename = $this->name = $file['name'];
    $this->filetype = $file['type'];
    $this->filesize = $file['size'];

    // Сюда будем копировать файл.
    $dest = os::path($storage, $this->filepath);

    if (file_exists($dest))
      throw new RuntimeException(t('Такой файл уже есть.'));

    // Создаём каталог для него.
    os::mkdir(dirname($dest), 'Файл не удалось сохранить, т.к. отсутствуют права на запись в каталог, где этот файл должен был бы храниться (%path).  Сообщите об этой проблеме администратору сайта.', array(
      '%path' => dirname($dest),
      ));

    // Копируем файл.
    if (!os::copy($file['tmp_name'], $dest))
      throw new RuntimeException(t('Не удалось скопировать файл %src в %dst.', array(
        '%src' => $file['tmp_name'],
        '%dst' => $dest,
        )));

    // Прикрепляем файл к родительскому объекту.
    if (!empty($file['parent_id']))
      $this->linkAddParent($file['parent_id']);

    return $this;
  }

  /**
   * Возвращает очищенное (от некошерных символов) имя файла.
   */
  private function getCleanFileName(array &$file)
  {
    $filename = mcms::translit($file['name']);
    $filename = preg_replace('/[^a-z0-9_.-]+/', '_', $filename);
    $filename = trim($filename, '_');
    $filename .= $this->getSafeExtension($filename);

    $md5 = md5_file($file['tmp_name']);

    $filepath = substr($md5, 0, 1) . '/' . substr($md5, 1, 1) . '/'
      . $filename;

    return $filepath;
  }

  /**
   * Возвращает безопасное расширение файла.
   */
  private function getSafeExtension($filename = null)
  {
    if (null === $filename)
      $filename = $this->filename;

    if (null === ($ext = os::getFileExtension($filename)))
      return null;

    switch ($ext) {
    case 'php':
    case 'php3':
    case 'php4':
    case 'pl':
    case 'phtml':
    case 'inc':
    case 'tpl':
    case 'sh':
      $ext .= '.txt';
      break;
    default:
      return null;
    }

    return $ext;
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
    $tmpdir = os::mkdir(os::path(Context::last()->config->getPath('main/tmpdir'), 'upload'));

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

            os::mkdir($zdir);
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
              'type' => os::getFileType($name),
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
  public function formProcess(array $data, $fileName = null)
  {
    parent::formProcess($data, $fileName);

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
   * параметром files_ftp; если такого параметра нет — используется
   * подпапка "ftp" в обычном файловом хранилище.
   *
   * @todo вынести из конфига в настройки модуля base.
   *
   * @return string
   */
  public static function getFTPRoot()
  {
    $config = Context::last()->config;
    if (null === ($path = $config->getPath('files_ftp')))
      $path = os::path(self::getStoragePath(), 'ftp');
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
        'volatile' => true,
        ),
      'filepath' => array (
        'label' => t('Локальный путь к файлу'),
        'type' => 'TextLineControl',
        'required' => true,
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

      /*
      $fields['replace'] = new AttachmentControl(array(
        'value' => 'replace',
        'label' => t('Заменить другим файлом'),
        'archive' => false,
        'unzip' => false,
        ));
      */

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
    if (file_exists($tmp = os::path(self::getStoragePath(), $this->filepath)))
      $result['url'] = $tmp;
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

    if ($this->checkPermission('u'))
      $list['replace'] = array(
        'icon' => 'upload',
        'title' => t('Заменить файл'),
        'href' => "admin/node/{$this->id}/upload?destination=CURRENT",
        );

    return $list;
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

  public function getRealURL()
  {
    return os::path(self::getStoragePath(), $this->filepath);
  }

  public function getListURL()
  {
    return 'admin/content/files';
  }

  public static function getStoragePath(Context $ctx = null)
  {
    if (null === $ctx)
      $ctx = Context::last();
    return os::path(MCMS_SITE_FOLDER, $ctx->config->get('modules/files/storage'));
  }

  /**
   * Возвращает дополнительные поля для предварительного просмотра.
   */
  public function getPreviewXML(Context $ctx)
  {
    $result = parent::getPreviewXML($ctx);

    if ($this->width and $this->height) {
      $tmp = $this->width . '×' . $this->height;
      if ($this->filesize)
        $tmp .= ', ' . mcms::filesize($this->filesize);
      $result .= html::em('field', array(
        'title' => t('Исходные размеры'),
        ), html::em('value', html::cdata($tmp)));
    }

    if ($this->duration)
      $result .= html::em('field', array(
        'title' => t('Продолжительность'),
        ), html::em('value', html::cdata($this->duration)));

    if ($this->bitrate)
      $result .= html::em('field', array(
        'title' => t('Битрейт'),
        ), html::em('value', html::cdata(ceil($this->bitrate))));

    if ($this->channels) {
      switch ($this->channels) {
      case 1:
        $tmp = t('моно');
        break;
      case 2:
        $tmp = t('стерео');
        break;
      case 4:
        $tmp = t('квадро');
        break;
      default:
        $tmp = t('%count каналов', array(
          '%count' => $this->channels,
          ));
      }
      $result .= html::em('field', array(
        'title' => t('Звук'),
        ), html::em('value', array(
          'channels' => $this->channels,
          ), html::cdata($tmp)));
    }

    if ($tmp = $this->getEmbedHTML($ctx)) {
      $result .= html::em('field', array(
        'title' => t('Оригинал'),
        ), html::em('value', array('class' => 'embed'), html::cdata($tmp)));
    }

    if (!empty($this->versions)) {
      foreach ((array)$this->versions as $name => $info) {
        $em = array(
          'title' => t('Версия %name', array(
            '%name' => $name,
            )),
          );

        $url = os::webpath($ctx->config->getPath('modules/files/storage'), $info['filename']);

        $tmp = t('<a href="@url"><img src="@url" width="%width" height="%height" alt="%filename" /></a>', array(
          '%name' => $name,
          '%width' => $info['width'],
          '%height' => $info['height'],
          '%filename' => basename($info['filename']),
          '@url' => $url,
          ));
        $em['#text'] = html::em('value', html::cdata($tmp));

        $result .= html::em('field', $em);
      }

      $versions = html::wrap('ul', $versions);

      $result .= html::em('field', array(
        'title' => t('Другие версии'),
        ), html::em('value', html::cdata($versions)));
    }

    $count = Node::count($ctx->db, array(
      'deleted' => 0,
      'tagged' => $this->id,
      ));
    if ($count) {
      $message = t('%count документов используют этот файл (<a href="@url">список</a>)', array(
        '%count' => $count,
        '@url' => 'admin/content/list?search=tagged%3A' . $this->id,
        ));
      $result .= html::em('field', array(
        'title' => t('Статистика'),
        ), html::em('value', html::cdata($message)));
    }

    return $result;
  }

  public function getEmbedHTML(Context $ctx)
  {
    $url = $ctx->url()->getBase($ctx) . os::webpath(MCMS_SITE_FOLDER, $ctx->config->get('modules/files/storage'), $this->filepath);

    if (0 === strpos($this->filetype, 'image/'))
      return t('<img src="@url" alt="%name" />', array(
        '@url' => $url,
        '%name' => $this->name,
        '%width' => $this->width,
        '%height' => $this->height,
        ));

    if (0 === strpos($this->filetype, 'video/'))
      return t('<object width="%width" height="%height" type="%type" data="@url" />', array(
        '@url' => $url,
        '%type' => $this->filetype,
        '%width' => $this->width,
        '%height' => $this->height,
        ));
  }

  /**
   * Возвращает описание версий в XML.
   */
  public function getVersionsXML()
  {
    $result = '';

    $storage = Context::last()->config->getPath('modules/files/storage', 'files');

    foreach ((array)$this->versions as $name => $info) {
      $info['name'] = $name;
      $info['url'] = os::webpath($storage, $info['filename']);
      $info['filename'] = basename($info['filename']);
      $result .= html::em('version', $info);
    }

    return $result;
  }

  /**
   * Возвращает дополнительные XML данные для ноды.
   */
  public function getExtraXMLContent()
  {
    return html::wrap('versions', $this->getVersionsXML());
  }
};
