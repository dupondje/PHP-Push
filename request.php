<?php
/***********************************************
* File      :   request.php
* Project   :   Z-Push
* Descr     :   This file contains the actual
*               request handling routines.
*               The request handlers are optimised
*               so that as little as possible
*               data is kept-in-memory, and all
*               output data is directly streamed
*               to the client, while also streaming
*               input data from the client.
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

include_once("proto.php");
include_once("wbxml.php");
include_once("statemachine.php");
include_once("backend/backend.php");
include_once("memimporter.php");
include_once("streamimporter.php");
include_once("zpushdtd.php");
include_once("zpushdefs.php");
include_once("include/utils.php");

function GetObjectClassFromFolderClass($folderclass)
{
    $classes = array ( "Email" => "syncmail", "Contacts" => "synccontact", "Calendar" => "syncappointment", "Tasks" => "synctask" );

    return $classes[$folderclass];
}

function HandleMoveItems($backend, $protocolversion) {
    global $zpushdtd;
    global $input, $output;

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    if(!$decoder->getElementStartTag(SYNC_MOVE_MOVES))
        return false;

    $moves = array();
    while($decoder->getElementStartTag(SYNC_MOVE_MOVE)) {
        $move = array();
        if($decoder->getElementStartTag(SYNC_MOVE_SRCMSGID)) {
            $move["srcmsgid"] = $decoder->getElementContent();
            if(!$decoder->getElementEndTag())
                break;
        }
        if($decoder->getElementStartTag(SYNC_MOVE_SRCFLDID)) {
            $move["srcfldid"] = $decoder->getElementContent();
            if(!$decoder->getElementEndTag())
                break;
        }
        if($decoder->getElementStartTag(SYNC_MOVE_DSTFLDID)) {
            $move["dstfldid"] = $decoder->getElementContent();
            if(!$decoder->getElementEndTag())
                break;
        }
        array_push($moves, $move);

        if(!$decoder->getElementEndTag())
            return false;
    }

    if(!$decoder->getElementEndTag())
        return false;

    $encoder->StartWBXML();

    $encoder->startTag(SYNC_MOVE_MOVES);

    foreach($moves as $move) {
        $encoder->startTag(SYNC_MOVE_RESPONSE);
        $encoder->startTag(SYNC_MOVE_SRCMSGID);
        $encoder->content($move["srcmsgid"]);
        $encoder->endTag();

        $importer = $backend->GetContentsImporter($move["srcfldid"]);
        $result = $importer->ImportMessageMove($move["srcmsgid"], $move["dstfldid"]);
        // We discard the importer state for now.

        $encoder->startTag(SYNC_MOVE_STATUS);
        $encoder->content($result ? 3 : 1);
        $encoder->endTag();

        $encoder->startTag(SYNC_MOVE_DSTMSGID);
        $encoder->content(is_string($result)?$result:$move["srcmsgid"]);
        $encoder->endTag();
        $encoder->endTag();
    }

    $encoder->endTag();
    return true;
}

function HandleNotify($backend, $protocolversion) {
    global $zpushdtd;
    global $input, $output;

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    if(!$decoder->getElementStartTag(SYNC_AIRNOTIFY_NOTIFY))
        return false;

    if(!$decoder->getElementStartTag(SYNC_AIRNOTIFY_DEVICEINFO))
        return false;

    if(!$decoder->getElementEndTag())
        return false;

    if(!$decoder->getElementEndTag())
        return false;

    $encoder->StartWBXML();

    $encoder->startTag(SYNC_AIRNOTIFY_NOTIFY);
    {
        $encoder->startTag(SYNC_AIRNOTIFY_STATUS);
        $encoder->content(1);
        $encoder->endTag();

        $encoder->startTag(SYNC_AIRNOTIFY_VALIDCARRIERPROFILES);
        $encoder->endTag();
    }

    $encoder->endTag();

    return true;

}

// Handle GetHierarchy method - simply returns current hierarchy of all folders
function HandleGetHierarchy($backend, $protocolversion, $devid) {
    global $zpushdtd;
    global $output;

    // Input is ignored, no data is sent by the PIM
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    $folders = $backend->GetHierarchy();

    if(!$folders)
        return false;

    // save folder-ids for fourther syncing
    _saveFolderData($devid, $folders);

    $encoder->StartWBXML();
    $encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERS);

    foreach ($folders as $folder) {
        $encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDER);
        $folder->encode($encoder);
        $encoder->endTag();
    }

    $encoder->endTag();
    return true;
}

// Handles a 'FolderSync' method - receives folder updates, and sends reply with
// folder changes on the server
function HandleFolderSync($backend, $protocolversion) {
    global $zpushdtd;
    global $input, $output;

    // Maps serverid -> clientid for items that are received from the PIM
    $map = array();

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    // Parse input

    if(!$decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_FOLDERSYNC))
        return false;

    if(!$decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_SYNCKEY))
        return false;

    $synckey = $decoder->getElementContent();

    if(!$decoder->getElementEndTag())
        return false;

    // First, get the syncstate that is associated with this synckey
    $statemachine = new StateMachine();

    // The state machine will discard any sync states before this one, as they are no
    // longer required
    $syncstate = $statemachine->getSyncState($synckey);

    // additional information about already seen folders
    $sfolderstate = $statemachine->getSyncState("s".$synckey);

    if (!$sfolderstate) {
        $foldercache = array();
        if ($sfolderstate === false)
            debugLog("Error: FolderChacheState for state 's". $synckey ."' not found. Reinitializing...");
    }
    else {
    	$foldercache = unserialize($sfolderstate);

    	// transform old seenfolder array
    	if (array_key_exists("0", $foldercache)) {
    		$tmp = array();
    		foreach($foldercache as $s) $tmp[$s] = new SyncFolder();
    		$foldercache = $tmp;
    	}
    }

    // We will be saving the sync state under 'newsynckey'
    $newsynckey = $statemachine->getNewSyncKey($synckey);
    $changes = false;

    if($decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_CHANGES)) {
        // Ignore <Count> if present
        if($decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_COUNT)) {
            $decoder->getElementContent();
            if(!$decoder->getElementEndTag())
                return false;
        }

        // Process the changes (either <Add>, <Modify>, or <Remove>)
        $element = $decoder->getElement();

        if($element[EN_TYPE] != EN_TYPE_STARTTAG)
            return false;

        while(1) {
            $folder = new SyncFolder();
            if(!$folder->decode($decoder))
                break;

            // Configure importer with last state
            $importer = $backend->GetHierarchyImporter();
            $importer->Config($syncstate);


            switch($element[EN_TAG]) {
                case SYNC_ADD:
                case SYNC_MODIFY:
                    $serverid = $importer->ImportFolderChange($folder);
                    // add folder to the serverflags
                    $foldercache[$serverid] = $folder;
                    $changes = true;
                    break;
                case SYNC_REMOVE:
                    $serverid = $importer->ImportFolderDeletion($folder);
                    $changes = true;
                    // remove folder from the folderchache
                    if (array_key_exists($serverid, $foldercache))
                        unset($foldercache[$serverid]);
                    break;
            }

            if($serverid)
                $map[$serverid] = $folder->clientid;
        }

        if(!$decoder->getElementEndTag())
            return false;
    }

    if(!$decoder->getElementEndTag())
        return false;

    // We have processed incoming foldersync requests, now send the PIM
    // our changes

    // The MemImporter caches all imports in-memory, so we can send a change count
    // before sending the actual data. As the amount of data done in this operation
    // is rather low, this is not memory problem. Note that this is not done when
    // sync'ing messages - we let the exporter write directly to WBXML.
    $importer = new ImportHierarchyChangesMem($foldercache);

    // Request changes from backend, they will be sent to the MemImporter passed as the first
    // argument, which stores them in $importer. Returns the new sync state for this exporter.
    $exporter = $backend->GetExporter();

    $exporter->Config($importer, false, false, $syncstate, 0, 0);

    while(is_array($exporter->Synchronize()));

    // Output our WBXML reply now
    $encoder->StartWBXML();

    $encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERSYNC);
    {
        $encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
        $encoder->content(1);
        $encoder->endTag();

        $encoder->startTag(SYNC_FOLDERHIERARCHY_SYNCKEY);
        // only send new synckey if changes were processed or there are outgoing changes
        $encoder->content((($changes || $importer->count > 0)?$newsynckey:$synckey));
        $encoder->endTag();

        $encoder->startTag(SYNC_FOLDERHIERARCHY_CHANGES);
        {
            $encoder->startTag(SYNC_FOLDERHIERARCHY_COUNT);
            $encoder->content($importer->count);
            $encoder->endTag();

            if(count($importer->changed) > 0) {
                foreach($importer->changed as $folder) {
                	// send a modify flag if the folder is already known on the device
                	if (isset($folder->serverid) && array_key_exists($folder->serverid, $foldercache) !== false)
                        $encoder->startTag(SYNC_FOLDERHIERARCHY_UPDATE);
                	else
                        $encoder->startTag(SYNC_FOLDERHIERARCHY_ADD);
                    $foldercache[$folder->serverid] = $folder;

                    $folder->encode($encoder);
                    $encoder->endTag();
                }
            }

            if(count($importer->deleted) > 0) {
                foreach($importer->deleted as $folder) {
                    $encoder->startTag(SYNC_FOLDERHIERARCHY_REMOVE);
                        $encoder->startTag(SYNC_FOLDERHIERARCHY_SERVERENTRYID);
                            $encoder->content($folder);
                        $encoder->endTag();
                    $encoder->endTag();

                    // remove folder from the folderchache
                    if (array_key_exists($folder, $foldercache))
                        unset($foldercache[$folder]);
                }
            }
        }
        $encoder->endTag();
    }
    $encoder->endTag();

    // Save the sync state for the next time
    $syncstate = $exporter->GetState();
    $statemachine->setSyncState($newsynckey, $syncstate);
    $statemachine->setSyncState("s".$newsynckey, serialize($foldercache));


    return true;
}

function HandleSync($backend, $protocolversion, $devid) {
    global $zpushdtd;
    global $input, $output;

    // Contains all containers requested
    $collections = array();

    // Init WBXML decoder
    $decoder = new WBXMLDecoder($input, $zpushdtd);

    // Init state machine
    $statemachine = new StateMachine();

    // Start decode
    if(!$decoder->getElementStartTag(SYNC_SYNCHRONIZE))
        return false;

    if(!$decoder->getElementStartTag(SYNC_FOLDERS))
        return false;

    while($decoder->getElementStartTag(SYNC_FOLDER))
    {
        $collection = array();
        $collection["truncation"] = SYNC_TRUNCATION_ALL;
        $collection["clientids"] = array();
        $collection["fetchids"] = array();

        if(!$decoder->getElementStartTag(SYNC_FOLDERTYPE))
            return false;

        $collection["class"] = $decoder->getElementContent();
        debugLog("Sync folder:{$collection["class"]}");

        if(!$decoder->getElementEndTag())
            return false;

        if(!$decoder->getElementStartTag(SYNC_SYNCKEY))
            return false;

        $collection["synckey"] = $decoder->getElementContent();

        if(!$decoder->getElementEndTag())
            return false;

        if($decoder->getElementStartTag(SYNC_FOLDERID)) {
            $collection["collectionid"] = $decoder->getElementContent();

            if(!$decoder->getElementEndTag())
                return false;
        }

        if($decoder->getElementStartTag(SYNC_SUPPORTED)) {
            while(1) {
                $el = $decoder->getElement();
                if($el[EN_TYPE] == EN_TYPE_ENDTAG)
                    break;
            }
        }

        if($decoder->getElementStartTag(SYNC_DELETESASMOVES))
            $collection["deletesasmoves"] = true;

        if($decoder->getElementStartTag(SYNC_GETCHANGES))
            $collection["getchanges"] = true;

        if($decoder->getElementStartTag(SYNC_MAXITEMS)) {
            $collection["maxitems"] = $decoder->getElementContent();
            if(!$decoder->getElementEndTag())
                return false;
        }

        if($decoder->getElementStartTag(SYNC_OPTIONS)) {
            while(1) {
                if($decoder->getElementStartTag(SYNC_FILTERTYPE)) {
                    $collection["filtertype"] = $decoder->getElementContent();
                    if(!$decoder->getElementEndTag())
                        return false;
                }
                if($decoder->getElementStartTag(SYNC_TRUNCATION)) {
                    $collection["truncation"] = $decoder->getElementContent();
                    if(!$decoder->getElementEndTag())
                        return false;
                }
                if($decoder->getElementStartTag(SYNC_RTFTRUNCATION)) {
                    $collection["rtftruncation"] = $decoder->getElementContent();
                    if(!$decoder->getElementEndTag())
                        return false;
                }

                if($decoder->getElementStartTag(SYNC_MIMESUPPORT)) {
                    $collection["mimesupport"] = $decoder->getElementContent();
                    if(!$decoder->getElementEndTag())
                        return false;
                }

                if($decoder->getElementStartTag(SYNC_MIMETRUNCATION)) {
                    $collection["mimetruncation"] = $decoder->getElementContent();
                    if(!$decoder->getElementEndTag())
                        return false;
                }

                if($decoder->getElementStartTag(SYNC_CONFLICT)) {
                    $collection["conflict"] = $decoder->getElementContent();
                    if(!$decoder->getElementEndTag())
                        return false;
                }
                $e = $decoder->peek();
                if($e[EN_TYPE] == EN_TYPE_ENDTAG) {
                    $decoder->getElementEndTag();
                    break;
                }
            }
        }

        // limit items to be synchronized to the mobiles if configured
        if (defined('SYNC_FILTERTIME_MAX') && SYNC_FILTERTIME_MAX > SYNC_FILTERTYPE_ALL &&
            (!isset($collection["filtertype"]) || $collection["filtertype"] > SYNC_FILTERTIME_MAX)) {
                $collection["filtertype"] = SYNC_FILTERTIME_MAX;
        }

        // compatibility mode - get folderid from the state directory
        if (!isset($collection["collectionid"])) {
            $collection["collectionid"] = _getFolderID($devid, $collection["class"]);
        }

        // set default conflict behavior from config if the device doesn't send a conflict resolution parameter
        if (!isset($collection["conflict"])) {
            $collection["conflict"] = (defined('SYNC_CONFLICT_DEFAULT'))? SYNC_CONFLICT_DEFAULT : SYNC_CONFLICT_OVERWRITE_PIM ;
        }

        //compatibility mode - set maxitems if the client doesn't send it as it breaks some devices
        if (!isset($collection["maxitems"])) {
            $collection["maxitems"] = 100;
        }

        // Get our sync state for this collection
        $collection["syncstate"] = $statemachine->getSyncState($collection["synckey"]);
        if($decoder->getElementStartTag(SYNC_PERFORM)) {

            // Configure importer with last state
            $importer = $backend->GetContentsImporter($collection["collectionid"]);
            $importer->Config($collection["syncstate"], $collection["conflict"]);

            $nchanges = 0;
            while(1) {
                $element = $decoder->getElement(); // MODIFY or REMOVE or ADD or FETCH

                if($element[EN_TYPE] != EN_TYPE_STARTTAG) {
                    $decoder->ungetElement($element);
                    break;
                }

                // before importing the first change, load potential conflicts
                // for the current state
                if ($nchanges == 0)
                    $importer->LoadConflicts($collection["class"], (isset($collection["filtertype"])) ? $collection["filtertype"] : false, $collection["syncstate"]);

                $nchanges++;

                if($decoder->getElementStartTag(SYNC_SERVERENTRYID)) {
                    $serverid = $decoder->getElementContent();

                    if(!$decoder->getElementEndTag()) // end serverid
                        return false;
                } else {
                    $serverid = false;
                }

                if($decoder->getElementStartTag(SYNC_CLIENTENTRYID)) {
                    $clientid = $decoder->getElementContent();

                    if(!$decoder->getElementEndTag()) // end clientid
                        return false;
                } else {
                    $clientid = false;
                }

                // Get application data if available
                if($decoder->getElementStartTag(SYNC_DATA)) {
                    switch($collection["class"]) {
                        case "Email":
                            $appdata = new SyncMail();
                            $appdata->decode($decoder);
                            break;
                        case "Contacts":
                            $appdata = new SyncContact($protocolversion);
                            $appdata->decode($decoder);
                            break;
                        case "Calendar":
                            $appdata = new SyncAppointment();
                            $appdata->decode($decoder);
                            break;
                        case "Tasks":
                            $appdata = new SyncTask();
                            $appdata->decode($decoder);
                            break;
                    }
                    if(!$decoder->getElementEndTag()) // end applicationdata
                        return false;

                }

                switch($element[EN_TAG]) {
                    case SYNC_MODIFY:
                        if(isset($appdata)) {
                            if(isset($appdata->read)) // Currently, 'read' is only sent by the PDA when it is ONLY setting the read flag.
                                $importer->ImportMessageReadFlag($serverid, $appdata->read);
                            else
                                $importer->ImportMessageChange($serverid, $appdata);
                            $collection["importedchanges"] = true;
                        }
                        break;
                    case SYNC_ADD:
                        if(isset($appdata)) {
                            $id = $importer->ImportMessageChange(false, $appdata);

                            if($clientid && $id) {
                                $collection["clientids"][$clientid] = $id;
                                $collection["importedchanges"] = true;
                            }
                        }
                        break;
                    case SYNC_REMOVE:
                        if(isset($collection["deletesasmoves"])) {
                            $folderid = $backend->GetWasteBasket($collection["class"]);

                            if($folderid) {
                                $importer->ImportMessageMove($serverid, $folderid);
                                $collection["importedchanges"] = true;
                                break;
                            }
                        }

                        $importer->ImportMessageDeletion($serverid);
                        $collection["importedchanges"] = true;
                        break;
                    case SYNC_FETCH:
                        array_push($collection["fetchids"], $serverid);
                        break;
                }

                if(!$decoder->getElementEndTag()) // end change/delete/move
                    return false;
            }

            debugLog("Processed $nchanges incoming changes");

            // Save the updated state, which is used for the exporter later
            $collection["syncstate"] = $importer->getState();


            if(!$decoder->getElementEndTag()) // end commands
                return false;
        }

        if(!$decoder->getElementEndTag()) // end collection
            return false;

        array_push($collections, $collection);
    }

    if(!$decoder->getElementEndTag()) // end collections
        return false;

    if(!$decoder->getElementEndTag()) // end sync
        return false;

    $encoder = new WBXMLEncoder($output, $zpushdtd);
    $encoder->startWBXML();

    $encoder->startTag(SYNC_SYNCHRONIZE);
    {
        $encoder->startTag(SYNC_FOLDERS);
        {
            foreach($collections as $collection) {
                // initialize exporter to get changecount
                $changecount = 0;
                if(isset($collection["getchanges"]) || $collection["synckey"] == "0") {
                    // Use the state from the importer, as changes may have already happened
                    $exporter = $backend->GetExporter($collection["collectionid"]);

                    $filtertype = isset($collection["filtertype"]) ? $collection["filtertype"] : false;
                    $exporter->Config($importer, $collection["class"], $filtertype, $collection["syncstate"], 0, $collection["truncation"]);

                    $changecount = $exporter->GetChangeCount();
            	}

                // Get a new sync key to output to the client if any changes have been requested or will be send
                if (isset($collection["importedchanges"]) || $changecount > 0 || $collection["synckey"] == "0")
                    $collection["newsynckey"] = $statemachine->getNewSyncKey($collection["synckey"]);

                $encoder->startTag(SYNC_FOLDER);

                $encoder->startTag(SYNC_FOLDERTYPE);
                $encoder->content($collection["class"]);
                $encoder->endTag();

                $encoder->startTag(SYNC_SYNCKEY);

                if(isset($collection["newsynckey"]))
                    $encoder->content($collection["newsynckey"]);
                else
                    $encoder->content($collection["synckey"]);

                $encoder->endTag();

                $encoder->startTag(SYNC_FOLDERID);
                $encoder->content($collection["collectionid"]);
                $encoder->endTag();

                $encoder->startTag(SYNC_STATUS);
                $encoder->content(1);
                $encoder->endTag();

                //check the mimesupport because we need it for advanced emails
                $mimesupport = isset($collection['mimesupport']) ? $collection['mimesupport'] : 0;

                // Output server IDs for new items we received from the PDA
                if(isset($collection["clientids"]) || count($collection["fetchids"]) > 0) {
                    $encoder->startTag(SYNC_REPLIES);
                    foreach($collection["clientids"] as $clientid => $serverid) {
                        $encoder->startTag(SYNC_ADD);
                        $encoder->startTag(SYNC_CLIENTENTRYID);
                        $encoder->content($clientid);
                        $encoder->endTag();
                        $encoder->startTag(SYNC_SERVERENTRYID);
                        $encoder->content($serverid);
                        $encoder->endTag();
                        $encoder->startTag(SYNC_STATUS);
                        $encoder->content(1);
                        $encoder->endTag();
                        $encoder->endTag();
                    }
                    foreach($collection["fetchids"] as $id) {
                        $data = $backend->Fetch($collection["collectionid"], $id, $mimesupport);
                        if($data !== false) {
                            $encoder->startTag(SYNC_FETCH);
                            $encoder->startTag(SYNC_SERVERENTRYID);
                            $encoder->content($id);
                            $encoder->endTag();
                            $encoder->startTag(SYNC_STATUS);
                            $encoder->content(1);
                            $encoder->endTag();
                            $encoder->startTag(SYNC_DATA);
                            $data->encode($encoder);
                            $encoder->endTag();
                            $encoder->endTag();
                        } else {
                            debugLog("unable to fetch $id");
                        }
                    }
                    $encoder->endTag();
                }

                if(isset($collection["getchanges"])) {
                    // exporter already intialized

                    if($changecount > $collection["maxitems"]) {
                        $encoder->startTag(SYNC_MOREAVAILABLE, false, true);
                    }

                    // Output message changes per folder
                    $encoder->startTag(SYNC_PERFORM);

                    // Stream the changes to the PDA
                    $importer = new ImportContentsChangesStream($encoder, GetObjectClassFromFolderClass($collection["class"]));

                    $filtertype = isset($collection["filtertype"]) ? $collection["filtertype"] : 0;

                    $n = 0;
                    while(1) {
                        $progress = $exporter->Synchronize();
                        if(!is_array($progress))
                            break;
                        $n++;

                        if($n >= $collection["maxitems"]) {
                        	debugLog("Exported maxItems of messages: ". $collection["maxitems"] . " - more available");
                            break;
                        }

                    }
                    $encoder->endTag();
                }

                $encoder->endTag();

                // Save the sync state for the next time
                if(isset($collection["newsynckey"])) {
                    if (isset($exporter) && $exporter)
                        $state = $exporter->GetState();

                    // nothing exported, but possibly imported
                    else if (isset($importer) && $importer)
                        $state = $importer->GetState();

                    // if a new request without state information (hierarchy) save an empty state
                    else if ($collection["synckey"] == "0")
                        $state = "";

                    if (isset($state)) $statemachine->setSyncState($collection["newsynckey"], $state);
                    else debugLog("error saving " . $collection["newsynckey"] . " - no state information available");
                }
            }
        }
        $encoder->endTag();
    }
    $encoder->endTag();

    return true;
}

function HandleGetItemEstimate($backend, $protocolversion, $devid) {
    global $zpushdtd;
    global $input, $output;

    $collections = array();

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    if(!$decoder->getElementStartTag(SYNC_GETITEMESTIMATE_GETITEMESTIMATE))
        return false;

    if(!$decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERS))
        return false;

    while($decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDER)) {
        $collection = array();

        if(!$decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERTYPE))
            return false;

        $class = $decoder->getElementContent();

        if(!$decoder->getElementEndTag())
            return false;

        if($decoder->getElementStartTag(SYNC_GETITEMESTIMATE_FOLDERID)) {
            $collectionid = $decoder->getElementContent();

            if(!$decoder->getElementEndTag())
                return false;
        }

        if(!$decoder->getElementStartTag(SYNC_FILTERTYPE))
            return false;

        $filtertype = $decoder->getElementContent();

        if(!$decoder->getElementEndTag())
            return false;

        if(!$decoder->getElementStartTag(SYNC_SYNCKEY))
            return false;

        $synckey = $decoder->getElementContent();

        if(!$decoder->getElementEndTag())
            return false;
        if(!$decoder->getElementEndTag())
            return false;

        // compatibility mode - get folderid from the state directory
        if (!isset($collectionid)) {
            $collectionid = _getFolderID($devid, $class);
        }

        $collection = array();
        $collection["synckey"] = $synckey;
        $collection["class"] = $class;
        $collection["filtertype"] = $filtertype;
        $collection["collectionid"] = $collectionid;

        array_push($collections, $collection);
    }

    $encoder->startWBXML();

    $encoder->startTag(SYNC_GETITEMESTIMATE_GETITEMESTIMATE);
    {
        foreach($collections as $collection) {
            $encoder->startTag(SYNC_GETITEMESTIMATE_RESPONSE);
            {
                $encoder->startTag(SYNC_GETITEMESTIMATE_STATUS);
                $encoder->content(1);
                $encoder->endTag();

                $encoder->startTag(SYNC_GETITEMESTIMATE_FOLDER);
                {
                    $encoder->startTag(SYNC_GETITEMESTIMATE_FOLDERTYPE);
                    $encoder->content($collection["class"]);
                    $encoder->endTag();

                    $encoder->startTag(SYNC_GETITEMESTIMATE_FOLDERID);
                    $encoder->content($collection["collectionid"]);
                    $encoder->endTag();

                    $encoder->startTag(SYNC_GETITEMESTIMATE_ESTIMATE);

                    $importer = new ImportContentsChangesMem();

                    $statemachine = new StateMachine();
                    $syncstate = $statemachine->getSyncState($collection["synckey"]);

                    $exporter = $backend->GetExporter($collection["collectionid"]);
                    $exporter->Config($importer, $collection["class"], $collection["filtertype"], $syncstate, 0, 0);

                    $encoder->content($exporter->GetChangeCount());

                    $encoder->endTag();
                }
                $encoder->endTag();
            }
            $encoder->endTag();
        }
    }
    $encoder->endTag();

    return true;
}

function HandleGetAttachment($backend, $protocolversion) {
    $attname = $_GET["AttachmentName"];

    if(!isset($attname))
        return false;

    header("Content-Type: application/octet-stream");

    $backend->GetAttachmentData($attname);

    return true;
}

function HandlePing($backend, $devid) {
    global $zpushdtd, $input, $output;
    global $user, $auth_pw;
    $timeout = (defined('PING_INTERVAL') && PING_INTERVAL > 0) ? PING_INTERVAL : 30;

    debugLog("Ping received");

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    $collections = array();
    $lifetime = 0;

    // Get previous defaults if they exist
    $file = STATE_DIR . "/" . $devid;
    if (file_exists($file)) {
        $ping = unserialize(file_get_contents($file));
        $collections = $ping["collections"];
        $lifetime = $ping["lifetime"];
    }

    if($decoder->getElementStartTag(SYNC_PING_PING)) {
        debugLog("Ping init");
        if($decoder->getElementStartTag(SYNC_PING_LIFETIME)) {
            $lifetime = $decoder->getElementContent();
            $decoder->getElementEndTag();
        }

        if($decoder->getElementStartTag(SYNC_PING_FOLDERS)) {
            // avoid ping init if not necessary
            $saved_collections = $collections;

            $collections = array();

            while($decoder->getElementStartTag(SYNC_PING_FOLDER)) {
                $collection = array();

                while(1) {
                    if($decoder->getElementStartTag(SYNC_PING_SERVERENTRYID)) {
                        $collection["serverid"] = $decoder->getElementContent();
                        $decoder->getElementEndTag();
                    }
                    if($decoder->getElementStartTag(SYNC_PING_FOLDERTYPE)) {
                        $collection["class"] = $decoder->getElementContent();
                        $decoder->getElementEndTag();
                    }

                    $e = $decoder->peek();
                    if($e[EN_TYPE] == EN_TYPE_ENDTAG) {
                        $decoder->getElementEndTag();
                        break;
                    }
                    // Failsave - a devoce or attacker could send an unspecified tag which would cause an endless loop.
                    else if (isset($e[EN_TAG]) && $e[EN_TAG] != SYNC_PING_SERVERENTRYID && $e[EN_TAG] != SYNC_PING_FOLDERTYPE) {
                        debugLog("Found unspecified tag in ping folder definition:". print_r($e,1));
                        break;
                    }
                }

                // initialize empty state
                $collection["state"] = "";

                // try to find old state in saved states
                foreach ($saved_collections as $saved_col) {
                    if ($saved_col["serverid"] == $collection["serverid"] && $saved_col["class"] == $collection["class"]) {
                        $collection["state"] = $saved_col["state"];
                        debugLog("reusing saved state for ". $collection["class"]);
                        break;
                    }
                }

                if ($collection["state"] == "")
                    debugLog("empty state for ". $collection["class"]);

                // Create start state for this collection
                $exporter = $backend->GetExporter($collection["serverid"]);
                $importer = false;
                $exporter->Config($importer, false, false, $collection["state"], BACKEND_DISCARD_DATA, 0);
                while(is_array($exporter->Synchronize()));
                $collection["state"] = $exporter->GetState();
                array_push($collections, $collection);
            }

            if(!$decoder->getElementEndTag())
                return false;
        }

        if(!$decoder->getElementEndTag())
            return false;
    }

    $changes = array();
    $dataavailable = false;

    debugLog("Waiting for changes... (lifetime $lifetime)");
    // Wait for something to happen
    for($n=0;$n<$lifetime / $timeout; $n++ ) {
        //check the remote wipe status
        if (PROVISIONING === true) {
	        $rwstatus = $backend->getDeviceRWStatus($user, $auth_pw, $devid);
	        if ($rwstatus == SYNC_PROVISION_RWSTATUS_PENDING || $rwstatus == SYNC_PROVISION_RWSTATUS_WIPED) {
	            //return 7 because it forces folder sync
	            $pingstatus = 7;
	            break;
	        }
        }

        if(count($collections) == 0) {
            $error = 1;
            break;
        }

        for($i=0;$i<count($collections);$i++) {
            $collection = $collections[$i];

            $exporter = $backend->GetExporter($collection["serverid"]);
            $state = $collection["state"];
            $importer = false;
            $ret = $exporter->Config($importer, false, false, $state, BACKEND_DISCARD_DATA, 0);

            // stop ping if exporter can not be configured (e.g. after Zarafa-server restart)
            if ($ret === false ) {
                // force "ping" to stop
                $n = $lifetime / $timeout;
                debugLog("Ping error: Exporter can not be configured. Waiting 30 seconds before ping is retried.");
                sleep(30);
                break;
            }

            $changecount = $exporter->GetChangeCount();

            if($changecount > 0) {
                $dataavailable = true;
                $changes[$collection["serverid"]] = $changecount;
            }

            // Discard any data
            while(is_array($exporter->Synchronize()));

            // Record state for next Ping
            $collections[$i]["state"] = $exporter->GetState();
        }

        if($dataavailable) {
            debugLog("Found change");
            break;
        }

        sleep($timeout);
    }

    $encoder->StartWBXML();

    $encoder->startTag(SYNC_PING_PING);
    {
        $encoder->startTag(SYNC_PING_STATUS);
        if(isset($error))
            $encoder->content(3);
        elseif (isset($pingstatus))
            $encoder->content($pingstatus);
        else
            $encoder->content(count($changes) > 0 ? 2 : 1);
        $encoder->endTag();

        $encoder->startTag(SYNC_PING_FOLDERS);
        foreach($collections as $collection) {
            if(isset($changes[$collection["serverid"]])) {
                $encoder->startTag(SYNC_PING_FOLDER);
                $encoder->content($collection["serverid"]);
                $encoder->endTag();
            }
        }
        $encoder->endTag();
    }
    $encoder->endTag();

    // Save the ping request state for this device
    file_put_contents( STATE_DIR . "/" . $devid, serialize(array("lifetime" => $lifetime, "collections" => $collections)));

    return true;
}

function HandleSendMail($backend, $protocolversion) {
    // All that happens here is that we receive an rfc822 message on stdin
    // and just forward it to the backend. We provide no output except for
    // an OK http reply

    global $input;

    $rfc822 = readStream($input);

    return $backend->SendMail($rfc822);
}

function HandleSmartForward($backend, $protocolversion) {
    global $input;
    // SmartForward is a normal 'send' except that you should attach the
    // original message which is specified in the URL

    $rfc822 = readStream($input);

    if(isset($_GET["ItemId"]))
        $orig = $_GET["ItemId"];
    else
        $orig = false;

    if(isset($_GET["CollectionId"]))
        $parent = $_GET["CollectionId"];
    else
        $parent = false;

    return $backend->SendMail($rfc822, $orig, false, $parent);
}

function HandleSmartReply($backend, $protocolversion) {
    global $input;
    // Smart reply should add the original message to the end of the message body

    $rfc822 = readStream($input);

    if(isset($_GET["ItemId"]))
        $orig = $_GET["ItemId"];
    else
        $orig = false;

    if(isset($_GET["CollectionId"]))
        $parent = $_GET["CollectionId"];
    else
        $parent = false;

    return $backend->SendMail($rfc822, false, $orig, $parent);
}

function HandleFolderCreate($backend, $protocolversion) {
    global $zpushdtd;
    global $input, $output;

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    $el = $decoder->getElement();

    if($el[EN_TYPE] != EN_TYPE_STARTTAG)
        return false;

    $create = $update = $delete = false;

    if($el[EN_TAG] == SYNC_FOLDERHIERARCHY_FOLDERCREATE)
        $create = true;
    else if($el[EN_TAG] == SYNC_FOLDERHIERARCHY_FOLDERUPDATE)
        $update = true;
    else if($el[EN_TAG] == SYNC_FOLDERHIERARCHY_FOLDERDELETE)
        $delete = true;

    if(!$create && !$update && !$delete)
        return false;

    // SyncKey
    if(!$decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_SYNCKEY))
        return false;
    $synckey = $decoder->getElementContent();
    if(!$decoder->getElementEndTag())
        return false;

    // ServerID
    $serverid = false;
    if($decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_SERVERENTRYID)) {
        $serverid = $decoder->getElementContent();
        if(!$decoder->getElementEndTag())
            return false;
    }

    // when creating or updating more information is necessary
    if (!$delete) {
	    // Parent
	    $parentid = false;
	    if($decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_PARENTID)) {
	        $parentid = $decoder->getElementContent();
	        if(!$decoder->getElementEndTag())
	            return false;
	    }

	    // Displayname
	    if(!$decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_DISPLAYNAME))
	        return false;
	    $displayname = $decoder->getElementContent();
	    if(!$decoder->getElementEndTag())
	        return false;

	    // Type
	    $type = false;
	    if($decoder->getElementStartTag(SYNC_FOLDERHIERARCHY_TYPE)) {
	        $type = $decoder->getElementContent();
	        if(!$decoder->getElementEndTag())
	            return false;
	    }
    }

    if(!$decoder->getElementEndTag())
        return false;

    // Get state of hierarchy
    $statemachine = new StateMachine();
    $syncstate = $statemachine->getSyncState($synckey);
    $newsynckey = $statemachine->getNewSyncKey($synckey);

    // additional information about already seen folders
    $seenfolders = unserialize($statemachine->getSyncState("s".$synckey));
    if (!$seenfolders) $seenfolders = array();

    // Configure importer with last state
    $importer = $backend->GetHierarchyImporter();
    $importer->Config($syncstate);

    if (!$delete) {
	    // Send change
	    $serverid = $importer->ImportFolderChange($serverid, $parentid, $displayname, $type);
    }
    else {
    	// delete folder
    	$deletedstat = $importer->ImportFolderDeletion($serverid, 0);
    }

    $encoder->startWBXML();
    if ($create) {
    	// add folder id to the seen folders
        $seenfolders[] = $serverid;

        $encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERCREATE);
        {
            {
                $encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
                $encoder->content(1);
                $encoder->endTag();

                $encoder->startTag(SYNC_FOLDERHIERARCHY_SYNCKEY);
                $encoder->content($newsynckey);
                $encoder->endTag();

                $encoder->startTag(SYNC_FOLDERHIERARCHY_SERVERENTRYID);
                $encoder->content($serverid);
                $encoder->endTag();
            }
            $encoder->endTag();
        }
        $encoder->endTag();
    }

    elseif ($update) {

        $encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERUPDATE);
        {
            {
                $encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
                $encoder->content(1);
                $encoder->endTag();

                $encoder->startTag(SYNC_FOLDERHIERARCHY_SYNCKEY);
                $encoder->content($newsynckey);
                $encoder->endTag();
            }
            $encoder->endTag();
        }
    }
    elseif ($delete) {

        $encoder->startTag(SYNC_FOLDERHIERARCHY_FOLDERDELETE);
        {
            {
                $encoder->startTag(SYNC_FOLDERHIERARCHY_STATUS);
                $encoder->content($deletedstat);
                $encoder->endTag();

                $encoder->startTag(SYNC_FOLDERHIERARCHY_SYNCKEY);
                $encoder->content($newsynckey);
                $encoder->endTag();
            }
            $encoder->endTag();
        }

        // remove folder from the folderflags array
        if (($sid = array_search($serverid, $seenfolders)) !== false) {
            unset($seenfolders[$sid]);
            $seenfolders = array_values($seenfolders);
            debugLog("deleted from seenfolders: ". $serverid);
        }
    }

    $encoder->endTag();
    // Save the sync state for the next time
    $statemachine->setSyncState($newsynckey, $importer->GetState());
    $statemachine->setSyncState("s".$newsynckey, serialize($seenfolders));

    return true;
}

// Handle meetingresponse method
function HandleMeetingResponse($backend, $protocolversion) {
    global $zpushdtd;
    global $output, $input;

    $requests = Array();

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    if(!$decoder->getElementStartTag(SYNC_MEETINGRESPONSE_MEETINGRESPONSE))
        return false;

    while($decoder->getElementStartTag(SYNC_MEETINGRESPONSE_REQUEST)) {
        $req = Array();

        if($decoder->getElementStartTag(SYNC_MEETINGRESPONSE_USERRESPONSE)) {
            $req["response"] = $decoder->getElementContent();
            if(!$decoder->getElementEndTag())
                return false;
        }

        if($decoder->getElementStartTag(SYNC_MEETINGRESPONSE_FOLDERID)) {
            $req["folderid"] = $decoder->getElementContent();
            if(!$decoder->getElementEndTag())
                return false;
        }

        if($decoder->getElementStartTag(SYNC_MEETINGRESPONSE_REQUESTID)) {
            $req["requestid"] = $decoder->getElementContent();
            if(!$decoder->getElementEndTag())
                return false;
        }

        if(!$decoder->getElementEndTag())
            return false;

        array_push($requests, $req);
    }

    if(!$decoder->getElementEndTag())
        return false;


    // Start output, simply the error code, plus the ID of the calendar item that was generated by the
    // accept of the meeting response

    $encoder->StartWBXML();

    $encoder->startTag(SYNC_MEETINGRESPONSE_MEETINGRESPONSE);

    foreach($requests as $req) {
        $calendarid = "";
        $ok = $backend->MeetingResponse($req["requestid"], $req["folderid"], $req["response"], $calendarid);
        $encoder->startTag(SYNC_MEETINGRESPONSE_RESULT);
            $encoder->startTag(SYNC_MEETINGRESPONSE_REQUESTID);
                $encoder->content($req["requestid"]);
            $encoder->endTag();

            $encoder->startTag(SYNC_MEETINGRESPONSE_STATUS);
                $encoder->content($ok ? 1 : 2);
            $encoder->endTag();

            if($ok) {
                $encoder->startTag(SYNC_MEETINGRESPONSE_CALENDARID);
                    $encoder->content($calendarid);
                $encoder->endTag();
            }

        $encoder->endTag();
    }

    $encoder->endTag();

    return true;
}


function HandleFolderUpdate($backend, $protocolversion) {
    return HandleFolderCreate($backend, $protocolversion);
}

function HandleFolderDelete($backend, $protocolversion) {
    return HandleFolderCreate($backend, $protocolversion);
}

function HandleProvision($backend, $devid, $protocolversion) {
    global $user, $auth_pw, $policykey;

    global $zpushdtd, $policies;
    global $output, $input;

    $status = SYNC_PROVISION_STATUS_SUCCESS;
    $rwstatus = $backend->getDeviceRWStatus($user, $auth_pw, $devid);
    $rwstatusWiped = false;

    $phase2 = true;

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    if(!$decoder->getElementStartTag(SYNC_PROVISION_PROVISION))
        return false;

    //handle android remote wipe.
    if ($decoder->getElementStartTag(SYNC_PROVISION_REMOTEWIPE)) {
        if(!$decoder->getElementStartTag(SYNC_PROVISION_STATUS))
            return false;

        $status = $decoder->getElementContent();

        if(!$decoder->getElementEndTag())
            return false;

        if(!$decoder->getElementEndTag())
            return false;

        $phase2 = false;
        $rwstatusWiped = true;
    }
    else {

        if(!$decoder->getElementStartTag(SYNC_PROVISION_POLICIES))
            return false;

        if(!$decoder->getElementStartTag(SYNC_PROVISION_POLICY))
            return false;

        if(!$decoder->getElementStartTag(SYNC_PROVISION_POLICYTYPE))
            return false;

        $policytype = $decoder->getElementContent();
        if ($policytype != 'MS-WAP-Provisioning-XML') {
            $status = SYNC_PROVISION_STATUS_SERVERERROR;
        }
        if(!$decoder->getElementEndTag()) //policytype
            return false;

        if ($decoder->getElementStartTag(SYNC_PROVISION_POLICYKEY)) {
            $devpolicykey = $decoder->getElementContent();

            if(!$decoder->getElementEndTag())
                return false;

            if(!$decoder->getElementStartTag(SYNC_PROVISION_STATUS))
                return false;

            $status = $decoder->getElementContent();
            //do status handling
            $status = SYNC_PROVISION_STATUS_SUCCESS;

            if(!$decoder->getElementEndTag())
                return false;

            $phase2 = false;
        }

        if(!$decoder->getElementEndTag()) //policy
            return false;

        if(!$decoder->getElementEndTag()) //policies
            return false;

        if ($decoder->getElementStartTag(SYNC_PROVISION_REMOTEWIPE)) {
            if(!$decoder->getElementStartTag(SYNC_PROVISION_STATUS))
                return false;

            $status = $decoder->getElementContent();

            if(!$decoder->getElementEndTag())
                return false;

            if(!$decoder->getElementEndTag())
                return false;

            $rwstatusWiped = true;
        }
    }
    if(!$decoder->getElementEndTag()) //provision
        return false;

    $encoder->StartWBXML();

    //set the new final policy key in the backend
    // START ADDED dw2412 Android provisioning fix
    //in case the send one does not match the one already in backend. If it matches, we
    //just return the already defined key. (This helps at least the RoadSync 5.0 Client to sync)
    if ($backend->CheckPolicy($policykey,$devid) == SYNC_PROVISION_STATUS_SUCCESS) {
        debugLog("Policykey is OK! Will not generate a new one!");
    }
    else {
        if (!$phase2) {
            $policykey = $backend->generatePolicyKey();
            //android sends "validate" as deviceid, it does not need to be added to the device list
            if (strcasecmp("validate", $devid) != 0) $backend->setPolicyKey($policykey, $devid);
        }
        else {
            // just create a temporary key (i.e. iPhone OS4 Beta does not like policykey 0 in response)
            $policykey = $backend->generatePolicyKey();
        }
    }
    // END ADDED dw2412 Android provisioning fix

    $encoder->startTag(SYNC_PROVISION_PROVISION);
    {
        $encoder->startTag(SYNC_PROVISION_STATUS);
            $encoder->content($status);
        $encoder->endTag();

        $encoder->startTag(SYNC_PROVISION_POLICIES);
            $encoder->startTag(SYNC_PROVISION_POLICY);

            if(isset($policytype)) {
                $encoder->startTag(SYNC_PROVISION_POLICYTYPE);
                    $encoder->content($policytype);
                $encoder->endTag();
            }

            $encoder->startTag(SYNC_PROVISION_STATUS);
                $encoder->content($status);
            $encoder->endTag();

            $encoder->startTag(SYNC_PROVISION_POLICYKEY);
                   $encoder->content($policykey);
            $encoder->endTag();

            if ($phase2) {
                $encoder->startTag(SYNC_PROVISION_DATA);
                if ($policytype == 'MS-WAP-Provisioning-XML') {
                    $encoder->content('<wap-provisioningdoc><characteristic type="SecurityPolicy"><parm name="4131" value="1"/><parm name="4133" value="1"/></characteristic></wap-provisioningdoc>');
                }
                else {
                    debugLog("Wrong policy type");
                    return false;
                }

                $encoder->endTag();//data
            }
            $encoder->endTag();//policy
        $encoder->endTag(); //policies
    }


    //wipe data if status is pending or wiped
    if ($rwstatus == SYNC_PROVISION_RWSTATUS_PENDING || $rwstatus == SYNC_PROVISION_RWSTATUS_WIPED) {
        $encoder->startTag(SYNC_PROVISION_REMOTEWIPE, false, true);
        $backend->setDeviceRWStatus($user, $auth_pw, $devid, ($rwstatusWiped)?SYNC_PROVISION_RWSTATUS_WIPED:SYNC_PROVISION_RWSTATUS_PENDING);
    }

    $encoder->endTag();//provision

    return true;
}


function HandleSearch($backend, $devid, $protocolversion) {
    global $zpushdtd;
    global $input, $output;

    $searchrange = '0';

    $decoder = new WBXMLDecoder($input, $zpushdtd);
    $encoder = new WBXMLEncoder($output, $zpushdtd);

    if(!$decoder->getElementStartTag(SYNC_SEARCH_SEARCH))
        return false;

    if(!$decoder->getElementStartTag(SYNC_SEARCH_STORE))
        return false;

    if(!$decoder->getElementStartTag(SYNC_SEARCH_NAME))
        return false;
    $searchname = $decoder->getElementContent();
    if(!$decoder->getElementEndTag())
        return false;

    if(!$decoder->getElementStartTag(SYNC_SEARCH_QUERY))
        return false;
    $searchquery = $decoder->getElementContent();
    if(!$decoder->getElementEndTag())
        return false;

    if($decoder->getElementStartTag(SYNC_SEARCH_OPTIONS)) {
        while(1) {
            if($decoder->getElementStartTag(SYNC_SEARCH_RANGE)) {
                $searchrange = $decoder->getElementContent();
                if(!$decoder->getElementEndTag())
                    return false;
                }
                $e = $decoder->peek();
                if($e[EN_TYPE] == EN_TYPE_ENDTAG) {
                    $decoder->getElementEndTag();
                    break;
                }
            }
        //if(!$decoder->getElementEndTag())
            //return false;
    }
    if(!$decoder->getElementEndTag()) //store
        return false;

    if(!$decoder->getElementEndTag()) //search
        return false;


    if (strtoupper($searchname) != "GAL") {
        debugLog("Searchtype $searchname is not supported");
        return false;
    }

    //get search results from backend
    if (defined('SEARCH_PROVIDER') && @constant('SEARCH_PROVIDER') != "" && class_exists(SEARCH_PROVIDER)) {
        $searchClass = constant('SEARCH_PROVIDER');
        $searchbackend = new $searchClass();
        $searchbackend->initialize($backend);
        $rows = $searchbackend->getSearchResults($searchquery, $searchrange);
        $searchbackend->disconnect();
    }
    else
        $rows = $backend->getSearchResults($searchquery, $searchrange);

    $encoder->startWBXML();

    $encoder->startTag(SYNC_SEARCH_SEARCH);

        $encoder->startTag(SYNC_SEARCH_STATUS);
        $encoder->content(1);
        $encoder->endTag();

        $encoder->startTag(SYNC_SEARCH_RESPONSE);
            $encoder->startTag(SYNC_SEARCH_STORE);

                $encoder->startTag(SYNC_SEARCH_STATUS);
                $encoder->content(1);
                $encoder->endTag();

                if (is_array($rows) && !empty($rows)) {
                    $searchrange = $rows['range'];
                    unset($rows['range']);
                    $searchtotal = $rows['searchtotal'];
                    unset($rows['searchtotal']);
                    foreach ($rows as $u) {
                        $encoder->startTag(SYNC_SEARCH_RESULT);
                            $encoder->startTag(SYNC_SEARCH_PROPERTIES);

                                $encoder->startTag(SYNC_GAL_DISPLAYNAME);
                                $encoder->content((isset($u[SYNC_GAL_DISPLAYNAME]))?$u[SYNC_GAL_DISPLAYNAME]:"No name");
                                $encoder->endTag();

                                if (isset($u[SYNC_GAL_PHONE])) {
                                    $encoder->startTag(SYNC_GAL_PHONE);
                                    $encoder->content($u[SYNC_GAL_PHONE]);
                                    $encoder->endTag();
                                }

                                if (isset($u[SYNC_GAL_OFFICE])) {
                                    $encoder->startTag(SYNC_GAL_OFFICE);
                                    $encoder->content($u[SYNC_GAL_OFFICE]);
                                    $encoder->endTag();
                                }

                                if (isset($u[SYNC_GAL_TITLE])) {
                                    $encoder->startTag(SYNC_GAL_TITLE);
                                    $encoder->content($u[SYNC_GAL_TITLE]);
                                    $encoder->endTag();
                                }

                                if (isset($u[SYNC_GAL_COMPANY])) {
                                    $encoder->startTag(SYNC_GAL_COMPANY);
                                    $encoder->content($u[SYNC_GAL_COMPANY]);
                                    $encoder->endTag();
                                }

                                if (isset($u[SYNC_GAL_ALIAS])) {
                                    $encoder->startTag(SYNC_GAL_ALIAS);
                                    $encoder->content($u[SYNC_GAL_ALIAS]);
                                    $encoder->endTag();
                                }

                                // Always send the firstname, even empty. Nokia needs this to display the entry
                                $encoder->startTag(SYNC_GAL_FIRSTNAME);
                                $encoder->content((isset($u[SYNC_GAL_FIRSTNAME]))?$u[SYNC_GAL_FIRSTNAME]:"");
                                $encoder->endTag();

                                $encoder->startTag(SYNC_GAL_LASTNAME);
                                $encoder->content((isset($u[SYNC_GAL_LASTNAME]))?$u[SYNC_GAL_LASTNAME]:"No name");
                                $encoder->endTag();

                                if (isset($u[SYNC_GAL_HOMEPHONE])) {
                                    $encoder->startTag(SYNC_GAL_HOMEPHONE);
                                    $encoder->content($u[SYNC_GAL_HOMEPHONE]);
                                    $encoder->endTag();
                                }

                                if (isset($u[SYNC_GAL_MOBILEPHONE])) {
                                    $encoder->startTag(SYNC_GAL_MOBILEPHONE);
                                    $encoder->content($u[SYNC_GAL_MOBILEPHONE]);
                                    $encoder->endTag();
                                }

                                $encoder->startTag(SYNC_GAL_EMAILADDRESS);
                                $encoder->content((isset($u[SYNC_GAL_EMAILADDRESS]))?$u[SYNC_GAL_EMAILADDRESS]:"");
                                $encoder->endTag();

                            $encoder->endTag();//result
                        $encoder->endTag();//properties
                    }

                    if ($searchtotal > 0) {
                        $encoder->startTag(SYNC_SEARCH_RANGE);
                        $encoder->content($searchrange);
                        $encoder->endTag();

                        $encoder->startTag(SYNC_SEARCH_TOTAL);
                        $encoder->content($searchtotal);
                        $encoder->endTag();
                    }
                }

            $encoder->endTag();//store
        $encoder->endTag();//response
    $encoder->endTag();//search


    return true;
}

function HandleRequest($backend, $cmd, $devid, $protocolversion) {
    switch($cmd) {
        case 'Sync':
            $status = HandleSync($backend, $protocolversion, $devid);
            break;
        case 'SendMail':
            $status = HandleSendMail($backend, $protocolversion);
            break;
        case 'SmartForward':
            $status = HandleSmartForward($backend, $protocolversion);
            break;
        case 'SmartReply':
            $status = HandleSmartReply($backend, $protocolversion);
            break;
        case 'GetAttachment':
            $status = HandleGetAttachment($backend, $protocolversion);
            break;
        case 'GetHierarchy':
            $status = HandleGetHierarchy($backend, $protocolversion, $devid);
            break;
        case 'CreateCollection':
            $status = HandleCreateCollection($backend, $protocolversion);
            break;
        case 'DeleteCollection':
            $status = HandleDeleteCollection($backend, $protocolversion);
            break;
        case 'MoveCollection':
            $status = HandleMoveCollection($backend, $protocolversion);
            break;
        case 'FolderSync':
            $status = HandleFolderSync($backend, $protocolversion);
            break;
        case 'FolderCreate':
            $status = HandleFolderCreate($backend, $protocolversion);
            break;
        case 'FolderDelete':
            $status = HandleFolderDelete($backend, $protocolversion);
            break;
        case 'FolderUpdate':
            $status = HandleFolderUpdate($backend, $protocolversion);
            break;
        case 'MoveItems':
            $status = HandleMoveItems($backend, $protocolversion);
            break;
        case 'GetItemEstimate':
            $status = HandleGetItemEstimate($backend, $protocolversion, $devid);
            break;
        case 'MeetingResponse':
            $status = HandleMeetingResponse($backend, $protocolversion);
            break;
        case 'Notify': // Used for sms-based notifications (pushmail)
            $status = HandleNotify($backend, $protocolversion);
            break;
        case 'Ping': // Used for http-based notifications (pushmail)
            $status = HandlePing($backend, $devid, $protocolversion);
            break;
        case 'Provision':
            $status = (PROVISIONING === true) ? HandleProvision($backend, $devid, $protocolversion) : false;
            break;
        case 'Search':
            $status = HandleSearch($backend, $devid, $protocolversion);
            break;

        default:
            debugLog("unknown command - not implemented");
            $status = false;
            break;
    }

    return $status;
}

function readStream(&$input) {
    $s = "";

    while(1) {
        $data = fread($input, 4096);
        if(strlen($data) == 0)
            break;
        $s .= $data;
    }

    return $s;
}

?>
