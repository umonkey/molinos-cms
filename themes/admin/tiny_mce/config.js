tinyMCE.init({
    language : "ru_UTF-8",
    mode : "specific_textareas",
    editor_selector : "mceEditor",
    theme : "advanced",
    plugins : "table,style,media,inlinepopups",
    inline_styles : true,
    extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
    theme_advanced_buttons3_add : "media,styleprops,tablecontrols",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_path_location : "bottom",
    convert_urls : true,
    relative_urls : false,
    file_browser_callback : "bebopImageBrowser"
});

function bebopImageBrowser(field_name, url, type, win)
{
    if (field_name == 'src')
      field_name += '&BebopFiles.search=image%2F';
    window.open('/admin/files/picker/?BebopFiles.picker='+field_name, '_blank');
}
