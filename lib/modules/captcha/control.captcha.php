<?php

class CaptchaControl extends Control
{
  public static function getInfo()
  {
  }

  public function __construct(array $form)
  {
    if (isset($form['label']))
      $form['label'] = t('Введите символы с картинки');
    return parent::__construct($form);
  }

  public function getXML($data)
  {
    if (!$this->isActive($data))
      return;

    $key = $this->generate();

    return parent::wrapXML(array(
      'key' => $key,
      ));
  }

  public function set($value, Node &$node)
  {
    if (!$this->isActive($node))
      return;

    $this->validate($value);
  }

  protected function validate($value)
  {
    if (is_array($value) and 2 == count($value)) {
      if ($value[0] == crypt::decrypt($value[1]))
        return true;
    }

    throw new ValidationException($this->label, t('Вы неверно ввели символы с картинки. Похоже, вы — робот, рассылающий спам!'));
  }

  private function isActive($data)
  {
    if (!($data instanceof Node))
      return false;

    if ($data->id)
      return false;

    $ctx = Context::last();

    if ($ctx->user->id)
      return false;

    if (!is_array($types = $ctx->config->get('modules/captcha/types')))
      return false;

    if (isset($types['__reset']))
      unset($types['__reset']);

    if (empty($types))
      return false;

    if (!in_array($data->class, $types))
      return false;

    return true;
  }

  private function generate()
  {
    $result = strtolower(substr(base64_encode(rand()), 0, 6));

    return crypt::encrypt($result);
  }
}
