<?php

class AttachmentNodeHook
{
  /**
   * @mcms_message ru.molinos.cms.hook.node
   */
  public static function hookNodeUpdate(Context $ctx, Node $node, $op)
  {
    if ($node instanceof FileNode and 0 === strpos($node->filetype, 'image/')) {
      if ('create' == $op or 'update' == $op)
        return self::transform($node);
    }
  }

  private static function transform(FileNode $file)
  {
    $modes = Node::find($file->getDB(), array(
      'class' => 'imgtransform',
      ));

    $result = false;

    foreach ($modes as $mode)
      $result |= self::transformOne($file, $mode->getObject());

    if ($result)
      $file->dirty = true;
  }

  private static function transformOne(FileNode &$file, ImgTransformNode $mode)
  {
    return $mode->apply($file, Context::last());
  }
}
