<?php

class SubscriptionControl extends SectionsControl implements iFormControl
{
  private $enabled = array();

  public static function getInfo()
  {
    return array(
      'name' => t("Подписка на новости"),
      );
  }

  public function __construct(array $data)
  {
    parent::__construct($data);

    $this->enabled = Node::create('subscription')->getEnabledSections();
  }

  public function getOptions($data)
  {
    if (empty($this->enabled))
      return null;

    return parent::getOptions($data);
  }

  public function getEnabled($data)
  {
    return $this->enabled;
  }

  public function getSelected($data)
  {
    if (null === ($email = $this->findEmail($data)))
      return array();

    $tags = mcms::db()->getResultsV("tid", "SELECT tid FROM node__rel WHERE nid IN (SELECT n.id FROM node n INNER JOIN node__rev v ON v.rid = n.rid WHERE n.class = 'subscription' AND n.deleted = 0 AND v.name = ?)", array($email));

    return is_array($tags)
      ? $tags
      : array();
  }

  public function set($value, Node &$node)
  {
    if (empty($value['__reset']))
      return;
    unset($value['__reset']);

    $this->validate($value);

    if (null === ($email = $this->findEmail($node)))
      throw new InvalidArgumentException(t('Не удалось подписать вас на новости: в профиле не обнаружен ни один почтовый адрес.'));

    $s = Node::find(array(
      'class' => 'subscription',
      'name' => $email,
      ));

    if (empty($s))
      $s = Node::create('subscription', array(
        'name' => $email,
        ));
    else
      $s = array_shift($s);

    $s->linkSetParents($value, 'tag', $this->enabled)->save();
  }

  protected function findEmail($data)
  {
    if ($data instanceof Node) {
      foreach ($data->getSchema() as $k => $v) {
        if ($v instanceof EmailControl and false !== strpos($email = $data->$k, '@'))
          return $email;
      }
      if (false !== strpos($email = $data->name, '@'))
        return $email;
    }

    elseif (is_array($data) and array_key_exists('email', $data)) {
      if (false !== strpos($email = $data['email'], '@'))
        return $email;
      if (false !== strpos($email = $data['name'], '@'))
        return $email;
    }

    return null;
  }

  protected function filterOptions(array $options, $enabled, $selected)
  {
    if (empty($enabled))
      return array();

    $options = array_intersect_key($options, array_flip($enabled));

    // FIXME
    foreach ($options as $k => $v)
      while (0 === strpos($v, '&nbsp;'))
        $options[$k] = $v = substr($v, 6);

    return $options;
  }
}
