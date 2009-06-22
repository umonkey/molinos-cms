<?php

class PrideTypograph
{
  private static $typo = null;

  /**
   * @mcms_message ru.molinos.cms.module.settings.pridetypograph
   */
  public static function on_get_settings(Context $ctx)
  {
    return new Schema(array(
      'fields' => array(
        'type' => 'SetControl',
        'label' => t('Обрабатываемые поля'),
        'options' => Node::getSortedList('field', 'label', 'name'),
        ),
      ));
  }

  /**
   * Дополнительная обработка текстовых полей.
   * 
   * @param Context $ctx 
   * @param string $fieldName
   * @param string $text 
   * @static
   * @access public
   * @return string
   * @mcms_message ru.molinos.cms.format.text
   */
  public static function on_format_text(Context $ctx, $fieldName, &$text)
  {
    $fields = $ctx->config->get('modules/pridetypograph/fields', array());

    /*
    if (!in_array($fieldName, $fields))
      return;
    */

    if (strlen(trim($text))) {
      try {
        $typo = self::getTypo();
        if (mb_check_encoding($text2 = $typo->parse($text), 'utf-8')) {
          $text = $text2;
        } else {
          Logger::log('Waring: PrideTypograph failed to process this text: ' . $text);
        }
      } catch (Exception $e) {
        Logger::trace($e);
      }
    }
  }

  /**
   * Возвращает экземпляр типографа. Кэширует его, для быстрого применения к нескольким полям.
   * 
   * @static
   * @access private
   * @return Pride_Typograph
   */
  private static function getTypo()
  {
    if (self::$typo === null) {
      $options = array(
        'array_regex_array' => require os::path('lib', 'modules', 'pridetypograph', 'resources', 'RulesRegex.php'),
        'array_replace_array' => require os::path('lib', 'modules', 'pridetypograph', 'resources', 'RulesReplace.php'),
        'array_clean_array' => require os::path('lib', 'modules', 'pridetypograph', 'resources', 'CleanHtml.php'),
        );

      self::$typo = Pride_Typograph::factory('array', $options);
    }

    return self::$typo;
  }
}
