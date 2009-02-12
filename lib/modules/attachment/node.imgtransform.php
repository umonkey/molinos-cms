<?php

class ImgTransformNode extends Node implements iContentType
{
  public function getFormFields()
  {
    $schema = new Schema(array(
      'name' => array(
        'type' => 'TextLineControl',
        'label' => t('Имя'),
        'description' => t('Только небольшие латинские буквы.'),
        'required' => true,
        ),
      'size' => array(
        'type' => 'TextLineControl',
        'label' => t('Размеры'),
        'description' => t('Формат: ШxВ.'),
        're' => '@^\d+x\d+$@',
        'required' => true,
        ),
      'scalemode' => array(
        'type' => 'EnumRadioControl',
        'label' => t('Режим масштабирования'),
        'options' => array(
          'scale' => t('Вписать в указанный прямоугольник целиком'),
          'crop' => t('Вписать и обрезать, получив нужный размер'),
          ),
        'required' => true,
        ),
      'quality' => array(
        'type' => 'EnumControl',
        'label' => t('Качество выходного изображения'),
        'required' => true,
        'options' => array(
          'png' => t('идеальное (PNG)'),
          '8' => t('хорошее (JPEG 80%)'),
          '5' => t('среднее (JPEG 50%)'),
          '3' => t('плохое (JPEG 30%)'),
          '1' => t('"попробуй, угадай" (JPEG 10%)'),
          ),
        ),
      ));

    return $schema;
  }

  public function getFormTitle()
  {
    return $this->isNew()
      ? t('Добавление новой транфсормации')
      : t('Трансформация «%name»', array(
        '%name' => $this->name,
        ));
  }

  public function checkPermission($perm)
  {
    return true;
  }

  /**
   * Трансформирует указанный файл.
   */
  public function apply(FileNode &$file)
  {
    $ctx = Context::last();

    $source = $file->filepath;
    $destination = $this->getTargetFileName($file);
    $prefix = $ctx->config->getPath('files') . DIRECTORY_SEPARATOR;

    if (!file_exists($prefix . $destination)) {
      $im = ImageMagick::getInstance();

      if (!($im->open($prefix . $source, $file->filetype)))
        return false;

      $size = explode('x', $this->size);

      $options = array(
        'downsize' => ($this->scalemode != 'crop'),
        'crop' => ($this->scalemode == 'crop'),
        'quality' => ($this->quality == 'png')
          ? 100
          : $this->quality * 10,
        );

      if (!$im->scale($size[0], $size[1], $options))
        return false;

      if (!$im->save($prefix . $destination, $this->quality == 'png' ? 'image/png' : 'image/jpeg'))
        return false;

      $ver = $file->versions;
      $ver[$this->name] = $destination;
      $file->versions = $ver;

      return true;
    }

    return false;
  }

  private function getTargetFileName(FileNode $file)
  {
    $name = substr($file->filepath, 0, strpos($file->filepath, '.'));

    $name .= '_' . $this->name;

    if ('png' == $this->quality)
      $name .= '.png';
    else
      $name .= '.jpg';

    return $name;
  }

  /**
   * Обновление трансформации.
   */
  public function save()
  {
    $this->deleteTransformedFiles();

    $nodes = Node::find(array(
      'class' => 'file',
      'filetype' => 'image/%',
      ));

    foreach ($nodes as $node)
      if (file_exists($node->getRealURL()))
        if ($this->apply($node))
          $node->save();

    return parent::save();
  }

  /**
   * Удаление трансформации.
   */
  public function delete()
  {
    $this->deleteTransformedFiles();
    return parent::delete();
  }

  /**
   * Удаление трансформированных файлов.
   */
  private function deleteTransformedFiles()
  {
    $files = glob(os::path(Context::last()->config->getPath('files'), '?', '?', '*_' . $this->name . '.*'));

    foreach ($files as $file)
      unlink($file);
  }

  public function canEditFields()
  {
    return false;
  }
}
