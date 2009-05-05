<?php

class GMapControl extends TextLineControl
{
  /**
   * @mcms_message ru.molinos.cms.control.enum
   */
  public static function getInfo()
  {
    return array(
      'name' => t('Карта'),
      'class' => __CLASS__,
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['description']))
      $form['description'] = t('Можно использовать готовый код для вставки проигрывателя или ссылку на страницу с клипом.');
    parent::__construct($form, array('value'));
  }

  // Отключаем индексирование.
  public function getSQL()
  {
    return null;
  }

  public function format($value)
  {
    if (!empty($value)) {
      $conf = Context::last()->config->googlemaps;

      $img = html::em('img', array(
        'src' => sprintf('http://maps.google.com/staticmap?center=%s&zoom=%u&size=%ux%u&key=%s', $value, $this->zoom_embed, $this->width, $this->height, $conf['key']),
        'width' => $this->width,
        'height' => $this->height,
        'alt' => $value,
        ));

      if (!($zoom_link = $this->zoom_link))
        $zoom_link = $this->zoom_embed + 2;

      $result = html::em('a', array(
        'href' => sprintf('http://maps.google.com/maps?ll=%s&z=%u', $value, $zoom_link),
        'title' => $value,
        ), $img);

      return html::cdata($result);
    }
  }

  public function getExtraSettings()
  {
    return array(
      'static' => array(
        'type' => 'BoolControl',
        'label' => t('Отображать в виде картинки'),
        'group' => t('Карта'),
        'weight' => 100,
        ),
      'width' => array(
        'type' => 'NumberControl',
        'label' => t('Ширина в пикселях'),
        'group' => t('Карта'),
        'default' => 400,
        'weight' => 101,
        ),
      'height' => array(
        'type' => 'NumberControl',
        'label' => t('Высота в пикселях'),
        'group' => t('Карта'),
        'default' => 200,
        'weight' => 102,
        ),
      'zoom_embed' => array(
        'type' => 'NumberControl',
        'label' => t('Масштаб для встраивания'),
        'group' => t('Карта'),
        'default' => 5,
        'weight' => 103,
        ),
      'zoom_link' => array(
        'type' => 'NumberControl',
        'label' => t('Масштаб для полноэкранной версии'),
        'description' => t('По умолчанию используется масштаб для встраивания +2 пункта.'),
        'group' => t('Карта'),
        'weight' => 104,
        ),
      );
  }
}
