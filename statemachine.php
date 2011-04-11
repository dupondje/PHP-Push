<?php
/***********************************************
* File      :   statemachine.php
* Project   :   Z-Push
* Descr     :   This class handles state requests;
*               Each differential mechanism can
*               store its own state information,
*               which is stored through the
*               state machine. SyncKey's are
*               of the  form {UUID}N, in which
*               UUID is allocated during the
*               first sync, and N is incremented
*               for each request to 'getNewSyncKey'.
*               A sync state is simple an opaque
*               string value that can differ
*               for each backend used - normally
*               a list of items as the backend has
*               sent them to the PIM. The backend
*               can then use this backend
*               information to compute the increments
*               with current data.
*
*               Old sync states are not deleted
*               until a sync state is requested.
*               At that moment, the PIM is
*               apparently requesting an update
*               since sync key X, so any sync
*               states before X are already on
*               the PIM, and can therefore be
*               removed. This algorithm is
*               automatically enforced by the
*               StateMachine class.
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


class StateMachine {
    // Gets the sync state for a specified sync key. Requesting a sync key also implies
    // that previous sync states for this sync key series are no longer needed, and the
    // state machine will tidy up these files.
    function getSyncState($synckey) {
        // No sync state for sync key '0'
        if($synckey == "0" || $synckey == "s0")
            return "";

        // Check if synckey is allowed
        if(!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $synckey, $matches)) {
            return false;
        }

        // Remember synckey GUID and ID
        $guid = $matches[1];
        $n = $matches[2];

        // Cleanup all older syncstates
        $dir = opendir( STATE_DIR);
        if(!$dir)
            return false;

        while($entry = readdir($dir)) {
            if(preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $entry, $matches)) {
                if($matches[1] == $guid && $matches[2] < $n) {
                    unlink( STATE_DIR . "/$entry");
                }
            }
        }

        // Read current sync state
        $filename = STATE_DIR . "/$synckey";

        if(file_exists($filename))
            return file_get_contents(STATE_DIR . "/$synckey");
        else return false;
    }

    // Gets the new sync key for a specified sync key. You must save the new sync state
    // under this sync key when done sync'ing (by calling setSyncState);
    function getNewSyncKey($synckey) {
        if(!isset($synckey) || $synckey == "0") {
            return "{" . $this->uuid() . "}" . "1";
        } else {
            if(preg_match('/^s{0,1}\{([a-fA-F0-9-]+)\}([0-9]+)$/', $synckey, $matches)) {
                $n = $matches[2];
                $n++;
                return "{" . $matches[1] . "}" . $n;
            } else return false;
        }
    }

    // Writes the sync state to a new synckey
    function setSyncState($synckey, $syncstate) {
        // Check if synckey is allowed
        if(!preg_match('/^s{0,1}\{[0-9A-Za-z-]+\}[0-9]+$/', $synckey)) {
            return false;
        }

        return file_put_contents(STATE_DIR . "/$synckey", $syncstate);
    }

    function uuid()
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                    mt_rand( 0, 0x0fff ) | 0x4000,
                    mt_rand( 0, 0x3fff ) | 0x8000,
                    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
    }
};

?>
