<?php
/**
 * Pride Typograph Library: Rule Handler Abstraction
 * 
 * @copyright   (c) 2008 Pridedesign.ru, <mail@pridedesign.ru>
 * @author      Артур Русаков <arthur@pridedesign.ru>
 * @version     1.7
 */

/**
 * Pride_Typograph_RuleHandler_Abstract
 * 
 * @category    Pride
 * @package 	Pride_Typograph
 * @subpackage 	RuleHandler
 */
abstract class Pride_Typograph_RuleHandler_Abstract
{
    /**
     * Константы
     */
    const QUOTE_FIRS_OPEN = '&laquo;';
    const QUOTE_FIRS_CLOSE = '&raquo;';
    const QUOTE_CRAWSE_OPEN = '&bdquo;';
    const QUOTE_CRAWSE_CLOSE = '&ldquo;';
    
    const QUOTE_PARSE_LIMIT = 15;
    
    const RULE_REGEXP_KEY_ACCESSKEY = 'access_key';
    const RULE_REGEXP_KEY_PATTERN = 'pattern';
    const RULE_REGEXP_KEY_REPLACEMENT = 'replacement';
    
    const RULE_REPLACE_KEY_ACCESSKEY = 'access_key';
    const RULE_REPLACE_KEY_SEARCH = 'search';
    const RULE_REPLACE_KEY_REPLACE = 'replace';
    
    /**
     * Текст для обработки
     * 
     * @var string
     */
    protected $_text = '';
    
    /**
     * Настройки исключений при обработке текста
     *
     * @var array
     */
    protected $_parseOptions = array();
    
    /**
     * Безопасные блоки - текст внутри данных блоков не типографируется, т.е. к нему
     * не применяются соответствующие правила
     *
     * @var array
     */
    protected $_safetyBlocks = array( array('<pre>', '</pre>'), array( '<script>', '</script>') );

    /**
     * Конструктор
     *
     * @param 	array $options параметры адаптера
     * @return 	void
     */
    public function __construct(array $options)
	{
		$this->_options = $options;
	}
	
	/**
	 * Типографирование текста
	 *
	 * @param 	string $text
	 * @return 	string
	 */
	public abstract function parse($text);
	
	/**
	 * Очистка текста от последствий типографирования
	 * 
	 * Все коды HTML будут заменены на их эквивалентные символы, например,
	 * 'ёлочки' на обычные кавычки.
	 *
	 * @param 	string $text
	 * @return 	string
	 */
	public abstract function clean($text);
	
	/**
     * Имя обработчика
     *
     * @return 	string
     */
	public abstract function getRuleHandler();
    
    /**
     * Добавление безопасного блока
     *
     * @throws  Pride_Typograph_Exception
     * @param   $before
     * @param   $after
     * @param 	$unquote
     * @return  void
     */
    public function addSafetyBlock($before, $after, $unquote = false)
    {
        foreach ($this->_safetyBlocks as $block) {
            if (in_array($before, $block) || in_array($before, $after)) {
                require_once 'Pride/Typograph/Exception.php';
                throw new Pride_Typograph_Exception("'$before' or '$after' already exists in safety blocks");
            }
        }
        
        if ($before === '<' || $after === '>') {
        	require_once 'Pride/Typograph/Exception.php';
            throw new Pride_Typograph_Exception("Bad '$before' or '$after'");
        }
        
        $this->_safetyBlocks[] = array($before, $after, $unquote);
    }
    
    /**
     * Список установленных безопасных блоков
     *
     * @return  array
     */
    public function getSafetyBlocks()
    {
        return $this->_safetyBlocks;
    }
    
    /**
     * Установка текста для типографирования
     *
     * @throws  Pride_Typograph_Exception
     * @param   string $text
     * @return  void
     */
    public function setText($text)
    {
        if (!is_string($text)) {
            require_once 'Pride/Typograph/Exception.php';
            throw new Pride_Typograph_Exception('Incorrect data');
        }
        
        $text = trim($text);
        
        if ('' === $text) {
            require_once 'Pride/Typograph/Exception.php';
            throw new Pride_Typograph_Exception('String is empty');
        }
        
        $this->_text = $this->clean($text);
    }
    
    /**
     * Установка опции обработки
     *
     * Значение может быть только типа 'boolean'!
     * 
     * @throws  Pride_Typograph_Exception
     * @param   string $key
     * @param   bool $value
     * @return  void
     */
    public function setParseOption($key, $value)
    {
        if (!is_bool($value)) {
            require_once 'Pride/Typograph/Exception.php';
            throw new Pride_Typograph_Exception("Access key '$key' must be boolean");
        }
        
        $this->_parseOptions[$key] = $value;
    }
    
    /**
     * Пакетная установка опций обработки
     * 
     * Необходимо передать массив, в котором ключ будет являться названием
     * соответствующего ключа доступа, значение должно быть типа 'boolean'.
     *
     * @throws  Pride_Typograph_Exception
     * @param   array $options
     * @return  void
     */
    public function setParseOptions(array $options)
    {
        if (!count($options)) {
            require_once 'Pride/Typograph/Exception.php';
            throw new Pride_Typograph_Exception('Options array is empty');
        }
        
        foreach ($options as $key => $value) {
            $this->setParseOption($key, $value);
        }
    }
    
    /**
     * Сохранение содержимого в безопасных блоках
     * Используется base64-кодирование.
     *
     * @throws  Pride_Typograph_Exception
     * @param   bool $safe
     * @return  void
     */
    protected function _safeChars($safe = true)
    {
        if (!is_bool($safe)) {
            require_once 'Pride/Typograph/Exception.php';
            throw new Pride_Typograph_Exception('Incorrect var type');
        }

        if (true === $safe) {
        	$this->_safeBlockChars($safe);
        	$this->_safeTagChars($safe);
        } else {
        	$this->_safeTagChars($safe);
        	$this->_safeBlockChars($safe);
        }
    }
    
    /**
     * Сохраняем содержимое тегов HTML
     *
     * Тег 'a' кодируется со специальным префиксом для дальнейшей
     * возможности выносить за него кавычки.
     * 
     * @param 	bool $safe
     */
    protected function _safeTagChars($safe)
    {
    	if (true === $safe) {
        	$this->_text = preg_replace('/(\<\/?)(.+?)(\>)/se', '"\1" .  ( substr(trim("\2"), 0, 1) === "a" ? "%%___"  : ""  ) . base64_encode(trim("\2"))  . "\3"', $this->_text);
        } else {
        	$this->_text = preg_replace('/(\<\/?)(.+?)(\>)/se', '"\1" .  ( substr(trim("\2"), 0, 3) === "%%___" ? base64_decode(substr(trim("\2"), 4)) : base64_decode(trim("\2")) ) . "\3"', $this->_text);	
        }
    }
    
    /**
     * Сохраняем содержимое пользовательских блоков
     *
     * @param 	bool $safe
     */
    protected function _safeBlockChars($safe)
    {
    	if (count($this->_safetyBlocks)) {
        	foreach ($this->_safetyBlocks as $block) {
        		if (empty($block[2])) {
        			$block[0] = preg_quote($block[0], '/');
            		$block[1] = preg_quote($block[1], '/');
        		}

        		$safeType = (true === $safe) ? 'base64_encode' : 'base64_decode';
        		$this->_text = preg_replace("/({$block[0]})(.+?)({$block[1]})/se",   "'\\1' . $safeType('\\2') . '\\3'"  , $this->_text);
        	}
        }
    }
    
    /**
     * Получение значение ключа
     *
     * Если массив пуст или не содержит запрошенного значения, будет возвращено true,
     * что привет к обработке инструкции
     * 
     * @param   string $key
     * @return  bool
     */
    protected function _parseOption($key)
    {
        $key = (string) $key;
        $key = trim($key);
        
        if ($key && isset($this->_options[$key])) {
            if (!$this->_options[$key]) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Поиск парных кавычек в строке
     *
     * @return  void
     */
    protected function _parseQuotes()
	{
		$i = 0;
		$quoteRule = '/(\s|\]|\>|^|\"|&nbsp;)(\"|\\\")([^\"]*?)(?<!\s)(\"|\\\")(\s|\"|\[|\<|\!|\.|\,|\?|$|&nbsp;|\))/';
		
		while (preg_match($quoteRule, $this->_text)) {
			$this->_text = preg_replace($quoteRule . 'e', "'\\1' . \$this->_buildQuotes('\\3')  . '\\5';", $this->_text);
			
			if (++$i > self::QUOTE_PARSE_LIMIT) {
				return;
			}
		}
	}
	
	/**
	 * Процесс расстановки 'правильных' кавычек
	 *
	 * Если в тексте присутствуют кавычки первого уровня ("ёлочки"), они будут заменены
	 * кавычками второго уровня ("лапками").
	 * 
	 * @param  string $t
	 * @return string
	 */
	protected function _buildQuotes($t = '')
	{
		$t 	= str_replace(self::QUOTE_FIRS_OPEN, self::QUOTE_CRAWSE_OPEN, $t);
		$t 	= str_replace(self::QUOTE_FIRS_CLOSE, self::QUOTE_CRAWSE_CLOSE, $t);
		
		return self::QUOTE_FIRS_OPEN . $t . self::QUOTE_FIRS_CLOSE;
	}
}