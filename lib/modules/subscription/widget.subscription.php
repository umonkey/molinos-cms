<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SubscriptionWidget extends Widget
{
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Управление подпиской',
      'description' => 'Позволяет пользователям подписываться на новости.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/SubscriptionWidget',
      );
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;
    $options['#cache'] = false;
    return $options;
  }

  public function onGet(array $options)
  {
    $sections = array_intersect_key(Node::getSortedList('tag'),
      array_flip(Node::create('subscription')->getEnabledSections()));

    $output = html::simpleOptions($sections, 'section', 'sections');
    if ($this->description)
      $output .= html::em('description', html::cdata($this->description));

    return $output;
  }
};
