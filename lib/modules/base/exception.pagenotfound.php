<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PageNotFoundException extends UserErrorException
{
  public function __construct($text = null)
  {
    if (null === $text)
      $text = t('Страница не найдена');

    parent::__construct($text, 404);
  }
};
