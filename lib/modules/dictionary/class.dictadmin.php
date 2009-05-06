<?php

class DictAdmin
{
  public static function on_get_list(Context $ctx)
  {
    $tmp = new DictList($ctx);
    return $tmp->getHTML('dictlist', array(
      '#raw' => true,
      ));
  }

  public static function on_get_create_form(Context $ctx)
  {
    $node = Node::create('type', array(
      'parent_id' => $parent_id,
      'isdictionary' => true,
      ));

    $schema = $node->getFormFields();
    unset($schema['tags']);
    // unset($schema['fields']);
    $schema['isdictionary'] = new HiddenControl(array(
      'value' => 'isdictionary',
      'default' => 1,
      ));

    $form = $schema->getForm();
    $form->title = t('Добавление справочника');

    $form->addClass('tabbed');
    $form->action = "?q=nodeapi.rpc&action=create&type=type&destination=admin/content/dict";

    $form->addControl(new SubmitControl(array(
      'text' => t('Добавить'),
      )));

    $page = new AdminPage(html::em('content', array(
      'name' => 'create',
      ), $form->getXML($node)));
    return $page->getResponse($ctx);
  }
}
