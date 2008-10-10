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

  public function getHTML(array $data)
  {
    if (empty($data[$this->value]))
      return;

    $html = $this->renderContent($data[$this->value]);

    return $this->wrapHTML($html, true, true);
  }

  private function renderContent(array $content)
  {
    $sum = 0;

    $rows = '<table class=\'orderdetails\'><thead>';
    $rows .= '<tr><th>id</th><th>Название</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr>';
    $rows .= '</thead><tbody>';

    foreach ($content as $item) {
      $rows .= '<tr>';
      $rows .= mcms::html('td', $item['id']);
      $rows .= mcms::html('td', $this->getProductLink($item));
      $rows .= mcms::html('td', array('class' => 'sum'), number_format($item['price'], 0, ',', ' '));
      $rows .= mcms::html('td', array('class' => 'qty'), number_format($item['qty'], 0, ',', ' '));
      $rows .= mcms::html('td', array('class' => 'sum'), number_format($item['sum'], 2, ',', '.'));
      $rows .= '</tr>';

      $sum += $item['sum'];
    }

    $rows .= '<tr><td colspan=\'4\' class=\'empty\'>&nbsp;</td><td><strong>'
      . number_format($sum, 2, ',', '.')
      . '</strong></td></tr>';

    $rows .= '</tbody></table>';

    mcms::extras('lib/modules/cart/control.orderdetails.css');

    return $rows;
  }

  private function getProductLink(array $item)
  {
    $ctx = new Context();

    if (0 === strpos($ctx->query(), 'admin/'))
      $url = '?q=admin/content/edit/'. $item['id'] .'&destination=admin';
    else
      $url = '?q=nodeapi.rpc&action=locate&node='. $item['id'];

    return l($url, htmlspecialchars($item['name']));
  }
}