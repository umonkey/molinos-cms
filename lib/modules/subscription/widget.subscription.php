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
    $options['section'] = $ctx->section;

    return $options;
  }

  public function onGet(array $options)
  {
    $sections = array_intersect_key(TagNode::getTags('select'),
      array_flip(Node::create('subscription')->getEnabledSections()));

    $result = array(
      'title' => $this->me->title,
      'description' => $this->me->description,
      'sections' => $sections,
      'enabled' => array_keys($sections),
      'section' => empty($options['section'])
        ? array()
        : $options['section']->getRaw(),
      );

    if (false !== strpos($e = $this->ctx->user->name, '@'))
      $result['email'] = $e;
    else
      $result['email'] = $this->ctx->user->email;

    return $result;
  }
};
