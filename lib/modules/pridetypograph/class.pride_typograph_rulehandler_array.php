<?php
/**
 * Pride Typograph Library: Rule Handler Array
 * 
 * @copyright   (c) 2008 Pridedesign.ru, <mail@pridedesign.ru>
 * @author      Артур Русаков <arthur@pridedesign.ru>
 * @version     1.7
 */

/**
 * Pride_Typograph_RuleHandler_Array
 * 
 * @category    Pride
 * @package 	Pride_Typograph
 * @subpackage 	RuleHandler
 */
class Pride_Typograph_RuleHandler_Array extends Pride_Typograph_RuleHandler_Abstract
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
        
        $replaceRules = $this->getReplaceRules();
        $regExpRules = $this->getRegExpRules();

        foreach ($replaceRules as $rule) {
        	if (is_array($rule) && count($rule)) {
            	if ($this->_parseOption($rule[parent::RULE_REPLACE_KEY_ACCESSKEY])) {
                	$this->_text = str_replace($rule[parent::RULE_REPLACE_KEY_SEARCH], $rule[parent::RULE_REPLACE_KEY_REPLACE], $this->_text);
            	}
        	}
        }
            
        foreach ($regExpRules as $rule) {
        	if (is_array($rule) && count($rule)) {
            	if ($this->_parseOption($rule[parent::RULE_REGEXP_KEY_ACCESSKEY])) {
                	$this->_text = preg_replace($rule[parent::RULE_REGEXP_KEY_PATTERN], $rule[parent::RULE_REGEXP_KEY_REPLACEMENT], $this->_text);
            	}
        	}
        }
        
        $this->_parseQuotes();
        $this->_safeChars(false);
        
        return $this->_text;
	}
	
	/**
	 * Правила для 'preg_replace'
	 *
	 * @return 	array
	 */
	public function getRegExpRules()
	{
		return $this->_options['array_regex_array'];
	}
	
	/**
	 * Правила для 'str_replace'
	 *
	 * @return 	array
	 */
	public function getReplaceRules()
	{
		return $this->_options['array_replace_array'];
	}
	
	/**
	 * Правила для очистки текста от типографии
	 *
	 * @return 	array
	 */
	public function getCleanRules()
	{
		return $this->_options['array_clean_array'];
	}
	
	/**
	 * Очистка текста от последствий типографирования
	 *
	 * @param 	string $text
	 * @return 	string
	 */
	public function getRuleHandler()
	{
		return 'array';
	}
	
	/**
	 * Очистка текста от последствий типографирования
	 *
	 * @param 	string $text
	 * @return 	string
	 */
	public function clean($text)
    {
    	$cleanRules = $this->getCleanRules();
    	
    	foreach ($cleanRules as $clean) {
    		if (is_array($clean) && count($clean)) {
            	$text = str_replace($clean['search'], $clean['replace'], $text);
    		}
        }
        
        return $text;
    }
}
