<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class AdminUISearchControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Форма поиска по списку'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form);
  }

  public function getHTML(array $data)
  {
    $output = $this->getLeftPart();
    return mcms::html('div', array('class' => 'tb_1'), $output);
  }

  private function getLeftPart()
  {
    $output = '';

    if (null !== ($tmp = $this->getCreateHTML()))
      $output .= $tmp . '&nbsp;|&nbsp;';

    $output .= $this->getSearchHTML();

    return mcms::html('div', array('class' => 'ctrl_left'), $output);
  }

  private function getCreateHTML()
  {
    if (count($this->type) == 1) {
      $type = $this->type[0];
    } else {
      $type = null;

      // Определяем доступные несистемные типы.
      $available = array_diff(mcms::user()->getAccess('c'), TypeNode::getInternal());

      // Если остался один — используем его, если их нет — отключаем добавление.
      if (empty($available))
        return;
      elseif (count($available) == 1) {
        $type = array_shift($available);
      }
    }

    $link = '?q=admin/content/create&type='. $type .'&destination=CURRENT';

    if ($this->dictlist)
      $link .= '&dictionary=1';

    $output = mcms::html('a', array(
      'class' => 'newlink',
      'href' => $link,
      ), 'Добавить');

    return $output;
  }

  private function getSearchHTML()
  {
    $output = '';

    $output = mcms::html('input', array(
      'type' => 'text',
      'name' => $this->value,
      'class' => 'search_field',
      'value' => $this->q,
      ));

    $output .= mcms::html('input', array(
      'type' => 'submit',
      'value' => 'Найти',
      ));

    $output .= '&nbsp;|&nbsp;';
    $output .= mcms::html('a', array(
      'href' => '?q=admin/content/search&from='. urlencode($_SERVER['REQUEST_URI']),
      ), 'Расширенный поиск');

    return $output;
  }
};
