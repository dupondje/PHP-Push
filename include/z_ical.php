<?php
/***********************************************
* File      :   z_ical.php
* Project   :   Z-Push
* Descr     :
*
* Created   :   01.12.2008
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

class ZPush_ical{
    function ZPush_ical($store){
        $this->_store = $store;
    }

    /*
    * Function reads calendar part and puts mapi properties into an array.
    */
    function extractProps($ical, &$mapiprops) {
        //mapping between partstat in ical and MAPI Meeting Response classes as well as icons
        $aClassMap = array(
            "ACCEPTED"          => array("class" => "IPM.Schedule.Meeting.Resp.Pos", "icon" => 0x405),
            "DECLINED"          => array("class" => "IPM.Schedule.Meeting.Resp.Neg", "icon" => 0x406),
            "TENTATIVE"         => array("class" => "IPM.Schedule.Meeting.Resp.Tent", "icon" => 0x407),
            "NEEDS-ACTION"      => array("class" => "IPM.Schedule.Meeting.Request", "icon" => 0x404), //iphone
            "REQ-PARTICIPANT"   => array("class" => "IPM.Schedule.Meeting.Request", "icon" => 0x404), //nokia
        );

        $aical = preg_split("/[\n]/", $ical);
        $elemcount = count($aical);
        $i=0;
        $nextline = $aical[0];
        //last element is empty
        while ($i < $elemcount - 1) {
            $line = $nextline;
            $nextline = $aical[$i+1];

            //if a line starts with a space or a tab it belongs to the previous line
            while (strlen($nextline) > 0 && ($nextline{0} == " " || $nextline{0} == "\t")) {
                $line = rtrim($line) . substr($nextline, 1);
                $nextline = $aical[++$i + 1];
            }
            $line = rtrim($line);

            switch (strtoupper($line)) {
                case "BEGIN:VCALENDAR":
                case "BEGIN:VEVENT":
                case "END:VEVENT":
                case "END:VCALENDAR":
                    break;
                default:
                    unset ($field, $data, $prop_pos, $property);
                    if (ereg ("([^:]+):(.*)", $line, $line)){
                        $field = $line[1];
                        $data = $line[2];
                        $property = $field;
                        $prop_pos = strpos($property,';');
                        if ($prop_pos !== false) $property = substr($property, 0, $prop_pos);
                        $property = strtoupper($property);

                        switch ($property) {
                            case 'DTSTART':
                                $data = $this->getTimestampFromStreamerDate($data);
                                $namedStartTime = GetPropIDFromString($this->_store, "PT_SYSTIME:{00062002-0000-0000-C000-000000000046}:0x820d");
                                $mapiprops[$namedStartTime] = $data;
                                $namedCommonStart = GetPropIDFromString($this->_store, "PT_SYSTIME:{00062008-0000-0000-C000-000000000046}:0x8516");
                                $mapiprops[$namedCommonStart] = $data;
                                $clipStart = GetPropIDFromString($this->_store, "PT_SYSTIME:{00062002-0000-0000-C000-000000000046}:0x8235");
                                $mapiprops[$clipStart] = $data;
                                $mapiprops[PR_START_DATE] = $data;
                                break;

                            case 'DTEND':
                                $data = $this->getTimestampFromStreamerDate($data);
                                $namedEndTime = GetPropIDFromString($this->_store, "PT_SYSTIME:{00062002-0000-0000-C000-000000000046}:0x820e");
                                $mapiprops[$namedEndTime] = $data;
                                $namedCommonEnd = GetPropIDFromString($this->_store, "PT_SYSTIME:{00062008-0000-0000-C000-000000000046}:0x8517");
                                $mapiprops[$namedCommonEnd] = $data;
                                $clipEnd = GetPropIDFromString($this->_store, "PT_SYSTIME:{00062002-0000-0000-C000-000000000046}:0x8236");
                                $mapiprops[$clipEnd] = $data;
                                $mapiprops[PR_END_DATE] = $data;
                                break;

                            case 'UID':
                                $goid = GetPropIDFromString($this->_store, "PT_BINARY:{6ED8DA90-450B-101B-98DA-00AA003F1305}:0x3");
                                $goid2 = GetPropIDFromString($this->_store, "PT_BINARY:{6ED8DA90-450B-101B-98DA-00AA003F1305}:0x23");
                                $mapiprops[$goid] = $mapiprops[$goid2] = getOLUidFromICalUid($data);
                                break;

                            case 'ATTENDEE':
                                $fields = explode(";", $field);
                                foreach ($fields as $field) {
                                    $prop_pos     = strpos($field, '=');
                                    if ($prop_pos !== false) {
                                        switch (substr($field, 0, $prop_pos)) {
                                            case 'PARTSTAT'    : $partstat = substr($field, $prop_pos+1); break;
                                            case 'CN'        : $cn = substr($field, $prop_pos+1); break;
                                            case 'ROLE'        : $role = substr($field, $prop_pos+1); break;
                                            case 'RSVP'        : $rsvp = substr($field, $prop_pos+1); break;
                                        }
                                    }
                                }
                                if (isset($partstat) && isset($aClassMap[$partstat]) &&

                                   (!isset($mapiprops[PR_MESSAGE_CLASS]) || $mapiprops[PR_MESSAGE_CLASS] == "IPM.Schedule.Meeting.Request")) {
                                    $mapiprops[PR_MESSAGE_CLASS] = $aClassMap[$partstat]['class'];
                                    $mapiprops[PR_ICON_INDEX] = $aClassMap[$partstat]['icon'];
                                }
                                // START ADDED dw2412 to support meeting requests on HTC Android Mail App
                                elseif (isset($role) && isset($aClassMap[$role]) &&
                                   (!isset($mapiprops[PR_MESSAGE_CLASS]) || $mapiprops[PR_MESSAGE_CLASS] == "IPM.Schedule.Meeting.Request")) {
                                    $mapiprops[PR_MESSAGE_CLASS] = $aClassMap[$role]['class'];
                                    $mapiprops[PR_ICON_INDEX] = $aClassMap[$role]['icon'];
                                }
                                // END ADDED dw2412 to support meeting requests on HTC Android Mail App
                                if (!isset($cn)) $cn = "";
                                $data         = str_replace ("MAILTO:", "", $data);
                                $attendee[] = array ('name' => stripslashes($cn), 'email' => stripslashes($data));
                                break;

                            case 'ORGANIZER':
                                $field          = str_replace("ORGANIZER;CN=", "", $field);
                                $data          = str_replace ("MAILTO:", "", $data);
                                $organizer[] = array ('name' => stripslashes($field), 'email' => stripslashes($data));
                                break;

                            case 'LOCATION':
                                $data = str_replace("\\n", "<br />", $data);
                                $data = str_replace("\\t", "&nbsp;", $data);
                                $data = str_replace("\\r", "<br />", $data);
                                $data = stripslashes($data);
                                $namedLocation = GetPropIDFromString($this->_store, "PT_STRING8:{00062002-0000-0000-C000-000000000046}:0x8208");
                                $mapiprops[$namedLocation] = $data;
                                $tneflocation = GetPropIDFromString($this->_store, "PT_STRING8:{6ED8DA90-450B-101B-98DA-00AA003F1305}:0x2");
                                $mapiprops[$tneflocation] = $data;
                                break;
                        }
                    }
                    break;
            }
            $i++;

        }
        $useTNEF = GetPropIDFromString($this->_store, "PT_BOOLEAN:{00062008-0000-0000-C000-000000000046}:0x8582");
        $mapiprops[$useTNEF] = true;
    }

    /*
     * Converts an YYYYMMDDTHHMMSSZ kind of string into an unixtimestamp
     *
     * @param string $data
     * @return long
     */
    function getTimestampFromStreamerDate ($data) {
        $data = str_replace('Z', '', $data);
        $data = str_replace('T', '', $data);

        preg_match ('/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})/', $data, $regs);
        if ($regs[1] < 1970) {
            $regs[1] = '1971';
        }

        if (!isset($regs[4]) || !is_numeric($regs[4])) $regs[4] = 0;
        if (!isset($regs[5]) || !is_numeric($regs[5])) $regs[5] = 0;
        if (!isset($regs[6]) || !is_numeric($regs[6])) $regs[6] = 0;

        return gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
    }
}

?>