<?php

class LabelNode extends Node
{
  /**
   * Дополнительные действия для предварительного просмотра.
   */
  public function getActionLinks()
  {
    return array_merge(parent::getActionLinks(), array(
      'find' => array(
        'title' => t('Показать документы'),
        'href' => 'admin/content/list?search=tags%3A' . $this->id,
        ),
      ));
  }
}
