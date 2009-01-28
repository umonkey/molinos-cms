<?php

class TinyMCEConfig implements iModuleConfig
{
  public static function formGetModuleConfig()
  {
    $form = new Form(array());
    $form->addClass('tabbed');

    $tab = new FieldSetControl(array(
      'name' => 'main',
      'label' => t('Основные настройки'),
      'class' => 'tabable',
      ));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_theme',
      'label' => t('Режим работы'),
      'options' => array(
        'simple' => t('Простой (B/I/U)'),
        'medium' => t('Простой с картинками'),
        'advanced' => t('Всё, что можно'),
        'overkill' => t('На стероидах'),
        ),
      'required' => true,
      )));
    $tab->addControl(new BoolControl(array(
      'value' => 'config_gzip',
      'label' => t('Использовать компрессию'),
      )));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_toolbar',
      'label' => t('Панель инструментов'),
      'required' => true,
      'options' => array(
        'topleft' => t('Сверху слева'),
        'topcenter' => t('Сверху по центру'),
        'bottomcenter' => t('Снизу по центру'),
        ),
      )));
    $tab->addControl(new EnumControl(array(
      'value' => 'config_path',
      'label' => t('Текущий элемент'),
      'required' => true,
      'options' => array(
        '' => t('Не показывать'),
        'bottom' => t('Снизу'),
        ),
      'description' => t('При отключении пропадает также возможность изменять размер редактора мышью.'),
      )));
    $tab->addControl(new TextAreaControl(array(
      'value' => 'config_initializer',
      'label' => t('Дополнительные инициализаторы'),
      'description' => t('Например: content_css: mcms_path + "/tiny.css", theme_advanced_styles: "Слева=left,Справа=right"'),
      )));
    $form->addControl($tab);

    $tab = new FieldSetControl(array(
      'name' => 'pages',
      'label' => t('Страницы'),
      'class' => 'tabable',
      ));
    $tab->addControl(new InfoControl(array(
      'text' => t('Этот модуль всегда используется в административном интерфейсе, отключить его нельзя.'),
      )));
    $tab->addControl(new SetControl(array(
      'value' => 'config_pages',
      'label' => t('Использовать редактор на страницах'),
      'options' => DomainNode::getFlatSiteMap('select', true),
      )));
    $form->addControl($tab);

    return $form;
  }
}
