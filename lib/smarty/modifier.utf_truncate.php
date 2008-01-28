<?php
/**
 * @package Bebop
 * @subpackage Smarty plugins
 */


/**
 * Smarty UTF-8 friendly truncate modifier plugin
 *
 * Type:     modifier<br>
 * Name:     truncate<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *           optionally splitting in the middle of a word, and
 *           appending the $etc string or inserting $etc into the middle.
 * @author   (original) Monte Ohrt <monte at ohrt dot com>
 * @author   (utf-8 support) Alexey Zakhlestine <indeyets@gmail.com>
 * @param string
 * @param integer
 * @param string
 * @param boolean
 * @param boolean
 * @return string
 */
function smarty_modifier_utf_truncate($string, $length = 80, $etc = '…',
                                  $break_words = false, $middle = false)
{
    if ($length == 0)
        return '';

    if (mb_strlen($string) > $length) {
        $length -= mb_strlen($etc);

        if (!$middle) {
            if (!$break_words) {
                $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1));
            }
            $string = mb_substr($string, 0, $length).$etc;
            return $string;
        } else {
            if ($length % 2 == 0) {
                $length1 = $length2 = $length / 2;
            } else {
                $length1 = ceil($length / 2);
                $length2 = floor($length / 2);
            }
                

            return mb_substr($string, 0, $length1).$etc.mb_substr($string, -$length2);
        }
    } else {
        return $string;
    }
}

/* vim: set expandtab: */

?>
