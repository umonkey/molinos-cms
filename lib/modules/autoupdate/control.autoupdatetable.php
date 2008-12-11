<?php
/**
 * Контрол для вывода списка обновлений.
 *
 * @package mod_autoupdate
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

class AutoUpdateTableControl extends Control
{
  public function __construct(array $form)
  {
    parent::__construct($form, array('value', 'columns'));
  }

  public static function getInfo()
  {
    return array(
      'name' => t('Список обновлений'),
      'hidden' => true,
      );
  }

  public function getHTML($data)
  {
    foreach ($this->getSections($data->{$this->value}) as $name => $modules)
      $result .= $this->getSectionHTML($name, $modules);

    $this->nolabel = true;

    $css = substr(Loader::getClassPath(__CLASS__), 0, -3) . 'css';
    mcms::extras($css);

    return $this->wrapHTML(mcms::html('table', $result));
  }

  private function getSections(array $data)
  {
    $map = array(
      'base' => t('Основная функциональность'),
      'admin' => t('Администрирование'),
      'core' => t('Ядро'),
      'blog' => t('Блоги'),
      'spam' => t('Спам'),
      'commerce' => t('Коммерция'),
      'interaction' => t('Интерактив'),
      'performance' => t('Производительность'),
      'service' => t('Служебные'),
      'multimedia' => t('Мультимедиа'),
      'syndication' => t('Обмен данными'),
      'templating' => t('Шаблоны'),
      'visual' => t('Визуальные редакторы'),
      );

    $result = array();

    foreach ($data as $k => $v) {
      $name = array_key_exists($v['section'], $map)
        ? $map[$v['section']]
        : $v['section'];
      $result[$name][$k] = $v;
    }

    ksort($result);

    return $result;
  }

  private function getSectionHTML($section, array $modules)
  {
    $output = mcms::html('tr', mcms::html('th', array(
      'colspan' => count($this->columns),
      ), $section));

    foreach ($modules as $name => $meta) {
      $row = "";

      foreach ($this->columns as $column) {
        switch ($column) {
        case 'check':
          $value = $this->getCheckCell($name, $meta);
          break;
        case 'name':
          $value = $this->getNameCell($name, $meta);
          break;
        case 'version':
          $value = $this->getVersionCell($name, $meta);
          break;
        case 'available':
          $value = $this->getAvailableCell($name, $meta);
          break;
        case 'settings':
          $value = $this->getSettingsCell($name, $meta);
          break;
        default:
          $value = null;
        }

        $row .= mcms::html('td', $value);
      }

      $output .= mcms::html('tr', $row);
    }

    return $output;
  }

  private function getCheckCell($name, array $meta)
  {
    return mcms::html('input', array(
      'type' => 'checkbox',
      'name' => $this->value . '[enable][]',
      'value' => $name,
      'checked' => empty($meta['enabled']) ? '' : 'checked',
      'disabled' => ($meta['priority'] == 'required') ? 'disabled' : '',
      ));
  }

  private function getNameCell($name, array $meta)
  {
    $name = mcms::html('strong', empty($meta['docurl'])
      ? $name
      : l($meta['docurl'], $name));

    if (!empty($meta['name']))
      $name .= mcms::html('p', array(
        'class' => 'description',
        ), $meta['name']);

    return $name;
  }

  private function getVersionCell($name, array $meta)
  {
    return empty($meta['version.local'])
      ? null
      : 'v' . $meta['version.local'];
  }

  private function getAvailableCell($name, array $meta)
  {
    if (empty($meta['version']) or empty($meta['version.local']))
      return null;

    if (version_compare($meta['version.local'], $meta['version'], '>='))
      return null;

    return t('доступна:&nbsp;v%version', array(
      '%version' => $meta['version'],
      ));
  }

  private function getSettingsCell($name, array $meta)
  {
    if (empty($meta['configurable']))
      return null;

    $img = mcms::html('img', array(
      'src' => 'themes/admin/img/configure.png',
      'alt' => t('ключ'),
      ));

    return l("?q=admin/structure/modules&action=config&name={$name}&destination=CURRENT", $img);
  }
}
