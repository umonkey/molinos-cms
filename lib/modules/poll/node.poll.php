<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PollNode extends Node implements iContentType
{
  public function getDefaultSchema()
  {
    return array(
      'mode' => array(
        'type' => 'EnumControl',
        'label' => t('Режим работы'),
        'required' => true,
        'options' => array(
          'single' => t('одно значение'),
          'multi' => t('несколько значений'),
          ),
        ),
      );
  }
};
