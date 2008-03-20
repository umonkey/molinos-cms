<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

interface iWidget
{
  public static function getWidgetInfo();

  public static function formGetConfig();
  public function formHookConfigData(array &$data);
  public function formHookConfigSaved();

  // Возвращает массив значений, реально используемых для обработки запроса.
  public function getRequestOptions(RequestContext $ctx);

  // Возвращает форму по идентификатору.
  public function formGet($id);
  public function formProcess($id, array $data);
};
