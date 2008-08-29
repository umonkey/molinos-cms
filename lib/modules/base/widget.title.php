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
      'description' => 'Возвращает названия указанных в адресной строке разделов, в обратном порядке.&nbsp; Используется в основном для формирования заголовка страницы.',
      );
  }

  public static function formGetConfig()
  {
    $form = parent::formGetConfig();

    $form->addControl(new BoolControl(array(
      'value' => 'config_showpath',
      'label' => t('Возвращать путь к текущему разделу'),
      'description' => t('Добавляет лишний запрос, незначительно сказывается '
        .'на производительности.'),
      )));

    return $form;
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
    $options = parent::getRequestOptions($ctx);

    $options['document_id'] = $ctx->document->id;
    $options['section_id'] = $ctx->section->id;

    return $this->options = $options;
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

    if (null !== ($tmp = $this->ctx->document))
      $result['document'] = $tmp->getRaw();

    if (null !== ($tmp = $this->ctx->section)) {
      $result['section'] = $tmp->getRaw();

      if ($this->showpath)
        foreach ($tmp->getParents() as $p)
          $result['path'][] = $p->getRaw();
    }

    return $result;
  }
};
