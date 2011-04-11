<?php
/***********************************************
* File      :   memimporter.php
* Project   :   Z-Push
* Descr     :   Classes that collect changes
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

class ImportContentsChangesMem extends ImportContentsChanges {
    var $_changes;
    var $_deletions;

    function ImportContentsChangesMem() {
        $this->_changes = array();
        $this->_deletions = array();
    }

    function ImportMessageChange($id, $message) {
        $this->_changes[] = $id;
        return true;
    }

    function ImportMessageDeletion($id) {
        $this->_deletions[] = $id;
        return true;
    }

    function ImportMessageReadFlag($message) { return true; }

    function ImportMessageMove($message) { return true; }

    function isChanged($id) {
        return in_array($id, $this->_changes);
    }

    function isDeleted($id) {
        return in_array($id, $this->_deletions);
    }

};

// This simply collects all changes so that they can be retrieved later, for
// statistics gathering for example
class ImportHierarchyChangesMem extends ImportHierarchyChanges {
    var $changed;
    var $deleted;
    var $count;
    var $foldercache;

    function ImportHierarchyChangesMem($foldercache) {
    	$this->foldercache = $foldercache;
        $this->changed = array();
        $this->deleted = array();
        $this->count = 0;

        return true;
    }

    function ImportFolderChange($folder) {
    	// The HierarchyExporter exports all kinds of changes.
    	// Frequently these changes are not relevant for the mobiles,
    	// as something changes but the relevant displayname and parentid
    	// stay the same. These changes will be dropped and not sent
    	if (array_key_exists($folder->serverid, $this->foldercache) &&
    	    $this->foldercache[$folder->serverid]->displayname == $folder->displayname &&
            $this->foldercache[$folder->serverid]->parentid == $folder->parentid &&
            $this->foldercache[$folder->serverid]->type == $folder->type
           ) {
            debugLog("Change for folder '".$folder->displayname."' will not be sent as modification is not relevant");
            return true;
    	}

        array_push($this->changed, $folder);
        $this->count++;
        // temporarily add/update the folder to the cache so changes are not sent twice
        $this->foldercache[$folder->serverid] = $folder;
        return true;
    }

    function ImportFolderDeletion($id) {
        array_push($this->deleted, $id);

        $this->count++;

        return true;
    }
};

?>