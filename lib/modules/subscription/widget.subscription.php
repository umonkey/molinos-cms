<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class SubscriptionWidget extends Widget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Управление подпиской',
      'description' => 'Позволяет пользователям подписываться на новости.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new SetControl(array(
      'value' => 'config_sections',
      'label' => t('Поместить документ в разделы'),
      'options' => TagNode::getTags('select'),
      )));

    return $form;
  }

  // Препроцессор параметров.
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['sections'] = empty($this->sections)
      ? array() : $this->sections;

    $options['#cache'] = false;

    return $options;
  }

  public function onGet(array $options)
  {
    $result = array(
      'title' => $this->me->title,
      'description' => $this->me->description,
      'sections' => TagNode::getTags('select',
        array('enabled' => $this->options['sections'])),
      'enabled' => $this->options['sections'],
      );

    if (false !== strpos($e = mcms::user()->name, '@'))
      $result['email'] = $e;
    else
      $result['email'] = mcms::user()->email;

    return $result;
  }
};
