<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once 'plugins/shared.escape_special_chars.php';

function is_ie()
{
    if (!defined('IS_IE')) {
        // we detect IE 5.5 and IE 6.0 (earlier are unfixable, 7.0+ is sane)
        if ((strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.5') !== false or strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0') !== false)
            and strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') === false
        ) {
            define('IS_IE', true);
        } else {
            define('IS_IE', false);
        }
    }

    return IS_IE;
}

function smarty_function_png_image($params, &$smarty)
{
    $extra = '';

    foreach ($params as $k => $v) {
        switch ($k) {
            case 'src':
            case 'width':
            case 'height':
                $$k = $v;
            break;
    
            case 'alt':
                $alt = smarty_function_escape_special_chars($v);
            break;
    
            default:
                $extra .= ' '.$k.'="'.smarty_function_escape_special_chars($v).'"';
            break;
        }
    }

    if (!isset($src)) {
        $smarty->trigger_error("png_image: missing 'src' parameter");
        return;
    }

    if (!isset($height) or !isset($width)) {
        $smarty->trigger_error("png_image: you have to specify width and height of an image");
        return;
    }

    if (!isset($alt))
        $alt = '';

    if (is_ie()) {
        $filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".$src."',sizingMethod='scale')";
        $html = '<span '.$extra.' style="height:'.$height.'px;width:'.$width.'px;filter:'.$filter.';"></span>';
    } else {
        $html = '<img src="'.$src.'" height="'.$height.'" width="'.$width.'" alt="'.smarty_function_escape_special_chars($alt).'" '.$extra.'>';
    }

    return $html;
}

?>
