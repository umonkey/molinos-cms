<?php

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'bootstrap.php';

function upgrade_8_05_6197()
{
  mcms::db()->beginTransaction();

  // Установка дефолтных пра на виджеты.
  $count = mcms::db()->fetch("SELECT COUNT(*) FROM node__access "
    . "WHERE uid = 0 AND nid IN (SELECT id FROM node "
    . "WHERE class = 'widget' AND deleted = 0)");

  if (!intval($count)) {
    mcms::db()->exec("INSERT INTO node__access (uid, nid, r) SELECT 0, id, 1 "
      . "FROM node WHERE class = 'widget' AND deleted = 0");
  }

  // Добавление полей для выбора разделов.
  foreach (Node::find(array('class' => 'type')) as $type) {
    if (in_array($type->name, TypeNode::getInternal()))
      continue;

    if (empty($type->notags) and empty($type->fields['sections'])) {
      $fields = $type->fields;

      $fields['sections'] = array(
        'type' => 'SectionsControl',
        'label' => t('Опубликовать в разделах'),
        'group' => t('Разделы'),
        );

      $type->fields = $fields;
      $type->save();
    }
  }

  mcms::db()->commit();
}

upgrade_8_05_6197();
