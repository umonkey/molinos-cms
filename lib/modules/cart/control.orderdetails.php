<?php
/**
 * Контрол для отображения содержимого заказа.
 *
 * @package mod_cart
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Контрол для отображения содержимого заказа.
 *
 * @package mod_cart
 * @subpackage Controls
 */

class OrderDetailsControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Содержимое заказа (только вывод)'),
      );
  }

  public function getHTML($data)
  {
    if (empty($data->{$this->value}))
      return;

    $html = $this->getLabel();
    $html .= $this->renderContent($data->{$this->value});

    return $this->wrapHTML($html, false, true);
  }

  private function renderContent(array $content)
  {
    $sum = 0;

    $rows = '<table class=\'orderdetails\'><thead>';
    $rows .= '<tr><th>id</th><th>Название</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr>';
    $rows .= '</thead><tbody>';

    foreach ($content as $k => $item) {
      if ($k !== 'total') {
        $rows .= '<tr>';
        $rows .= html::em('td', $item['id']);
        $rows .= html::em('td', $this->getProductLink($item));
        $rows .= html::em('td', array('class' => 'sum'), number_format(abs($item['price']), 2, ',', '.'));
        if (is_numeric($k))
          $rows .= html::em('td', array('class' => 'qty'), number_format($item['qty'], 0, ',', ' '));
        else
          $rows .= html::em('td');
        $rows .= html::em('td', array('class' => 'sum'), number_format(abs($item['sum']), 2, ',', '.'));
        $rows .= '</tr>';
      }
    }

    $rows .= '<tr><td colspan=\'3\' class=\'empty\'>&nbsp;</td>'
      . '<td>'. $content['total']['qty'] .'</td>'
      . '<td><strong>'
      . number_format($content['total']['sum'], 2, ',', '.')
      . '</strong></td></tr>';

    $rows .= '</tbody></table>';

    mcms::extras('lib/modules/cart/control.orderdetails.css');

    return $rows;
  }

  private function getProductLink(array $item)
  {
    $ctx = new Context();

    if (empty($item['id']))
      return htmlspecialchars($item['name']);

    if (0 === strpos($ctx->query(), 'admin/'))
      $url = '?q=admin/content/edit/'. $item['id'] .'&destination=admin';
    else
      $url = '?q=nodeapi.rpc&action=locate&node='. $item['id'];

    return l($url, htmlspecialchars($item['name']));
  }
}
