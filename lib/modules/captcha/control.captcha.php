<?php

class CaptchaControl extends Control
{
  public static function getInfo()
  {
    return array(
      'name' => t('Капча'),
      );
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

    // Сбрасываем капчу, чтобы если отключена загрузка картинок,
    // пользователь получил ошибку.
    mcms::session('captcha:' . $this->value, null);

    return parent::wrapXML(array(
      ));
  }

  /**
   * Ничего никуда не сохраняет, просто валидирует значение.
   */
  public function set($value, Node &$node)
  {
    if (!$this->isActive($node))
      return;

    $this->validate($value);
  }

  /**
   * Сверяет введённое значение с сохранённым в сессии.
   */
  protected function validate($value)
  {
    if (!($good = mcms::session('captcha:' . $this->value)) or $good != $value)
      throw new ValidationException($this->label, t('Вы неверно ввели символы с картинки. Похоже, вы — робот, рассылающий спам!'));
    mcms::session('captcha:' . $this->value, null);
  }

  private function isActive($data)
  {
    if ($data->id)
      return false;

    $ctx = Context::last();

    if ($ctx->user->id and !$this->required)
      return false;

    return true;
  }

  private function generate()
  {
    $result = substr(base64_encode(rand()), 0, 6);

    return crypt::encrypt($result);
  }

  /**
   * Добавление в форму создания документа.
   * @mcms_message ru.molinos.cms.form.node.create
   */
  public static function onModifyNodeForm(Context $ctx, Node $node, Schema &$schema)
  {
    $types = $ctx->config->getArray('modules/captcha/types');

    if (in_array($node->class, $types) and !$ctx->user->id and !$node->id)
      $schema['captcha'] = new CaptchaControl(array(
        'value' => 'captcha',
        'label' => t('Введите символы с картинки'),
        'required' => true,
        ));
  }
}
