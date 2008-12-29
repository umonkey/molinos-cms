<?php

class template
{
  /**
   * Подбирает подходящий шаблон и пропускает через него данные.
   * В случае неудачи возвращает false.
   */
  public static function render($themeName, $templateType, $templateName, array $data = array())
  {
    if (false === strpos($themeName, DIRECTORY_SEPARATOR))
      $themeName = os::path('themes', $themeName);

    $pass1 = $pass2 = array();

    // Формирование списка возможных шаблонов.
    foreach (self::getEngines() as $ext => $class) {
      $pass1[os::path($themeName, 'templates', $templateType . '.' . $templateName . '.' . $ext)] = $class;
      $pass2[os::path($themeName, 'templates', $templateType . '.default.' . $ext)] = $class;
    }

    // Поиск и обработка шаблона.
    foreach ($pass1 + $pass2 as $file => $class)
      if (false !== ($output = self::renderFile($file, $class, $data)))
        return $output;

    return false;
  }

  /**
   * Пропускает данные через шаблон указанного класса.
   */
  public static function renderClass($className, array $data)
  {
    if (false !== ($classPath = Loader::getClassPath())) {
      $base = self::getBaseName($classPath);

      foreach (self::getEngines() as $ext => $class)
        if (file_exists($tmp = $base . '.' . $ext))
          return self::renderFile($tmp, $class, $data);
    }

    return false;
  }

  /**
   * Пропускает шаблон $fileName через шаблонизатор $className.  Подключает
   * дополнительные css/js файлы.  При отсутствии обработчика возвращает false.
   */
  private static function renderFile($fileName, $className, array $data)
  {
    if (file_exists($fileName)) {
      $result = call_user_func(array($className, 'processTemplate'), $fileName, $data);

      $base = self::getBaseName($fileName);

      if (file_exists($tmp = $base . '.css'))
        mcms::extras($tmp);

      if (file_exists($tmp = $base . '.js'))
        mcms::extras($tmp);

      return $result;
    }

    return false;
  }

  /**
   * Возвращает массив с описанием шаблонизаторов:
   *   расширение => класс
   */
  private static function getEngines()
  {
    $engines = Structure::getInstance()->getTemplateEngines();

    return $engines;
  }

  /**
   * Возвращает путь к файлу без расширения.
   */
  private static function getBaseName($fileName)
  {
    return substr($fileName, 0, strrpos($fileName, '.'));
  }
}
