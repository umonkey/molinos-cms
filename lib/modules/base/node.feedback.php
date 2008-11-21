<?php

class FeedbackNode extends Node
{
  public function getFormTitle()
  {
    return t('Обратная связь');
  }

  public function getFormSubmitText()
  {
    return t('Отправить');
  }
}
