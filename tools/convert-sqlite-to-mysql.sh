#!/bin/sh
if [ -z "$2" ]; then
  echo "Usage: $(basename $0) filename.db filename.sql"
  exit 1
fi
if which gsed; then SED=gsed; else SED=sed; fi
cp "$1" "$1.tmp" && \
php -f $(dirname $0)/sqlite-to-mysql.php "sqlite:$1.tmp" && \
echo "SET names utf8;" > "$2" && \
echo "SET character_set_client = utf8;" >> "$2" && \
echo .dump | sqlite3 "$1.tmp" | \
grep -v 'sqlite_sequence' | \
$SED -re 's/(CREATE TABLE|INSERT INTO) "([^"]+)"/\1 `\2`/g' | \
$SED -re 's/PRIMARY KEY AUTOINCREMENT/PRIMARY KEY AUTO_INCREMENT/g' | \
$SED -re 's/(int[^P]+) PRIMARY KEY[^,]*,/\1 PRIMARY KEY AUTO_INCREMENT,/g' | \
$SED -re 's/^CREATE TABLE `([^`]+)`/DROP TABLE IF EXISTS `\1`; CREATE TABLE `\1`/g' | \
$SED -re 's/`name_lc` TEXT/`name_lc` VARCHAR(255)/g' | \
grep -v 'INSERT INTO `node__session`' | \
grep -vE '^(BEGIN TRANSACTION|COMMIT);' >> "$2" && \
rm -f "$1.tmp"
