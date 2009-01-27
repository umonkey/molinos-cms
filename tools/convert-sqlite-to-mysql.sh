#!/bin/sh
if which gsed; then SED=gsed; else SED=sed; fi
echo "SET names utf8;" > "$2" && \
echo "SET character_set_client = utf8;" >> "$2" && \
echo .dump | sqlite3 "$1" | \
grep -v 'sqlite_sequence' | \
$SED -re 's/(CREATE TABLE|INSERT INTO) "([^"]+)"/\1 `\2`/g' | \
$SED -re 's/PRIMARY KEY AUTOINCREMENT/PRIMARY KEY AUTO_INCREMENT/g' | \
$SED -re 's/(int[^P]+) PRIMARY KEY[^,]*,/\1 PRIMARY KEY AUTO_INCREMENT,/g' | \
$SED -re 's/^CREATE TABLE `([^`]+)`/DROP TABLE IF EXISTS `\1`; CREATE TABLE `\1`/g' | \
$SED -re 's/`name_lc` TEXT/`name_lc` VARCHAR(255)/g' | \
grep -vE '^(BEGIN TRANSACTION|COMMIT);' >> "$2"
