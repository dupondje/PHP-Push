<?php
/***********************************************
* File      :   zpushdefs.php
* Project   :   Z-Push
* Descr     :   Constants' definition file
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

define("SYNC_SYNCHRONIZE","Synchronize");
define("SYNC_REPLIES","Replies");
define("SYNC_ADD","Add");
define("SYNC_MODIFY","Modify");
define("SYNC_REMOVE","Remove");
define("SYNC_FETCH","Fetch");
define("SYNC_SYNCKEY","SyncKey");
define("SYNC_CLIENTENTRYID","ClientEntryId");
define("SYNC_SERVERENTRYID","ServerEntryId");
define("SYNC_STATUS","Status");
define("SYNC_FOLDER","Folder");
define("SYNC_FOLDERTYPE","FolderType");
define("SYNC_VERSION","Version");
define("SYNC_FOLDERID","FolderId");
define("SYNC_GETCHANGES","GetChanges");
define("SYNC_MOREAVAILABLE","MoreAvailable");
define("SYNC_MAXITEMS","MaxItems");
define("SYNC_PERFORM","Perform");
define("SYNC_OPTIONS","Options");
define("SYNC_FILTERTYPE","FilterType");
define("SYNC_TRUNCATION","Truncation");
define("SYNC_RTFTRUNCATION","RtfTruncation");
define("SYNC_CONFLICT","Conflict");
define("SYNC_FOLDERS","Folders");
define("SYNC_DATA","Data");
define("SYNC_DELETESASMOVES","DeletesAsMoves");
define("SYNC_NOTIFYGUID","NotifyGUID");
define("SYNC_SUPPORTED","Supported");
define("SYNC_SOFTDELETE","SoftDelete");
define("SYNC_MIMESUPPORT","MIMESupport");
define("SYNC_MIMETRUNCATION","MIMETruncation");
define("SYNC_NEWMESSAGE","NewMessage");

// POOMCONTACTS
define("SYNC_POOMCONTACTS_ANNIVERSARY","POOMCONTACTS:Anniversary");
define("SYNC_POOMCONTACTS_ASSISTANTNAME","POOMCONTACTS:AssistantName");
define("SYNC_POOMCONTACTS_ASSISTNAMEPHONENUMBER","POOMCONTACTS:AssistnamePhoneNumber");
define("SYNC_POOMCONTACTS_BIRTHDAY","POOMCONTACTS:Birthday");
define("SYNC_POOMCONTACTS_BODY","POOMCONTACTS:Body");
define("SYNC_POOMCONTACTS_BODYSIZE","POOMCONTACTS:BodySize");
define("SYNC_POOMCONTACTS_BODYTRUNCATED","POOMCONTACTS:BodyTruncated");
define("SYNC_POOMCONTACTS_BUSINESS2PHONENUMBER","POOMCONTACTS:Business2PhoneNumber");
define("SYNC_POOMCONTACTS_BUSINESSCITY","POOMCONTACTS:BusinessCity");
define("SYNC_POOMCONTACTS_BUSINESSCOUNTRY","POOMCONTACTS:BusinessCountry");
define("SYNC_POOMCONTACTS_BUSINESSPOSTALCODE","POOMCONTACTS:BusinessPostalCode");
define("SYNC_POOMCONTACTS_BUSINESSSTATE","POOMCONTACTS:BusinessState");
define("SYNC_POOMCONTACTS_BUSINESSSTREET","POOMCONTACTS:BusinessStreet");
define("SYNC_POOMCONTACTS_BUSINESSFAXNUMBER","POOMCONTACTS:BusinessFaxNumber");
define("SYNC_POOMCONTACTS_BUSINESSPHONENUMBER","POOMCONTACTS:BusinessPhoneNumber");
define("SYNC_POOMCONTACTS_CARPHONENUMBER","POOMCONTACTS:CarPhoneNumber");
define("SYNC_POOMCONTACTS_CATEGORIES","POOMCONTACTS:Categories");
define("SYNC_POOMCONTACTS_CATEGORY","POOMCONTACTS:Category");
define("SYNC_POOMCONTACTS_CHILDREN","POOMCONTACTS:Children");
define("SYNC_POOMCONTACTS_CHILD","POOMCONTACTS:Child");
define("SYNC_POOMCONTACTS_COMPANYNAME","POOMCONTACTS:CompanyName");
define("SYNC_POOMCONTACTS_DEPARTMENT","POOMCONTACTS:Department");
define("SYNC_POOMCONTACTS_EMAIL1ADDRESS","POOMCONTACTS:Email1Address");
define("SYNC_POOMCONTACTS_EMAIL2ADDRESS","POOMCONTACTS:Email2Address");
define("SYNC_POOMCONTACTS_EMAIL3ADDRESS","POOMCONTACTS:Email3Address");
define("SYNC_POOMCONTACTS_FILEAS","POOMCONTACTS:FileAs");
define("SYNC_POOMCONTACTS_FIRSTNAME","POOMCONTACTS:FirstName");
define("SYNC_POOMCONTACTS_HOME2PHONENUMBER","POOMCONTACTS:Home2PhoneNumber");
define("SYNC_POOMCONTACTS_HOMECITY","POOMCONTACTS:HomeCity");
define("SYNC_POOMCONTACTS_HOMECOUNTRY","POOMCONTACTS:HomeCountry");
define("SYNC_POOMCONTACTS_HOMEPOSTALCODE","POOMCONTACTS:HomePostalCode");
define("SYNC_POOMCONTACTS_HOMESTATE","POOMCONTACTS:HomeState");
define("SYNC_POOMCONTACTS_HOMESTREET","POOMCONTACTS:HomeStreet");
define("SYNC_POOMCONTACTS_HOMEFAXNUMBER","POOMCONTACTS:HomeFaxNumber");
define("SYNC_POOMCONTACTS_HOMEPHONENUMBER","POOMCONTACTS:HomePhoneNumber");
define("SYNC_POOMCONTACTS_JOBTITLE","POOMCONTACTS:JobTitle");
define("SYNC_POOMCONTACTS_LASTNAME","POOMCONTACTS:LastName");
define("SYNC_POOMCONTACTS_MIDDLENAME","POOMCONTACTS:MiddleName");
define("SYNC_POOMCONTACTS_MOBILEPHONENUMBER","POOMCONTACTS:MobilePhoneNumber");
define("SYNC_POOMCONTACTS_OFFICELOCATION","POOMCONTACTS:OfficeLocation");
define("SYNC_POOMCONTACTS_OTHERCITY","POOMCONTACTS:OtherCity");
define("SYNC_POOMCONTACTS_OTHERCOUNTRY","POOMCONTACTS:OtherCountry");
define("SYNC_POOMCONTACTS_OTHERPOSTALCODE","POOMCONTACTS:OtherPostalCode");
define("SYNC_POOMCONTACTS_OTHERSTATE","POOMCONTACTS:OtherState");
define("SYNC_POOMCONTACTS_OTHERSTREET","POOMCONTACTS:OtherStreet");
define("SYNC_POOMCONTACTS_PAGERNUMBER","POOMCONTACTS:PagerNumber");
define("SYNC_POOMCONTACTS_RADIOPHONENUMBER","POOMCONTACTS:RadioPhoneNumber");
define("SYNC_POOMCONTACTS_SPOUSE","POOMCONTACTS:Spouse");
define("SYNC_POOMCONTACTS_SUFFIX","POOMCONTACTS:Suffix");
define("SYNC_POOMCONTACTS_TITLE","POOMCONTACTS:Title");
define("SYNC_POOMCONTACTS_WEBPAGE","POOMCONTACTS:WebPage");
define("SYNC_POOMCONTACTS_YOMICOMPANYNAME","POOMCONTACTS:YomiCompanyName");
define("SYNC_POOMCONTACTS_YOMIFIRSTNAME","POOMCONTACTS:YomiFirstName");
define("SYNC_POOMCONTACTS_YOMILASTNAME","POOMCONTACTS:YomiLastName");
define("SYNC_POOMCONTACTS_RTF","POOMCONTACTS:Rtf");
define("SYNC_POOMCONTACTS_PICTURE","POOMCONTACTS:Picture");

// POOMMAIL
define("SYNC_POOMMAIL_ATTACHMENT","POOMMAIL:Attachment");
define("SYNC_POOMMAIL_ATTACHMENTS","POOMMAIL:Attachments");
define("SYNC_POOMMAIL_ATTNAME","POOMMAIL:AttName");
define("SYNC_POOMMAIL_ATTSIZE","POOMMAIL:AttSize");
define("SYNC_POOMMAIL_ATTOID","POOMMAIL:AttOid");
define("SYNC_POOMMAIL_ATTMETHOD","POOMMAIL:AttMethod");
define("SYNC_POOMMAIL_ATTREMOVED","POOMMAIL:AttRemoved");
define("SYNC_POOMMAIL_BODY","POOMMAIL:Body");
define("SYNC_POOMMAIL_BODYSIZE","POOMMAIL:BodySize");
define("SYNC_POOMMAIL_BODYTRUNCATED","POOMMAIL:BodyTruncated");
define("SYNC_POOMMAIL_DATERECEIVED","POOMMAIL:DateReceived");
define("SYNC_POOMMAIL_DISPLAYNAME","POOMMAIL:DisplayName");
define("SYNC_POOMMAIL_DISPLAYTO","POOMMAIL:DisplayTo");
define("SYNC_POOMMAIL_IMPORTANCE","POOMMAIL:Importance");
define("SYNC_POOMMAIL_MESSAGECLASS","POOMMAIL:MessageClass");
define("SYNC_POOMMAIL_SUBJECT","POOMMAIL:Subject");
define("SYNC_POOMMAIL_READ","POOMMAIL:Read");
define("SYNC_POOMMAIL_TO","POOMMAIL:To");
define("SYNC_POOMMAIL_CC","POOMMAIL:Cc");
define("SYNC_POOMMAIL_FROM","POOMMAIL:From");
define("SYNC_POOMMAIL_REPLY_TO","POOMMAIL:Reply-To");
define("SYNC_POOMMAIL_ALLDAYEVENT","POOMMAIL:AllDayEvent");
define("SYNC_POOMMAIL_CATEGORIES","POOMMAIL:Categories");
define("SYNC_POOMMAIL_CATEGORY","POOMMAIL:Category");
define("SYNC_POOMMAIL_DTSTAMP","POOMMAIL:DtStamp");
define("SYNC_POOMMAIL_ENDTIME","POOMMAIL:EndTime");
define("SYNC_POOMMAIL_INSTANCETYPE","POOMMAIL:InstanceType");
define("SYNC_POOMMAIL_BUSYSTATUS","POOMMAIL:BusyStatus");
define("SYNC_POOMMAIL_LOCATION","POOMMAIL:Location");
define("SYNC_POOMMAIL_MEETINGREQUEST","POOMMAIL:MeetingRequest");
define("SYNC_POOMMAIL_ORGANIZER","POOMMAIL:Organizer");
define("SYNC_POOMMAIL_RECURRENCEID","POOMMAIL:RecurrenceId");
define("SYNC_POOMMAIL_REMINDER","POOMMAIL:Reminder");
define("SYNC_POOMMAIL_RESPONSEREQUESTED","POOMMAIL:ResponseRequested");
define("SYNC_POOMMAIL_RECURRENCES","POOMMAIL:Recurrences");
define("SYNC_POOMMAIL_RECURRENCE","POOMMAIL:Recurrence");
define("SYNC_POOMMAIL_TYPE","POOMMAIL:Type");
define("SYNC_POOMMAIL_UNTIL","POOMMAIL:Until");
define("SYNC_POOMMAIL_OCCURRENCES","POOMMAIL:Occurrences");
define("SYNC_POOMMAIL_INTERVAL","POOMMAIL:Interval");
define("SYNC_POOMMAIL_DAYOFWEEK","POOMMAIL:DayOfWeek");
define("SYNC_POOMMAIL_DAYOFMONTH","POOMMAIL:DayOfMonth");
define("SYNC_POOMMAIL_WEEKOFMONTH","POOMMAIL:WeekOfMonth");
define("SYNC_POOMMAIL_MONTHOFYEAR","POOMMAIL:MonthOfYear");
define("SYNC_POOMMAIL_STARTTIME","POOMMAIL:StartTime");
define("SYNC_POOMMAIL_SENSITIVITY","POOMMAIL:Sensitivity");
define("SYNC_POOMMAIL_TIMEZONE","POOMMAIL:TimeZone");
define("SYNC_POOMMAIL_GLOBALOBJID","POOMMAIL:GlobalObjId");
define("SYNC_POOMMAIL_THREADTOPIC","POOMMAIL:ThreadTopic");
define("SYNC_POOMMAIL_MIMEDATA","POOMMAIL:MIMEData");
define("SYNC_POOMMAIL_MIMETRUNCATED","POOMMAIL:MIMETruncated");
define("SYNC_POOMMAIL_MIMESIZE","POOMMAIL:MIMESize");
define("SYNC_POOMMAIL_INTERNETCPID","POOMMAIL:InternetCPID");

// AIRNOTIFY
define("SYNC_AIRNOTIFY_NOTIFY","AirNotify:Notify");
define("SYNC_AIRNOTIFY_NOTIFICATION","AirNotify:Notification");
define("SYNC_AIRNOTIFY_VERSION","AirNotify:Version");
define("SYNC_AIRNOTIFY_LIFETIME","AirNotify:Lifetime");
define("SYNC_AIRNOTIFY_DEVICEINFO","AirNotify:DeviceInfo");
define("SYNC_AIRNOTIFY_ENABLE","AirNotify:Enable");
define("SYNC_AIRNOTIFY_FOLDER","AirNotify:Folder");
define("SYNC_AIRNOTIFY_SERVERENTRYID","AirNotify:ServerEntryId");
define("SYNC_AIRNOTIFY_DEVICEADDRESS","AirNotify:DeviceAddress");
define("SYNC_AIRNOTIFY_VALIDCARRIERPROFILES","AirNotify:ValidCarrierProfiles");
define("SYNC_AIRNOTIFY_CARRIERPROFILE","AirNotify:CarrierProfile");
define("SYNC_AIRNOTIFY_STATUS","AirNotify:Status");
define("SYNC_AIRNOTIFY_REPLIES","AirNotify:Replies");
define("SYNC_AIRNOTIFY_VERSION='1.1'","AirNotify:Version='1.1'");
define("SYNC_AIRNOTIFY_DEVICES","AirNotify:Devices");
define("SYNC_AIRNOTIFY_DEVICE","AirNotify:Device");
define("SYNC_AIRNOTIFY_ID","AirNotify:Id");
define("SYNC_AIRNOTIFY_EXPIRY","AirNotify:Expiry");
define("SYNC_AIRNOTIFY_NOTIFYGUID","AirNotify:NotifyGUID");

// POOMCAL
define("SYNC_POOMCAL_TIMEZONE","POOMCAL:Timezone");
define("SYNC_POOMCAL_ALLDAYEVENT","POOMCAL:AllDayEvent");
define("SYNC_POOMCAL_ATTENDEES","POOMCAL:Attendees");
define("SYNC_POOMCAL_ATTENDEE","POOMCAL:Attendee");
define("SYNC_POOMCAL_EMAIL","POOMCAL:Email");
define("SYNC_POOMCAL_NAME","POOMCAL:Name");
define("SYNC_POOMCAL_BODY","POOMCAL:Body");
define("SYNC_POOMCAL_BODYTRUNCATED","POOMCAL:BodyTruncated");
define("SYNC_POOMCAL_BUSYSTATUS","POOMCAL:BusyStatus");
define("SYNC_POOMCAL_CATEGORIES","POOMCAL:Categories");
define("SYNC_POOMCAL_CATEGORY","POOMCAL:Category");
define("SYNC_POOMCAL_RTF","POOMCAL:Rtf");
define("SYNC_POOMCAL_DTSTAMP","POOMCAL:DtStamp");
define("SYNC_POOMCAL_ENDTIME","POOMCAL:EndTime");
define("SYNC_POOMCAL_EXCEPTION","POOMCAL:Exception");
define("SYNC_POOMCAL_EXCEPTIONS","POOMCAL:Exceptions");
define("SYNC_POOMCAL_DELETED","POOMCAL:Deleted");
define("SYNC_POOMCAL_EXCEPTIONSTARTTIME","POOMCAL:ExceptionStartTime");
define("SYNC_POOMCAL_LOCATION","POOMCAL:Location");
define("SYNC_POOMCAL_MEETINGSTATUS","POOMCAL:MeetingStatus");
define("SYNC_POOMCAL_ORGANIZEREMAIL","POOMCAL:OrganizerEmail");
define("SYNC_POOMCAL_ORGANIZERNAME","POOMCAL:OrganizerName");
define("SYNC_POOMCAL_RECURRENCE","POOMCAL:Recurrence");
define("SYNC_POOMCAL_TYPE","POOMCAL:Type");
define("SYNC_POOMCAL_UNTIL","POOMCAL:Until");
define("SYNC_POOMCAL_OCCURRENCES","POOMCAL:Occurrences");
define("SYNC_POOMCAL_INTERVAL","POOMCAL:Interval");
define("SYNC_POOMCAL_DAYOFWEEK","POOMCAL:DayOfWeek");
define("SYNC_POOMCAL_DAYOFMONTH","POOMCAL:DayOfMonth");
define("SYNC_POOMCAL_WEEKOFMONTH","POOMCAL:WeekOfMonth");
define("SYNC_POOMCAL_MONTHOFYEAR","POOMCAL:MonthOfYear");
define("SYNC_POOMCAL_REMINDER","POOMCAL:Reminder");
define("SYNC_POOMCAL_SENSITIVITY","POOMCAL:Sensitivity");
define("SYNC_POOMCAL_SUBJECT","POOMCAL:Subject");
define("SYNC_POOMCAL_STARTTIME","POOMCAL:StartTime");
define("SYNC_POOMCAL_UID","POOMCAL:UID");

// Move
define("SYNC_MOVE_MOVES","Move:Moves");
define("SYNC_MOVE_MOVE","Move:Move");
define("SYNC_MOVE_SRCMSGID","Move:SrcMsgId");
define("SYNC_MOVE_SRCFLDID","Move:SrcFldId");
define("SYNC_MOVE_DSTFLDID","Move:DstFldId");
define("SYNC_MOVE_RESPONSE","Move:Response");
define("SYNC_MOVE_STATUS","Move:Status");
define("SYNC_MOVE_DSTMSGID","Move:DstMsgId");

// GetItemEstimate
define("SYNC_GETITEMESTIMATE_GETITEMESTIMATE","GetItemEstimate:GetItemEstimate");
define("SYNC_GETITEMESTIMATE_VERSION","GetItemEstimate:Version");
define("SYNC_GETITEMESTIMATE_FOLDERS","GetItemEstimate:Folders");
define("SYNC_GETITEMESTIMATE_FOLDER","GetItemEstimate:Folder");
define("SYNC_GETITEMESTIMATE_FOLDERTYPE","GetItemEstimate:FolderType");
define("SYNC_GETITEMESTIMATE_FOLDERID","GetItemEstimate:FolderId");
define("SYNC_GETITEMESTIMATE_DATETIME","GetItemEstimate:DateTime");
define("SYNC_GETITEMESTIMATE_ESTIMATE","GetItemEstimate:Estimate");
define("SYNC_GETITEMESTIMATE_RESPONSE","GetItemEstimate:Response");
define("SYNC_GETITEMESTIMATE_STATUS","GetItemEstimate:Status");

// FolderHierarchy
define("SYNC_FOLDERHIERARCHY_FOLDERS","FolderHierarchy:Folders");
define("SYNC_FOLDERHIERARCHY_FOLDER","FolderHierarchy:Folder");
define("SYNC_FOLDERHIERARCHY_DISPLAYNAME","FolderHierarchy:DisplayName");
define("SYNC_FOLDERHIERARCHY_SERVERENTRYID","FolderHierarchy:ServerEntryId");
define("SYNC_FOLDERHIERARCHY_PARENTID","FolderHierarchy:ParentId");
define("SYNC_FOLDERHIERARCHY_TYPE","FolderHierarchy:Type");
define("SYNC_FOLDERHIERARCHY_RESPONSE","FolderHierarchy:Response");
define("SYNC_FOLDERHIERARCHY_STATUS","FolderHierarchy:Status");
define("SYNC_FOLDERHIERARCHY_CONTENTCLASS","FolderHierarchy:ContentClass");
define("SYNC_FOLDERHIERARCHY_CHANGES","FolderHierarchy:Changes");
define("SYNC_FOLDERHIERARCHY_ADD","FolderHierarchy:Add");
define("SYNC_FOLDERHIERARCHY_REMOVE","FolderHierarchy:Remove");
define("SYNC_FOLDERHIERARCHY_UPDATE","FolderHierarchy:Update");
define("SYNC_FOLDERHIERARCHY_SYNCKEY","FolderHierarchy:SyncKey");
define("SYNC_FOLDERHIERARCHY_FOLDERCREATE","FolderHierarchy:FolderCreate");
define("SYNC_FOLDERHIERARCHY_FOLDERDELETE","FolderHierarchy:FolderDelete");
define("SYNC_FOLDERHIERARCHY_FOLDERUPDATE","FolderHierarchy:FolderUpdate");
define("SYNC_FOLDERHIERARCHY_FOLDERSYNC","FolderHierarchy:FolderSync");
define("SYNC_FOLDERHIERARCHY_COUNT","FolderHierarchy:Count");
define("SYNC_FOLDERHIERARCHY_VERSION","FolderHierarchy:Version");

// MeetingResponse
define("SYNC_MEETINGRESPONSE_CALENDARID","MeetingResponse:CalendarId");
define("SYNC_MEETINGRESPONSE_FOLDERID","MeetingResponse:FolderId");
define("SYNC_MEETINGRESPONSE_MEETINGRESPONSE","MeetingResponse:MeetingResponse");
define("SYNC_MEETINGRESPONSE_REQUESTID","MeetingResponse:RequestId");
define("SYNC_MEETINGRESPONSE_REQUEST","MeetingResponse:Request");
define("SYNC_MEETINGRESPONSE_RESULT","MeetingResponse:Result");
define("SYNC_MEETINGRESPONSE_STATUS","MeetingResponse:Status");
define("SYNC_MEETINGRESPONSE_USERRESPONSE","MeetingResponse:UserResponse");
define("SYNC_MEETINGRESPONSE_VERSION","MeetingResponse:Version");

// POOMTASKS
define("SYNC_POOMTASKS_BODY","POOMTASKS:Body");
define("SYNC_POOMTASKS_BODYSIZE","POOMTASKS:BodySize");
define("SYNC_POOMTASKS_BODYTRUNCATED","POOMTASKS:BodyTruncated");
define("SYNC_POOMTASKS_CATEGORIES","POOMTASKS:Categories");
define("SYNC_POOMTASKS_CATEGORY","POOMTASKS:Category");
define("SYNC_POOMTASKS_COMPLETE","POOMTASKS:Complete");
define("SYNC_POOMTASKS_DATECOMPLETED","POOMTASKS:DateCompleted");
define("SYNC_POOMTASKS_DUEDATE","POOMTASKS:DueDate");
define("SYNC_POOMTASKS_UTCDUEDATE","POOMTASKS:UtcDueDate");
define("SYNC_POOMTASKS_IMPORTANCE","POOMTASKS:Importance");
define("SYNC_POOMTASKS_RECURRENCE","POOMTASKS:Recurrence");
define("SYNC_POOMTASKS_TYPE","POOMTASKS:Type");
define("SYNC_POOMTASKS_START","POOMTASKS:Start");
define("SYNC_POOMTASKS_UNTIL","POOMTASKS:Until");
define("SYNC_POOMTASKS_OCCURRENCES","POOMTASKS:Occurrences");
define("SYNC_POOMTASKS_INTERVAL","POOMTASKS:Interval");
define("SYNC_POOMTASKS_DAYOFWEEK","POOMTASKS:DayOfWeek");
define("SYNC_POOMTASKS_DAYOFMONTH","POOMTASKS:DayOfMonth");
define("SYNC_POOMTASKS_WEEKOFMONTH","POOMTASKS:WeekOfMonth");
define("SYNC_POOMTASKS_MONTHOFYEAR","POOMTASKS:MonthOfYear");
define("SYNC_POOMTASKS_REGENERATE","POOMTASKS:Regenerate");
define("SYNC_POOMTASKS_DEADOCCUR","POOMTASKS:DeadOccur");
define("SYNC_POOMTASKS_REMINDERSET","POOMTASKS:ReminderSet");
define("SYNC_POOMTASKS_REMINDERTIME","POOMTASKS:ReminderTime");
define("SYNC_POOMTASKS_SENSITIVITY","POOMTASKS:Sensitivity");
define("SYNC_POOMTASKS_STARTDATE","POOMTASKS:StartDate");
define("SYNC_POOMTASKS_UTCSTARTDATE","POOMTASKS:UtcStartDate");
define("SYNC_POOMTASKS_SUBJECT","POOMTASKS:Subject");
define("SYNC_POOMTASKS_RTF","POOMTASKS:Rtf");

// ResolveRecipients
define("SYNC_RESOLVERECIPIENTS_RESOLVERECIPIENTS","ResolveRecipients:ResolveRecipients");
define("SYNC_RESOLVERECIPIENTS_RESPONSE","ResolveRecipients:Response");
define("SYNC_RESOLVERECIPIENTS_STATUS","ResolveRecipients:Status");
define("SYNC_RESOLVERECIPIENTS_TYPE","ResolveRecipients:Type");
define("SYNC_RESOLVERECIPIENTS_RECIPIENT","ResolveRecipients:Recipient");
define("SYNC_RESOLVERECIPIENTS_DISPLAYNAME","ResolveRecipients:DisplayName");
define("SYNC_RESOLVERECIPIENTS_EMAILADDRESS","ResolveRecipients:EmailAddress");
define("SYNC_RESOLVERECIPIENTS_CERTIFICATES","ResolveRecipients:Certificates");
define("SYNC_RESOLVERECIPIENTS_CERTIFICATE","ResolveRecipients:Certificate");
define("SYNC_RESOLVERECIPIENTS_MINICERTIFICATE","ResolveRecipients:MiniCertificate");
define("SYNC_RESOLVERECIPIENTS_OPTIONS","ResolveRecipients:Options");
define("SYNC_RESOLVERECIPIENTS_TO","ResolveRecipients:To");
define("SYNC_RESOLVERECIPIENTS_CERTIFICATERETRIEVAL","ResolveRecipients:CertificateRetrieval");
define("SYNC_RESOLVERECIPIENTS_RECIPIENTCOUNT","ResolveRecipients:RecipientCount");
define("SYNC_RESOLVERECIPIENTS_MAXCERTIFICATES","ResolveRecipients:MaxCertificates");
define("SYNC_RESOLVERECIPIENTS_MAXAMBIGUOUSRECIPIENTS","ResolveRecipients:MaxAmbiguousRecipients");
define("SYNC_RESOLVERECIPIENTS_CERTIFICATECOUNT","ResolveRecipients:CertificateCount");

// ValidateCert
define("SYNC_VALIDATECERT_VALIDATECERT","ValidateCert:ValidateCert");
define("SYNC_VALIDATECERT_CERTIFICATES","ValidateCert:Certificates");
define("SYNC_VALIDATECERT_CERTIFICATE","ValidateCert:Certificate");
define("SYNC_VALIDATECERT_CERTIFICATECHAIN","ValidateCert:CertificateChain");
define("SYNC_VALIDATECERT_CHECKCRL","ValidateCert:CheckCRL");
define("SYNC_VALIDATECERT_STATUS","ValidateCert:Status");

// POOMCONTACTS2
define("SYNC_POOMCONTACTS2_CUSTOMERID","POOMCONTACTS2:CustomerId");
define("SYNC_POOMCONTACTS2_GOVERNMENTID","POOMCONTACTS2:GovernmentId");
define("SYNC_POOMCONTACTS2_IMADDRESS","POOMCONTACTS2:IMAddress");
define("SYNC_POOMCONTACTS2_IMADDRESS2","POOMCONTACTS2:IMAddress2");
define("SYNC_POOMCONTACTS2_IMADDRESS3","POOMCONTACTS2:IMAddress3");
define("SYNC_POOMCONTACTS2_MANAGERNAME","POOMCONTACTS2:ManagerName");
define("SYNC_POOMCONTACTS2_COMPANYMAINPHONE","POOMCONTACTS2:CompanyMainPhone");
define("SYNC_POOMCONTACTS2_ACCOUNTNAME","POOMCONTACTS2:AccountName");
define("SYNC_POOMCONTACTS2_NICKNAME","POOMCONTACTS2:NickName");
define("SYNC_POOMCONTACTS2_MMS","POOMCONTACTS2:MMS");

// Ping
define("SYNC_PING_PING","Ping:Ping");
define("SYNC_PING_STATUS","Ping:Status");
define("SYNC_PING_LIFETIME", "Ping:LifeTime");
define("SYNC_PING_FOLDERS", "Ping:Folders");
define("SYNC_PING_FOLDER", "Ping:Folder");
define("SYNC_PING_SERVERENTRYID", "Ping:ServerEntryId");
define("SYNC_PING_FOLDERTYPE", "Ping:FolderType");

//Provision
define("SYNC_PROVISION_PROVISION", "Provision:Provision");
define("SYNC_PROVISION_POLICIES", "Provision:Policies");
define("SYNC_PROVISION_POLICY", "Provision:Policy");
define("SYNC_PROVISION_POLICYTYPE", "Provision:PolicyType");
define("SYNC_PROVISION_POLICYKEY", "Provision:PolicyKey");
define("SYNC_PROVISION_DATA", "Provision:Data");
define("SYNC_PROVISION_STATUS", "Provision:Status");
define("SYNC_PROVISION_REMOTEWIPE", "Provision:RemoteWipe");
define("SYNC_PROVISION_EASPROVISIONDOC", "Provision:EASProvisionDoc");

//Search
define("SYNC_SEARCH_SEARCH", "Search:Search");
define("SYNC_SEARCH_STORE", "Search:Store");
define("SYNC_SEARCH_NAME", "Search:Name");
define("SYNC_SEARCH_QUERY", "Search:Query");
define("SYNC_SEARCH_OPTIONS", "Search:Options");
define("SYNC_SEARCH_RANGE", "Search:Range");
define("SYNC_SEARCH_STATUS", "Search:Status");
define("SYNC_SEARCH_RESPONSE", "Search:Response");
define("SYNC_SEARCH_RESULT", "Search:Result");
define("SYNC_SEARCH_PROPERTIES", "Search:Properties");
define("SYNC_SEARCH_TOTAL", "Search:Total");
define("SYNC_SEARCH_EQUALTO", "Search:EqualTo");
define("SYNC_SEARCH_VALUE", "Search:Value");
define("SYNC_SEARCH_AND", "Search:And");
define("SYNC_SEARCH_OR", "Search:Or");
define("SYNC_SEARCH_FREETEXT", "Search:FreeText");
define("SYNC_SEARCH_DEEPTRAVERSAL", "Search:DeepTraversal");
define("SYNC_SEARCH_LONGID", "Search:LongId");
define("SYNC_SEARCH_REBUILDRESULTS", "Search:RebuildResults");
define("SYNC_SEARCH_LESSTHAN", "Search:LessThan");
define("SYNC_SEARCH_GREATERTHAN", "Search:GreaterThan");
define("SYNC_SEARCH_SCHEMA", "Search:Schema");
define("SYNC_SEARCH_SUPPORTED", "Search:Supported");

//GAL
define("SYNC_GAL_DISPLAYNAME", "GAL:DisplayName");
define("SYNC_GAL_PHONE", "GAL:Phone");
define("SYNC_GAL_OFFICE", "GAL:Office");
define("SYNC_GAL_TITLE", "GAL:Title");
define("SYNC_GAL_COMPANY", "GAL:Company");
define("SYNC_GAL_ALIAS", "GAL:Alias");
define("SYNC_GAL_FIRSTNAME", "GAL:FirstName");
define("SYNC_GAL_LASTNAME", "GAL:LastName");
define("SYNC_GAL_HOMEPHONE", "GAL:HomePhone");
define("SYNC_GAL_MOBILEPHONE", "GAL:MobilePhone");
define("SYNC_GAL_EMAILADDRESS", "GAL:EmailAddress");

// Other constants
define("SYNC_FOLDER_TYPE_OTHER", 1);
define("SYNC_FOLDER_TYPE_INBOX", 2);
define("SYNC_FOLDER_TYPE_DRAFTS", 3);
define("SYNC_FOLDER_TYPE_WASTEBASKET", 4);
define("SYNC_FOLDER_TYPE_SENTMAIL", 5);
define("SYNC_FOLDER_TYPE_OUTBOX", 6);
define("SYNC_FOLDER_TYPE_TASK", 7);
define("SYNC_FOLDER_TYPE_APPOINTMENT", 8);
define("SYNC_FOLDER_TYPE_CONTACT", 9);
define("SYNC_FOLDER_TYPE_NOTE", 10);
define("SYNC_FOLDER_TYPE_JOURNAL", 11);
define("SYNC_FOLDER_TYPE_USER_MAIL", 12);
define("SYNC_FOLDER_TYPE_USER_APPOINTMENT", 13);
define("SYNC_FOLDER_TYPE_USER_CONTACT", 14);
define("SYNC_FOLDER_TYPE_USER_TASK", 15);
define("SYNC_FOLDER_TYPE_USER_JOURNAL", 16);
define("SYNC_FOLDER_TYPE_USER_NOTE", 17);
define("SYNC_FOLDER_TYPE_UNKNOWN", 18);
define("SYNC_FOLDER_TYPE_RECIPIENT_CACHE", 19);
define("SYNC_FOLDER_TYPE_DUMMY", "__dummy.Folder.Id__");

define("SYNC_CONFLICT_OVERWRITE_SERVER", 0);
define("SYNC_CONFLICT_OVERWRITE_PIM", 1);

define("SYNC_FILTERTYPE_ALL", 0);
define("SYNC_FILTERTYPE_1DAY", 1);
define("SYNC_FILTERTYPE_3DAYS", 2);
define("SYNC_FILTERTYPE_1WEEK", 3);
define("SYNC_FILTERTYPE_2WEEKS", 4);
define("SYNC_FILTERTYPE_1MONTH", 5);
define("SYNC_FILTERTYPE_3MONTHS", 6);
define("SYNC_FILTERTYPE_6MONTHS", 7);

define("SYNC_TRUNCATION_HEADERS", 0);
define("SYNC_TRUNCATION_512B", 1);
define("SYNC_TRUNCATION_1K", 2);
define("SYNC_TRUNCATION_2K", 3);
define("SYNC_TRUNCATION_5K", 4);
define("SYNC_TRUNCATION_SEVEN", 7);
define("SYNC_TRUNCATION_ALL", 9);

define("SYNC_PROVISION_STATUS_SUCCESS", 1);
define("SYNC_PROVISION_STATUS_PROTERROR", 2);
define("SYNC_PROVISION_STATUS_SERVERERROR", 3);
define("SYNC_PROVISION_STATUS_DEVEXTMANAGED", 4);
define("SYNC_PROVISION_STATUS_POLKEYMISM", 5);

define("SYNC_PROVISION_RWSTATUS_NA", 0);
define("SYNC_PROVISION_RWSTATUS_OK", 1);
define("SYNC_PROVISION_RWSTATUS_PENDING", 2);
define("SYNC_PROVISION_RWSTATUS_WIPED", 3);
?>