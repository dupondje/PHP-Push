<?php

/***********************************************
* File      :   utils.php
* Project   :   Z-Push
* Descr     :
*
* Created   :   03.04.2008
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

// saves information about folder data for a specific device
function _saveFolderData($devid, $folders) {
    if (!is_array($folders) || empty ($folders))
        return false;

    $unique_folders = array ();

    foreach ($folders as $folder) {
        if (!isset($folder->type))
            continue;

        // don't save folder-ids for emails
        if ($folder->type == SYNC_FOLDER_TYPE_INBOX)
            continue;

        // no folder from that type    or the default folder
        if (!array_key_exists($folder->type, $unique_folders) || $folder->parentid == 0) {
            $unique_folders[$folder->type] = $folder->serverid;
        }
    }

    // Treo does initial sync for calendar and contacts too, so we need to fake
    // these folders if they are not supported by the backend
    if (!array_key_exists(SYNC_FOLDER_TYPE_APPOINTMENT, $unique_folders))
        $unique_folders[SYNC_FOLDER_TYPE_APPOINTMENT] = SYNC_FOLDER_TYPE_DUMMY;
    if (!array_key_exists(SYNC_FOLDER_TYPE_CONTACT, $unique_folders))
        $unique_folders[SYNC_FOLDER_TYPE_CONTACT] = SYNC_FOLDER_TYPE_DUMMY;

    if (!file_put_contents(STATE_DIR."/compat-$devid", serialize($unique_folders))) {
        debugLog("_saveFolderData: Data could not be saved!");
    }
}

// returns information about folder data for a specific device
function _getFolderID($devid, $class) {
    $filename = STATE_DIR."/compat-$devid";

    if (file_exists($filename)) {
        $arr = unserialize(file_get_contents($filename));

        if ($class == "Calendar")
            return $arr[SYNC_FOLDER_TYPE_APPOINTMENT];
        if ($class == "Contacts")
            return $arr[SYNC_FOLDER_TYPE_CONTACT];

    }

    return false;
}

/**
 * Function which converts a hex entryid to a binary entryid.
 * @param string @data the hexadecimal string
 */
function hex2bin($data) {
    return pack("H*", $data);
}

//if the ICS backend is loaded in CombinedBackend and Zarafa > 7
//STORE_SUPPORTS_UNICODE is true and the convertion will not be done
//for other backends.
function utf8_to_windows1252($string, $option = "", $force_convert = false)
{
    //if the store supports unicode return the string without converting it
    if (!$force_convert && defined('STORE_SUPPORTS_UNICODE') && STORE_SUPPORTS_UNICODE == true) return $string;

    if (function_exists("iconv")){
        return @iconv("UTF-8", "Windows-1252" . $option, $string);
    }else{
        return utf8_decode($string); // no euro support here
    }
}

function windows1252_to_utf8($string, $option = "", $force_convert = false)
{
    //if the store supports unicode return the string without converting it
    if (!$force_convert && defined('STORE_SUPPORTS_UNICODE') && STORE_SUPPORTS_UNICODE == true) return $string;

    if (function_exists("iconv")){
        return @iconv("Windows-1252", "UTF-8" . $option, $string);
    }else{
        return utf8_encode($string); // no euro support here
    }
}

function w2u($string) { return windows1252_to_utf8($string); }
function u2w($string) { return utf8_to_windows1252($string); }

function w2ui($string) { return windows1252_to_utf8($string, "//TRANSLIT"); }
function u2wi($string) { return utf8_to_windows1252($string, "//TRANSLIT"); }

/**
 * Truncate an UTF-8 encoded sting correctly
 *
 * If it's not possible to truncate properly, an empty string is returned
 *
 * @param string $string - the string
 * @param string $length - position where string should be cut
 * @return string truncated string
 */
function utf8_truncate($string, $length) {
    if (strlen($string) <= $length)
        return $string;

    while($length >= 0) {
        if ((ord($string[$length]) < 0x80) || (ord($string[$length]) >= 0xC0))
            return substr($string, 0, $length);

        $length--;
    }
    return "";
}


/**
 * Build an address string from the components
 *
 * @param string $street - the street
 * @param string $zip - the zip code
 * @param string $city - the city
 * @param string $state - the state
 * @param string $country - the country
 * @return string the address string or null
 */
function buildAddressString($street, $zip, $city, $state, $country) {
    $out = "";

    if (isset($country) && $street != "") $out = $country;

    $zcs = "";
    if (isset($zip) && $zip != "") $zcs = $zip;
    if (isset($city) && $city != "") $zcs .= (($zcs)?" ":"") . $city;
    if (isset($state) && $state != "") $zcs .= (($zcs)?" ":"") . $state;
    if ($zcs) $out = $zcs . "\r\n" . $out;

    if (isset($street) && $street != "") $out = $street . (($out)?"\r\n\r\n". $out: "") ;

    return ($out)?$out:null;
}

/**
 * Checks if the PHP-MAPI extension is available and in a requested version
 *
 * @param string $version - the version to be checked ("6.30.10-18495", parts or build number)
 * @return boolean installed version is superior to the checked strin
 */
function checkMapiExtVersion($version = "") {
    // compare build number if requested
    if (preg_match('/^\d+$/', $version) && strlen($version) > 3) {
        $vs = preg_split('/-/', phpversion("mapi"));
        return ($version <= $vs[1]);
    }

    if (extension_loaded("mapi")){
        if (version_compare(phpversion("mapi"), $version) == -1){
            return false;
        }
    }
    else
        return false;

    return true;
}

/**
 * Parses and returns an ecoded vCal-Uid from an
 * OL compatible GlobalObjectID
 *
 * @param string $olUid - an OL compatible GlobalObjectID
 * @return string the vCal-Uid if available in the olUid, else the original olUid as HEX
 */
function getICalUidFromOLUid($olUid){
    //check if "vCal-Uid" is somewhere in outlookid case-insensitive
    $icalUid = stristr($olUid, "vCal-Uid");
    if ($icalUid !== false) {
        //get the length of the ical id - go back 4 position from where "vCal-Uid" was found
        $begin = unpack("V", substr($olUid, strlen($icalUid) * (-1) - 4, 4));
        //remove "vCal-Uid" and packed "1" and use the ical id length
        return substr($icalUid, 12, ($begin[1] - 13));
    }
    return strtoupper(bin2hex($olUid));
}

/**
 * Checks the given UID if it is an OL compatible GlobalObjectID
 * If not, the given UID is encoded inside the GlobalObjectID
 *
 * @param string $icalUid - an appointment uid as HEX
 * @return string an OL compatible GlobalObjectID
 *
 */
function getOLUidFromICalUid($icalUid) {
    if (strlen($icalUid) <= 64) {
        $len = 13 + strlen($icalUid);
        $OLUid = pack("V", $len);
        $OLUid .= "vCal-Uid";
        $OLUid .= pack("V", 1);
        $OLUid .= $icalUid;
        return hex2bin("040000008200E00074C5B7101A82E0080000000000000000000000000000000000000000". bin2hex($OLUid). "00");
    }
    else
       return hex2bin($icalUid);
}

/**
 * Extracts the basedate of the GlobalObjectID and the RecurStartTime
 *
 * @param string $goid - OL compatible GlobalObjectID
 * @param long $recurStartTime - RecurStartTime
 * @return long basedate
 *
 */
function extractBaseDate($goid, $recurStartTime) {
    $hexbase = substr(bin2hex($goid), 32, 8);
    $day = hexdec(substr($hexbase, 6, 2));
    $month = hexdec(substr($hexbase, 4, 2));
    $year = hexdec(substr($hexbase, 0, 4));

    if ($day && $month && $year) {
        $h = $recurStartTime >> 12;
        $m = ($recurStartTime - $h * 4096) >> 6;
        $s = $recurStartTime - $h * 4096 - $m * 64;

        return gmmktime($h, $m, $s, $month, $day, $year);
    }
    else
        return false;
}


/**
 * Prints the Z-Push legal header to STDOUT
 * Using this function breaks ActiveSync synchronization
 *
 * @param string $additionalMessage - additional message to be displayed
 * @return
 *
 */
function printZPushLegal($message = "", $additionalMessage = "") {
    global $zpush_version;

    if ($message)
        $message = "<h3>". $message . "</h3>";
    if ($additionalMessage)
        $additionalMessage .= "<br>";

    $zpush_legal = <<<END
<html>
<header>
<title>Z-Push ActiveSync</title>
</header>
<body>
<font face="verdana">
<h2>Z-Push - Open Source ActiveSync</h2>
<b>Version $zpush_version</b><br>
$message $additionalMessage
<br><br>
More information about Z-Push can be found at:<br>
<a href="http://z-push.sf.net/">Z-Push homepage</a><br>
<a href="http://z-push.sf.net/download">Z-Push download page at BerliOS</a><br>
<a href="http://z-push.sf.net/tracker">Z-Push Bugtracker and Roadmap</a><br>
<br>
All modifications to this sourcecode must be published and returned to the community.<br>
Please see <a href="http://www.gnu.org/licenses/agpl-3.0.html">AGPLv3 License</a> for details.<br>
</font face="verdana">
</body>
</html>
END;

    header("Content-type: text/html");
    print $zpush_legal;
}


/**
Our own utf7_decode function because imap_utf7_decode converts a string
into ISO-8859-1 encoding which doesn't have euro sign (it will be converted
into two chars: [space](ascii 32) and "¬" ("not sign", ascii 172)). Also
php iconv function expects '+' as delimiter instead of '&' like in IMAP.

@param string $str IMAP folder name
@return string
*/
function zp_utf7_iconv_decode($string) {
    //do not alter string if there aren't any '&' or '+' chars because
    //it won't have any utf7-encoded chars and nothing has to be escaped.
    if (strpos($string, '&') === false && strpos($string, '+') === false ) return $string;

    //Get the string length and go back through it making the replacements
    //necessary
    $len = strlen($string) - 1;
    while ($len > 0) {
        //look for '&-' sequence and replace it with '&'
        if ($len > 0 && $string{($len-1)} == '&' && $string{$len} == '-') {
            $string = substr_replace($string, '&', $len - 1, 2);
            $len--; //decrease $len as this char has alreasy been processed
        }
        //search for '&' which weren't found in if clause above and
        //replace them with '+' as they mark an utf7-encoded char
        if ($len > 0 && $string{($len-1)} == '&') {
            $string = substr_replace($string, '+', $len - 1, 1);
            $len--; //decrease $len as this char has alreasy been processed
        }
        //finally "escape" all remaining '+' chars
        if ($len > 0 && $string{($len-1)} == '+') {
            $string = substr_replace($string, '+-', $len - 1, 1);
        }
        $len--;
    }
    return $string;
}


/**
Our own utf7_encode function because the string has to be converted from
standard UTF7 into modified UTF7 (aka UTF7-IMAP).

@param string $str IMAP folder name
@return string
*/
function zp_utf7_iconv_encode($string) {
    //do not alter string if there aren't any '&' or '+' chars because
    //it won't have any utf7-encoded chars and nothing has to be escaped.
    if (strpos($string, '&') === false && strpos($string, '+') === false ) return $string;

    //Get the string length and go back through it making the replacements
    //necessary
    $len = strlen($string) - 1;
    while ($len > 0) {
        //look for '&-' sequence and replace it with '&'
        if ($len > 0 && $string{($len-1)} == '+' && $string{$len} == '-') {
            $string = substr_replace($string, '+', $len - 1, 2);
            $len--; //decrease $len as this char has alreasy been processed
        }
        //search for '&' which weren't found in if clause above and
        //replace them with '+' as they mark an utf7-encoded char
        if ($len > 0 && $string{($len-1)} == '+') {
            $string = substr_replace($string, '&', $len - 1, 1);
            $len--; //decrease $len as this char has alreasy been processed
        }
        //finally "escape" all remaining '+' chars
        if ($len > 0 && $string{($len-1)} == '&') {
            $string = substr_replace($string, '&-', $len - 1, 1);
        }
        $len--;
    }
    return $string;
}

function zp_utf7_to_utf8($string) {
    if (function_exists("iconv")){
        return @iconv("UTF7", "UTF-8", $string);
    }
    return $string;
}

function zp_utf8_to_utf7($string) {
    if (function_exists("iconv")){
        return @iconv("UTF-8", "UTF7", $string);
    }
    return $string;
}
?>