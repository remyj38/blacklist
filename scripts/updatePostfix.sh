#!/bin/bash

# Updates postfix blacklist.
# Need to be run as root

DATABASE_URL="Download link of the database"
DESTINATION="/etc/postfix/blacklist"

#Get sum of previous database
if [ -f $DESTINATION ]; then
    OLD_SUM=($(md5sum $DESTINATION))
else
    OLD_SUM=""
fi
# Temporary file
TMP_FILE=$(mktemp)

cleanup() {
    rm "$TMP_FILE"
}

#Backup previous database
if [ -f $DESTINATION ]; then
    cp "$DESTINATION" "$DESTINATION".backup
fi

wget -qO "$TMP_FILE" "$DATABASE_URL" > /dev/null
if [ $? -ne 0 ]; then
    echo "Unable to download database."
    cleanup
    exit 1
else
    NEW_SUM=($(md5sum $TMP_FILE))
    if [ "$NEW_SUM" == "$OLD_SUM" ]; then
        echo "No update required"
        exit 0
    fi
    cp "$TMP_FILE" "$DESTINATION"
    postmap "$DESTINATION"
    systemctl reload postfix
    if [ $? -ne 0 ]; then
        echo "Postfix reload failed. Restoring backup"
        if [ -f "$DESTINATION".backup ]; then
            cp "$DESTINATION".backup "$DESTINATION"
            systemctl reload postfix
            postmap "$DESTINATION"
            echo "Backup restored"
        else
            echo "No backup to restore. Removing database"
            rm "$DESTINATION"
        fi
        cleanup
        exit 1
    fi
    echo "Database Updated"
    cleanup
fi
