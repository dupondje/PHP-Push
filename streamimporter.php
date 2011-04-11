<?php
/***********************************************
* File      :   streamimporter.php
* Project   :   Z-Push
* Descr     :   Stream import classes
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

// We don't support caching changes for messages
class ImportContentsChangesStream {
    var $_encoder;
    var $_type;
    var $_seenObjects;

    function ImportContentsChangesStream(&$encoder, $type) {
        $this->_encoder = &$encoder;
        $this->_type = $type;
        $this->_seenObjects = array();
    }

    function ImportMessageChange($id, $message) {
        if(strtolower(get_class($message)) != $this->_type)
            return true; // ignore other types

        // prevent sending the same object twice in one request
        if (in_array($id, $this->_seenObjects)) {
        	debugLog("Object $id discarded! Object already sent in this request.");
        	return true;
        }

        $this->_seenObjects[] = $id;

        if ($message->flags === false || $message->flags === SYNC_NEWMESSAGE)
            $this->_encoder->startTag(SYNC_ADD);
        else
            $this->_encoder->startTag(SYNC_MODIFY);

        $this->_encoder->startTag(SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->startTag(SYNC_DATA);
        $message->encode($this->_encoder);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    function ImportMessageDeletion($id) {
        $this->_encoder->startTag(SYNC_REMOVE);
        $this->_encoder->startTag(SYNC_SERVERENTRYID);
        $this->_encoder->content($id);
        $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    function ImportMessageReadFlag($id, $flags) {
        if($this->_type != "syncmail")
            return true;
        $this->_encoder->startTag(SYNC_MODIFY);
            $this->_encoder->startTag(SYNC_SERVERENTRYID);
                $this->_encoder->content($id);
            $this->_encoder->endTag();
            $this->_encoder->startTag(SYNC_DATA);
                $this->_encoder->startTag(SYNC_POOMMAIL_READ);
                    $this->_encoder->content($flags);
                $this->_encoder->endTag();
            $this->_encoder->endTag();
        $this->_encoder->endTag();

        return true;
    }

    function ImportMessageMove($message) {
        return true;
    }
};

class ImportHierarchyChangesStream {

    function ImportHierarchyChangesStream() {
        return true;
    }

    function ImportFolderChange($folder) {
        return true;
    }

    function ImportFolderDeletion($folder) {
        return true;
    }
};

?>