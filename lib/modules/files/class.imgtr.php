<?php

class Imgtr
{
  const confroot = 'modules/files/transformations';

  public static function on_get_list(Context $ctx)
  {
    $result = '';
    foreach ($ctx->config->getArray(self::confroot) as $k => $v) {
      $result .= html::em('item', array('name' => $k) + $v);
    }

    return html::em('content', array(
      'name' => 'list',
      ), html::em('items', $result));
  }

  public static function on_get_add(Context $ctx)
  {
    $form = self::get_schema()->sort()->getForm(array(
      'title' => t('Новое правило трансформации'),
      ));
    return html::em('content', array(
      'name' => 'form',
      ), $form->getXML(Control::data()));
  }

  /**
   * Создание нового правила.
   */
  public static function on_post_add(Context $ctx)
  {
    $data = self::get_schema()->getFormData($ctx)->dump();

    $name = $data['name'];
    unset($data['name']);

    $all = $ctx->config->getArray(self::confroot);
    if (isset($all[$name]))
      throw new BadRequestException(t('Такое правило уже есть.'));
    $ctx->config->set(self::confroot . '/' . $name, $data)->save();

    return $ctx->redirect('admin/system/settings/imgtransform');
  }

  /**
   * Настройка правила (форма).
   */
  public static function on_get_edit(Context $ctx)
  {
    if (!($name = $ctx->get('name')))
      throw new BadRequestException(t('Не указано имя трансформации (параметр name).'));

    if (!($data = $ctx->config->getArray(self::confroot . '/' . $name)))
      throw new PageNotFoundException();

    $data['name'] = $name;

    $form = self::get_schema()->sort()->getForm(array(
      'title' => t('Настройка трансформации «%name»', array(
        '%name' => $name,
        )),
      ))->getXML(Control::data($data));

    return html::em('content', array(
      'name' => 'form',
      ), $form);
  }

  /**
   * Настройка правила (обработчик).
   */
  public static function on_post_edit(Context $ctx)
  {
    if (!($name = $ctx->get('name')))
      throw new BadRequestException(t('Не указано имя (GET-параметр name).'));

    $all = $ctx->config->getArray(self::confroot);
    if (!array_key_exists($name, $all))
      throw new PageNotFoundException();

    $data = self::get_schema()->getFormData($ctx)->dump();

    unset($all[$name]);
    $name = $data['name'];
    unset($data['name']);
    $all[$name] = $data;

    $ctx->config->set(self::confroot, $all)->save();

    return $ctx->getRedirect('admin/system/settings/imgtransform');
  }

  /**
   * Возвращает схему для редактирования правил.
   */
  private static function get_schema()
  {
    return new Schema(array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Имя'),
        'description' => t('Только небольшие латинские буквы.'),
        'required' => true,
        'weight' => 10,
        ),
      'width' => array(
        'type' => 'NumberControl',
        'label' => t('Требуемая ширина'),
        'weight' => 20,
        ),
      'height' => array(
        'type' => 'NumberControl',
        'label' => t('Требуемая высота'),
        'weight' => 30,
        ),
      'format' => array(
        'type' => 'EnumRadioControl',
        'label' => t('Формат файла'),
        'options' => array(
          'jpg' => 'JPEG',
          'png' => 'PNG',
          ),
        'required' => true,
        'weight' => 40,
        ),
      'quality' => array(
        'type' => 'NumberControl',
        'label' => t('Качество, %'),
        'default' => 75,
        'weight' => 50,
        'description' => t('По умолчанию: 75%. Используется только если выбран формат JPEG.'),
        ),
      'scalemode' => array(
        'type' => 'EnumRadioControl',
        'label' => t('Режим масштабирования'),
        'options' => array(
          'scale' => t('Вписать в указанный прямоугольник целиком'),
          'crop' => t('Вписать и обрезать, получив нужный размер'),
          ),
        'required' => true,
        'weight' => 60,
        ),
      'submit' => array(
        'type' => 'SubmitControl',
        'label' => t('Сохранить'),
        'weight' => 70,
        ),
      ));
  }

  /**
   * Обработка файла.
   */
  public static function transform(FileNode $node)
  {
    if (0 !== strpos($node->filetype, 'image/'))
      return;

    $result = array();

    // Относительный путь к исходному файлу
    $source = $node->getRealURL();

    // Путь к файловому хранилищу, используется только чтобы
    // из полного пути сделать относительный после трансформации.
    $storage = Context::last()->config->getPath('modules/files/storage', 'files');

    // Файлы без расширений не обрабатываем, чтобы не нагенерировать каких-нибудь странных имён.
    if (!($ext = os::getFileExtension($source)))
      return;

    // Правила перевода расширений в типы.
    $typemap = array(
      'png' => 'image/png',
      'jpg' => 'image/jpeg',
      );

    foreach (Context::last()->config->getArray(self::confroot) as $name => $settings) {
      $target = substr($source, 0, - strlen($ext)) . $name . '.' . $settings['format'];

      if (file_exists($target))
        unlink($target);

      $im = ImageMagick::getInstance();

      if ($im->open($source)) {
        $options = array(
          'downsize' => ($settings['scalemode'] != 'crop'),
          'crop' => ($settings['scalemode'] == 'crop'),
          'quality' => intval($settings['quality']),
          );

        if ($im->scale($settings['width'], $settings['height'], $options)) {
          if ($im->save($target, $typemap[$settings['format']])) {
            $tmp = array(
              'width' => $im->getWidth(),
              'height' => $im->getHeight(),
              'filename' => substr($target, strlen($storage) + 1),
              'filesize' => filesize($target),
              );

            $result[$name] = $tmp;
          }
        }
      }
    }

    return $result;
  }
}
