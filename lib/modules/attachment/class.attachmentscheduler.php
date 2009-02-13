<?php

class AttachmentScheduler implements iScheduler
{
  public static function taskRun(Context $ctx)
  {
    $ctx->db->beginTransaction();
    $timestamp = $ctx->db->getResult("SELECT MIN(`updated`) FROM `node` WHERE `class` = 'imgtransform' AND `deleted` = 0");
    $fileids = $ctx->db->getResultsV("id", "SELECT `id` FROM `node` WHERE `class` = 'file' AND `deleted` = 0 AND `updated` < ?", array($timestamp));

    // Обработка всех устаревших файлов.
    foreach ($fileids as $id) {
      $file = NodeStub::create($id, $ctx->db);
      if (0 === strpos($file->filetype, 'image/')) {
        $file->touch();
        $file->getObject()->save();
      }
    }

    $ctx->db->commit();
  }
}
