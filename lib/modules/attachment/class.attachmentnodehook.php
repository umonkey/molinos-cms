<?php

class AttachmentNodeHook implements iNodeHook
{
  public static function hookNodeUpdate(Node $node, $op)
  {
    if ($node instanceof FileNode and 0 === strpos($node->filetype, 'image/')) {
      if ('create' == $op or 'update' == $op)
        return self::transform($node);
    }
  }

  private static function transform(FileNode $file)
  {
    $modes = Node::find(array(
      'class' => 'imgtransform',
      ));

    $result = false;

    foreach ($modes as $mode)
      $result |= self::transformOne($file, $mode);

    if ($result)
      $file->dirty = true;
  }

  private static function transformOne(FileNode &$file, ImgTransformNode $mode)
  {
    return $mode->apply($file);
  }
}
