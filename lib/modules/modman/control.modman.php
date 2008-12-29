<?php
/**
 * Контрол для вывода списка обновлений.
 *
 * @package mod_base
 * @subpackage Controls
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

class ModManControl extends Control
{
  public function __construct(array $form)
  {
    if (!array_key_exists('disable_required', $form))
      $form['disable_required'] = true;

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
    $result = $this->getRowsHTML($data->{$this->value});

    $this->nolabel = true;

    mcms::extras(substr(Loader::getClassPath(__CLASS__), 0, -3) . 'css');
    mcms::extras(substr(Loader::getClassPath(__CLASS__), 0, -3) . 'js');

    return $this->wrapHTML(html::em('table', $result));
  }

  private function getRowsHTML(array $modules)
  {
    $output = '';

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

        $row .= html::em('td', $value);
      }

      $class = array();

      if (!empty($meta['installed']))
        $class[] = 'installed';
      if (!empty($meta['section']))
        $class[] = 'section-' . $meta['section'];

      $output .= html::em('tr', array(
        'class' => $class,
        ), $row);
    }

    return $output;
  }

  private function getCheckCell($name, array $meta)
  {
    return html::em('input', array(
      'type' => 'checkbox',
      'name' => $this->value . '[]',
      'value' => $name,
      'checked' => empty($meta['installed']) ? '' : 'checked',
      'disabled' => ($meta['priority'] == 'required' and $this->disable_required) ? 'disabled' : '',
      ));
  }

  private function getNameCell($name, array $meta)
  {
    $name = html::em('strong', empty($meta['docurl'])
      ? $name
      : l($meta['docurl'], $name));

    if (!empty($meta['name']))
      $name .= html::em('p', array(
        'class' => 'description',
        ), $meta['name']);

    return $name;
  }

  private function getVersionCell($name, array $meta)
  {
    if (!empty($meta['version.local']))
      return 'v' . $meta['version.local'];
    if (!empty($meta['version']))
      return 'v' . $meta['version'];
    return null;
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
    if (0 == count(Loader::getImplementors('iModuleConfig', $name)))
      return null;

    $img = html::em('img', array(
      'src' => 'lib/modules/modman/configure.png',
      'alt' => t('ключ'),
      ));

    return l("?q=admin&cgroup=system&module=modman&mode=config&name={$name}&destination=CURRENT", $img);
  }
}
