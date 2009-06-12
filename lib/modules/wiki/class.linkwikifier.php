<?php

class LinkWikifier
{
  /**
   * Обработка ссылок в формате [[текст]].
   * @mcms_message ru.molinos.cms.format.text
   */
  public static function on_format_text(Context $ctx, $fieldName, &$text)
  {
    if ($map = self::map($text)) {
      $names = array();
      foreach ($map as $v)
        $names[] = $v[0];

      list($sql, $params) = Query::build(array(
        'class' => array('story', 'label'),
        'published' => 1,
        'deleted' => 0,
        'name' => $names,
        ))->getSelect(array("id", "name"));

      $data = (array)$ctx->db->getResultsKV("name", "id", $sql, $params);

      $replacement = array();

      foreach ($map as $k => $v) {
        $link = $v[1];
        if (isset($data[$v[0]])) {
          $link = t("<a href='@url'>%text</a>", array(
            '@url' => 'node/' . $data[$v[0]],
            '%text' => $v[1],
            ));
        }
        $replacement[$k] = $link;
      }

      $text = str_replace(array_keys($replacement), array_values($replacement), $text);
    }
  }

  /**
   * Поиск ключевых слов.
   */
  private static function map($input)
  {
    $map = array();

    if (preg_match_all('/(\[\[([^\]]+)\]\])/', $input, $m)) {
      foreach ($m[0] as $idx => $source) {
        $linkName = $displayName = $m[2][$idx];
        if (false !== strpos($linkName, '|'))
          list($linkName, $displayName) = explode('|', $linkName, 2);

        $map[$source] = array($linkName, $displayName);
      }
    }

    return $map;
  }
}
