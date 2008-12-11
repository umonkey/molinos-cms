#!/bin/sh
NAME=$(basename $0 .sh)

if [ -z "$5" ]; then
  echo "Usage: $NAME sqlite_db mysql_server mysql_user mysql_password mysql_database"
  exit 1
fi

if [ ! -f "$1" ]; then
  echo "File $1 does not exist."
  exit 1
fi

if [ -z $(which sqlite3) ]; then
  echo "sqlite3 binary not found."
  exit 1
fi

echo "Preparing a MySQL script."
echo .dump | sqlite3 $1 | \
sed -Ee 's/(CREATE TABLE|INSERT INTO) "([^"]+)"/\1 `\2`/g' | \
sed -Ee 's/PRIMARY KEY AUTOINCREMENT/PRIMARY KEY AUTO_INCREMENT/g' | \
sed -Ee 's/^(DELETE FROM|INSERT INTO) .*sqlite_sequence.*$//g' | \
sed -Ee 's/^CREATE TABLE `([^`]+)`/DROP TABLE IF EXISTS `\1`; CREATE TABLE `\1`/g' | \
sed -Ee 's/`name_lc` TEXT/`name_lc` VARCHAR(255)/g' | \
grep -vE '^(BEGIN TRANSACTION|COMMIT);' > $NAME.sql

echo "Importing into MySQL."
mysql --host="$2" --user="$3" --password="$4" "$5" < $NAME.sql > /dev/null 2> $NAME.log

if [ -s $NAME.log ]; then
  echo "Something went wrong, SQL dump saved as: $NAME.sql"
  cat $NAME.log && rm -f $NAME.log
else
  rm -f $NAME.sql $NAME.log
  echo "Import OK."

  for config in $(ls $(dirname $1)/*.config.php $(dirname $1)/*.ini 2>/dev/null); do
    echo "Updating $config"
    cat $config | sed -Ee "s#sqlite:$1#mysql://$3:$4@$2/$5#g" > $NAME.config && \
    cat $NAME.config > $config && \
    rm -f $NAME.config
  done

  echo "Done."
fi
