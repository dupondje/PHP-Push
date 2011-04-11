<?php
/***********************************************
* File      :   proto.php
* Project   :   Z-Push
* Descr     :   WBXML entities that can be parsed
*               directly (as a stream) from WBXML.
*               They are automatically decoded
*               according to $mapping,
*               and the Sync WBXML mappings.
*
* Created   :   01.10.2007
*
* Copyright 2007 - 2010 Zarafa Deutschland GmbH
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License, version 3,
* as published by the Free Software Foundation with the following additional
* term according to sec. 7:
*
* According to sec. 7 of the GNU Affero General Public License, version 3,
* the terms of the AGPL are supplemented with the following terms:
*
* "Zarafa" is a registered trademark of Zarafa B.V.
* "Z-Push" is a registered trademark of Zarafa Deutschland GmbH
* The licensing of the Program under the AGPL does not imply a trademark license.
* Therefore any rights, title and interest in our trademarks remain entirely with us.
*
* However, if you propagate an unmodified version of the Program you are
* allowed to use the term "Z-Push" to indicate that you distribute the Program.
* Furthermore you may use our trademarks where it is necessary to indicate
* the intended purpose of a product or service provided you use it in accordance
* with honest practices in industrial or commercial matters.
* If you want to propagate modified versions of the Program under the name "Z-Push",
* you may only do so if you have a written permission by Zarafa Deutschland GmbH
* (to acquire a permission please contact Zarafa at trademark@zarafa.com).
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* Consult LICENSE file for details
************************************************/

include_once("streamer.php");

class SyncFolder extends Streamer {
    var $serverid;
    var $parentid;
    var $displayname;
    var $type;

    function SyncFolder() {
        $mapping = array (
                                SYNC_FOLDERHIERARCHY_SERVERENTRYID => array (STREAMER_VAR => "serverid"),
                                SYNC_FOLDERHIERARCHY_PARENTID => array (STREAMER_VAR => "parentid"),
                                SYNC_FOLDERHIERARCHY_DISPLAYNAME => array (STREAMER_VAR => "displayname"),
                                SYNC_FOLDERHIERARCHY_TYPE => array (STREAMER_VAR => "type")
                              );

        parent::Streamer($mapping);
    }
};

class SyncAttachment extends Streamer {
    var $attmethod;
    var $attsize;
    var $displayname;
    var $attname;
    var $attoid;
    var $attremoved;

    function SyncAttachment() {
        $mapping = array(
                                SYNC_POOMMAIL_ATTMETHOD => array (STREAMER_VAR => "attmethod"),
                                SYNC_POOMMAIL_ATTSIZE => array (STREAMER_VAR => "attsize"),
                                SYNC_POOMMAIL_DISPLAYNAME => array (STREAMER_VAR => "displayname"),
                                SYNC_POOMMAIL_ATTNAME => array (STREAMER_VAR => "attname"),
                                SYNC_POOMMAIL_ATTOID => array (STREAMER_VAR => "attoid"),
                                SYNC_POOMMAIL_ATTREMOVED => array (STREAMER_VAR => "attremoved"),
                        );

        parent::Streamer($mapping);
    }
};

class SyncMeetingRequest extends Streamer {
    function SyncMeetingRequest() {
        $mapping = array (
                                SYNC_POOMMAIL_ALLDAYEVENT => array (STREAMER_VAR => "alldayevent"),
                                SYNC_POOMMAIL_STARTTIME => array (STREAMER_VAR => "starttime", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                                SYNC_POOMMAIL_DTSTAMP => array (STREAMER_VAR => "dtstamp", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                                SYNC_POOMMAIL_ENDTIME => array (STREAMER_VAR => "endtime", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                                SYNC_POOMMAIL_INSTANCETYPE => array (STREAMER_VAR => "instancetype"),
                                SYNC_POOMMAIL_LOCATION => array (STREAMER_VAR => "location"),
                                SYNC_POOMMAIL_ORGANIZER => array (STREAMER_VAR => "organizer"),
                                SYNC_POOMMAIL_RECURRENCEID => array (STREAMER_VAR => "recurrenceid", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                                SYNC_POOMMAIL_REMINDER => array (STREAMER_VAR => "reminder"),
                                SYNC_POOMMAIL_RESPONSEREQUESTED => array (STREAMER_VAR => "responserequested"),
                                SYNC_POOMMAIL_RECURRENCES => array (STREAMER_VAR => "recurrences", STREAMER_TYPE => "SyncMeetingRequestRecurrence", STREAMER_ARRAY => SYNC_POOMMAIL_RECURRENCE),
                                SYNC_POOMMAIL_SENSITIVITY => array (STREAMER_VAR => "sensitivity"),
                                SYNC_POOMMAIL_BUSYSTATUS => array (STREAMER_VAR => "busystatus"),
                                SYNC_POOMMAIL_TIMEZONE => array (STREAMER_VAR => "timezone"),
                                SYNC_POOMMAIL_GLOBALOBJID => array (STREAMER_VAR => "globalobjid"),
                              );

        parent::Streamer($mapping);
    }

};

class SyncMail extends Streamer {
    var $body;
    var $bodysize;
    var $bodytruncated;
    var $datereceived;
    var $displayto;
    var $importance;
    var $messageclass;
    var $subject;
    var $read;
    var $to;
    var $cc;
    var $from;
    var $reply_to;

    var $attachments = array();

    function SyncMail() {
        global $protocolversion;

        $mapping = array (
                                SYNC_POOMMAIL_TO => array (STREAMER_VAR => "to"),
                                SYNC_POOMMAIL_CC => array (STREAMER_VAR => "cc"),
                                SYNC_POOMMAIL_FROM => array (STREAMER_VAR => "from"),
                                SYNC_POOMMAIL_SUBJECT => array (STREAMER_VAR => "subject"),
                                SYNC_POOMMAIL_THREADTOPIC => array (STREAMER_VAR => "threadtopic"),
                                SYNC_POOMMAIL_DATERECEIVED => array (STREAMER_VAR => "datereceived", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES ),
                                SYNC_POOMMAIL_DISPLAYTO =>  array (STREAMER_VAR => "displayto"),
                                SYNC_POOMMAIL_IMPORTANCE => array (STREAMER_VAR => "importance"),
                                SYNC_POOMMAIL_READ => array (STREAMER_VAR => "read"),
                                SYNC_POOMMAIL_ATTACHMENTS => array (STREAMER_VAR => "attachments", STREAMER_TYPE => "SyncAttachment", STREAMER_ARRAY => SYNC_POOMMAIL_ATTACHMENT ),
                                SYNC_POOMMAIL_MIMETRUNCATED => array ( STREAMER_VAR => "mimetruncated" ),//
                                SYNC_POOMMAIL_MIMEDATA => array ( STREAMER_VAR => "mimedata", STREAMER_TYPE => STREAMER_TYPE_MAPI_STREAM),//
                                SYNC_POOMMAIL_MIMESIZE => array ( STREAMER_VAR => "mimesize" ),//
                                SYNC_POOMMAIL_BODYTRUNCATED => array (STREAMER_VAR => "bodytruncated"),
                                SYNC_POOMMAIL_BODYSIZE => array (STREAMER_VAR => "bodysize"),
                                SYNC_POOMMAIL_BODY => array (STREAMER_VAR => "body"),
                                SYNC_POOMMAIL_MESSAGECLASS => array (STREAMER_VAR => "messageclass"),
                                SYNC_POOMMAIL_MEETINGREQUEST => array (STREAMER_VAR => "meetingrequest", STREAMER_TYPE => "SyncMeetingRequest"),
                                SYNC_POOMMAIL_REPLY_TO => array (STREAMER_VAR => "reply_to"),
                              );

        if(isset($protocolversion) && $protocolversion >= 2.5) {
            $mapping += array(
                                SYNC_POOMMAIL_INTERNETCPID => array (STREAMER_VAR => "internetcpid"),
                              );
        }

        parent::Streamer($mapping);
    }
};

class SyncContact extends Streamer {
    var $anniversary;
    var $assistantname;
    var $assistnamephonenumber;
    var $birthday;
    var $body;
    var $bodysize;
    var $bodytruncated;
    var $business2phonenumber;
    var $businesscity;
    var $businesscountry;
    var $businesspostalcode;
    var $businessstate;
    var $businessstreet;
    var $businessfaxnumber;
    var $businessphonenumber;
    var $carphonenumber;
    var $categories;
    var $children;
    var $companyname;
    var $department;
    var $email1address;
    var $email2address;
    var $email3address;
    var $fileas;
    var $firstname;
    var $home2phonenumber;
    var $homecity;
    var $homecountry;
    var $homepostalcode;
    var $homestate;
    var $homestreet;
    var $homefaxnumber;
    var $homephonenumber;
    var $jobtitle;
    var $lastname;
    var $middlename;
    var $mobilephonenumber;
    var $officelocation;
    var $othercity;
    var $othercountry;
    var $otherpostalcode;
    var $otherstate;
    var $otherstreet;
    var $pagernumber;
    var $radiophonenumber;
    var $spouse;
    var $suffix;
    var $title;
    var $webpage;
    var $yomicompanyname;
    var $yomifirstname;
    var $yomilastname;
    var $rtf;
    var $picture;
    var $nickname;

    function SyncContact() {
        global $protocolversion;

        $mapping = array (
            SYNC_POOMCONTACTS_ANNIVERSARY => array (STREAMER_VAR => "anniversary", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES  ),
            SYNC_POOMCONTACTS_ASSISTANTNAME => array (STREAMER_VAR => "assistantname"),
            SYNC_POOMCONTACTS_ASSISTNAMEPHONENUMBER => array (STREAMER_VAR => "assistnamephonenumber"),
            SYNC_POOMCONTACTS_BIRTHDAY => array (STREAMER_VAR => "birthday", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES  ),
            SYNC_POOMCONTACTS_BODY => array (STREAMER_VAR => "body"),
            SYNC_POOMCONTACTS_BODYSIZE => array (STREAMER_VAR => "bodysize"),
            SYNC_POOMCONTACTS_BODYTRUNCATED => array (STREAMER_VAR => "bodytruncated"),
            SYNC_POOMCONTACTS_BUSINESS2PHONENUMBER => array (STREAMER_VAR => "business2phonenumber"),
            SYNC_POOMCONTACTS_BUSINESSCITY => array (STREAMER_VAR => "businesscity"),
            SYNC_POOMCONTACTS_BUSINESSCOUNTRY => array (STREAMER_VAR => "businesscountry"),
            SYNC_POOMCONTACTS_BUSINESSPOSTALCODE => array (STREAMER_VAR => "businesspostalcode"),
            SYNC_POOMCONTACTS_BUSINESSSTATE => array (STREAMER_VAR => "businessstate"),
            SYNC_POOMCONTACTS_BUSINESSSTREET => array (STREAMER_VAR => "businessstreet"),
            SYNC_POOMCONTACTS_BUSINESSFAXNUMBER => array (STREAMER_VAR => "businessfaxnumber"),
            SYNC_POOMCONTACTS_BUSINESSPHONENUMBER => array (STREAMER_VAR => "businessphonenumber"),
            SYNC_POOMCONTACTS_CARPHONENUMBER => array (STREAMER_VAR => "carphonenumber"),
            SYNC_POOMCONTACTS_CHILDREN => array (STREAMER_VAR => "children", STREAMER_ARRAY => SYNC_POOMCONTACTS_CHILD ),
            SYNC_POOMCONTACTS_COMPANYNAME => array (STREAMER_VAR => "companyname"),
            SYNC_POOMCONTACTS_DEPARTMENT => array (STREAMER_VAR => "department"),
            SYNC_POOMCONTACTS_EMAIL1ADDRESS => array (STREAMER_VAR => "email1address"),
            SYNC_POOMCONTACTS_EMAIL2ADDRESS => array (STREAMER_VAR => "email2address"),
            SYNC_POOMCONTACTS_EMAIL3ADDRESS => array (STREAMER_VAR => "email3address"),
            SYNC_POOMCONTACTS_FILEAS => array (STREAMER_VAR => "fileas"),
            SYNC_POOMCONTACTS_FIRSTNAME => array (STREAMER_VAR => "firstname"),
            SYNC_POOMCONTACTS_HOME2PHONENUMBER => array (STREAMER_VAR => "home2phonenumber"),
            SYNC_POOMCONTACTS_HOMECITY => array (STREAMER_VAR => "homecity"),
            SYNC_POOMCONTACTS_HOMECOUNTRY => array (STREAMER_VAR => "homecountry"),
            SYNC_POOMCONTACTS_HOMEPOSTALCODE => array (STREAMER_VAR => "homepostalcode"),
            SYNC_POOMCONTACTS_HOMESTATE => array (STREAMER_VAR => "homestate"),
            SYNC_POOMCONTACTS_HOMESTREET => array (STREAMER_VAR => "homestreet"),
            SYNC_POOMCONTACTS_HOMEFAXNUMBER => array (STREAMER_VAR => "homefaxnumber"),
            SYNC_POOMCONTACTS_HOMEPHONENUMBER => array (STREAMER_VAR => "homephonenumber"),
            SYNC_POOMCONTACTS_JOBTITLE => array (STREAMER_VAR => "jobtitle"),
            SYNC_POOMCONTACTS_LASTNAME => array (STREAMER_VAR => "lastname"),
            SYNC_POOMCONTACTS_MIDDLENAME => array (STREAMER_VAR => "middlename"),
            SYNC_POOMCONTACTS_MOBILEPHONENUMBER => array (STREAMER_VAR => "mobilephonenumber"),
            SYNC_POOMCONTACTS_OFFICELOCATION => array (STREAMER_VAR => "officelocation"),
            SYNC_POOMCONTACTS_OTHERCITY => array (STREAMER_VAR => "othercity"),
            SYNC_POOMCONTACTS_OTHERCOUNTRY => array (STREAMER_VAR => "othercountry"),
            SYNC_POOMCONTACTS_OTHERPOSTALCODE => array (STREAMER_VAR => "otherpostalcode"),
            SYNC_POOMCONTACTS_OTHERSTATE => array (STREAMER_VAR => "otherstate"),
            SYNC_POOMCONTACTS_OTHERSTREET => array (STREAMER_VAR => "otherstreet"),
            SYNC_POOMCONTACTS_PAGERNUMBER => array (STREAMER_VAR => "pagernumber"),
            SYNC_POOMCONTACTS_RADIOPHONENUMBER => array (STREAMER_VAR => "radiophonenumber"),
            SYNC_POOMCONTACTS_SPOUSE => array (STREAMER_VAR => "spouse"),
            SYNC_POOMCONTACTS_SUFFIX => array (STREAMER_VAR => "suffix"),
            SYNC_POOMCONTACTS_TITLE => array (STREAMER_VAR => "title"),
            SYNC_POOMCONTACTS_WEBPAGE => array (STREAMER_VAR => "webpage"),
            SYNC_POOMCONTACTS_YOMICOMPANYNAME => array (STREAMER_VAR => "yomicompanyname"),
            SYNC_POOMCONTACTS_YOMIFIRSTNAME => array (STREAMER_VAR => "yomifirstname"),
            SYNC_POOMCONTACTS_YOMILASTNAME => array (STREAMER_VAR => "yomilastname"),
            SYNC_POOMCONTACTS_RTF => array (STREAMER_VAR => "rtf"),
            SYNC_POOMCONTACTS_PICTURE => array (STREAMER_VAR => "picture"),
            SYNC_POOMCONTACTS_CATEGORIES => array (STREAMER_VAR => "categories", STREAMER_ARRAY => SYNC_POOMCONTACTS_CATEGORY ),
    );

        if(isset($protocolversion) && $protocolversion >= 2.5) {
            $mapping += array(
                SYNC_POOMCONTACTS2_CUSTOMERID => array (STREAMER_VAR => "customerid"),
                SYNC_POOMCONTACTS2_GOVERNMENTID => array (STREAMER_VAR => "governmentid"),
                SYNC_POOMCONTACTS2_IMADDRESS => array (STREAMER_VAR => "imaddress"),
                SYNC_POOMCONTACTS2_IMADDRESS2 => array (STREAMER_VAR => "imaddress2"),
                SYNC_POOMCONTACTS2_IMADDRESS3 => array (STREAMER_VAR => "imaddress3"),
                SYNC_POOMCONTACTS2_MANAGERNAME => array (STREAMER_VAR => "managername"),
                SYNC_POOMCONTACTS2_COMPANYMAINPHONE => array (STREAMER_VAR => "companymainphone"),
                SYNC_POOMCONTACTS2_ACCOUNTNAME => array (STREAMER_VAR => "accountname"),
                SYNC_POOMCONTACTS2_NICKNAME => array (STREAMER_VAR => "nickname"),
                SYNC_POOMCONTACTS2_MMS => array (STREAMER_VAR => "mms"),
            );
        }

        parent::Streamer($mapping);
    }
}

class SyncAttendee extends Streamer {
    function SyncAttendee() {
        $mapping = array(
                      SYNC_POOMCAL_EMAIL => array (STREAMER_VAR => "email"),
                      SYNC_POOMCAL_NAME => array (STREAMER_VAR => "name" )
                  );

        parent::Streamer($mapping);
    }
}

class SyncAppointment extends Streamer {
    function SyncAppointment() {
        $mapping = array(
                      SYNC_POOMCAL_TIMEZONE => array (STREAMER_VAR => "timezone"),
                      SYNC_POOMCAL_DTSTAMP => array (STREAMER_VAR => "dtstamp", STREAMER_TYPE => STREAMER_TYPE_DATE),
                      SYNC_POOMCAL_STARTTIME => array (STREAMER_VAR => "starttime", STREAMER_TYPE => STREAMER_TYPE_DATE),
                      SYNC_POOMCAL_SUBJECT => array (STREAMER_VAR => "subject"),
                      SYNC_POOMCAL_UID => array (STREAMER_VAR => "uid"),
                      SYNC_POOMCAL_ORGANIZERNAME => array (STREAMER_VAR => "organizername"),
                      SYNC_POOMCAL_ORGANIZEREMAIL => array (STREAMER_VAR => "organizeremail"),
                      SYNC_POOMCAL_LOCATION => array (STREAMER_VAR => "location"),
                      SYNC_POOMCAL_ENDTIME => array (STREAMER_VAR => "endtime", STREAMER_TYPE => STREAMER_TYPE_DATE),
                      SYNC_POOMCAL_RECURRENCE => array (STREAMER_VAR => "recurrence", STREAMER_TYPE => "SyncRecurrence"),
                      SYNC_POOMCAL_SENSITIVITY => array (STREAMER_VAR => "sensitivity"),
                      SYNC_POOMCAL_BUSYSTATUS => array (STREAMER_VAR => "busystatus"),
                      SYNC_POOMCAL_ALLDAYEVENT => array (STREAMER_VAR => "alldayevent"),
                      SYNC_POOMCAL_REMINDER => array (STREAMER_VAR => "reminder"),
                      SYNC_POOMCAL_RTF => array (STREAMER_VAR => "rtf"),
                      SYNC_POOMCAL_MEETINGSTATUS => array (STREAMER_VAR => "meetingstatus"),
                      SYNC_POOMCAL_ATTENDEES => array (STREAMER_VAR => "attendees", STREAMER_TYPE => "SyncAttendee", STREAMER_ARRAY => SYNC_POOMCAL_ATTENDEE),
                      SYNC_POOMCAL_BODY => array (STREAMER_VAR => "body"),
                      SYNC_POOMCAL_BODYTRUNCATED => array (STREAMER_VAR => "bodytruncated"),
                      SYNC_POOMCAL_EXCEPTIONS => array (STREAMER_VAR => "exceptions", STREAMER_TYPE => "SyncAppointment", STREAMER_ARRAY => SYNC_POOMCAL_EXCEPTION),
                      SYNC_POOMCAL_DELETED => array (STREAMER_VAR => "deleted"),
                      SYNC_POOMCAL_EXCEPTIONSTARTTIME => array (STREAMER_VAR => "exceptionstarttime", STREAMER_TYPE => STREAMER_TYPE_DATE),
                      SYNC_POOMCAL_CATEGORIES => array (STREAMER_VAR => "categories", STREAMER_ARRAY => SYNC_POOMCAL_CATEGORY),

        );

        parent::Streamer($mapping);
    }
}

class SyncRecurrence extends Streamer {
    var $type;
    var $until;
    var $occurrences;
    var $interval;
    var $dayofweek;
    var $dayofmonth;
    var $weekofmonth;
    var $monthofyear;

    function SyncRecurrence() {
        $mapping = array (
                      SYNC_POOMCAL_TYPE => array (STREAMER_VAR => "type"),
                      SYNC_POOMCAL_UNTIL => array (STREAMER_VAR => "until", STREAMER_TYPE => STREAMER_TYPE_DATE),
                      SYNC_POOMCAL_OCCURRENCES => array (STREAMER_VAR => "occurrences"),
                      SYNC_POOMCAL_INTERVAL => array (STREAMER_VAR => "interval"),
                      SYNC_POOMCAL_DAYOFWEEK => array (STREAMER_VAR => "dayofweek"),
                      SYNC_POOMCAL_DAYOFMONTH => array (STREAMER_VAR => "dayofmonth"),
                      SYNC_POOMCAL_WEEKOFMONTH => array (STREAMER_VAR => "weekofmonth"),
                      SYNC_POOMCAL_MONTHOFYEAR => array (STREAMER_VAR => "monthofyear")
        );

        parent::Streamer($mapping);
    }
}

// Exactly the same as SyncRecurrence, but then with SYNC_POOMMAIL_*
class SyncMeetingRequestRecurrence extends Streamer {
    var $type;
    var $until;
    var $occurrences;
    var $interval;
    var $dayofweek;
    var $dayofmonth;
    var $weekofmonth;
    var $monthofyear;

    function SyncMeetingRequestRecurrence() {
        $mapping = array (
                      SYNC_POOMMAIL_TYPE => array (STREAMER_VAR => "type"),
                      SYNC_POOMMAIL_UNTIL => array (STREAMER_VAR => "until", STREAMER_TYPE => STREAMER_TYPE_DATE),
                      SYNC_POOMMAIL_OCCURRENCES => array (STREAMER_VAR => "occurrences"),
                      SYNC_POOMMAIL_INTERVAL => array (STREAMER_VAR => "interval"),
                      SYNC_POOMMAIL_DAYOFWEEK => array (STREAMER_VAR => "dayofweek"),
                      SYNC_POOMMAIL_DAYOFMONTH => array (STREAMER_VAR => "dayofmonth"),
                      SYNC_POOMMAIL_WEEKOFMONTH => array (STREAMER_VAR => "weekofmonth"),
                      SYNC_POOMMAIL_MONTHOFYEAR => array (STREAMER_VAR => "monthofyear")
        );

        parent::Streamer($mapping);
    }
}

// Exactly the same as SyncRecurrence, but then with SYNC_POOMTASKS_*
class SyncTaskRecurrence extends Streamer {
    function SyncTaskRecurrence() {
        $mapping = array (
                      SYNC_POOMTASKS_TYPE => array (STREAMER_VAR => "type"),
                      SYNC_POOMTASKS_UNTIL => array (STREAMER_VAR => "until", STREAMER_TYPE => STREAMER_TYPE_DATE),
                      SYNC_POOMTASKS_OCCURRENCES => array (STREAMER_VAR => "occurrences"),
                      SYNC_POOMTASKS_INTERVAL => array (STREAMER_VAR => "interval"),
                      SYNC_POOMTASKS_DAYOFWEEK => array (STREAMER_VAR => "dayofweek"),
                      SYNC_POOMTASKS_DAYOFMONTH => array (STREAMER_VAR => "dayofmonth"),
                      SYNC_POOMTASKS_WEEKOFMONTH => array (STREAMER_VAR => "weekofmonth"),
                      SYNC_POOMTASKS_MONTHOFYEAR => array (STREAMER_VAR => "monthofyear"),
        );

        parent::Streamer($mapping);
    }
}

class SyncTask extends Streamer {
    var $body;
    var $categories = array();
    var $complete;
    var $datecompleted;
    var $duedate;
    var $utcduedate;
    var $importance;
    var $recurrence;
    var $regenerate;
    var $deadoccur;
    var $reminderset;
    var $remindertime;
    var $sensitivity;
    var $startdate;
    var $utcstartdate;
    var $subject;
    var $rtf;

    function SyncTask() {
        $mapping = array (
                      SYNC_POOMTASKS_BODY => array (STREAMER_VAR => "body"),
                      SYNC_POOMTASKS_COMPLETE => array (STREAMER_VAR => "complete"),
                      SYNC_POOMTASKS_DATECOMPLETED => array (STREAMER_VAR => "datecompleted", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                      SYNC_POOMTASKS_DUEDATE => array (STREAMER_VAR => "duedate", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                      SYNC_POOMTASKS_UTCDUEDATE => array (STREAMER_VAR => "utcduedate", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                      SYNC_POOMTASKS_IMPORTANCE => array (STREAMER_VAR => "importance"),
                      SYNC_POOMTASKS_RECURRENCE => array (STREAMER_VAR => "recurrence", STREAMER_TYPE => "SyncTaskRecurrence"),
                      SYNC_POOMTASKS_REGENERATE => array (STREAMER_VAR => "regenerate"),
                      SYNC_POOMTASKS_DEADOCCUR => array (STREAMER_VAR => "deadoccur"),
                      SYNC_POOMTASKS_REMINDERSET => array (STREAMER_VAR => "reminderset"),
                      SYNC_POOMTASKS_REMINDERTIME => array (STREAMER_VAR => "remindertime", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                      SYNC_POOMTASKS_SENSITIVITY => array (STREAMER_VAR => "sensitivity"),
                      SYNC_POOMTASKS_STARTDATE => array (STREAMER_VAR => "startdate", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                      SYNC_POOMTASKS_UTCSTARTDATE => array (STREAMER_VAR => "utcstartdate", STREAMER_TYPE => STREAMER_TYPE_DATE_DASHES),
                      SYNC_POOMTASKS_SUBJECT => array (STREAMER_VAR => "subject"),
                      SYNC_POOMTASKS_RTF => array (STREAMER_VAR => "rtf"),
                      SYNC_POOMTASKS_CATEGORIES => array (STREAMER_VAR => "categories", STREAMER_ARRAY => SYNC_POOMTASKS_CATEGORY),
        );

        parent::Streamer($mapping);
    }
}

?>