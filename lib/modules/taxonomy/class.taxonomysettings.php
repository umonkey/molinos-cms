<?php

class TaxonomySettings
{
  /**
   * @mcms_message ru.molinos.cms.module.settings.taxonomy
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'multitagtypes' => array(
        'type' => 'SetControl',
        'label' => t('Помещать в несколько разделов можно'),
        'options' => Node::getSortedList('type', 'title', 'name'),
        ),
      ));
  }
}
