<?php
// vim: expandtab tabstop=2 shiftwidth=2 softtabstop=2:

require_once(dirname(__FILE__).'/../dashboard/dashboard.php');

class PollWidget extends Widget implements iContentType
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }
  
  // Возвращаем описание типа документа.
  public static function getTypeSchema()
  {
    return array(
      'poll' => array(
        'name' => 'Опрос',
        'description' => "Опрос пользователей на произвольные темы.&nbsp; Создавая новый документ Вы <a href='/admin/content/add/poll/'>создаёте новый опрос</a>, в котором затем можете указать до 10 вариантов ответа.&nbsp; Опрос можно закрыть в любой момент.",
        'fixedfields' => true,
        'fields' => array(
          'name' => array(
            'type' => 'TextLineControl',
            'label' => 'Вопрос',
            'required' => true,
            ),
          'created' => array(
            'type' => 'DatetimeControl',
            'label' => 'Дата начала опроса',
            'required' => true,
            ),
          ),
        ),
      );
  }

  // Правим форму редактирования опроса.
  public static function modifyEditForm(array &$form, array $data)
  {
    if (!empty($data['id']))
      $answers = PDO_Singleton::getInstance()->getResultsKV("nid", "count", "SELECT `nid`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = :nid GROUP BY `nid`", array(':nid' => $data['id']));

    for ($i = 0; $i < 10; $i++) {
      $item = array(
        '#type' => 'TextLineControl',
        '#text' => "Вариант ответа #". ($i + 1),
        '#value' => @$data['answers'][$i],
        );

      if (!empty($answers[$i]))
        $item['#description'] = "Голосов за этот вариант: {$answers[$i]}";

      $form['option'. $i] = $item;
    }
  }

  // Обрабатываем сохранение.
  public static function hookNodeUpdate(array &$node, array &$data)
  {
    $answers = array();

    for ($i = 0; $i < 10; $i++)
      $options[$i] = strval(@$data['option'. $i]);

    $node['options'] = $options;
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => 'Форма для голосования',
      'description' => 'Возвращает форму для голосования.',
      );
  }

  public function onGet(array $options)
  {
    $result = array();

    if (intval($nid = $options['pollid'])) {
      $tg = Tagger::getInstance();
      $node = $tg->getObject($nid);

      if ($node['class'] == 'poll') {
        // Рисуем форму для голосования.
        if (empty($_SESSION['poll'][$nid])) {
          if (empty($node['closed'])) {
            $form = array(
              'answer' => array(
                '#type' => 'EnumRadioControl',
                '#text' => $node['name'],
                '#values' => array(),
                ),
              );

            foreach ($node['answers'] as $k => $v) {
              if (!empty($v))
                $form['answer']['#values'][$k] = $v;
            }
          }

          $result['form'] = bebop_render_form($this->getInstanceName(), $form);
        }

        // Возвращаем результат.
        $answers = PDO_Singleton::getInstance()->getResultsKV("option", "count", "SELECT `option`, COUNT(*) AS `count` FROM `node__poll` WHERE `nid` = :nid GROUP BY `nid`", array(':nid' => $nid));
        foreach ($node['answers'] as $k => $v) {
          if (!empty($v))
            $result['status'][$k] = array('name' => $v, 'votes' => intval(@$answers[$k]));
        }
      }
    }

    return $result;
  }

  public function onPost(array $options, array $post, array $files)
  {
    if (intval($nid = $options['pollid'])) {
      if (empty($post['answer']) or !is_numeric($post['answer']))
        throw new UserErrorException("Ответ не указан", 500, "Ответ не указан", "Вы забыли указать, за что голосуете.");

      $tg = Tagger::getInstance();
      $node = $tg->getObject($nid);

      if (empty($node))
        throw new UserErrorException("Опрос не найден", 404, "Опрос {$nid} не найден", "Опрос с таким кодом не существует.");

      if ($node['class'] != 'poll')
        throw new UserErrorException("Опрос не найден", 404, "Опрос не найден", "Документ с кодом {$nid} не является опросом.");

      if (!empty($node['closed']))
        throw new UserErrorException("Опрос закрыт", 403, "Опрос закрыт", "Опрос &laquo;{$node['name']}&raquo; закрыт администратором, голос не может быть принят.");

      $sth = PDO_Singleton::getInstance()->prepare("INSERT INTO `node__poll` (`nid`, `option`) VALUES (:nid, :answer)");
      $sth->execute(array(':nid' => $nid, ':answer' => $post['answer']));

      $_SESSION['poll'][$nid] = true;
    }
  }

  public function getRequestOptions(RequestContext $ctx)
  {
    throw new InvalidArgumentException("FIXME");

    $options = parent::getRequestOptions($ctx);
    $options['pollid'] = intval(@$ctx->apath[$this->pollid - 1]);
    $options['sid'] = session_id();
    return $options;
  }
}
