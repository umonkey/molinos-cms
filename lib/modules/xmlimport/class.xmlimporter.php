<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class XmlImporter extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'XML-импортер',
      'description' => 'Возвращает массив, составленный из элементов XML-дерева.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new TextLineControl(array(
      'value' => 'config_url',
      'label' => t('Адрес потока'),
      'description' => t('Укажите адрес URI, по которому доступен поток XML-данных.'),
      )));

    return $form;
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = parent::getRequestOptions($ctx);

    $options['url'] = $this->url;

    return $options;
  }

  // Обработка GET запросов.
  public function onGet(array $options)
  {
    $result = array();

    if (!empty($this->url)) {
      if (null !== ($fcont = mcms_fetch_file($options['url'], true))) {
        $xmlStream = new SimpleXMLElement($fcont);
        $result = $this->forArray($xmlStream);
      } else {
        // throw new Exception('Could not fetch XML-stream off ' . $options['url']);
      }
    }

    return $result;
  }

  public function onPost(array $options)
  {
    return $this->onGet($options);
  }

  private function forArray($object)
  {
    $return = array();
    if (is_array($object)) {
      foreach ($object as $key => $value) {
        $return[$key] = $this->forArray($value);
      }
    } else {
      $vars = get_object_vars($object);
      if (is_array($vars)) {
        foreach ($vars as $key => $value) {
          if (stristr($key, '@')) {
            $key = str_replace('@', '', $key);
          }
          $return[$key] = ($key && !$value) ? null : $this->forArray($value);
        }
      } else {
        return $object;
      }
    }

    return $return;
  }
};
