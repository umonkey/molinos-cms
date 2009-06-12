<?php
/**
 * Pride Library: Typograph Factory
 *
 * @copyright   (c) 2008 Pridedesign.ru, <mail@pridedesign.ru>
 * @author      Артур Русаков <arthur@pridedesign.ru>
 * @version     1.7
 */

/**
 * Pride_Typograph
 *
 * @category    Pride
 * @package 	Pride_Typograph
 */
class Pride_Typograph
{
	/**
	 * Фабричный метод
	 *
	 * @param 	string $handler имя адаптера работы с правилами
	 * @param 	array $options массив опций для выбранного адаптера
	 * @throws 	Pride_Typograph_Exception
	 * @return 	Pride_Typograph_RuleHandler_Abstract
	 */
	public static function factory($handler, array $options)
    {
    	$handlerName = (string) $handler;
    	$handlerName = ucfirst(strtolower($handlerName));
    	
    	if ('' === $handlerName) {
    		// require_once 'Pride/Typograph/Exception.php';
    		throw new Pride_Typograph_Exception('Incorrect rule handler name');
    	}
    	
    	if (!count($options)) {
    		// require_once 'Pride/Typograph/Exception.php';
    		throw new Pride_Typograph_Exception('Options is empty');
    	}
    	
    	$handlerClass = "Pride_Typograph_RuleHandler_$handlerName";
    	
    	if (!class_exists($handlerClass))
    		throw new Pride_Typograph_Exception('Class not exists');
    	
    	$ruleHandler = new $handlerClass($options);
    	
    	if (!$ruleHandler instanceof Pride_Typograph_RuleHandler_Abstract) {
        // require_once 'Pride/Typograph/Exception.php';
    		throw new Pride_Typograph_Exception('Handler class must be extend Pride_Typograph_RuleHandler_Abstract');
      }
    
      return $ruleHandler;
    }
}
