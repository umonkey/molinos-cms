<?php

class FileHooks
{
  /**
   * @mcms_message ru.molinos.cms.node.clone
   */
  public static function on_clone(Node &$node)
  {
    if ($node instanceof FileNode)
      throw new ForbiddenException(t('Клонирование файлов невозможно.'));
  }
}
