#!/bin/bash

# Updates postfix blacklist.
# Need to be run as root

DATABASE_URL="Download link of the database"
DESTINATION="/etc/postfix/blacklist"

#Get sum of previous database
OLD_SUM=$(/usr/bin/md5sum $DESTINATION)
# Temporary file
TMP_FILE=$(/usr/bin/mktemp)

cleanup() {
    rm "$TMP_FILE"
}

#Backup previous database
cp "$DESTINATION" "$DESTINATION".backup

wget -O "$TMP_FILE" "$DATABASE_URL"
if [ $? -ne 0 ]; then
    echo "Database update failed."
    cleanup()
    exit 1
else
    NEW_SUM=$(/usr/bin/md5sum $TMP_FILE)
    if [ "$NEW_SUM" == "$OLD_SUM" ]; then
        echo "No update required"
        exit 0
    fi
    cp "$TMP_FILE" "$DESTINATION"
    /usr/sbin/postmap "$DESTINATION"
    /usr/bin/systemctl reload postfix
    if [ $? -ne 0 ]; then
        echo "Postfix reload failed. Restoring backup"
        cp "$DESTINATION".backup "$DESTINATION"
        /usr/sbin/postmap "$DESTINATION"
        /usr/bin/systemctl reload postfix
        echo "Backup restored"
        cleanup()
        exit 1
    fi
    echo "Database Updated"
    cleanup()
fi
