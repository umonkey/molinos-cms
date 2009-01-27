#!/bin/sh
NAME=$(basename $0 .sh)
PHP=$(dirname $0)/$NAME.php

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
if [ -z $(which php) ]; then
  echo "PHP binary not found."
  exit 1
fi
if [ ! -f $PHP ]; then
  echo "$PHP not found."
  exit 1
fi

echo "Preparing the SQLite database."
php -f $PHP sqlite:$1

echo "Preparing the MySQL script."
sh $(dirname $0)/convert-sqlite-to-mysql.sh "$1" "$NAME.sql"

echo "Importing into MySQL."
mysql --host="$2" --user="$3" --password="$4" "$5" < $NAME.sql > /dev/null 2> $NAME.log

if [ -s $NAME.log ]; then
  echo "Something went wrong, SQL dump saved as: $NAME.sql"
  cat $NAME.log && rm -f $NAME.log
else
  rm -f $NAME.sql $NAME.log

  echo "Post-processing the MySQL database."
  php -f $PHP mysql://$3:$4@$2/$5

  for config in $(ls $(dirname $1)/*.config.php $(dirname $1)/*.ini 2>/dev/null); do
    echo "Updating $config"
    cat $config | sed -Ee "s#sqlite:$1#mysql://$3:$4@$2/$5#g" > $NAME.config && \
    cat $NAME.config > $config && \
    rm -f $NAME.config
  done

  echo "Done."
fi
