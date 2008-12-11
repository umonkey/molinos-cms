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

  public function getHTML($data)
  {
    $output = $this->getLeftPart();
    return html::em('div', array('class' => 'tb_1'), $output);
  }

  private function getLeftPart()
  {
    $output = '';

    if (null !== ($tmp = $this->getCreateHTML()))
      $output .= $tmp . '&nbsp;|&nbsp;';

    $output .= $this->getSearchHTML();

    return html::em('div', array('class' => 'ctrl_left'), $output);
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

    $output = html::em('a', array(
      'class' => 'newlink',
      'href' => $link,
      ), 'Добавить');

    return $output;
  }

  private function getSearchHTML()
  {
    $output = '';

    $output = html::em('input', array(
      'type' => 'text',
      'name' => $this->value,
      'class' => 'search_field',
      'value' => $this->q,
      ));

    $output .= html::em('input', array(
      'type' => 'submit',
      'value' => 'Найти',
      ));

    $output .= '&nbsp;|&nbsp;';
    $output .= html::em('a', array(
      'href' => '?q=admin/content/search&from='. urlencode($_SERVER['REQUEST_URI']),
      ), 'Расширенный поиск');

    return $output;
  }
};
