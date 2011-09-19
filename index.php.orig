<?php
/***********************************************
* File      :   index.php
* Project   :   Z-Push
* Descr     :   This is the entry point
*               through which all requests
*               are called.
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

ob_start(false, 1048576);

include_once('zpushdefs.php');
include_once("config.php");
include_once("proto.php");
include_once("request.php");
include_once("debug.php");
include_once("compat.php");
include_once("version.php");

// Attempt to set maximum execution time
ini_set('max_execution_time', SCRIPT_TIMEOUT);
set_time_limit(SCRIPT_TIMEOUT);

ini_set('memory_limit', '256M');

$input = fopen("php://input", "r");
$output = fopen("php://output", "w+");

// The script must always be called with authorisation info
if(!isset($_SERVER['PHP_AUTH_PW'])) {
    debugLog("Start");
    debugLog("Z-Push version: $zpush_version");
    debugLog("Client IP: ". $_SERVER['REMOTE_ADDR']);
    header("WWW-Authenticate: Basic realm=\"ZPush\"");
    header("HTTP/1.1 401 Unauthorized");
    printZPushLegal("Access denied. Please send authorisation information");
    debugLog("Access denied: no password sent.");
    debugLog("end");
    debugLog("--------");
    return;
}

// split username & domain if received as one
global $auth_user;
$pos = strrpos($_SERVER['PHP_AUTH_USER'], '\\');
if($pos === false){
    $auth_user = $_SERVER['PHP_AUTH_USER'];
    $auth_domain = '';
}else{
    $auth_domain = substr($_SERVER['PHP_AUTH_USER'],0,$pos);
    $auth_user = substr($_SERVER['PHP_AUTH_USER'],$pos+1);
}
$auth_pw = $_SERVER['PHP_AUTH_PW'];

debugLog("Start");
debugLog("Z-Push version: $zpush_version");
debugLog("Client IP: ". $_SERVER['REMOTE_ADDR']);

$cmd = $user = $devid = $devtype = "";

// Parse the standard GET parameters
if(isset($_GET["Cmd"]))
    $cmd = $_GET["Cmd"];
if(isset($_GET["User"]))
    $user = $_GET["User"];
if(isset($_GET["DeviceId"]))
    $devid = $_GET["DeviceId"];
if(isset($_GET["DeviceType"]))
    $devtype = $_GET["DeviceType"];

// The GET parameters are required
if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(!isset($user) || !isset($devid) || !isset($devtype)) {
        print("Your device requested the Z-Push URL without the required GET parameters");
        return;
    }
}

// Get the request headers so we can get the AS headers
$requestheaders = array_change_key_case(apache_request_headers(), CASE_LOWER);

global $protocolversion, $policykey, $useragent;
$protocolversion = (isset($requestheaders["ms-asprotocolversion"]))? $requestheaders["ms-asprotocolversion"] : "1.0";
$policykey = (isset($requestheaders["x-ms-policykey"]))? $requestheaders["x-ms-policykey"] : 0;
$useragent = (isset($requestheaders["user-agent"]))? $requestheaders["user-agent"] : "unknown";

debugLog("Client supports version " . $protocolversion);

// Load our backend driver
$backend_dir = opendir(BASE_PATH . "/backend");
while($entry = readdir($backend_dir)) {
    $subdirfile = BASE_PATH . "/backend/" . $entry . "/" . $entry . ".php";

    if(substr($entry,0,1) == "." || (substr($entry,-3) != "php" && !is_file($subdirfile)))
        continue;

    // do not load Zarafa backend if PHP-MAPI is unavailable
    if (!function_exists("mapi_logon") && ($entry == "ics.php"))
        continue;

    // do not load Kolab backend if not a Kolab system
    if (! file_exists('Horde/Kolab/Kolab_Zpush/lib/kolabActivesyncData.php') && ($entry == "kolab"))
        continue;

    if (is_file($subdirfile))
        $entry = $entry . "/" . $entry . ".php";

    include_once(BASE_PATH . "/backend/" . $entry);
}

// Initialize our backend
eval('$backend = new '.$BACKEND_PROVIDER.'($BACKEND_CONFIG);');
#$backend = new $BACKEND_PROVIDER();

if($backend->Logon($auth_user, $auth_domain, $auth_pw) == false) {
    header("HTTP/1.1 401 Unauthorized");
    header("WWW-Authenticate: Basic realm=\"ZPush\"");
    printZPushLegal("Access denied. Username or password incorrect.");
    debugLog("Access denied: backend logon failed.");
    debugLog("end");
    debugLog("--------");
    return;
}

// $user is usually the same as the PHP_AUTH_USER. This allows you to sync the 'john' account if you
// have sufficient privileges as user 'joe'.
if($backend->Setup($user, $devid, $protocolversion) == false) {
    header("HTTP/1.1 401 Unauthorized");
    header("WWW-Authenticate: Basic realm=\"ZPush\"");
    printZPushLegal("Access denied or user '$user' unknown.");
    debugLog("Access denied: backend setup failed.");
    debugLog("end");
    debugLog("--------");
    return;
}

// check policy header
if (PROVISIONING === true && $_SERVER["REQUEST_METHOD"] == 'POST' && $cmd != 'Ping' && $cmd != 'Provision' &&
    $backend->CheckPolicy($policykey, $devid) != SYNC_PROVISION_STATUS_SUCCESS &&
    (LOOSE_PROVISIONING === false ||
    (LOOSE_PROVISIONING === true && isset($requestheaders["X-MS-PolicyKey"])))) {

    header("HTTP/1.1 449 Retry after sending a PROVISION command");
    header("MS-Server-ActiveSync: 6.5.7638.1");
    header("MS-ASProtocolVersions: 1.0,2.0,2.1,2.5");
    header("MS-ASProtocolCommands: Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,Provision,ResolveRecipients,ValidateCert,Search,Ping");
    header("Cache-Control: private");
    debugLog("POST cmd $cmd denied: Retry after sending a PROVISION command");
    debugLog("end");
    debugLog("--------");
    return;
}

// Do the actual request
switch($_SERVER["REQUEST_METHOD"]) {
    case 'OPTIONS':
        header("MS-Server-ActiveSync: 6.5.7638.1");
        header("MS-ASProtocolVersions: 1.0,2.0,2.1,2.5");
        header("MS-ASProtocolCommands: Sync,SendMail,SmartForward,SmartReply,GetAttachment,GetHierarchy,CreateCollection,DeleteCollection,MoveCollection,FolderSync,FolderCreate,FolderDelete,FolderUpdate,MoveItems,GetItemEstimate,MeetingResponse,ResolveRecipients,ValidateCert,Provision,Search,Ping");
        debugLog("Options request");
        break;
    case 'POST':
        header("MS-Server-ActiveSync: 6.5.7638.1");
        debugLog("POST cmd: $cmd");
        // Do the actual request
        if(!HandleRequest($backend, $cmd, $devid, $protocolversion)) {
            // Request failed. Try to output some kind of error information. We can only do this if
            // output had not started yet. If it has started already, we can't show the user the error, and
            // the device will give its own (useless) error message.
            if(!headers_sent())
                printZPushLegal("Error rocessing command <i>$cmd</i> from your PDA.", "Here is the debug output:<br><pre>". getDebugInfo() . "</pre>");
        }
        break;
    case 'GET':
        debugLog("GET request from agent: ". $useragent);
        printZPushLegal("GET not supported", "This is the z-push location and can only be accessed by Microsoft ActiveSync-capable devices.");
        break;
}


$len = ob_get_length();
$data = ob_get_contents();

ob_end_clean();

// Unfortunately, even though zpush can stream the data to the client
// with a chunked encoding, using chunked encoding also breaks the progress bar
// on the PDA. So we de-chunk here and just output a content-length header and
// send it as a 'normal' packet. If the output packet exceeds 1MB (see ob_start)
// then it will be sent as a chunked packet anyway because PHP will have to flush
// the buffer.

header("Content-Length: $len");
print $data;

// destruct backend after all data is on the stream
$backend->Logoff();

debugLog("end");
debugLog("--------");
?>
