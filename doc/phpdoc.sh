#!/bin/sh
cd $(dirname $0)
if [ ! -d phpdoc ]; then
    mkdir phpdoc
else
    rm -rf phpdoc/*
fi
phpdoc -dn 'mod_base' -t phpdoc -d ../lib/modules/base --ignore '*.phtml,*.png,*.cs,*.js,tests.php,*.test.php' -o HTML:default:default --title "Molinos CMS Documentation"
