<?php
/***********************************************
* File      :   backend.php
* Project   :   Z-Push
* Descr     :   This is what C++ people
*               (and PHP5) would call an
*               abstract class. All backend
*               modules should adhere to this
*               specification. All communication
*               with this module is done via
*               the Sync* object types, the
*               backend module itself is
*               responsible for converting any
*               necessary types and formats.
*
*               If you wish to implement a new
*               backend, all you need to do is
*               to subclass the following class,
*               and place the subclassed file in
*               the backend/ directory. You can
*               then use your backend by
*               specifying it in the config.php file
*
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

define('EXPORT_HIERARCHY', 1);
define('EXPORT_CONTENTS', 2);

define('BACKEND_DISCARD_DATA', 1);

class ImportContentsChanges {
    function ImportMessageChange($id, $message) {}

    function ImportMessageDeletion($message) {}

    function ImportMessageReadStateChange($message) {}

    function ImportMessageMove($message) {}
};

class ImportHierarchyChanges {
    function ImportFolderChange($folder) {}

    function ImportFolderDeletion($folder) {}
};

class ExportChanges {
    // Exports (returns) changes since '$synckey' as an array of Sync* objects. $flags
    // can be EXPORT_HIERARCHY or EXPORT_CONTENTS. $restrict contains the restriction on which
    // messages should be filtered. Synckey is updated via reference (!)
    function ExportChanges($importer, $folderid, $restrict, $syncstate, $flags) {}
};

class Backend {
    var $hierarchyimporter;
    var $contentsimporter;
    var $exporter;

    // Returns TRUE if the logon succeeded, FALSE if not
    function Logon($username, $domain, $password) {}

    // called before closing connection
    function Logoff() {}

    // Returns an array of SyncFolder types for the entire folder hierarchy
    // on the server (the array itself is flat, but refers to parents via the 'parent'
    // property)
    function GetHierarchy() {}

    // Called when a message has to be sent and the message needs to be saved to the 'sent items'
    // folder
    function SendMail($rfc822, $forward = false, $reply = false, $parent = false) {}
};

?>