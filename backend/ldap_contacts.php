<?php
/***************************************************************************
 *  Z-Push LDAP Contacts Diffbackend                                       *
 *  Copyright (C) 2008 by Zarafa Deutschland GmbH, and                     *
 *                2009 by Rutger ter Borg                                  *
 *		  2010 by Marco van Beek, Forget About IT Ltd.             *
 *		  2010 snippet of code from Contagged Project		   *
 *                                                                         *
 *  This library is free software; you can redistribute it and/or          *
 *  modify it under the terms of the GNU Lesser General Public             *
 *  License as published by the Free Software Foundation; either           *
 *  version 2.1 of the License, or (at your option) any later version.     *
 *                                                                         *
 *  This library is distributed in the hope that it will be useful,        *
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of         *
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU      *
 *  Lesser General Public License for more details.                        *
 *                                                                         *
 *  You should have received a copy of the GNU Lesser General Public       *
 *  License along with this library; if not, write to the Free Software    *
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  *
 ***************************************************************************/

include_once('diffbackend.php');

class BackendLDAP_Contacts extends BackendDiff {

    protected $_link_id;

    var $_username;
    var $_domain;
    var $_password;
    var $_config;

    function BackendLDAP_Contacts($config) {
	$this->_config = $config;
    }

    function Logon( $username, $domain, $password ) {
        debugLog('LdapContacts::Logon(user='.$username.')');

	# Replace variables in config
	foreach ( $this->_config as $key => $value )
	    {
	    # Enter variables to replace ...
	    debugLog("LdapContacts::Config: Updating $key");
    	    $this->_config[$key] = str_replace( "%u", $username, $this->_config[$key] );
	    debugLog("LdapContacts::Config: Updated $key with " .$this->_config[$key]);
	    }

        $this->_link_id = ldap_connect( $this->_config['LDAP_SERVER'] );
        debugLog( "Successfully connected to LDAP server, link id ". $this->_link_id );
        debugLog( "Login dn: " .$this->_config['LDAP_USER_DN']);
        //
        // do v3 stuff here
        ldap_set_option($this->_link_id, LDAP_OPT_PROTOCOL_VERSION, 3);
        //
        if ( ldap_bind( $this->_link_id, $this->_config['LDAP_USER_DN'], $password ) ) {
            debugLog( "Successfully bound to LDAP server" );
            } else {
                  debugLog( "Could not bind to LDAP server: " . ldap_error( $this->_link_id ) );
                  return false;
            }
        return true;
    }


    function Logoff() {
        debugLog('LdapContacts::Logoff()');
            if ( ldap_unbind( $this->_link_id ) ) {
                  debugLog( "LDAP connection closed successfully" );
            } else {
                  debugLog( "LDAP could not disconnect: " . ldap_error( $this->_link_id ) );
                  //return false;
            }
        return true;
    }

    function SendMail($rfc822, $forward = false, $reply = false, $parent = false) {
        return false;
    }

    function GetWasteBasket($class) {
        return false;
    }

    function GetMessageList( $folderid, $cutoffdate ) {
        $ldap_date = gmdate( "YmdHis", $cutoffdate )."Z";
        debugLog('LdapContacts::GetMessageList('.$folderid.', '.$cutoffdate .', ldap date '.$ldap_date.')');

        $messages = array();

        // TODO make this configurable
        // use an LDAP filter to only get the last modified contacts :-)
        // this uses the ConTagged idea of "Private" and "Public" addressbooks, so it is an array here
	debugLog("LdapContacts::GetMessageList Public:"  .$this->_config['LDAP_PUBLIC_CONTACTS']);
	debugLog("LdapContacts::GetMessageList Private:" .$this->_config['LDAP_PRIVATE_CONTACTS']); 

        $base_dns = array( $this->_config['LDAP_PUBLIC_CONTACTS'], $this->_config['LDAP_PRIVATE_CONTACTS']);
        # $base_dns = array( $this->_config['LDAP_PUBLIC_CONTACTS']);

        foreach( $base_dns as $base_dn ) {

	    debugLog( "LdapContacts::GetMessageList searching " .$base_dn ." (modifyTimestamp>=" .$ldap_date .")");

            # $result_id = ldap_list( $this->_link_id, $base_dn, "(&(uid=*)(modifyTimestamp>=".$ldap_date."))", array( "entryUUID", "modifyTimestamp" ) ); // "(modifyTimestamp>=".$cutoffdate.")" ); //, array( "entryUUID", "modifyTimestamp" ) );
            $result_id = ldap_list( $this->_link_id, $base_dn, "(modifyTimestamp>=".$ldap_date.")", array( "entryUUID", "modifyTimestamp" ) ); // "(modifyTimestamp>=".$cutoffdate.")" ); //, array( "entryUUID", "modifyTimestamp" ) );
	    
            debugLog( "LdapContacts::GetMessageList returned ". ldap_count_entries( $this->_link_id, $result_id ). " entries" );

            // minimize memory footprint, iterate over result set
            for( $entry_id = ldap_first_entry( $this->_link_id, $result_id ); 
                         $entry_id != false; 
                         $entry_id = ldap_next_entry( $this->_link_id, $entry_id ) ) {
                $uid = ldap_get_values( $this->_link_id, $entry_id, "entryUUID" );
                $m_time = ldap_get_values( $this->_link_id, $entry_id, "modifyTimestamp" );
                array_push( $messages, array( "id" => $uid[0], "mod" => $m_time[0], "flags" => 1 ) );
            }
            ldap_free_result( $result_id );
        }

        return $messages;
    }

    function GetFolderList() {
        debugLog('LdapContacts::GetFolderList()');
        $contacts = array();
        $folder = $this->StatFolder("root");
        $contacts[] = $folder;

        return $contacts;
    }

    function GetFolder($id) {
        debugLog('LdapContacts::GetFolder('.$id.')');
        if($id == "root") {
            $folder = new SyncFolder();
            $folder->serverid = $id;
            $folder->parentid = "0";
            $folder->displayname = "Contacts";
            $folder->type = SYNC_FOLDER_TYPE_CONTACT;

            return $folder;
    	    } 
	else 
	    {
    	    debugLog('LdapContacts::GetFolder:Folder id not root');
	    return false;
	    }
    }

    function StatFolder($id) {
        debugLog('LdapContacts::StatFolder('.$id.')');
        $folder = $this->GetFolder($id);
        $stat = array();
        $stat["id"] = $id;
        $stat["parent"] = $folder->parentid;
        $stat["mod"] = $folder->displayname;
        return $stat;
    }

    function GetAttachmentData($attname) {
        return false;
    }

    function GetMessageEntry( $folderid, $id, $attribute_array = false ) {
        debugLog('LdapContacts::GetMessageEntry('.$folderid.', '.$id.')');
	debugLog("LdapContacts::GetMessageList Public:"  .$this->_config['LDAP_PUBLIC_CONTACTS'] );
	debugLog("LdapContacts::GetMessageList Private:" .$this->_config['LDAP_PRIVATE_CONTACTS'] ); 

        $base_dns = array( $this->_config['LDAP_PUBLIC_CONTACTS'], $this->_config['LDAP_PRIVATE_CONTACTS'] );
        # $base_dns = array( $this->_config['LDAP_PUBLIC_CONTACTS'] );

        foreach( $base_dns as $base_dn ) {
            if ( $attribute_array ) {
                $result_id = ldap_list( $this->_link_id, $base_dn, "(entryUUID=".$id.")", $attribute_array );
            } else {
                $result_id = ldap_list( $this->_link_id, $base_dn, "(entryUUID=".$id.")" );
            }
            if ( $result_id ) {
                $entry_id = ldap_first_entry( $this->_link_id, $result_id );
                if ( $entry_id ) {
                    return array( $result_id, $entry_id );
                }
            }
        }
        return false;
    }

    function StatMessage( $folderid, $id ) {
        debugLog('LdapContacts::StatMessage('.$folderid.', '.$id.')');
        if($folderid != "root")
            return false;

        $message_entry = $this->GetMessageEntry( $folderid, $id, array( "modifyTimestamp" ) );
        if ( $message_entry ) {
            list( $result_id, $entry_id ) = $message_entry;
            $m_time = ldap_get_values( $this->_link_id, $entry_id, "modifyTimestamp" );
            ldap_free_result( $result_id );
            return array( "id" => $id, "mod" => $m_time[0], "flags" => 1 );
        }
        return false;
    }


    // This code splits an address according to international
    // address formatting
    function SplitPostal( $combined_address ) {
        $result = array();
        $lines = preg_split( '/[\n\r]+/', $combined_address );

        $countries = array( "Belgium","Netherlands","United Kingdom" );
        $default_country = $this->_config['LDAP_DEFAULT_COUNTRY'];

        $country_line = array_slice( $lines, -1, 1 );
        $country = $country_line[ 0 ];

        if ( !in_array( $country, $countries ) ) {
            $country = $default_country;
        }

        $result[ "country" ] = $country;

        if ( $country == "Netherlands" ) {
            // this code parses Dutch addresses
            $result[ "street" ] = $lines[0];
            preg_match( '/^([0-9]{4}[ ]*[a-zA-Z]{2})[ ](.*)$/', $lines[1], $matches );
            $result[ "postalcode" ] = $matches[1];
            $result[ "city" ] = $matches[2];
        }

        if ( $country == "Belgium" || $country == "België" || $country == "Belgie" ) {
            // this code parses Belgium addresses
            $result[ "street" ] = $lines[0];
            preg_match( '/^([0-9]{4})[ ](.*)$/', $lines[1], $matches );
            $result[ "postalcode" ] = $matches[1];
            $result[ "city" ] = $matches[2];
        }
	
        if ( $country == "United Kingdom" ) {
            // this code parses UK addresses
	    // We will assume that the first line is the street, second is the town / city, third is the county, fourth is the postcode 
            $result[ "street" ] = $lines[0];
            $result[ "city" ] = $lines[1];
	    $result[ "state" ] = $lines[2];
            $result[ "postalcode" ] = $lines[3];
        }
	

	# Bit of idiot checking...
	if ( !$result["street"] )	$result["street"] = "";
	if ( !$result["city"] ) 	$result["city"] = "";
	if ( !$result["state"] )	$result["state"] = "";
	if ( !$result["postalcode"] ) 	$result["postalcode"] = "";
	if ( !$result["country"] )	$result["country"] = "";

        return $result;
    }



    function GetMessage( $folderid, $id, $truncsize, $mimesupport = 0 ) {
        debugLog('LdapContacts::GetMessage('.$folderid.', '.$id.', ' .$truncsize .')' );
        if ( $folderid != "root" )
            return;

        // TODO reuse this mapping (see MessageToArray)
        $message = new SyncContact();
        $mapping = array(
            "secretary" => array( &$message->assistantname ),
            "facsimileTelephoneNumber" => array( &$message->businessfaxnumber ),
            "telephoneNumber" => array( &$message->businessphonenumber, &$message->business2phonenumber ),
            "o" => array( &$message->companyname ),
            "mail" => array( &$message->email1address, &$message->email2address, &$message->email3address ),
            "homePhone" => array( &$message->homephonenumber, &$message->home2phonenumber ),
            "givenName" => array( &$message->firstname ),
            "sn" => array( &$message->lastname ),
            "mobile" => array( &$message->mobilephonenumber ),
            "l" => array( &$message->businesscity ),
            "postalCode" => array( &$message->businesspostalcode ),
            "street" => array( &$message->businessstreet ),
            "physicalDeliveryOfficeName" => array( &$message->officelocation ),
            "pager" => array( &$message->pagernumber ),
            "labeledURI" => array( &$message->webpage ),
            "cn" => array( &$message->fileas ),
            "ou" => array( &$message->department ),

            // ConTagged stuff
            "birthday" => array( &$message->birthday ),
            "anniversary" => array( &$message->anniversary ),
        );


        $message_entry = $this->GetMessageEntry( $folderid, $id );
        if ( $message_entry ) {
            list( $result_id, $entry_id ) = $message_entry;

            $attributes = ldap_get_attributes( $this->_link_id, $entry_id );
            $attribute_nr = 0;
            for( $attribute_nr=0; $attribute_nr < $attributes[ "count" ]; ++$attribute_nr ) {
                $attribute_name = $attributes[ $attribute_nr ];
                if (array_key_exists( $attribute_name, $mapping )) {
                    $ldap_values = ldap_get_values( $this->_link_id, $entry_id, $attribute_name );
                    $sync_targets = $mapping[ $attribute_name ];
                    $i = 0;
                    while( ($i < count( $sync_targets)) && ($i < $ldap_values["count"]) ) {
                        if ( ($attribute_name == "birthday") || 
                                                     ($attribute_name == "anniversary") ) {
                            $tz = date_default_timezone_get();
                            date_default_timezone_set('UTC');
                            $sync_targets[ $i ] = strtotime( $ldap_values[ $i ] );
                            date_default_timezone_set($tz);
                        } else {
                            $sync_targets[ $i ] = $ldap_values[ $i ];
                        }
                        ++$i;
                    }
                }

                // part of inetOrgPerson
                if ( $attribute_name == "jpegPhoto" ) {
                    debugLog( "Sending Picture..." );
                    $data = ldap_get_values_len( $this->_link_id, $entry_id, $attribute_name );
                    $message->picture = base64_encode( $data[0] );
                }

                // part of inetOrgPerson
                if ( $attribute_name == "description" ) {
                    $data = ldap_get_values( $this->_link_id, $entry_id, $attribute_name );
                    $message->body = $data[0];
                    $message->bodysize = strlen( $data[0] );
                    $message->bodytruncated = 0;
                }

                // part of inetOrgPerson
                if ( $attribute_name == "homePostalAddress" ) {
                    $data = ldap_get_values( $this->_link_id, $entry_id, $attribute_name );
                    if ( $data[ "count" ] > 0 ) {
                        debugLog( $data[0] );
                        $result = $this->SplitPostal( $data[0] );
                        $message->homestreet = $result[ "street" ];
                        $message->homepostalcode = $result[ "postalcode" ];
                        $message->homecity = $result[ "city" ];
                        $message->homestate = $result[ "state" ];
                        $message->homecountry = $result[ "country" ];
                    }
                }

            }
            ldap_free_result( $result_id );
        }
        debugLog( "Sending contact data of ". $message->firstname. " " . $message->lastname );

        return $message;
    }

    function DeleteMessage( $folderid, $id ) {
        debugLog('LdapContacts::DeleteMessage('.$folderid.', '.$id.')');
        $message_entry = $this->GetMessageEntry( $folderid, $id, array( "entryUUID" ) );
        if ( $message_entry ) {
            list( $result_id, $entry_id ) = $message_entry;
            $message_dn = ldap_get_dn( $this->_link_id, $entry_id );
            ldap_free_result( $result_id );
            ldap_delete( $this->_link_id, $message_dn );
            return true;
        }
        return false;
    }

    function SetReadFlag($folderid, $id, $flags) {
        return false;
    }


    function MessageToLdap( $attribute, $value ) {
        if ( $attribute == "birthday" || $attribute == "anniversary" ) {
            return date( "Y-m-d", $value );
        }
        if ( $attribute == "jpegPhoto" ) {
            return base64_decode( $value );
        }

        if ( $attribute == "description" ) {
            debugLog( "LdapContacts:: Found a description!" );
            debugLog( "LdapContacts:: " .$value );
        }

        return $value;
    }

    function MessageToArray( $message ) {

        // TODO reuse this mapping across functions
        $ldap_array = array();

	# We need to force the last name as it is used for a required field.
	if ( $message->lastname == "" ) $message->lastname = "Unknown";
	$combined_home_address = $message->homestreet ."\n" .$message->homecity ."\n".$message->homestate ."\n" .$message->homepostalcode ."\n" .$message->homecountry;

        $mapping = array(
            "secretary" => array( &$message->assistantname ),
            "facsimileTelephoneNumber" => array( &$message->businessfaxnumber ),
            "telephoneNumber" => array( &$message->businessphonenumber, &$message->business2phonenumber ),
            "o" => array( &$message->companyname ),
            "mail" => array( &$message->email1address, &$message->email2address, &$message->email3address ),
            "homePhone" => array( &$message->homephonenumber, &$message->home2phonenumber ),
            "givenName" => array( &$message->firstname ),
            "sn" => array( &$message->lastname ),
            "mobile" => array( &$message->mobilephonenumber ),
            "l" => array( &$message->businesscity ),
            "postalCode" => array( &$message->businesspostalcode ),
            "street" => array( &$message->businessstreet ),
	    "st" => array( &$message->businessstate ),
            "physicalDeliveryOfficeName" => array( &$message->officelocation ),
            "pager" => array( &$message->pagernumber ),
            "labeledURI" => array( &$message->webpage ),
            "cn" => array( &$message->fileas ),
            "ou" => array( &$message->department ),

            //"jpegPhoto" => array( &$message->picture ),
            // defunct, not getting it back right from the mobile
            //"description" => array( &$message->body ), 

	    //home Address stuff
	    //"homePostalAddress" => array ( &$combined_home_address ),

            // ConTagged stuff
            "birthday" => array( &$message->birthday ),
            "anniversary" => array( &$message->anniversary ),
        );

        foreach( $mapping as $attribute_name => $message_attributes ) {
            foreach( $message_attributes as $message_attribute ) {

                if ( isset( $message_attribute ) ) {
                    debugLog( "LdapContacts::Attribute set for: " .$attribute_name );
                    // It could be that we're dealing with an empty attribute, 
                    // this could mean deletion (we're not sure yet!).
                    // To trigger deletion with the LDAP server, assign an empty array.
                    if ( !array_key_exists( $attribute_name, $ldap_array ) ) {
                        $ldap_array[ $attribute_name ] = array();
                    }
                }

                if ( !empty( $message_attribute ) ) {
                    // LDAP funkyness.
                    // First, assign the variable directly, like
                    // $array[ "mail" ] = "a@b.com";
                    // If that exists, change to an indexed array with
                    // $array[ "mail" ][ 0 ] = "a@b.com";
                    // $array[ "mail" ][ 1 ] = "b@c.com";
                    if ( sizeof( $ldap_array[ $attribute_name ] ) == 0 ) {
                        $ldap_array[ $attribute_name ] = $this->MessageToLdap( $attribute_name, $message_attribute );
                    } else {
                        if ( !is_array( $ldap_array[ $attribute_name ] ) ) {
                            $cur_value = $ldap_array[ $attribute_name ];
                            $ldap_array[ $attribute_name ] = array();
                            $ldap_array[ $attribute_name ][ 0 ] = $cur_value;
                        }
                        $index = sizeof( $ldap_array[ $attribute_name ] );
                        $ldap_array[ $attribute_name ][ $index ] = $this->MessageToLdap( $attribute_name, $message_attribute );
                    }
                }
            }
        }

        //MOD: $ldap_array[ "objectClass" ] = array( 0 => "inetOrgPerson", 1 => "contactPerson" );
	$ldap_array[ "objectClass" ] = array( 0 => "inetOrgPerson" );
        return $ldap_array;
    }


    function ChangeMessage( $folderid, $id, $message ) {

     debugLog('LdapContacts::ChangeMessage('.$folderid.', '.$id.')');

        // disabled until fully functional
        //return false;
        // ldap_mod_del
        // ldap_mod_replace
        // ldap_mod_add

        debugLog( "LdapContacts:: Message body equals: " . $message->body );
        debugLog( "LdapContacts:: Body truncated?      " . $message->bodytruncated );

        $message_entry = $this->GetMessageEntry( $folderid, $id );

        if ( $message_entry ) {
             list( $result_id, $entry_id ) = $message_entry;
            $message_dn = ldap_get_dn( $this->_link_id, $entry_id );

            debugLog( "LdapContacts::Found Record:: dn is " . $message_dn );

            $ldap_array = $this->MessageToArray( $message );

            debugLog( "LdapContacts:: \n" .print_r( $ldap_array, 1 ) );

            return ldap_modify( $this->_link_id, $message_dn, $ldap_array );

        }
	else {
	    # No matching record so we need to create it...
	    # First we need a new dn value. Methodology respectfully pinched from Contagged Project.
	    # Only problem with this is that it can lead to duplicates as 'time' is only seconds.
	    $NewContactFolderName = "LDAP_" .$this->_config['LDAP_NEW_CONTACT_FOLDER'] ."_CONTACTS"; 
	    $NewContactFolder = $this->_config[$NewContactFolderName];
	    
	    $new_uid = time() .str_pad(mt_rand(0,999999),6,"0", STR_PAD_LEFT);
	    $new_dn = "uid=" .$new_uid ."," .$NewContactFolder;
	    debugLog("LdapContacts::New Record:: dn will be " .$new_dn );

            $ldap_array = $this->MessageToArray( $message );

	    # Make sure we have something in the surname field, as it is required.
	    # if ( !$ldap_array['sn'] || $ldap_array['sn'] == "" ) $ldap_array['sn'] = "Unknown";
	    # Know done in MessageToArray function

            debugLog( "LdapContacts::Array Dump: \n" .print_r( $ldap_array, 1 ) );


	    if ( ldap_add( $this->_link_id, $new_dn, $ldap_array ) ) {
		debugLog("LdapContacts::Record sucessfully added. LDAP said: " .ldap_error($this->_link_id) );
		# Get the new UUID so that we can return it
		$result_id = ldap_list( $this->_link_id, $NewContactFolder, "(uid=" .$new_uid .")", array( "entryUUID", "sn"),0,1);

		# Bit of debugging code
		# $info = ldap_get_entries( $this->_link_id, $result_id);

		if ( ldap_count_entries( $this->_link_id, $result_id ) > 0 ) {
		    debugLog("LdapContacts:: Got " .ldap_count_entries( $this->_link_id, $result_id )  ." match for new record");
		    $new_info = ldap_get_entries( $this->_link_id, $result_id );
		    for ( $i=0; $i<$new_info['count']; $i++ ) {
			$newid = $new_info[$i]["entryuuid"][0];
			debugLog("LdapContacts::entryUUID " .$newid);
		    }
		    debugLog("LdapContacts:: New id is " .$newid );
		}		    
		return $this->StatMessage($folderid, $newid);
	    }
	    else return false;
	}
	
        return false;
    }

    function MoveMessage($folderid, $id, $newfolderid) {
        return false;
    }

    /* At this moment we DO NOT support AlterPing
     * But because Ping does not have a cutoffdate,
     * we get into issues. So we just "fake" AlterPing
     */
    function AlterPing() {
	    return true;
    }

    function AlterPingChanges($folderid, &$syncstate) {
	    return array();
    }
};
?>
