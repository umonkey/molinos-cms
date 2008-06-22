<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class PageNotFoundException extends UserErrorException
{
  public function __construct($page = null, $description = null, $text = null)
  {
    $description .= 'Попробуйте поискать требуемую информацию на <a href="./">главной странице</a> сайта.';

    if (null === $text)
      $text = t('Страница не найдена');

    parent::__construct(
      $text,
      404,
      'Такой <span class="highlight">страницы нет</span> на этом сайте',
      $description);
  }
};
