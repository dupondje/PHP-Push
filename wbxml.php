<?php
/***********************************************
* File      :   wbxml.php
* Project   :   Z-Push
* Descr     :   WBXML mapping file
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
include_once('debug.php');

define('WBXML_DEBUG', false);

define('WBXML_SWITCH_PAGE',     0x00);
define('WBXML_END',             0x01);
define('WBXML_ENTITY',          0x02);
define('WBXML_STR_I',           0x03);
define('WBXML_LITERAL',         0x04);
define('WBXML_EXT_I_0',         0x40);
define('WBXML_EXT_I_1',         0x41);
define('WBXML_EXT_I_2',         0x42);
define('WBXML_PI',              0x43);
define('WBXML_LITERAL_C',       0x44);
define('WBXML_EXT_T_0',         0x80);
define('WBXML_EXT_T_1',         0x81);
define('WBXML_EXT_T_2',         0x82);
define('WBXML_STR_T',           0x83);
define('WBXML_LITERAL_A',       0x84);
define('WBXML_EXT_0',           0xC0);
define('WBXML_EXT_1',           0xC1);
define('WBXML_EXT_2',           0xC2);
define('WBXML_OPAQUE',          0xC3);
define('WBXML_LITERAL_AC',      0xC4);

define('EN_TYPE',               1);
define('EN_TAG',                2);
define('EN_CONTENT',            3);
define('EN_FLAGS',              4);
define('EN_ATTRIBUTES',         5);

define('EN_TYPE_STARTTAG',      1);
define('EN_TYPE_ENDTAG',        2);
define('EN_TYPE_CONTENT',       3);

define('EN_FLAGS_CONTENT',      1);
define('EN_FLAGS_ATTRIBUTES',   2);

class WBXMLDecoder {
    var $dtd;
    var $in;

    var $version;
    var $publicid;
    var $publicstringid;
    var $charsetid;
    var $stringtable;

    var $tagcp = 0;
    var $attrcp = 0;

    var $ungetbuffer;

    var $logStack = array();

    function WBXMLDecoder($input, $dtd) {
        $this->in = $input;
        $this->dtd = $dtd;

        $this->version = $this->getByte();
        $this->publicid = $this->getMBUInt();
        if($this->publicid == 0) {
            $this->publicstringid = $this->getMBUInt();
        }

        $this->charsetid = $this->getMBUInt();
        $this->stringtable = $this->getStringTable();

    }

    // Returns either start, content or end, and auto-concatenates successive content
    function getElement()
    {
        $element = $this->getToken();

        switch($element[EN_TYPE]) {
            case EN_TYPE_STARTTAG:
                return $element;
            case EN_TYPE_ENDTAG:
                return $element;
            case EN_TYPE_CONTENT:
                while(1) {
                    $next = $this->getToken();
                    if($next == false)
                        return false;
                    else if($next[EN_TYPE] == EN_CONTENT) {
                        $element[EN_CONTENT] .= $next[EN_CONTENT];
                    } else {
                        $this->ungetElement($next);
                        break;
                    }
                }
                return $element;
        }

        return false;
    }

    function peek()
    {
        $element = $this->getElement();

        $this->ungetElement($element);

        return $element;
    }

    function getElementStartTag($tag)
    {
        $element = $this->getToken();

        if($element[EN_TYPE] == EN_TYPE_STARTTAG && $element[EN_TAG] == $tag)
            return $element;
        else {
            debug("Unmatched tag $tag:");
            debug(print_r($element,true));
            $this->ungetElement($element);
        }

        return false;
    }

    function getElementEndTag()
    {
        $element = $this->getToken();

        if($element[EN_TYPE] == EN_TYPE_ENDTAG)
            return $element;
        else {
            debug("Unmatched end tag:");
            debug(print_r($element,true));
            $bt = debug_backtrace();
            $c = count($bt);
            debugLog(print_r($bt,true));
            debug("From " . $bt[$c-2]["file"] . ":" . $bt[$c-2]["line"]);
            $this->ungetElement($element);
        }

        return false;
    }

    function getElementContent()
    {
        $element = $this->getToken();

        if($element[EN_TYPE] == EN_TYPE_CONTENT) {
            return $element[EN_CONTENT];
        }
        else {
            debug("Unmatched content:");
            debug(print_r($element, true));
            $this->ungetElement($element);
        }

        return false;
    }

    // ---------------------- Private functions ------------------------

    function getToken() {
        // See if there's something in the ungetBuffer
        if($this->ungetbuffer) {
            $element = $this->ungetbuffer;
            $this->ungetbuffer = false;
            return $element;
        }

        $el = $this->_getToken();

        $this->logToken($el);

        return $el;
    }

    function logToken($el) {
        if(!WBXML_DEBUG)
            return;
        $spaces = str_repeat(" ", count($this->logStack));

        switch($el[EN_TYPE]) {
            case EN_TYPE_STARTTAG:
                if($el[EN_FLAGS] & EN_FLAGS_CONTENT) {
                    debugLog("I " . $spaces . " <". $el[EN_TAG] . ">");
                    array_push($this->logStack, $el[EN_TAG]);
                } else
                    debugLog("I " . $spaces . " <" . $el[EN_TAG] . "/>");

                break;
            case EN_TYPE_ENDTAG:
                $tag = array_pop($this->logStack);
                debugLog("I " . $spaces . "</" . $tag . ">");
                break;
            case EN_TYPE_CONTENT:
                debugLog("I " . $spaces . " " . $el[EN_CONTENT]);
                break;
        }
    }

    // Returns either a start tag, content or end tag
    function _getToken() {

        // Get the data from the input stream
        $element = array();

        while(1) {
            $byte = $this->getByte();

            if(!isset($byte))
                break;

            switch($byte) {
                case WBXML_SWITCH_PAGE:
                    $this->tagcp = $this->getByte();
                    continue;

                case WBXML_END:
                    $element[EN_TYPE] = EN_TYPE_ENDTAG;
                    return $element;

                case WBXML_ENTITY:
                    $entity = $this->getMBUInt();
                    $element[EN_TYPE] = EN_TYPE_CONTENT;
                    $element[EN_CONTENT] = $this->entityToCharset($entity);
                    return $element;

                case WBXML_STR_I:
                    $element[EN_TYPE] = EN_TYPE_CONTENT;
                    $element[EN_CONTENT] = $this->getTermStr();
                    return $element;

                case WBXML_LITERAL:
                    $element[EN_TYPE] = EN_TYPE_STARTTAG;
                    $element[EN_TAG] = $this->getStringTableEntry($this->getMBUInt());
                    $element[EN_FLAGS] = 0;
                    return $element;

                case WBXML_EXT_I_0:
                case WBXML_EXT_I_1:
                case WBXML_EXT_I_2:
                    $this->getTermStr();
                    // Ignore extensions
                    continue;

                case WBXML_PI:
                    // Ignore PI
                    $this->getAttributes();
                    continue;

                case WBXML_LITERAL_C:
                    $element[EN_TYPE] = EN_TYPE_STARTTAG;
                    $element[EN_TAG] = $this->getStringTableEntry($this->getMBUInt());
                    $element[EN_FLAGS] = EN_FLAGS_CONTENT;
                    return $element;

                case WBXML_EXT_T_0:
                case WBXML_EXT_T_1:
                case WBXML_EXT_T_2:
                    $this->getMBUInt();
                    // Ingore extensions;
                    continue;

                case WBXML_STR_T:
                    $element[EN_TYPE] = EN_TYPE_CONTENT;
                    $element[EN_CONTENT] = $this->getStringTableEntry($this->getMBUInt());
                    return $element;

                case WBXML_LITERAL_A:
                    $element[EN_TYPE] = EN_TYPE_STARTTAG;
                    $element[EN_TAG] = $this->getStringTableEntry($this->getMBUInt());
                    $element[EN_ATTRIBUTES] = $this->getAttributes();
                    $element[EN_FLAGS] = EN_FLAGS_ATTRIBUTES;
                    return $element;
                case WBXML_EXT_0:
                case WBXML_EXT_1:
                case WBXML_EXT_2:
                    continue;

                case WBXML_OPAQUE:
                    $length = $this->getMBUInt();
                    $element[EN_TYPE] = EN_TYPE_CONTENT;
                    $element[EN_CONTENT] = $this->getOpaque($length);
                    return $element;

                case WBXML_LITERAL_AC:
                    $element[EN_TYPE] = EN_TYPE_STARTTAG;
                    $element[EN_TAG] = $this->getStringTableEntry($this->getMBUInt());
                    $element[EN_ATTRIBUTES] = $this->getAttributes();
                    $element[EN_FLAGS] = EN_FLAGS_ATTRIBUTES | EN_FLAGS_CONTENT;
                    return $element;

                default:
                    $element[EN_TYPE] = EN_TYPE_STARTTAG;
                    $element[EN_TAG] = $this->getMapping($this->tagcp, $byte & 0x3f);
                    $element[EN_FLAGS] = ($byte & 0x80 ? EN_FLAGS_ATTRIBUTES : 0) | ($byte & 0x40 ? EN_FLAGS_CONTENT : 0);
                    if($byte & 0x80)
                        $element[EN_ATTRIBUTES] = $this->getAttributes();
                    return $element;
            }
        }
    }

    function ungetElement($element) {
        if($this->ungetbuffer)
            debugLog("Double unget!");

        $this->ungetbuffer = $element;
    }

    function getAttributes() {
        $attributes = array();
        $attr = "";

        while(1) {
            $byte = $this->getByte();

            if(count($byte) == 0)
                break;

            switch($byte) {
                case WBXML_SWITCH_PAGE:
                    $this->attrcp = $this->getByte();
                    break;

                case WBXML_END:
                    if($attr != "")
                        $attributes += $this->splitAttribute($attr);

                    return $attributes;

                case WBXML_ENTITY:
                    $entity = $this->getMBUInt();
                    $attr .= $this->entityToCharset($entity);
                    return $element;

                case WBXML_STR_I:
                    $attr .= $this->getTermStr();
                    return $element;

                case WBXML_LITERAL:
                    if($attr != "")
                        $attributes += $this->splitAttribute($attr);

                    $attr = $this->getStringTableEntry($this->getMBUInt());
                    return $element;

                case WBXML_EXT_I_0:
                case WBXML_EXT_I_1:
                case WBXML_EXT_I_2:
                    $this->getTermStr();
                    continue;

                case WBXML_PI:
                case WBXML_LITERAL_C:
                    // Invalid
                    return false;

                case WBXML_EXT_T_0:
                case WBXML_EXT_T_1:
                case WBXML_EXT_T_2:
                    $this->getMBUInt();
                    continue;

                case WBXML_STR_T:
                    $attr .= $this->getStringTableEntry($this->getMBUInt());
                    return $element;

                case WBXML_LITERAL_A:
                    return false;

                case WBXML_EXT_0:
                case WBXML_EXT_1:
                case WBXML_EXT_2:
                    continue;

                case WBXML_OPAQUE:
                    $length = $this->getMBUInt();
                    $attr .= $this->getOpaque($length);
                    return $element;

                case WBXML_LITERAL_AC:
                    return false;

                default:
                    if($byte < 128) {
                        if($attr != "") {
                            $attributes += $this->splitAttribute($attr);
                            $attr = "";
                        }
                    }

                    $attr .= $this->getMapping($this->attrcp, $byte);
                    break;
            }
        }

    }

    function splitAttribute($attr) {
        $attributes = array();

        $pos = strpos($attr,chr(61)); // equals sign

        if($pos)
            $attributes[substr($attr, 0, $pos)] = substr($attr, $pos+1);
        else
            $attributes[$attr] = null;

        return $attributes;
    }

    function getTermStr() {
        $str = "";
        while(1) {
            $in = $this->getByte();

            if($in == 0)
                break;
            else
                $str .= chr($in);
        }

        return $str;
    }

    function getOpaque($len) {
        return fread($this->in, $len);
    }

    function getByte() {
        $ch = fread($this->in, 1);
        if(strlen($ch) > 0)
            return ord($ch);
        else
            return;
    }

    function getMBUInt() {
        $uint = 0;

        while(1) {
          $byte = $this->getByte();

          $uint |= $byte & 0x7f;

          if($byte & 0x80)
              $uint = $uint << 7;
          else
              break;
        }

        return $uint;
    }

    function getStringTable() {
        $stringtable = "";

        $length = $this->getMBUInt();
        if($length > 0)
            $stringtable = fread($this->in, $length);

        return $stringtable;
    }

    function getMapping($cp, $id) {
        if(!isset($this->dtd["codes"][$cp]) || !isset($this->dtd["codes"][$cp][$id]))
            return false;
        else {
            if(isset($this->dtd["namespaces"][$cp])) {
                return $this->dtd["namespaces"][$cp] . ":" . $this->dtd["codes"][$cp][$id];
            } else
                return $this->dtd["codes"][$cp][$id];
        }
    }
}

class WBXMLEncoder {
    var $_dtd;
    var $_out;

    var $_tagcp;
    var $_attrcp;

    var $logStack = array();

    // We use a delayed output mechanism in which we only output a tag when it actually has something
    // in it. This can cause entire XML trees to disappear if they don't have output data in them; Ie
    // calling 'startTag' 10 times, and then 'endTag' will cause 0 bytes of output apart from the header.

    // Only when content() is called do we output the current stack of tags

    var $_stack;

    function WBXMLEncoder($output, $dtd) {
        $this->_out = $output;

        $this->_tagcp = 0;
        $this->_attrcp = 0;

        // reverse-map the DTD
        foreach($dtd["namespaces"] as $nsid => $nsname) {
            $this->_dtd["namespaces"][$nsname] = $nsid;
        }

        foreach($dtd["codes"] as $cp => $value) {
            $this->_dtd["codes"][$cp] = array();
            foreach($dtd["codes"][$cp] as $tagid => $tagname) {
                $this->_dtd["codes"][$cp][$tagname] = $tagid;
            }
        }
        $this->_stack = array();
    }

    function startWBXML() {
        header("Content-Type: application/vnd.ms-sync.wbxml");

        $this->outByte(0x03); // WBXML 1.3
        $this->outMBUInt(0x01); // Public ID 1
        $this->outMBUInt(106); // UTF-8
        $this->outMBUInt(0x00); // string table length (0)
    }

    function startTag($tag, $attributes = false, $nocontent = false) {
        $stackelem = array();

        if(!$nocontent) {
            $stackelem['tag'] = $tag;
            $stackelem['attributes'] = $attributes;
            $stackelem['nocontent'] = $nocontent;
            $stackelem['sent'] = false;

            array_push($this->_stack, $stackelem);

            // If 'nocontent' is specified, then apparently the user wants to force
            // output of an empty tag, and we therefore output the stack here
        } else {
            $this->_outputStack();
            $this->_startTag($tag, $attributes, $nocontent);
        }
    }

    function endTag() {
        $stackelem = array_pop($this->_stack);

        // Only output end tags for items that have had a start tag sent
        if($stackelem['sent']) {
            $this->_endTag();
        }
    }

    function content($content) {
        // We need to filter out any \0 chars because it's the string terminator in WBXML. We currently
        // cannot send \0 characters within the XML content anywhere.
        $content = str_replace("\0","",$content);

        if("x" . $content == "x")
            return;
        $this->_outputStack();
        $this->_content($content);
    }

    // Output any tags on the stack that haven't been output yet
    function _outputStack() {
        for($i=0;$i<count($this->_stack);$i++) {
            if(!$this->_stack[$i]['sent']) {
                $this->_startTag($this->_stack[$i]['tag'], $this->_stack[$i]['attributes'], $this->_stack[$i]['nocontent']);
                $this->_stack[$i]['sent'] = true;
            }
        }
    }

    // Outputs an actual start tag
    function _startTag($tag, $attributes = false, $nocontent = false) {
        $this->logStartTag($tag, $attributes, $nocontent);

        $mapping = $this->getMapping($tag);

        if(!$mapping)
            return false;

        if($this->_tagcp != $mapping["cp"]) {
            $this->outSwitchPage($mapping["cp"]);
            $this->_tagcp = $mapping["cp"];
        }

        $code = $mapping["code"];
        if(isset($attributes) && is_array($attributes) && count($attributes) > 0) {
            $code |= 0x80;
        }

        if(!isset($nocontent) || !$nocontent)
            $code |= 0x40;

        $this->outByte($code);

        if($code & 0x80)
            $this->outAttributes($attributes);
    }

    // Outputs actual data
    function _content($content) {
        $this->logContent($content);
        $this->outByte(WBXML_STR_I);
        $this->outTermStr($content);
    }

    // Outputs an actual end tag
    function _endTag() {
        $this->logEndTag();
        $this->outByte(WBXML_END);
    }

    // --------------------------- Private

    function outByte($byte) {
        fwrite($this->_out, chr($byte));
    }

    function outMBUInt($uint) {
        while(1) {
            $byte = $uint & 0x7f;
            $uint = $uint >> 7;
            if($uint == 0) {
                $this->outByte($byte);
                break;
            } else {
                $this->outByte($byte | 0x80);
            }
        }
    }

    function outTermStr($content) {
        fwrite($this->_out, $content);
        fwrite($this->_out, chr(0));
    }

    function outAttributes() {
        // We don't actually support this, because to do so, we would have
        // to build a string table before sending the data (but we can't
        // because we're streaming), so we'll just send an END, which just
        // terminates the attribute list with 0 attributes.
        $this->outByte(WBXML_END);
    }

    function outSwitchPage($page) {
        $this->outByte(WBXML_SWITCH_PAGE);
        $this->outByte($page);
    }

    function getMapping($tag) {
        $mapping = array();

        $split = $this->splitTag($tag);

        if(isset($split["ns"])) {
            $cp = $this->_dtd["namespaces"][$split["ns"]];
        } else {
            $cp = 0;
        }

        $code = $this->_dtd["codes"][$cp][$split["tag"]];

        $mapping["cp"] = $cp;
        $mapping["code"] = $code;

        return $mapping;
    }

    function splitTag($fulltag) {
        $ns = false;
        $pos = strpos($fulltag, chr(58)); // chr(58) == ':'

        if($pos) {
            $ns = substr($fulltag, 0, $pos);
            $tag = substr($fulltag, $pos+1);
        } else {
            $tag = $fulltag;
        }

        $ret = array();
        if($ns)
            $ret["ns"] = $ns;
        $ret["tag"] = $tag;

        return $ret;
    }

    function logStartTag($tag, $attr, $nocontent) {
        if(!WBXML_DEBUG)
            return;

        $spaces = str_repeat(" ", count($this->logStack));
        if($nocontent)
            debugLog("O " . $spaces . " <$tag/>");
        else {
            array_push($this->logStack, $tag);
            debugLog("O " . $spaces . " <$tag>");
        }
    }

    function logEndTag() {
        if(!WBXML_DEBUG)
            return;

        $spaces = str_repeat(" ", count($this->logStack));
        $tag = array_pop($this->logStack);
        debugLog("O " . $spaces . "</$tag>");
    }

    function logContent($content) {
        if(!WBXML_DEBUG)
            return;

        $spaces = str_repeat(" ", count($this->logStack));
        debugLog("O " . $spaces . $content);
    }
}