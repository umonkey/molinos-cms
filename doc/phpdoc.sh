#!/bin/sh
DIR=tmpdoc
cd $(dirname $0)
if [ ! -d phpdoc ]; then
    mkdir "$DIR"
else
    rm -rf "$DIR"/*
fi
phpdoc -dn 'mod_base' -t "$DIR" -d ../lib/modules/base --ignore '*.phtml,*.png,*.cs,*.js,tests.php,*.test.php' -o HTML:default:default --title "Molinos CMS Documentation"
