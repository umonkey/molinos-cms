#!/bin/sh

echo -n "Enter widget name, without the widget- prefix: "
read name

if [ -z "$name" ]; then
  exit 0
fi

if [ -d "widget-$name" ]; then
  echo "Something with that name already exists."
  ls -ld "widget-$name"
  exit 0
fi

echo -n "Enter class name: "
read class

if [ -z "$class" ]; then
  exit 0
fi

mkdir "widget-$name"
cat > "widget-$name/widget-$name.info" << EOF
group = widgets
classes = $class
interface[iWidget] = $class
name[en] = $class Widget
name[ru] = Виджет $class
EOF

cat > "widget-$name/widget-$name.php" << EOF
<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class $class extends Widget
{
  public function __construct(Node \$node)
  {
    parent::__construct(\$node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Название виджета',
      'description' => 'Описание виджета.',
      );
  }

  public static function formGetConfig()
  {
    \$form = parent::formGetConfig();

    /*
    \$form->addControl(new TextLineControl(array(
      'value' => 'config_varname',
      'label' => t('Название настройки'),
      )));
    */

    return \$form;
  }

  public function formHookConfigData(array &\$data)
  {
    // \$data['xyz'] = 123;
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext \$ctx)
  {
    \$options = parent::getRequestOptions(\$ctx);
    return \$options;
  }

  // Обработка GET запросов.
  public function onGet(array \$options)
  {
    \$result = array(
      );

    return \$result;
  }
};
EOF
