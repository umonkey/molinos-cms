<?php
/**
 * Контрол для вывода страничной листалки.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для вывода страничной листалки.
 *
 * @package mod_base
 * @subpackage Controls
 */
class PagerControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Переключатель страниц'),
      'hidden' => true,
      );
  }

  public function __construct(array $form)
  {
    parent::__construct($form, array('value'));
  }

  public function getHTML($data)
  {
    if (empty($data->{$this->value}))
      return null;

    $pager = $this->getPagerData($data->{$this->value});

    $left = $right = '';

    if ((null !== $pager) and ($pager['pages'] > 1)) {
      if (!empty($pager['prev']))
        $left .= "<a href='{$pager['prev']}'>&larr;</a>";

      foreach ($pager['list'] as $idx => $link) {
        if (!empty($link))
          $left .= "<a href='{$link}'>{$idx}</a>";
        else
          $left .= "<a class='current'>{$idx}</a>";
      }

      if (!empty($pager['next']))
        $left .= "<a href='{$pager['next']}'>&rarr;</a>";
    } elseif (null !== $this->showempty and !empty($data[$this->value]['total'])) {
      $left = t('Все документы поместились на одну страницу.');
    }

    foreach (array(10, 30, 60) as $x) {
      $url = bebop_split_url();

      if (null === $this->widget) {
        $url['args']['limit'] = $x;
        $url['args']['page'] = null;
      } else {
        $url['args'][$this->widget]['limit'] = $x;
        $url['args'][$this->widget]['page'] = null;
      }

      $right .= html::em('a', array('href' => bebop_combine_url($url, false)), $x);
    }

    $output = html::em('div', array('class' => 'pager_left'), $left);
    $output .= html::em('div', array('class' => 'pager_right'), $right .'<p>'. t('пунктов на странице') .'</p>');

    return html::em('div', array('class' => 'pager'), $output);
  }

  private function getPagerData(array $input)
  {
    if (null === $this->widget)
      return $input;

    if (empty($input['limit']))
      return null;

    $output = array();
    $output['documents'] = intval($input['total']);
    $output['perpage'] = intval($input['limit']);
    $output['pages'] = ceil($output['documents'] / $output['perpage']);
    $output['current'] = $input['page'];

    if ($output['current'] == 'last')
      $output['current'] = $output['pages'];

    if ($output['pages'] > 0) {
      if ($output['current'] > $output['pages'] or $output['current'] <= 0)
        throw new PageNotFoundException();

      // С какой страницы начинаем список?
      $beg = max(1, $output['current'] - 5);
      // На какой заканчиваем?
      $end = min($output['pages'], $output['current'] + 5);

      // Расщеплённый текущий урл.
      $url = bebop_split_url();

      for ($i = $beg; $i <= $end; $i++) {
        if (null === $this->widget)
          $url['args']['page'] = ($i == 1) ? null : $i;
        else
          $url['args'][$this->widget]['page'] = ($i == 1) ? null : $i;
        $output['list'][$i] = ($i == $output['current']) ? null : bebop_combine_url($url);
      }

      if (!empty($output['list'][$output['current'] - 1]))
        $output['prev'] = $output['list'][$output['current'] - 1];
      if (!empty($output['list'][$output['current'] + 1]))
        $output['next'] = $output['list'][$output['current'] + 1];
    }

    return $output;
  }
};
