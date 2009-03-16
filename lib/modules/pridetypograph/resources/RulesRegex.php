<?php
/**
 * Pride Resource: Typograph PHP Regexp Rules
 * 
 * @category    Pride
 * @package 	Pride_Typograph
 * @subpackage 	Resources
 * @copyright   (c) 2008 Pridedesign.ru, <mail@pridedesign.ru>
 * @author      Артур Русаков <arthur@pridedesign.ru>
 * @version     1.7
 */
return array(	'updated' => '1220315072',

				array(	'access_key' 	=> 'super_nbsp',
						'pattern' 		=> '/(\s|^)([a-zа-я]{1,3}\s+)([a-zа-я]{1,3}\s+)?([a-zа-я]{3,})/eui', 
						'replacement' 	=> '"\1" . trim("\2") . "&nbsp;" . ("\3" ? trim("\3") . "&nbsp;" : "") . "\4"', 
						'description' 	=> 'Расстановка неразрывных пробелов.'),
				
				array(	'access_key' 	=> 'many_spaces_to_one',
						'pattern' 		=> '/[\040]+/', 
						'replacement' 	=> ' ', 
						'description' 	=> 'Замена двух и более пробелов одним.'),
						
				array(	'access_key' 	=> 'auto_comma',
						'pattern' 		=> '/([a-zа-я])(\s|&nbsp;)(но|а|когда)(\s|&nbsp;)/iu', 
						'replacement' 	=> '\1,\2\3\4', 
						'description' 	=> 'Расстановка запятых перед предлогами "но", "а", "когда"'),
						
				array(	'access_key' 	=> 'tm_replace',
						'pattern' 		=> '/\(tm\)/i', 
						'replacement' 	=> '&trade;', 
						'description' 	=> 'Замена (tm) в любом регистре на соответствующий код.'),
						
				array(	'access_key' 	=> 'r_sign_replace',
						'pattern' 		=> '/\(r\)/i', 
						'replacement' 	=> '&reg;', 
						'description' 	=> 'Замена (r) в любом регистре на соответствующий код.'),
						
				array(	'access_key' 	=> 'copy_replace',
						'pattern' 		=> '/\((c|с)\)\s+/i', 
						'replacement' 	=> '&copy;&nbsp;', 
						'description' 	=> 'Замена (c) в любом регистре на соответствующий код.'),
						
				array(	'access_key' 	=> 'left_space_mdash',
						'pattern' 		=> '/\040\-(\s|$)/', 
						'replacement' 	=> '&nbsp;&mdash;\1', 
						'description' 	=> 'Замена пробела перед дефисом на неразрывный, дефис - на тире.'),
						
				array(	'access_key' 	=> 'mdash',
						'pattern' 		=> '/(\s+|^)\-(\s+)/', 
						'replacement' 	=> '\1&mdash;\2', 
						'description' 	=> 'Расстановка тире, когда слева и справа пробельные символы (табуляция, каретка, перенос строки), а также, когда тире является первым символом в абзаце.'),
						
				array(	'access_key' 	=> 'punctuation_marks_limit',
						'pattern' 		=> '/([\!\.\?]){4,}/', 
						'replacement' 	=> '\1\1\1', 
						'description' 	=> 'Замена четырех и более символов !, ? или . на три.'),
						
				array(	'access_key' 	=> 'last_number_letter_autoperiod',
						'pattern' 		=> '/([a-zа-я])$/iu', 
						'replacement' 	=> '\1.', 
						'description' 	=> 'Если строка оканчивается на букву, вставка точки.'),
				
				array(	'access_key' 	=> 'punctuation_marks_base_limit',
						'pattern' 		=> '/([\,\:]){2,}/', 
						'replacement' 	=> '\1', 
						'description' 	=> 'Удаление лишних повторов знаков препинания.'),		

				array(	'access_key' 	=> 'autospace_after_comma',
						'pattern' 		=> '/\s*\,([а-яa-z])/iu', 
						'replacement' 	=> ', \1', 
						'description' 	=> 'Расстановка пробелов после запятых, если идущее после запятой слово написано слитно с самой запятой.'),
						
				array(	'access_key' 	=> 'remove_space_before_punctuationmarks',
						'pattern' 		=> '/(\s+)([\,\:\.])(\s+)/', 
						'replacement' 	=> '\2\3', 
						'description' 	=> 'Удаление перед знаками пунктуации пробелов.'),
	
				array(	'access_key' 	=> 'first_letter_line_to_uppercase',
						'pattern' 		=> '/^([a-zа-я])/eu', 
						'replacement' 	=> 'mb_strtoupper("\1", "utf-8");', 
						'description' 	=> 'Перевод первого символа в строке в верхний регистр.'),
						
				array(	'access_key' 	=> 'first_letter_sentence_to_uppercase',
						'pattern' 		=> '/([а-яa-z])([\!\.\?]+)(\s+)([а-яa-z])/eu', 
						'replacement' 	=> '"\1" . "\2" . "\3" . mb_strtoupper("\4", "utf-8");', 
						'description' 	=> 'Перевод первого символа нового предложения в верхний регистр. Новым предложением считается такое, перед которым идет точка, воскл. или вопр. знаки с пробелом.'),
					
				array(	'access_key' 	=> 'nobr_after_first_bracket',
						'pattern' 		=> '/\b\(\s*(\w+|\()/', 
						'replacement' 	=> '&nbsp;(\1', 
						'description' 	=> 'Расстановка неразрывного пробела между открывающейся скобкой и позади идущего слова (даже в том случае, если пробельный символ отсутствует).'),
						
				array(	'access_key' 	=> 'nobr_before_unit',
						'pattern' 		=> '/(\s|^)(\d+)(м|мм|см|км|гм|km|dm|cm|mm)(\s|\.|\!|\?|\,)/iu', 
						'replacement' 	=> '\1\2&nbsp;\3\4', 
						'description' 	=> 'Расстановка неразрывного пробела между числом и фиксированной единицей измерения, если они написаны слитно.'),
						
				array(	'access_key' 	=> 'nobr_acronym',
						'pattern' 		=> '/(\s)(гл|стр|рис|илл)\.(\s*)(\d+)(\s|\.|\,\?\!)/iu', 
						'replacement' 	=> '\1\2.&nbsp;\3\4\5', 
						'description' 	=> 'Расстановка неразрывного пробела между сокращениями "рис.", "стр.", "илл." и "гл." и спереди идущей цифрой.'),
						
				array(	'access_key' 	=> 'say_yes_to_apostrophe_eng',
						'pattern' 		=> '/\b([a-z]{2,})\'([a-z]+)\b/i', 
						'replacement' 	=> '\1&rsquo;\2', 
						'description' 	=> 'Расстановка "правильного" апострафа в английских словах.'),
						
				array(	'access_key' 	=> 'nobr_abbreviation',
						'pattern' 		=> '/(\s+|^)(\d+)(dpi|lpi)([\s\;\.\?\!\:\(]+)/i', 
						'replacement' 	=> '\1\2&nbsp;\3\4', 
						'description' 	=> 'Расстановка неразрывных пробелов между сокращениями "dpi", "lpi" и позади идущим числом.'),
						
				array(	'access_key' 	=> 'auto_times_x',
						'pattern' 		=> '/(\d+)(\040*)(x|х)(\040*)(\d+)/', 
						'replacement' 	=> '\1&times;\5', 
						'description' 	=> 'Заменя буквы "х" между цифрами на соответствующий код.'),
						
				array(	'access_key' 	=> 'quotes_outside_a',
						'pattern' 		=> '/(\<%%\_\_.+?\>)\"(.+?)\"(\<\/%%\_\_.+?\>)/s', 
						'replacement' 	=> '"\1\2\3"', 
						'description' 	=> ''),
);