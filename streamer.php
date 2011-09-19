<?php
/***********************************************
* File      :   streamer.php
* Project   :   Z-Push
* Descr     :   This file handles streaming of
*                WBXML objects. It must be
*                subclassed so the internals of
*                the object can be specified via
*                $mapping. Basically we set/read
*                the object variables of the
*                subclass according to the mappings
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
include_once("zpushdtd.php");

define('STREAMER_VAR', 1);
define('STREAMER_ARRAY', 2);
define('STREAMER_TYPE', 3);

define('STREAMER_TYPE_DATE', 1);
define('STREAMER_TYPE_HEX', 2);
define('STREAMER_TYPE_DATE_DASHES', 3);
define('STREAMER_TYPE_MAPI_STREAM', 4);


class Streamer {
    var $_mapping;

    var $content;
    var $attributes;
    var $flags;

    function Streamer($mapping) {
        $this->_mapping = $mapping;
        $this->flags = false;
    }

    // Decodes the WBXML from $input until we reach the same depth level of WBXML. This
    // means that if there are multiple objects at this level, then only the first is decoded
    // SubOjects are auto-instantiated and decoded using the same functionality
    function decode(&$decoder) {
        while(1) {
            $entity = $decoder->getElement();

            if($entity[EN_TYPE] == EN_TYPE_STARTTAG) {
                if(! ($entity[EN_FLAGS] & EN_FLAGS_CONTENT)) {
                    $map = $this->_mapping[$entity[EN_TAG]];
                    if (isset($map[STREAMER_ARRAY])) {
                        $this->$map[STREAMER_VAR] = array();
                    } else if(!isset($map[STREAMER_TYPE])) {
                        $this->$map[STREAMER_VAR] = "";
                    } else if ($map[STREAMER_TYPE] == STREAMER_TYPE_DATE || $map[STREAMER_TYPE] == STREAMER_TYPE_DATE_DASHES ) {
                        $this->$map[STREAMER_VAR] = "";
                    }
                    continue;
                }
                // Found a start tag
                if(!isset($this->_mapping[$entity[EN_TAG]])) {
                    // This tag shouldn't be here, abort
                    debug("Tag " . $entity[EN_TAG] . " unexpected in type XML type " . get_class($this));
                    return false;
                } else {
                    $map = $this->_mapping[$entity[EN_TAG]];

                    // Handle an array
                    if(isset($map[STREAMER_ARRAY])) {
                        while(1) {
                            if(!$decoder->getElementStartTag($map[STREAMER_ARRAY]))
                                break;
                            if(isset($map[STREAMER_TYPE])) {
                                $decoded = new $map[STREAMER_TYPE];
                                $decoded->decode($decoder);
                            } else {
                                $decoded = $decoder->getElementContent();
                            }

                            if(!isset($this->$map[STREAMER_VAR]))
                                $this->$map[STREAMER_VAR] = array($decoded);
                            else
                                array_push($this->$map[STREAMER_VAR], $decoded);

                            if(!$decoder->getElementEndTag())
                                return false;
                        }
                        if(!$decoder->getElementEndTag())
                            return false;
                    } else { // Handle single value
                        if(isset($map[STREAMER_TYPE])) {
                            // Complex type, decode recursively
                            if($map[STREAMER_TYPE] == STREAMER_TYPE_DATE || $map[STREAMER_TYPE] == STREAMER_TYPE_DATE_DASHES) {
                                $decoded = $this->parseDate($decoder->getElementContent());
                                if(!$decoder->getElementEndTag())
                                    return false;
                            } else if($map[STREAMER_TYPE] == STREAMER_TYPE_HEX) {
                                $decoded = hex2bin($decoder->getElementContent());
                                if(!$decoder->getElementEndTag())
                                    return false;
                            } else {
                                $subdecoder = new $map[STREAMER_TYPE]();
                                if($subdecoder->decode($decoder) === false)
                                    return false;

                                $decoded = $subdecoder;

                                if(!$decoder->getElementEndTag()) {
                                    debug("No end tag for " . $entity[EN_TAG]);
                                    return false;
                                }
                            }
                        } else {
                            // Simple type, just get content
                            $decoded = $decoder->getElementContent();

                            if($decoded === false) {
                                // the tag is declared to have content, but no content is available.
                                // set an empty content
                                $decoded = "";
                            }

                            if(!$decoder->getElementEndTag()) {
                                debug("Unable to get end tag for " . $entity[EN_TAG]);
                                return false;
                            }
                        }
                        // $decoded now contains data object (or string)
                        $this->$map[STREAMER_VAR] = $decoded;
                    }
                }
            }
            else if($entity[EN_TYPE] == EN_TYPE_ENDTAG) {
                $decoder->ungetElement($entity);
                break;
            }
            else {
                debug("Unexpected content in type");
                break;
            }
        }
    }

    // Encodes this object and any subobjects - output is ordered according to mapping
    function encode(&$encoder) {
        $attributes = isset($this->attributes) ? $this->attributes : array();

        foreach($this->_mapping as $tag => $map) {
            if(isset($this->$map[STREAMER_VAR])) {
                // Variable is available
                if(is_object($this->$map[STREAMER_VAR])) {
                    // Subobjects can do their own encoding
                    $encoder->startTag($tag);
                    $this->$map[STREAMER_VAR]->encode($encoder);
                    $encoder->endTag();
                } else if(isset($map[STREAMER_ARRAY])) {
                    // Array of objects
                    $encoder->startTag($tag); // Outputs array container (eg Attachments)
                    foreach ($this->$map[STREAMER_VAR] as $element) {
                        if(is_object($element)) {
                            $encoder->startTag($map[STREAMER_ARRAY]); // Outputs object container (eg Attachment)
                            $element->encode($encoder);
                            $encoder->endTag();
                        } else {
                            if(strlen($element) == 0)
                                  // Do not output empty items. Not sure if we should output an empty tag with $encoder->startTag($map[STREAMER_ARRAY], false, true);
                                  ;
                            else {
                                $encoder->startTag($map[STREAMER_ARRAY]);
                                $encoder->content($element);
                                $encoder->endTag();
                            }
                        }
                    }
                    $encoder->endTag();
                } else {
                    // Simple type
                    if(strlen($this->$map[STREAMER_VAR]) == 0) {
                          // Do not output empty items. See above: $encoder->startTag($tag, false, true);
                        continue;
                    } else
                        $encoder->startTag($tag);

                    if(isset($map[STREAMER_TYPE]) && ($map[STREAMER_TYPE] == STREAMER_TYPE_DATE || $map[STREAMER_TYPE] == STREAMER_TYPE_DATE_DASHES)) {
                        if($this->$map[STREAMER_VAR] != 0) // don't output 1-1-1970
                            $encoder->content($this->formatDate($this->$map[STREAMER_VAR], $map[STREAMER_TYPE]));
                    } else if(isset($map[STREAMER_TYPE]) && $map[STREAMER_TYPE] == STREAMER_TYPE_HEX) {
                        $encoder->content(strtoupper(bin2hex($this->$map[STREAMER_VAR])));
                    } else if(isset($map[STREAMER_TYPE]) && $map[STREAMER_TYPE] == STREAMER_TYPE_MAPI_STREAM) {
                        $encoder->content($this->$map[STREAMER_VAR]);
                    } else {
                        $encoder->content($this->$map[STREAMER_VAR]);
                    }
                    $encoder->endTag();
                }
            }
        }
        // Output our own content
        if(isset($this->content))
            $encoder->content($this->content);

    }



    // Oh yeah. This is beautiful. Exchange outputs date fields differently in calendar items
    // and emails. We could just always send one or the other, but unfortunately nokia's 'Mail for
    // exchange' depends on this quirk. So we have to send a different date type depending on where
    // it's used. Sigh.
    function formatDate($ts, $type) {
        if($type == STREAMER_TYPE_DATE)
            return gmstrftime("%Y%m%dT%H%M%SZ", $ts);
        else if($type == STREAMER_TYPE_DATE_DASHES)
            return gmstrftime("%Y-%m-%dT%H:%M:%S.000Z", $ts);
    }

    function parseDate($ts) {
        if(preg_match("/(\d{4})[^0-9]*(\d{2})[^0-9]*(\d{2})(T(\d{2})[^0-9]*(\d{2})[^0-9]*(\d{2})(.\d+)?Z){0,1}$/", $ts, $matches)) {
            if ($matches[1] >= 2038){
                $matches[1] = 2038;
                $matches[2] = 1;
                $matches[3] = 18;
                $matches[5] = $matches[6] = $matches[7] = 0;
            }

            if (!isset($matches[5])) $matches[5] = 0;
            if (!isset($matches[6])) $matches[6] = 0;
            if (!isset($matches[7])) $matches[7] = 0;

            return gmmktime($matches[5], $matches[6], $matches[7], $matches[2], $matches[3], $matches[1]);
        }
        return 0;
    }
};

?>