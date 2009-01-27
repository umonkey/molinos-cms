<?php
/**
 * Виджет «названия разделов».
 *
 * Возвращает имена текущего раздела и документа.  Используется, как правило,
 * для формирования заголовка страницы (встроенный шаблон формирует title, для
 * включения в head).
 *
 * @package mod_base
 * @subpackage Widgets
 * @author Justin Forest <justin.forest@gmail.com>
 * @copyright 2006-2008 Molinos.RU
 * @license http://www.gnu.org/copyleft/gpl.html GPL
 */

/**
 * Виджет «названия разделов».
 *
 * Возвращает имена текущего раздела и документа.  Используется, как правило,
 * для формирования заголовка страницы (встроенный шаблон формирует title, для
 * включения в head).
 *
 * @package mod_base
 * @subpackage Widgets
 */
class TitleWidget extends Widget
{
  /**
   * Возвращает описание виджета.
   *
   * @return array описание виджета, ключи: name, description.
   */
  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Названия разделов',
      'description' => 'Выводит названия раздела и документа, указанного в адресной строке.',
      'docurl' => 'http://code.google.com/p/molinos-cms/wiki/TitleWidget',
      );
  }

  public static function getConfigOptions()
  {
    return array(
      'showpath' => array(
        'type' => 'BoolControl',
        'label' => t('Возвращать путь к текущему разделу'),
        'description' => t('Добавляет лишний запрос, незначительно сказывается '
          .'на производительности.'),
        ),
      );
  }

  /**
   * Препроцессор параметров.
   *
   * @param Context $ctx контекст вызова.
   *
   * @return array параметры виджета.
   */
  protected function getRequestOptions(Context $ctx)
  {
    if (!is_array($options = parent::getRequestOptions($ctx)))
      return $options;

    $options['document_id'] = $ctx->document->id;
    $options['section_id'] = $ctx->section->id;

    return $options;
  }

  /**
   * Обработчик GET запросов.
   *
   * @param array $options параметры запроса.
   *
   * @return array данные для шаблона.  Подмассив list содержит описание двух
   * или менее объектов: текущего раздела и текущего документа.  Если код
   * раздела или документа неизвестен — соответствующий объект не возвращается.
   * В худшем случае массив будет пустым.
   */
  public function onGet(array $options)
  {
    $result = array('list' => array());

    if ($this->ctx->document->id)
      $result['document'] = $this->ctx->document->getRaw();

    if ($this->ctx->section->id) {
      $result['section'] = $this->ctx->section->getRaw();

      if ($this->showpath)
        foreach ($this->ctx->section->getParents() as $p)
          $result['path'][] = $p->getRaw();
    }

    return $result;
  }
};
