<?php
/**
 * Pride Resource: Typograph PHP Replace Rules
 * 
 * @category    Pride
 * @package 	Pride_Typograph
 * @subpackage 	Resources
 * @copyright   (c) 2008 Pridedesign.ru, <mail@pridedesign.ru>
 * @author      Артур Русаков <arthur@pridedesign.ru>
 * @version     1.7
 */
return array(	'updated' => '1220315072',

				array(	'access_key' 	=> 'replace_plus_minus',
						'search' 		=> '+-',
						'replace' 		=> '&plusmn;',
						'description' 	=> 'Замена +- на код.' ),
						
				array(	'access_key' 	=> 'replace_paragraph',
						'search' 		=> '§',
						'replace' 		=> '&sect;',
						'description' 	=> 'Замена символа параграфа на код.' ),
						
				array(	'access_key' 	=> 'replace_three_dots',
						'search' 		=> '...',
						'replace' 		=> '&hellip;',
						'description' 	=> 'Замена многоточие на код.' ),
						
				array(	'access_key' 	=> 'replace_manual_mdash',
						'search' 		=> '—',
						'replace' 		=> '-',
						'description' 	=> 'Замена в ручную проставленного тире на дефис для последующего типографирования.' ),
						
				array(	'access_key' 	=> 'replace_manual_lquote',
						'search' 		=> '«',
						'replace' 		=> '"',
						'description' 	=> 'Замена символа левой "ёлочки" на кавычку.' ),
						
				array(	'access_key' 	=> 'replace_manual_laquo',
						'search' 		=> '«',
						'replace' 		=> '"',
						'description' 	=> 'Замена символа левой "ёлочки" на кавычку.' ),
						
				array(	'access_key' 	=> 'replace_manual_raquo',
						'search' 		=> '»',
						'replace' 		=> '"',
						'description' 	=> 'Замена символа правой "ёлочки" на кавычку.' ),
				
				array(	'access_key' 	=> 'replace_manual_bdquo',
						'search' 		=> '„',
						'replace' 		=> '"',
						'description' 	=> 'Замена открывающей "лапки" на кавычку.' ),		

				array(	'access_key' 	=> 'replace_manual_ldquo',
						'search' 		=> '“',
						'replace' 		=> '"',
						'description' 	=> 'Замена закрывающей "лапки" на кавычку.' ),		
									
				array(	'access_key' 	=> 'replace_ge',
						'search' 		=> '>=',
						'replace' 		=> '&ge;',
						'description' 	=> 'Больше или равно' ),
						
				array(	'access_key' 	=> 'replace_le',
						'search' 		=> '<=',
						'replace' 		=> '&le;',
						'description' 	=> 'Меньше или равно' ),
						
				array(	'access_key' 	=> 'replace_ne',
						'search' 		=> '!=',
						'replace' 		=> '&ne;',
						'description' 	=> 'Не равно равно' ),
						
				array(	'access_key' 	=> 'replace_equiv',
						'search' 		=> '===',
						'replace' 		=> '&equiv;',
						'description' 	=> 'Тождественно равно' ),
);