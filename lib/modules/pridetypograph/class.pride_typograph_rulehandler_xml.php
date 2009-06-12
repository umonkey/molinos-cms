<?php
/**
 * Pride Typograph Library: Rule Handler Xml
 * 
 * @copyright   (c) 2008 Pridedesign.ru, <mail@pridedesign.ru>
 * @author      Артур Русаков <arthur@pridedesign.ru>
 * @version     1.7
 */

/**
 * @see Pride_Typograph_RuleHandler_Abstract
 */
// require_once 'Pride/Typograph/RuleHandler/Abstract.php';

/**
 * Pride_Typograph_RuleHandler_Xml
 * 
 * @category    Pride
 * @package 	Pride_Typograph
 * @subpackage 	RuleHandler
 */
class Pride_Typograph_RuleHandler_Xml extends Pride_Typograph_RuleHandler_Abstract
{
	/**
	 * Типографирование текста
	 *
	 * @param 	string $text
	 * @return 	string
	 */
	public function parse($text)
	{
		$this->setText($text);
        $this->_safeChars(true);
        
        foreach ($this->getReplaceRules()->rule as $rule) {
            if ($this->_parseOption($rule->{parent::RULE_REPLACE_KEY_ACCESSKEY})) {
                $this->_text = str_replace($rule->{parent::RULE_REPLACE_KEY_SEARCH}, $rule->{parent::RULE_REPLACE_KEY_REPLACE}, $this->_text);
            }
        }
            
        foreach ($this->getRegExpRules()->rule as $rule) {
            if ($this->_parseOption($rule->{parent::RULE_REGEXP_KEY_ACCESSKEY})) {
                $this->_text = preg_replace($rule->{parent::RULE_REGEXP_KEY_PATTERN}, $rule->{parent::RULE_REGEXP_KEY_REPLACEMENT}, $this->_text);
            }
        }
        
        $this->_parseQuotes();
        $this->_safeChars(false);
        
        return $this->_text;
	}
	
	/**
	 * Правила для 'preg_replace'
	 *
	 * @return 	SimpleXML
	 */
	public function getRegExpRules()
	{
		$this->_checkXmlFile($this->_options['xml_regex_file']);
		return simplexml_load_file($this->_options['xml_regex_file']);
	}
	
	/**
	 * Правила для 'str_replace'
	 *
	 * @return 	SimpleXML
	 */
	public function getReplaceRules()
	{
		$this->_checkXmlFile($this->_options['xml_replace_file']);
		return simplexml_load_file($this->_options['xml_replace_file']);
	}
	
	/**
	 * Правила для очистки текста от типографии
	 *
	 * @return 	SimpleXML
	 */
	public function getCleanRules()
	{
		$this->_checkXmlFile($this->_options['xml_clean_file']);
		return simplexml_load_file($this->_options['xml_clean_file']);
	}
	
	/**
	 * Очистка текста от последствий типографирования
	 *
	 * @param 	string $text
	 * @return 	string
	 */
	public function clean($text)
    {
    	foreach ($this->getCleanRules()->clean as $clean) {
            $text = str_replace($clean->search, $clean->replace, $text);
        }
        
        return $text;
    }
    
    /**
     * Имя текущего обработчика
     *
     * @return 	string
     */
    public function getRuleHandler()
	{
		return 'xml';
	}
	
	/**
	 * Проверка корректности файла с правилами
	 *
	 * @param 	string $path прямой путь к файлу
	 * @throws 	Pride_Typograph_Exception
	 * @return 	void
	 */
	protected function _checkXmlFile($path)
	{
		if (!is_string($path) || empty($path)) {
			// require_once 'Pride/Typograph/Exception.php';
			throw new Pride_Typograph_Exception('Bad regex file name');
		}
		
		if (!is_readable($path)) {
			// require_once 'Pride/Typograph/Exception.php';
			throw new Pride_Typograph_Exception("File '{$path}' isn't readable");
		}
		
		if (!filesize($this->_options['xml_regex_file'])) {
			// require_once 'Pride/Typograph/Exception.php';
			throw new Pride_Typograph_Exception("File '{$path}' is empty");
		}
	}
}
