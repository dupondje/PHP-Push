<?php

require_once('diffbackend.php');
require_once('class_caldav_client.php');
require_once('class_ical_client.php');

class BackendCalDav extends BackendDiff {
    var $_user;
    var $_devid;
    var $_protocolversion;
    var $_path;
    var $_events = array();
    var $_tasks = array();
    var $_currentTimezone = null;

    function BackendCalDAV($config) {
        $this->_config = $config;
    }
    
    function Logon($username, $domain, $password) {
        debugLog('CalDAV::logon to CalDav server');

        # Replace variables in config
        foreach ($this->_config as $key => $value)
        {
            # Enter variables to replace ...
            debugLog("CalDAV::Config: Updating $key");
            $this->_config[$key] = str_replace("%u", $username, $this->_config[$key]);
            debugLog("CalDAV::Config: Updated $key with " . $this->_config[$key]);
        }
        $this->cdc = new CalDAVClient($this->_config['CALDAV_SERVER'] . $this->_config['CALDAV_PATH'], $username, $password, "calendar" );                                            
        debugLog('CalDAV::Successful Logon To CalDAV Server');
        return true;
    }

    // completing protocol
    function Logoff() {
        debugLog('CalDAV::Closing Connection');
        return true;
    }

    function Setup($user, $devid, $protocolversion) {
        $this->_user = $user;
        $this->_devid = $devid;
        $this->_protocolversion = $protocolversion;
        return true;
    }

    function SendMail($rfc822, $forward = false, $reply = false, $parent = false) {
        return false;
    }

    function GetWasteBasket($class) {
        return false;
    }
    
    /* Should return a list (array) of messages, each entry being an associative array
    * with the same entries as StatMessage(). This function should return stable information; ie
    * if nothing has changed, the items in the array must be exactly the same. The order of
    * the items within the array is not important though.
    *
    * The cutoffdate is a date in the past, representing the date since which items should be shown.
    * This cutoffdate is determined by the user's setting of getting 'Last 3 days' of e-mail, etc. If
    * you ignore the cutoffdate, the user will not be able to select their own cutoffdate, but all
    * will work OK apart from that.
    */  
    function GetMessageList($folderid, $cutoffdate) {
        debugLog('CalDAV::GetMessageList('.$folderid.')');
        if ($folderid != "calendar" && $folderid != "tasks")
            return false;

        $messages = array();
    
        /* Calculating the range of events we want to sync */
        $begin = date("Ymd\THis\Z", $cutoffdate);
        $diff = time() - $cutoffdate;
        $finish = date("Ymd\THis\Z", 2147483647);

        if ($folderid == "calendar")
        {
            $events = $this->cdc->GetEvents($begin, $finish);
            debugLog("CalDAV::GetMessageList: Got " . count($events) . " events (" . $begin . " to " . $finish . ")");
            foreach ($events as $e)
            {
                $id = $e['href'];
                $this->_events[$id] = $e;
                $message = $this->StatMessage($folderid, $id);
            }
            return $this->_events;
        }
        if ($folderid == "tasks")
        {
            $tasks = $this->cdc->GetTodos($begin, $finish);
            debugLog("CalDAV::GetMessageList: Got " . count($tasks) . " tasks (" . $begin . " to " . $finish . ")");
            foreach ($tasks as $e)
            {
                $id = $e['href'];
                $this->_tasks[$id] = $e;
                $message = $this->StatMessage($folderid, $id);
            }
            return $this->_tasks;
        }
        return false;
    }

    function GetFolderList() {
        debugLog('CalDAV::GetFolderList()');
        $folders = array();
        $folder = $this->StatFolder("calendar");
        $folders[] = $folder;
        $folder = $this->StatFolder("tasks");
        $folders[] = $folder;
        return $folders;
    }


    function GetFolder($id) {
        debugLog('CalDAV::GetFolder('.$id.')');
        if ($id == "calendar") {
            $folder = new SyncFolder();
            $folder->serverid = $id;
            $folder->parentid = "0";
            $folder->displayname = "Calendar";
            $folder->type = SYNC_FOLDER_TYPE_APPOINTMENT;
            return $folder;
        }
        if ($id == "tasks") {
            $folder = new SyncFolder();
            $folder->serverid = $id;
            $folder->parentid = "0";
            $folder->displayname = "Tasks";
            $folder->type = SYNC_FOLDER_TYPE_TASK;
            return $folder;
        }
        return false;
    }

    function StatFolder($id) {
        debugLog('CalDAV::StatFolder('.$id.')');
        $folder = $this->GetFolder($id);

        $stat = array();
        $stat["id"] = $id;
        $stat["parent"] = $folder->parentid;
        $stat["mod"] = $folder->displayname;

        return $stat;
    }

    function GetAttachmentData($attname) {
        return false;
    }

    function StatMessage($folderid, $id) {       
 
        if ($folderid != "calendar" && $folderid != "tasks") {
            return false;
        }
        if (trim($id == "")) {
            return false;
        }

        debugLog("CalDAV::StatMessage($folderid: $id)");    
 
        $e = null;
        if ($folderid == "calendar")
        {
            if (array_key_exists($id, $this->_events))
                $e = $this->_events[$id];
        }
        if ($folderid == "tasks")
        {
            if (array_key_exists($id, $this->_tasks))
                $e = $this->_tasks[$id];
        }
        if ($e == null)
        {
            $e = $this->cdc->GetEntryByUid(substr($id, 0, strlen($id)-4));
            if ($e == null && count($e) <= 0)
                return;
            $e = $e[0];
        }

        $event = $this->isevent($e['data']);
        if ($event && $folderid == "calendar") {
            debugLog('CalDAV::StatMessage('.$folderid.', '.$id.') is '.$event);
            $message = $e;
            $message["id"] = $e['href'];
            $message["mod"] = $this->getLastModified($e['data'], $event);
            debugLog('CalDAV::message moded at '. $message["mod"]);
            $message["flags"] = 1; // always 'read'
            $this->_events[$id] = $message;
            return $message;
        }
        if (!$event && $folderid == "tasks") {
            debugLog('CalDAV::StatMessage('.$folderid.', '.$id.') is '.$event);
            $message = $e;
            $message["id"] = $e['href'];
            $message["mod"] = $this->getLastModified($e['data'], $event);
            debugLog('CalDAV::message moded at '. $message["mod"]);
            $message["flags"] = 1; // always 'read'
            $this->_tasks[$id] = $message;
            return $message;
        }
        return false;
    }

    function isevent($data) {
        $v = new vcalendar();
        $v->parse($data);
            
        if ($vevent = $v->getComponent('vevent')) {
            return true;
        } else {
            return false;
        }
    }
    
    function getLastModified($data, $event)
    {
        $v = new vcalendar();
        $v->parse($data);
        if ($event) {
            while ($vevent = $v->getComponent('vevent')) {
                $m = $vevent->getProperty('LAST-MODIFIED');
                if ($m)
                    return gmdate("Ymd\THis\Z", gmmktime($m['hour'], $m['min'], $m['sec'], $m['month'], $m['day'], $m['year']));
            }
        }
        else {
            while ($vtodo = $v->getComponent('vtodo')) {
                $m = $vtodo->getProperty('LAST-MODIFIED');
                if ($m)
                    return gmdate("Ymd\THis\Z", gmmktime($m['hour'], $m['min'], $m['sec'], $m['month'], $m['day'], $m['year']));
            }
        }
        return gmdate("Ymd\THis\Z");
    }

    function GetMessage($folderid, $id, $truncsize, $mimesupport = 0) {

        debugLog('CalDAV::GetMessage('.$folderid.', '.$id.', ..)');

        if (trim($id) == "")
            return;
 
        if ($folderid == "calendar")
            $output = $this->_events[$id]['data'];
        elseif ($folderid == "tasks")
            $output = $this->_tasks[$id]['data'];
        else
            return;     
        
        //debugLog("CalDAV::Got File ".$id." now parseing ".$output);
        $v = new vcalendar();
        $v->parse($output);
        $v->sort();
        
        if ($vtimezone = $v->getComponent( 'vtimezone' )) {
            $this->_currentTimezone = $vtimezone->getProperty('tzid');
        }

        $vcounter = 1;
        if ($folderid == "tasks") {
            while ($vtodo = $v->getComponent('vtodo', $vcounter)) {
                $message = $this->converttotask($vtodo, $truncsize);
                $vcounter++;
            }
        } else {
            $fullexceptionsarray = array();
            while ($vevent = $v->getComponent( 'vevent', $vcounter)) {
                $val = $vevent->getProperty("RECURRENCE-ID");
                if ($val === false) {
                    $message = $this->converttoappointment($vevent, $truncsize);
                } else {
                    $tmp = $this->converttoappointment($vevent, $truncsize);
                    $tmp->deleted = "0";
                    //The exceptionstarttime is the ORIGINAL starttime of the event
                    //On Thunderbird this is equal to the RECCURENCE-ID (which is in $val)
                    $tmp->exceptionstarttime = $this->makeGMTTime($val);
                    unset($tmp->uid);
                    unset($tmp->exceptions);
                    array_push($fullexceptionsarray, $tmp);
                    unset($tmp);
                }
                $vcounter++;
            }
            $message->exceptions = array_merge($message->exceptions, $fullexceptionsarray);
        }

        if ($vtimezone = $v->getComponent( 'vtimezone' )) { 
            $message = $this->setoutlooktimezone($message, $vtimezone);
        }           
        debugLog("CalDAV::Finsihed Converting ".$id." now returning");
        return $message;
    }

    function DeleteMessage($folderid, $id) {
        debugLog("CalDAV::DeleteMessage(" . $folderid . ", " . $id.", ..)");
        $http_status_code = $this->cdc->DoDELETERequest($id);
        debugLog("CalDAV::DeleteMessage Reply: $http_status_code");
        if ($http_status_code == "204") {
            return true;
        } else {
            return false;
        }
    }

    function SetReadFlag($folderid, $id, $flags) {
        return false;
    }

    function ChangeMessage($folderid, $id, $message) {
        debugLog('CalDAV::ChangeMessage('.$folderid.', '.$id.', ..)');
        debugLog("CalDAV::Their Message = ");

        if (trim($id) != "") {
            $return = $this->StatMessage($folderid, $id);
        } else {
            $return = false;
        }
        if ($return === false) {
            debugLog('CalDAV::Found new message on device');    
            #create new id, this is a new record from device.
            $date   = date('Ymd\THisT');
            $unique = substr(microtime(), 2, 4);
            $base   = 'aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPrRsStTuUvVxXuUvVwWzZ1234567890';
            $start  = 0;
            $end    = strlen( $base ) - 1;
            $length = 6;
            $str    = null;
            for($p = 0; $p < $length; $p++)
                $unique .= $base{mt_rand( $start, $end )};
            $id = $date.'-'.$unique.".ics";
        } else {
            debugLog('CalDAV::Event Already On Server');            
        }

        $task = false;

        if ($folderid == "tasks") {
            $vtodo = $this->converttovtodo($message);
            if (substr($id, strlen($id)-4) == ".ics") {
                $vtodo->setProperty( "UID", substr($id, 0, -4));
            } else {
                $vtodo->setProperty( "UID", $id);
            }
        } else {
            $vevent = $this->converttovevent($message);
            $exarray = array();
            if (isset($message->exceptions) && is_array($message->exceptions)) {
                $deletedarray = array();
                foreach ($message->exceptions as $ex) {
                    if ($ex->deleted == "1") {
                        array_push($deletedarray, $this->parseDate($ex->exceptionstarttime));
                    } else {
                        debugLog('CalDAV::Found non deleted exception Converting...');      
                        $tmpevent = $this->converttovevent($ex);
                        if (isset($ex->alldayevent) && $ex->alldayevent == "1") {
                            $tmpevent->setProperty("recurrence-id",  $this->parseDate($ex->exceptionstarttime), array('VALUE'=>'DATE'));
                        } else {
                            $tmpevent->setProperty("recurrence-id",  $this->parseDate($ex->exceptionstarttime));
                        }
                        array_push($exarray, $tmpevent);            
                    }
                }
                debugLog("CalDAV:: ".print_r($deletedarray ,true));
                if (count($deletedarray) > 0) {
                    $vevent->setProperty("exdate", $deletedarray);
                }
            }
            if (substr($id, strlen($id)-4) == ".ics") {
                $vevent->setProperty( "UID", substr($id, 0, -4));
            } else {
                $vevent->setProperty( "UID", $id);
            }
        }
        
        // Set mod date to current
        $mod = $this->parseGMTDate(time());

        #   $somethingelse = convert2ical();
        debugLog('CalDAV::Converted to iCal: ');    
        $v = new vcalendar();
        
        if ($folderid == "tasks") {
            $vtodo->setProperty("LAST-MODIFIED", $mod);
            $v->setComponent( $vtodo );
        } else {
            $vevent->setProperty("LAST-MODIFIED", $mod);
            $v->setComponent( $vevent );
            if (count($exarray) > 0) {
                foreach($exarray as $exvevent) {
                    $sdt = $exvevent->getProperty("dtstart");
    
                    if (substr($id, strlen($id)-4) == ".ics") {
                        $exvevent->setProperty( "UID", substr($id, 0, -4));
                    } else {
                        $exvevent->setProperty( "UID", $id);
                    }
    
                    $v->setComponent( $exvevent );          
                }
            }
        }
        
        $output = $v->createCalendar();
        debugLog("CalDAV::putting to ".$this->_path.$id);   

        $etag = "*";
        if ($return)
            $etag = $return['etag'];

        $retput = $this->cdc->DoPUTRequest($this->_path.$id, $output, $etag);
        debugLog("CalDAV::output putted etag: $etag new etag: $retput");

        $obj = array();
        $obj["mod"] = $mod;
        $obj["etag"] = $retput;
        $obj["href"] = $id;
        $obj["data"] = $output;

        if ($folderid == "calendar")
            $this->_events[$id] = $obj;
        else
            $this->_tasks[$id] = $obj;

        return $this->StatMessage($folderid, $id);
    }

    function MoveMessage($folderid, $id, $newfolderid) {
        return false;
    }

    /* At this moment we DO NOT support AlterPing
     * But because Ping does not have a cutoffdate,
     * we get into issues. So we just "fake" AlterPing
     */
    function AlterPing() {
        return true;
    }

    function AlterPingChanges($folderid, &$syncstate) {
        return array();
    }

    function setoutlooktimezone($message, $vtimezone) {
        $message->timezone = $this->getTimezoneString($this->_currentTimezone);
        return $message;
    }
    
    function converttoappointment($vevent, $truncsize) {
        debugLog("CalDAV::converting vevent to outlook appointment");
        $message = new SyncAppointment();
        $message->alldayevent = "0";
        $message->sensitivity = "0";
        $message->meetingstatus = "0";
        $message->busystatus = "2";

        $mapping = array(
            "dtstart" => array("starttime", 3),
            "dtstamp" => array("dtstamp", 3),
            "dtend" => array("endtime", 3)
        );

        $message = $this->converttooutlook($message, $vevent, $truncsize, $mapping);

        if (($message->endtime-$message->starttime) >= 24*60*60) {
            debugLog("CalDAV:: sdt edt diff : Endtime: " . $message->endtime . " Startime: " . $message->starttime);
            $message->alldayevent = "1";
        }

        $mapping = array(
            "class" => array("sensitivity", 1),
            "description" => array("body", 2),
            "location" => array("location", 0),
            "organizer" => array("organizername", 4),
            "status" => array("meetingstatus", 1),
            "summary" => array("subject", 9),
            "transp" => array("busystatus", 1),
            "uid" => array("uid", 8),
            "rrule" => array("recurrence", 5),
            "duration" => array("endtime", 6),
            "attendee" => array("attendees", 13),
            "categories" => array("categories", 10),
            "valarm" => array("reminder", 7)
        );

        $message = $this->converttooutlook($message, $vevent, $truncsize, $mapping, new SyncRecurrence());

        $excounter = 1;
        $tmparray = array();
        while (is_array($vevent->getProperty("exdate", $excounter))) {
            $val = $vevent->getProperty("exdate", $excounter);
            if (!array_key_exists("year", $val)) {
                foreach ($val as $exdate) {
                    if (is_array($exdate)) {
                        array_push($tmparray, $this->getdeletedexceptionobject($exdate));                           
                    }
                }
            }
            $excounter++;
        }
        $message->exceptions = $tmparray;
        return $message;
    }
    
    function converttotask($vtodo, $truncsize) {
        debugLog("CalDAV::converting vtodo to outlook appointment");
        $message = new SyncTask();
        $message->sensitivity = "0";
    
        $mapping = array(
            "class" => array("sensitivity", 1),
            "description" => array("body", 2),
            "completed" => array("datecompleted", 11),
            "status" => array("complete", 1),
            "due" => array("duedate", 11),
            "dtstart" => array("startdate", 11),
            "summary" => array("subject", 9),
            "priority" => array("importance", 1),
            "uid" => array("uid", 8),
            "rrule" => array("recurrence", 5),
            "categories" => array("categories", 10),
            "valarm" => array("reminder", 12)
        );

        $message = $this->converttooutlook($message, $vtodo, $truncsize, $mapping, new SyncTaskRecurrence());
        return $message;
    }
    
    function converttooutlook($message, $icalcomponent, $truncsize, $mapping, $rruleobj = false) {
        foreach($mapping as $k => $e) {
            $val = $icalcomponent->getProperty($k);
            if ($val !== false) {
            // if found $k in event convert and put in message
                if ($e[1] == 0) {
                    $val = trim($val);
                }
                if ($e[1] == 1) {
                    $val = trim(strtoupper($val));
                    switch ($e[0]) {
                        case "importance":
                            if ($val > 6) {
                                $val = "0";
                            } else  if ($val > 3 && $val < 7) {
                                $val = "1";
                            } else if ($val < 4) {
                                $val = "2";
                            }
                        break;

                        case "sensitivity":
                            switch ( $val ) {
                                case "PUBLIC":
                                    $val = 0;
                                    break;
                                case "PRIVATE":
                                    $val = 2;
                                    break;
                                case "CONFIDENTIAL":
                                    $val = 3;
                                    break;
                            }    
                        break;
                        case "meetingstatus":
                            switch ( $val ) {
                                case "TENTATIVE":
                                    $val = 1;
                                    break;
                                case "CONFIRMED":
                                    $val = 3;
                                    break;
                                case "CANCELLED":
                                    $val = 5;
                                    break;
    
                            }    
                        break;
                        case "busystatus":
                            switch ( $val ) {
                                case "TRANSPARENT":
                                    $val = 0;
                                    break;
                                case "OPAQUE":
                                    $val = 2;
                                    break;
                                default:
                                    $val = 2;
                            }    
                        break;
                        case "complete":
                            switch ($val) {
                                case "NEEDS-ACTION":
                                case "IN-PROCESS":
                                case "CANCELLED":
                                    $val = "0";
                                    break;
                                case "COMPLETED":
                                default:
                                    $val = "1";
                            }
                    } 
                }
                if ($e[1] == 2) {
                    if ($truncsize != 0 && strlen($val) > $truncsize) {
                        $message->bodytruncated = 1;
                        $val = substr($val, 0, $truncsize);
                    }
                    $val = str_replace("\\n", "\r\n", $val);
                }
                if ($e[1] == 3) {
                    // convert to date
                    if (is_array($val)) {
                        if (!empty($val['TZID'])) {
                            $message->timezone = $this->getTimezoneString($val['TZID']);
                        }
                        $val = $this->makeGMTTime($val);
                    } else {
                        $val =  $this->parseDateToOutlook($val);
                    }
                }
                if ($e[1] == 4) {
                    // extract organizers name and email
                    $val = trim($val);
                    $message->organizeremail = $val;
                    $val = $this->parseOrganizer($val);
                }
                if ($e[1] == 5) {
                    // recurrence?
                    $val = $this->getrecurrence($val, $message->starttime, $rruleobj);
                }
                if ($e[1] == 6) {
                    // duration
                    $starttime = $this->parseDate($vevent->getProperty("dtstart"));
                    $duration = $val;
                    $week = $this->parseDuration($duration, "W");
                    $starttime = $this->dateAdd("W", $week, $starttime);
                    $hour = $this->parseDuration($duration, "H");
                    $starttime = $this->dateAdd("H", $hour, $starttime);
                    $minute = $this->parseDuration($duration, "M");
                    $starttime = $this->dateAdd("M", $minute, $starttime);
                    $second = $this->parseDuration($duration, "S");
                    $starttime = $this->dateAdd("S", $second, $starttime);
                    $day = $this->parseDuration($duration, "D");
                    $starttime = $this->dateAdd("D", $day, $starttime);
                    if ($week > 0 || $day > 0) {
                        $message->alldayevent = "1";
                    } else {
                        $message->alldayevent = "0";                
                    }
                    $val = $startime;
                }
                if ($e[1] == 8) {
                    $val = bin2hex($val);
                }
                if ($e[1] == 9) {
                    $val = str_replace("\n", "", trim($val));
                }
                if ($e[1] == 10) {
                    $val = explode(",", $val);
                    foreach ($val as $k => $v) {
                        $val[$k] = trim($v);
                    }
                }
                if ($e[1] == 11) {
                    if (is_array($val)) {
                        if (!empty($val['TZID'])) {
                            $message->timezone = $this->getTimezoneString($val['TZID']);
                        }
                        $val = $this->makeGMTTime($val);
                    } else {
                        $val = $this->parseDateToOutlook($val);
                    }
                }
                if ($e[1] == 13) {
                    $tmpcounter = 1;
                    $val = array();
                    while ($tmpval = $icalcomponent->getProperty($k, $tmpcounter)) {
                        $tmp = new SyncAttendee();
                        $tmp->email = trim($tmpval);
                        $tmpval2 = $icalcomponent->getProperty($k, $tmpcounter, TRUE);
                        if (isset($tmpval2['params']['CN'])) $tmp->name = $tmpval2['params']['CN'];
                        array_push($val, $tmp);
                        $tmpcounter++;
                    }
                }
                $message->$e[0] = $val;             
            }
            if ($e[1] == 7) {
                $val = $icalcomponent->getComponent($k);
                if (is_object($val)) {
                    $trigger = $val->getProperty("trigger");
                    if (is_array($trigger)) {
                        $reminder = 0;
                        if (array_key_exists("min", $trigger))
                        {
                            $reminder += $trigger["min"];
                        }
                        if (array_key_exists("hour", $trigger))
                        {
                            $reminder += $trigger["hour"] * 60;
                        }
                        $message->$e[0] = $reminder;
                    } else {
                        $message->$e[0] = "";                   
                    }
                } else {
                    $message->$e[0] = "";
                }
            }
            if ($e[1] == 12) {
                $val = $icalcomponent->getComponent($k);
                if (is_object($val)) {
                    $trigger = $val->getProperty("trigger");
                    if (is_array($trigger)) {
                        $message->$e[0] = $trigger["min"];
                    }
                }
            }
        }
        return $message;
    }   
    
    function getrecurrence($args, $sdt, $rtn) {
        switch (trim(strtoupper($args['FREQ']))) {
            case "DAILY":
                $rtn->type = "0";
                break;
            case "WEEKLY":
                $rtn->type = "1";
                $day = date('N', $sdt);
                if ($day == 7) $daybin = 1;
                if ($day == 1) $daybin = 2;
                if ($day == 2) $daybin = 4;
                if ($day == 3) $daybin = 8;
                if ($day == 4) $daybin = 16;
                if ($day == 5) $daybin = 32;
                if ($day == 6) $daybin = 64;
                $rtn->dayofweek = $daybin;          
                break;
            case "MONTHLY":
                $rtn->type = "2";
                $rtn->dayofmonth = date('d', $sdt);
                break;
            case "YEARLY":
                $rtn->type = "5";
                $rtn->dayofmonth = date('d', $sdt);
                $rtn->monthofyear = date('m', $sdt);                
                break;
        }

        if (array_key_exists("BYDAY", $args) && is_array($args['BYDAY'])) {
            $daybin = 0;
            $single = false;
            foreach ($args['BYDAY'] as $day) {
                if (is_array($day)) {
                    if (count($day) == 2) {
                        $rtn->weekofmonth = $day[0];
                        if ($rtn->type == "2") $rtn->type = "3";
                        if ($rtn->type == "5") $rtn->type = "6";
                        $rtn->dayofmonth = "";
                    }
                    if ($day["DAY"] == "SU") $daybin += 1;
                    if ($day["DAY"] == "MO") $daybin += 2;
                    if ($day["DAY"] == "TU") $daybin += 4;
                    if ($day["DAY"] == "WE") $daybin += 8;
                    if ($day["DAY"] == "TH") $daybin += 16;
                    if ($day["DAY"] == "FR") $daybin += 32;
                    if ($day["DAY"] == "SA") $daybin += 64;
                } else {
                    $single = true;
                    break;
                }
            }
            if ($single) {
                if (count($args['BYDAY']) == 2) {
                    $rtn->weekofmonth = $args['BYDAY'][0];
                    if ($rtn->type == "2") $rtn->type = "3";
                    if ($rtn->type == "5") $rtn->type = "6";
                }
                if ($args['BYDAY']["DAY"] == "SU") $daybin += 1;
                if ($args['BYDAY']["DAY"] == "MO") $daybin += 2;
                if ($args['BYDAY']["DAY"] == "TU") $daybin += 4;
                if ($args['BYDAY']["DAY"] == "WE") $daybin += 8;
                if ($args['BYDAY']["DAY"] == "TH") $daybin += 16;
                if ($args['BYDAY']["DAY"] == "FR") $daybin += 32;
                if ($args['BYDAY']["DAY"] == "SA") $daybin += 64;
            }
            $rtn->dayofweek = $daybin;
        }
        if (array_key_exists("DAYOFMONTH", $args)) {
            if (is_numeric($args['DAYOFMONTH'])) $rtn->dayofmonth = $args['DAYOFMONTH'];
        }
        if (array_key_exists("MONTHOFYEAR", $args)) {
            if (is_numeric($args['MONTHOFYEAR'])) $rtn->monthofyear = $args['MONTHOFYEAR'];
        }
    
        if (array_key_exists("COUNT", $args)) $rtn->occurrences = $args['COUNT'];
        if (array_key_exists("INTERVAL", $args))
            $rtn->interval = $args['INTERVAL'];        
        else
            $rtn->interval = "1";
        if (array_key_exists("UNTIL", $args)) $rtn->until = gmmktime($args['UNTIL']['hour'], $args['UNTIL']['min'], $args['UNTIL']['sec'], $args['UNTIL']['month'], $args['UNTIL']['day'], $args['UNTIL']['year']);
        
        return $rtn;
    }
    
    function parseDuration($duration, $interval) {
        $temp = strpos($duration, $interval);
        if ($temp !== false) {
            $end = $temp;
            while ($temp > 0 && isdigit(substr($duration, $temp, 1))) {
                $temp--;
            }
            return substr($duration, $temp, $end - $temp);
        } else {
            return 0;
        }
    }
    
    function isdigit($char) {
        return in_array($char, array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9"));
    }
    
    function parseDate($ts, $extradays = 0) {
        $ts = $ts + ($extradays*24*60*60);
        return date('Ymd\THis', $ts);
    }
    function parseGMTDate($ts, $extradays = 0) {
        $ts = $ts + ($extradays*24*60*60);
        return gmdate('Ymd\THis\Z', $ts);
    }
 
    function parseDateToOutlook($ts) {
        return strtotime($ts);
    }
    
    function parseOrganizer($val) {
        $name = substr($val, 0, strpos($val, "@"));
        return $name;
    }
    
    function dateAdd($interval, $number, $date) {
        $date_time_array = getdate($date);
        $hours = $date_time_array['hours'];
        $minutes = $date_time_array['minutes'];
        $seconds = $date_time_array['seconds'];
        $month = $date_time_array['mon'];
        $day = $date_time_array['mday'];
        $year = $date_time_array['year'];
    
        switch ($interval) {
            case 'D':
                $day+=$number;
                break;
            case 'W':
                $day+=($number*7);
                break;
            case 'H':
                $hours+=$number;
                break;
            case 'M':
                $minutes+=$number;
                break;
            case 'S':
                $seconds+=$number;
                break;            
        }
        $timestamp= mktime($hours,$minutes,$seconds,$month,$day,$year);
        return $timestamp;
    }
   
    function makeGMTTime($val)
    {
        $tz = timezone_open('GMT');
        if (!empty($val['TZID'])) {
            $tz = timezone_open($val['TZID']);
        }
        elseif ($this->_currentTimezone)
        {
            $tz = timezone_open($this->_currentTimezone);
        }
        $timestr = null;
        $date = null;
        if (array_key_exists('hour', $val) && array_key_exists('min', $val) && array_key_exists('sec', $val)) {
            $timestr = sprintf("%d-%d-%d %d:%d:%d", $val['year'], $val['month'], $val['day'], $val['hour'], $val['min'], $val['sec']);
            $date = date_create_from_format('Y-m-d H:i:s', $timestr, $tz);
        }
        else {
            $timestr = sprintf("%d-%d-%d %d:%d:%d UTC", $val['year'], $val['month'], $val['day'], 0, 0, 0);
            $date = date_create_from_format('Y-m-d H:i:s T', $timestr, $tz);
        }
        return date_timestamp_get($date);
    }

    function getTimezoneString($timezone)
    {
        // UTC needs special handling
        if ($timezone == "UTC")
            return base64_encode(pack('la64vvvvvvvvla64vvvvvvvvl', 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0));
        try {
            //Generate a timezone string (PHP 5.3 needed for this)
            debugLog("getTimezoneString: Generating timezone base64");
            $timezone = new DateTimeZone($timezone);
            $trans = $timezone->getTransitions(time());
            $stdTime = null;
            $dstTime = null;
            if ($trans[1]['isdst'] == 1)
            {
                $dstTime = $trans[1];
                $stdTime = $trans[2];
            }
            else
            {
                $dstTime = $trans[2];
                $stdTime = $trans[1];
            }
            $stdTime_array = date_parse($stdTime['time']);
            $stdBias = $stdTime['offset'] / -60;
            $stdName = $stdTime['abbr'];
            $stdYear = 0;
            $stdMonth = $stdTime_array['month'];
            $stdDay = $stdTime_array['day'];
            $stdHour = $stdTime_array['hour'];
            $stdMinute = $stdTime_array['minute'];
            $dstTime_array = date_parse($dstTime['time']);
            $dstName = $dstTime['abbr'];
            $dstYear = 0;
            $dstMonth = $dstTime_array['month'];
            $dstDay = $dstTime_array['day'];
            $dstHour = $dstTime_array['hour'];
            $dstMinute = $dstTime_array['minute'];
            $dstBias = ($dstTime['offset'] - $stdTime['offset']) / -60;
            return base64_encode(pack('la64vvvvvvvvla64vvvvvvvvl', $stdBias, $stdName, 0, $stdMonth, $stdDay, 0, $stdHour, $stdMinute, 0, 0, 0, $dstName, 0, $dstMonth, $dstDay, 0, $dstHour, $dstMinute, 0, 0, $dstBias));
        }
        catch (Exception $e) {
            // If invalid timezone is given, we return UTC
            return base64_encode(pack('la64vvvvvvvvla64vvvvvvvvl', 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0));
        }
        return base64_encode(pack('la64vvvvvvvvla64vvvvvvvvl', 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0, '', 0, 0, 0, 0, 0, 0, 0, 0, 0));
    }
    
    function converttovevent($message) {
    
        debugLog('CalDAV:: About to create new event.');
        $vevent = new vevent();
        debugLog('CalDAV:: About to create mapping array.');
      
        $allday = false;
        if (isset($message->alldayevent)) {
            $val = $message->alldayevent;
            if (trim($val) == '1') {
                $allday = true;
            }
        }

        $mapping = array(
            "dtstart" => array("starttime", 3),
            "dtstamp" => array("dtstamp", 3),
            "dtend" => array("endtime", 3),
            "class" => array("sensitivity", 1),
            "description" => array("rtf", 10),
            "description" => array("body", 2),
            "location" => array("location", 0),
            "organizer" => array("organizername", 4),
            "organizer" => array("organizeremail", 4),
            "status" => array("meetingstatus", 1),
            "summary" => array("subject", 0),
            "transp" => array("busystatus", 1),
            "uid" => array("uid", 0),
            "rrule" => array("recurrence", 5),
            "attendee" => array("attendees", 0),
            "categories" => array("categories", 2),
            "valarm" => array("reminder", 7),
            "attendee" => array("attendees", 9)
        );
        
        debugLog('CalDAV:: About to loop through calendar array.');
        $vevent = $this->converttoical($vevent, $message, $mapping, $allday);
        return $vevent;
    }

    function converttovtodo($message) {
        debugLog('CalDAV:: About to create new todo.');
        $vtodo = new vtodo();
    
        $mapping = array(
            "class" => array("sensitivity", 1),
            "description" => array("rtf", 10),
            "description" => array("body", 2),
            "completed" => array("datecompleted", 6),
            "status" => array("complete", 11),
            "due" => array("duedate", 3),
            "dtstart" => array("startdate", 3),
            "priority" => array("importance", 1),
            "summary" => array("subject", 0),
            "uid" => array("uid", 0),
            "rrule" => array("recurrence", 5),
            "categories" => array("categories", 2),
            "valarm" => array("remindertime", 8)
        );
        
        debugLog('CalDAV:: About to loop through calendar array.');
        $vtodo = $this->converttoical($vtodo, $message, $mapping, false);
        return $vtodo;
    }
    
    function converttoical($icalcomponent, $message, $mapping, $allday = false) {
        foreach($mapping as $k => $e) {
            if (isset($message->$e[0])) {
                $val = $message->$e[0];
                if (!is_object($val) && !is_array($val)) {
                    $val = trim($val);
                }
                if ($val != '') {
                    $k = strtoupper($k);
                    // if found $k in message convert and put in event
                    if ($e[1] == 0) {
                        $icalcomponent->setProperty( $k, $val);
                    }
                    if ($e[1] == 1) {
                        $val = trim($val); 
                        switch ($k) {
                            case "CLASS":
                            switch ( $val ) {
                                case "0":
                                $val = "PUBLIC";
                                break;
                                case "1":
                                $val = "PRIVATE";
                                break;
                                case "2":
                                $val = "PRIVATE";
                                break;
                                case "3":
                                $val = "CONFIDENTIAL";
                                break;
                            }    
                            break;
    
                            case "STATUS":
                            switch ( $val ) {
                                case "1":
                                $val = "TENTATIVE";
                                break;
                                case "3":
                                $val = "CONFIRMED";
                                break;
                                case "5":
                                $val = "CANCELLED";
                                break;
                            }    
                            break;
    
                            case "TRANSP":
                            switch ( $val ) {
                                case "0":
                                    $val = "TRANSPARENT";
                                    break;
                                case "2":
                                    $val = "OPAQUE";
                                    break;
                                default:
                                    $val = "OPAQUE";
                            }    
                            break;
                            
                            case "PRIORITY":
                            switch ( $val ) {
                                case "0":
                                    $val = "9";
                                    break;
                                case "1":
                                    $val = "5";
                                    break;
                                case "2":
                                    $val = "1";
                                    break;
                                default:
                                    $val = "";
                            }    
                            break;
                        } 
                        $icalcomponent->setProperty( $k, $val);
                    }
                    if ($e[1] == 2) {
                        $icalcomponent->setProperty( $k, $val);
                    }
                    if ($e[1] == 3) {
                        // convert to date
                        $val = $this->parseGMTDate($val);
                        if ($allday) {
                            $icalcomponent->setProperty( $k, $val, array('VALUE'=>'DATE'));                     
                        } else {
                            $icalcomponent->setProperty( $k, $val);
                        }
                    }
                    if ($e[1] == 4) {
                        // extract organizers name and email
                        if (trim($val) != '') {
                            $icalcomponent->setProperty( $k, $val);
                        }
                    }
                    if ($e[1] == 5) {
                        // recurrence?
                        switch ( trim($val->type) ) {
                            case "0":
                                $args['FREQ'] = "DAILY";
                                break;
                            case "1":
                                $args['FREQ'] = "WEEKLY";
                                break;
                            case "2":
                                $args['FREQ'] = "MONTHLY";
                                break;
                            case "3":
                                $args['FREQ'] = "MONTHLY";
                                break;
                            case "5":
                                $args['FREQ'] = "YEARLY";
                                break;
                            case "6":
                                $args['FREQ'] = "YEARLY";
                                break;
                        }
                        if (isset($val->dayofweek) && $val->dayofweek != "" && is_numeric($val->dayofweek)) {
                            $tmp = "0000000".decbin($val->dayofweek);
                            $args["BYDAY"] = array();
                            $len = strlen($tmp);
                            if (isset($val->weekofmonth) && $val->weekofmonth != "" && is_numeric($val->weekofmonth)) {
                                $wn = $val->weekofmonth;
                                if (substr($tmp,$len-1,1) == "1") array_push($args["BYDAY"], array($wn, "DAY" => "SU"));
                                if (substr($tmp,$len-2,1) == "1") array_push($args["BYDAY"], array($wn, "DAY" => "MO"));
                                if (substr($tmp,$len-3,1) == "1") array_push($args["BYDAY"], array($wn, "DAY" => "TU"));
                                if (substr($tmp,$len-4,1) == "1") array_push($args["BYDAY"], array($wn, "DAY" => "WE"));
                                if (substr($tmp,$len-5,1) == "1") array_push($args["BYDAY"], array($wn, "DAY" => "TH"));
                                if (substr($tmp,$len-6,1) == "1") array_push($args["BYDAY"], array($wn, "DAY" => "FR"));
                                if (substr($tmp,$len-7,1) == "1") array_push($args["BYDAY"], array($wn, "DAY" => "SA"));
                            } else {
                                if (substr($tmp,$len-1,1) == "1") array_push($args["BYDAY"], array("DAY" => "SU"));
                                if (substr($tmp,$len-2,1) == "1") array_push($args["BYDAY"], array("DAY" => "MO"));
                                if (substr($tmp,$len-3,1) == "1") array_push($args["BYDAY"], array("DAY" => "TU"));
                                if (substr($tmp,$len-4,1) == "1") array_push($args["BYDAY"], array("DAY" => "WE"));
                                if (substr($tmp,$len-5,1) == "1") array_push($args["BYDAY"], array("DAY" => "TH"));
                                if (substr($tmp,$len-6,1) == "1") array_push($args["BYDAY"], array("DAY" => "FR"));
                                if (substr($tmp,$len-7,1) == "1") array_push($args["BYDAY"], array("DAY" => "SA"));
                            }
                        }
                        if (isset($val->dayofmonth) && $val->dayofmonth != "" && is_numeric($val->dayofmonth)) {
                            $args['BYMONTHDAY'] = $val->dayofmonth;
                        }
                        if (isset($val->monthofyear) && $val->monthofyear != "" && is_numeric($val->monthofyear)) {
                            $args['BYMONTH'] = $val->monthofyear;
                        }

                        $args['INTERVAL'] = 1;
                        if (isset($val->interval) && $val->interval != "") $args['INTERVAL'] = $val->interval;
                        if (isset($val->until) && $val->until != "") $args['UNTIL'] = $this->parseGMTDate($val->until);
                        if (isset($val->occurrences) && $val->occurrences != "") $args['COUNT'] = $val->occurrences;

                        $icalcomponent->setProperty( $k, $args);                        
                    }
                    if ($e[1] == 6) {
                        if ($val != "") {
                            $val = $this->parseDate($val);
                            $icalcomponent->setProperty( $k, $val);
                            $icalcomponent->setProperty( "PERCENT_COMPLETE", 100);                          
                            $icalcomponent->setProperty( "STATUS", "COMPLETED");                            
                        }
                    }
                    if ($e[1] == 7) {
                        $valarm = new valarm();
                        $valarm->setProperty( "ACTION", "DISPLAY");
                        $valarm->setProperty( "DESCRIPTION", $icalcomponent->getProperty( "SUMMARY" ));
                        $valarm->setProperty( "TRIGGER", "-PT0H".$val."M0S");
                        $icalcomponent->setComponent( $valarm );
                    }
                    if ($e[1] == 8) {
                        $valarm = new valarm();
                        $valarm->setProperty( "ACTION", "DISPLAY");
                        $valarm->setProperty( "DESCRIPTION", $icalcomponent->getProperty( "SUMMARY" ));
                        $valarm->setProperty( "TRIGGER", array("timestamp" => $val));
                        
                        $icalcomponent->setComponent( $valarm );
                    }
                    if ($e[1] == 9 && is_array($val)) {
                        foreach ($val as $att) {
                            $icalcomponent->setProperty( $k, $att->email, array("CN" => $att->name));
                        }
                    }
                    if ($e[1] == 10) {
                        require_once('z_RTF.php');
                        $rtfparser = new rtf();
                        $rtfparser->loadrtf(base64_decode($val));
                        $rtfparser->output("ascii");
                        $rtfparser->parse();
                        $icalcomponent->setProperty( $k, $rtfparser->out);
                    }
                    if ($e[1] == 11) {
                        debugLog("converttoical: completed is $val");
                        if ($val == "1") {
                            $icalcomponent->setProperty( "PERCENT_COMPLETE", 100);
                            $icalcomponent->setProperty( "STATUS", "COMPLETED");
                        }
                        else {
                            $icalcomponent->setProperty( "PERCENT_COMPLETE", 0);
                            $icalcomponent->setProperty( "STATUS", "NEEDS-ACTION");
                        }
                    }
                }
            }
        }
        return $icalcomponent;
    }

    function getdeletedexceptionobject($val) {
        $rtn = new SyncAppointment();
        $rtn->deleted = "1";
        if (is_array($val)) {
            $val = $this->makeGMTTime($val);
        } else {
            $val =  $this->parseDateToOutlook($val);
        }
        $rtn->exceptionstarttime = $val;
        
        return $rtn;
    }
};
?>
