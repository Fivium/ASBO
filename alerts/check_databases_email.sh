#/bin/sh
#
# $Id: //Infrastructure/GitHub/Database/asbo/alerts/check_databases_email.sh#6 $
#i

export MUTT_PORT=port_number
export MUTT_FROM=sender
export MUTT_SERVER=smtp_server


DIR='/app/alerts/'
REPORT_FILENAME="${DIR}/db_checks.html"
EMAIL_SUBJECT='Databases Alerts'
EMAIL_TAIL='+alerts_my_databases'
EMAIL_DOMAIN='mydomain.co.uk'
EMAIL_TO="email_to${EMAIL_TAIL}@${EMAIL_DOMAIN}"
CC1="email_cc1${EMAIL_TAIL}@${EMAIL_DOMAIN}"
CC2="email_cc2${EMAIL_TAIL}@${EMAIL_DOMAIN}"
CC3="email_cc3${EMAIL_TAIL}@${EMAIL_DOMAIN}"
FORCE_SEND_EMAIL=$1

SEND_EMAIL=''
#
# Get database status report
#
curl "http://localhost/show_page.php?page=db_checks_all_dbs.php&html=1&suppress_ok=1" > $REPORT_FILENAME
#
# Any crticals?
#
CRITICAL_COUNT=`grep CRITICAL $REPORT_FILENAME | wc -l`

#CRITICAL_COUNT=$1;

CRITICAL_INDICATOR_FILE_STUB="${DIR}/critical_running.ind"
CRITICAL_INDICATOR_FILES="${CRITICAL_INDICATOR_FILE_STUB}*"
CRITICAL_INDICATOR_FILE="${CRITICAL_INDICATOR_FILE_STUB}.crit_count__${CRITICAL_COUNT}"

echo "Critical count : $CRITICAL_COUNT"

if [ "$CRITICAL_COUNT" -ge "1" ]
then
    #
    # New critical?
    # or need to send a reminder
    #
    REMINDER_STR=""
    if [ -f "$CRITICAL_INDICATOR_FILE" ] && [ `stat --format=%Y $CRITICAL_INDICATOR_FILE` -le $(( `date +%s` - 3600 )) ]
    then
      REMINDER_STR=" - REMINDER"
    fi

    if [ ! -f "$CRITICAL_INDICATOR_FILE" ] || [ ! -z "$REMINDER_STR" ]
    then
        touch $CRITICAL_INDICATOR_FILE
        SEND_EMAIL='YES'
        EMAIL_SUBJECT="$EMAIL_SUBJECT CRITICAL Count : $CRITICAL_COUNT $REMINDER_STR"
    else
        #
        # Currently critical
        #
        echo "Current CRITICAL running"
    fi
else
    #
    # Crit running?
    #
    files=($CRITICAL_INDICATOR_FILES)

    if [ -e ${files[0]} ]
    then
        echo "Removing critcal indicator file"
        rm $CRITICAL_INDICATOR_FILES
        SEND_EMAIL='YES'
        EMAIL_SUBJECT="$EMAIL_SUBJECT OK"
    fi

fi

if [ ! -z "$SEND_EMAIL" ] || [ ! -z "$FORCE_SEND_EMAIL" ]
then
    echo "Send email - Subject : $EMAIL_SUBJECT - Report file : $REPORT_FILENAME"
    mutt $EMAIL_TO -e "set content_type=text/html" -s "$EMAIL_SUBJECT" -c $CC1 -c $CC2 -c $CC3 < $REPORT_FILENAME
    echo "Email Sent"
fi
