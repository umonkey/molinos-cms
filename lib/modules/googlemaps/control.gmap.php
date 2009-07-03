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
      );
  }

  public function __construct(array $form)
  {
    if (empty($form['description']))
      $form['description'] = t('Введите максимально точный адрес географической локации, например: «Россия, Санкт-Петербург, Сенная площадь».');
    parent::__construct($form, array('value'));
  }

  // Отключаем индексирование.
  public function getSQL()
  {
    return null;
  }

  public function format(Node $noed, $em)
  {
    $value = $node->{$this->value};

    if (is_array($value) and !empty($value['lat']) and !empty($value['lon'])) {
      $ll = $value['lat'] . ',' . $value['lon'];
      $key = Context::last()->config->get('modules/googlemaps/key');

      $img = html::em('img', array(
        'src' => sprintf('http://maps.google.com/staticmap?center=%s&zoom=%u&size=%ux%u&hl=ru&key=%s', $ll, $this->zoom_embed, $this->width, $this->height, $key),
        'width' => $this->width,
        'height' => $this->height,
        'alt' => $value,
        ));

      if (!($zoom_link = $this->zoom_link))
        $zoom_link = $this->zoom_embed + 2;

      $result = html::em('a', array(
        'href' => sprintf('http://maps.google.com/maps?ll=%s&z=%u', $ll, $zoom_link),
        'title' => $value,
        ), $img);

      return html::wrap($em, html::cdata($result), array(
        'address' => $value['query'],
        ));
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

  /**
   * Сохранение значения.
   */
  public function set($value, &$node)
  {
    $node->{$this->value} = null;

    if (!empty($value)) {
      if (preg_match('/^[0-9\.\,]+$/', $value))
        list($lat, $lon) = explode(',', $value);

      elseif ($key = Context::last()->config->get('modules/googlemaps/key')) {
        $url = 'http://maps.google.com/maps/geo?q=' . urlencode(trim($value)) . '&output=csv&oe=utf8&sensor=false&key=' . urlencode($key);
        if ($data = http::fetch($url, http::CONTENT)) {
          list($status, $accuracy, $lat, $lon) = explode(',', $data);
          if (200 != $status)
            return;
        } else {
          return;
        }
      } else {
        return;
      }

      $node->{$this->value} = array(
        'query' => $value,
        'lat' => $lat,
        'lon' => $lon,
        );
    }
  }

  protected function getValue($data)
  {
    if (is_array($value = $data->{$this->value}))
      return $value['query'];
  }
}
