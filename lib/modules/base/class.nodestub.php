<?php

class NodeStub
{
  private $id = null;
  private $data = null;

  /**
   * Устанавливается при необходимости сохранить объект.
   */
  private $dirty = false;

  private $db = null;

  public function __construct($id, PDO $db)
  {
    $this->id = $id;
    $this->db = $db;
  }

  /**
   * Обращение к свойствам объекта.  Подгружает и разворачивает
   * дополнительные данные по мере необходимости.
   */
  public final function __get($key)
  {
    if ('id' == $key)
      return $this->id;

    $this->makeSureFieldIsAvailable($key);

    $value = array_key_exists($key, $this->data)
      ? $this->data[$key]
      : null;

    if ('uid' == $key)
      return new NodeStub($value, $this->db);

    return $value;
  }

  /**
   * Изменение свойств объекта.
   */
  public final function __set($key, $value)
  {
    if ('id' == $key) {
      if (null !== $this->id)
        throw new InvalidArgumentException(t('Идентификатор объекта нельзя изменить.'));
      if (!is_integer($value))
        throw new InvalidArgumentException(t('Идентификатор объекта должен быть числовым.'));
      $this->id = $value;
    }

    $this->makeSureFieldIsAvailable($key);

    switch ($key) {
    case 'published':
    case 'deleted':
    case 'parent_id':
      throw new InvalidArgumentException(t('Свойство %name нельзя изменять стандартными средствами, используйте специальные методы.', array(
        '%name' => $key,
        )));
    }

    if (!array_key_exists($key, $this->data) or $this->data[$key] !== $value) {
      $this->data[$key] = $value;
      $this->dirty = true;
    }
  }

  /**
   * Проверяет, установлено ли поле.
   */
  public final function __isset($key)
  {
    if ('id' == $key)
      return null !== $this->id;

    $this->makeSureFieldIsAvailable($key);
    return !empty($this->data[$key]);
  }

  /**
   * Подгружает данные, необходимые для доступа к полю.
   */
  private function makeSureFieldIsAvailable($fieldName)
  {
    if ('id' == $fieldName)
      return;

    if (null === $this->data)
      $this->retrieve();

    if (!$this->isBasicField($fieldName)) {
      if (array_key_exists('data', $this->data)) {
        $fields = empty($this->data['data'])
          ? array()
          : unserialize($this->data['data']);
        $this->data = array_merge($fields, $this->data);
        unset($this->data['data']);
      }
    }
  }

  /**
   * Возвращает объект в виде XML.
   */
  public final function getXML($em = 'node', $extraContent = null)
  {
    if (null !== $this->id) {
      $ckey = 'node:' . $this->id . ':xml';

      if (!is_array($data = mcms::cache($ckey))) {
        $data = array(
          'id' => $this->id,
          );
        $this->makeSureFieldIsAvailable('this_field_never_exists');
        $data = array_merge($data, $this->data);

        mcms::cache($ckey, $data);
      }
    }

    if (null !== $extraContent) {
      if (!array_key_exists('#text', $data))
        $data['#text'] = $extraContent;
      else
        $data['#text'] .= $extraContent;
    }

    return html::em($em, $data);
  }

  /**
   * Сохраняет объект в БД.
   */
  public function save()
  {
    if ($this->dirty) {
      $data = $this->pack();

      // Создание новой ноды.
      if (null === $this->id) {
        $fields = '`' . join('`, `', array_keys($data)) . '`';
        $params = substr(str_repeat('?,', count($data)), 0, -1);

        $sql = "INSERT INTO `node` ({$fields}) VALUES ({$params})";
        $sth = $this->db->prepare($sql);
        $sth->execute($data);
        $this->id = $this->db->lastInsertId();
      }

      // Обновление существующей ноды.
      else {
        // Сохраняем текущую версию в архиве.
        $fields = '`id`, `lang`, `class`, `left`, `right`, `uid`, `created`, `updated`, `name`, `data`';
        $sth = $this->db->prepare("INSERT INTO `node__archive` ({$fields}) SELECT {$fields} FROM `node` WHERE `id` = ?");
        $sth->execute($this->id);

        // Обновляем текущую версию.
        $pairs = array();
        foreach ($data as $k => $v)
          $pairs[] = "`{$k}` = ?";
        $data[] = $this->id;
        $sth = $this->db->prepare("UPDATE `node` SET " . join(', ', $pairs) . " WHERE `id` = ?");
        $sth->execute($data);
      }

      $this->dirty = false;
    }
  }

  /**
   * Удаление ноды.
   */
  public function delete()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка удалить новый объект'));
    $this->setDeleted(1);
  }

  /**
   * Восстановление ноды.
   */
  public function undelete()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка восстановить новый объект.'));
    $this->setDeleted(0);
  }

  private function setDeleted($value)
  {
    $sth = $this->db->prepare("UPDATE `node` SET `deleted` = ? WHERE `id` = ?");
    $sth->execute(array($value, $this->id));
  }

  /**
   * Публикация ноды.
   */
  public function publish()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка публикации несуществующей ноды.'));
    $this->setPublished(1);
  }

  /**
   * Сокрытие ноды.
   */
  public function unpublish()
  {
    if (null === $this->id)
      throw new RuntimeException(t('Попытка сокрытия несуществующей ноды.'));
    $this->setPublished(0);
  }

  private function setPublished($value)
  {
    $sth = $this->db->prepare("UPDATE `node` SET `published` = ? WHERE `id` = ?");
    $sth->execute(array($value, $this->id));
  }

  /**
   * Упаковывает ноду для сохранения в БД.
   */
  private function pack()
  {
    $fields = $extra = array();

    if (null !== $this->id)
      $fields['id'] = $this->id;

    foreach ($this->data as $k => $v) {
      if ($this->isBasicField($k))
        $fields[$k] = $v;
      else
        $extra[$k] = $v;
    }

    $fields['data'] = serialize($extra);

    return $fields;
  }

  /**
   * Проверяет, является ли поле стандартным.
   */
  private function isBasicField($fieldName)
  {
    switch ($fieldName) {
    case 'id':
    case 'parent_id':
    case 'lang':
    case 'class':
    case 'left':
    case 'right':
    case 'uid':
    case 'created':
    case 'updated':
    case 'published':
    case 'deleted':
      return true;
    default:
      return false;
    }
  }

  private function retrieve()
  {
    $this->data = $this->db->getResults("SELECT `node`.`parent_id`, "
      . "`node`.`lang`, `node`.`class`, `node`.`left`, `node`.`right`, "
      . "`node`.`uid`, `node`.`created`, `node`.`updated`, "
      . "`node`.`published`, `node`.`deleted`, "
      . "`node__rev`.`name`, `node__rev`.`data` "
      . "FROM `node` "
      . "INNER JOIN `node__rev` ON `node__rev`.`rid` = `node`.`rid` "
      . "WHERE `node`.`id` = " . intval($this->id));

    if (null === $this->data)
      $this->data = array();
  }
}
