<?php
/***********************************************
* File      :   compat.php
* Project   :   Z-Push
* Descr     :   Help function for files
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

if (!function_exists("file_put_contents")) {
    function file_put_contents($n,$d) {
        $f=@fopen($n,"w");
        if (!$f) {
            return false;
        } else {
            fwrite($f,$d);
            fclose($f);
            return true;
        }
    }
}

if (!function_exists("array_change_key_case")) {
    if (!defined('CASE_LOWER')) define('CASE_LOWER', 0);
    if (!defined('CASE_UPPER')) define('CASE_UPPER', 1);

    function array_change_key_case($array, $target) {
        if(!is_array($array)) return FALSE;
        $output = array();
        foreach($array as $key => $value){
            $key2 = (is_string($key)) ? (($target == CASE_UPPER)?strtoupper($key):strtolower($key)):$key;
            $output[$key2] = $value;
        }
        return $output;
    }
}


if (!function_exists("quoted_printable_encode")) {
  /**
  * Process a string to fit the requirements of RFC2045 section 6.7. Note that
  * this works, but replaces more characters than the minimum set. For readability
  * the spaces and CRLF pairs aren't encoded though.
  *
  * @see http://www.php.net/manual/en/function.quoted-printable-decode.php#89417
  */
    function quoted_printable_encode($string) {
        return preg_replace('/[^\r\n]{73}[^=\r\n]{2}/', "$0=\n", str_replace(array('%20', '%0D%0A', '%'), array(' ', "\r\n", '='), rawurlencode($string)));
    }
}

// iPhone defines standard summer time information for current year only,
// starting with time change in February. Dates from the 1st January until
// the time change are undefined and the server uses GMT or its current time.
// The function parses the ical attachment and replaces DTSTART one year back
// in VTIMEZONE section if the event takes place in this undefined time.
// See also http://developer.berlios.de/mantis/view.php?id=311
function icalTimezoneFix($ical) {

    $stdTZpos = strpos($ical, "BEGIN:STANDARD");
    $dltTZpos = strpos($ical, "BEGIN:DAYLIGHT");

    // do not try to fix ical if TZ definitions are not set
    if ($stdTZpos === false || $dltTZpos === false)
        return $ical;

    $eventDate = substr($ical, (strpos($ical, ":", strpos($ical, "DTSTART", strpos($ical, "BEGIN:VEVENT")))+1), 8);
    $posStd = strpos($ical, "DTSTART:", $stdTZpos) + strlen("DTSTART:");
    $posDst = strpos($ical, "DTSTART:",$dltTZpos) + strlen("DTSTART:");
    $beginStandard = substr($ical, $posStd , 8);
    $beginDaylight = substr($ical, $posDst , 8);
    if (($eventDate < $beginStandard) && ($eventDate < $beginDaylight) ) {
        debugLog("icalTimezoneFix for event on $eventDate, standard:$beginStandard, daylight:$beginDaylight");
        $year = intval(date("Y")) - 1;
        $ical = substr_replace($ical, $year, (($beginStandard < $beginDaylight) ? $posDst : $posStd), strlen($year));
    }

    return $ical;
}
?>