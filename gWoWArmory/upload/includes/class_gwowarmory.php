<?php
	// ########################################################################
	//
	// gWoWArmory, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gWoWArmory, please contact me at ghryphen@gmail.com for collaboration.
	// I appreciate your kind consideration.
	//
	// This work is licensed under the Creative Commons
	// Attribution-Noncommercial-No Derivative Works 3.0 United States License.
	// To view a copy of this license, visit
	// http://creativecommons.org/licenses/by-nc-nd/3.0/us/ or send a letter to
	// Creative Commons, 171 Second Street, Suite 300,
	// San Francisco, California, 94105, USA.
	//
	// ########################### SVN info ###################################
	// $Id: class_gwowarmory.php 1168 2009-04-17 01:09:02Z ghryphen $
	// $Rev: 1168 $
	// $LastChangedBy: ghryphen $
	// $Date: 2009-04-16 18:09:02 -0700 (Thu, 16 Apr 2009) $

	class gWoWArmory {

		var $fetchurl;
		var $charname;
		var $guildname;
		var $realmname;
		var $locale;

		function SetFetchURL( $fetchurl )
		{
			$this->fetchurl = $fetchurl;
		}

		function SetRealmName( $realmname )
		{
			$this->realmname = $realmname;
		}

		function SetCharName( $charname )
		{
			$this->charname = $charname;
		}

		function SetGuildName( $guildname )
		{
			$this->guildname = $guildname;
		}

		function SetLocale( $locale = NULL )
		{
			$locale = strtolower($locale);

			switch ($locale)
			{
				case "us":
					$this->locale = $locale . ".wowarmory.com";
					break;
				case "eu":
					$this->locale = $locale . ".wowarmory.com";
					break;
				case "tw":
					$this->locale = $locale . ".wowarmory.com";
					break;
				case "kr":
					$this->locale = $locale . ".wowarmory.com";
					break;
				case "cn":
					$this->locale = $locale . ".wowarmory.com";;
					break;
				default:
					$this->locale = "us.wowarmory.com";
			}
		}

		function FetchCharacterSheet()
		{
			return $this->_FetchArmoryCharacter("sheet");
		}

		function FetchCharacterSkills()
		{
			return $this->_FetchArmoryCharacter("skills");
		}

		function FetchCharacterTalents()
		{
			return $this->_FetchArmoryCharacter("talents");
		}

		function FetchCharacterReputation()
		{
			return $this->_FetchArmoryCharacter("reputation");
		}

		function FetchCharacterAchievements()
		{
			return $this->_FetchArmoryCharacter("achievements");
		}

		function FetchCharacterStatistics()
		{
			return $this->_FetchArmoryCharacter("statistics");
		}

		function _FetchArmoryCharacter ( $tab )
		{
			$this->SetFetchURL("http://" . $this->locale . "/character-" . $tab . ".xml?r=" . urlencode( $this->realmname ) . "&cn=" . urlencode( $this->charname ) . "&rhtml=n" );

			$array = $this->ParseXML();

			if ( $array != false )
			{
				return $array;
			}
			else
			{
				return false;
			}
		}

		function FetchGuildInfo()
		{
			return $this->_FetchArmoryGuild("info");
		}

		function FetchGuildStats()
		{
			return $this->_FetchArmoryGuild("stats");
		}

		function _FetchArmoryGuild( $tab )
		{
			$this->SetFetchURL("http://" . $this->locale . "/guild-" . $tab . ".xml?r=" . urlencode( $this->realmname ) . "&gn=" . urlencode( $this->guildname ) . "&rhtml=n" );

			$array = $this->ParseXML();

			if ( is_array($array) )
			{
				return $array;
			}
			else
			{
				return false;
			}
		}

		function FetchArmoryItemID( $itemid )
		{
			$this->SetFetchURL("http://" . $this->locale . "/item-info.xml?i=" . $itemid . "&rhtml=n" );

			$array = $this->ParseXML();

			if ( is_array($array) )
			{
				return $array;
			}
			else
			{
				return false;
			}
		}

		function FetchArmoryItemName( $itemname )
		{
			$this->SetFetchURL("http://" . $this->locale . "/search.xml?searchQuery=" . urlencode( $itemname ) . "&searchType=items&rhtml=n" );

			$array = $this->ParseXML();

			if ( is_array($array) )
			{
				//return $this->FetchArmoryItemID($array['armorySearch']['searchResults']['items']['item']['id'])
				return $array;
			}
			else
			{
				return false;
			}
		}

		function FetchURL()
		{
			sleep(5); // Sleep a bit cause Armory doesn't seem to like fast queries.

			$fp = curl_init();

			curl_setopt( $fp, CURLOPT_URL, $this->fetchurl );
			curl_setopt( $fp, CURLOPT_HEADER, 0 );
			curl_setopt( $fp, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $fp, CURLOPT_CONNECTTIMEOUT, 10 );
			curl_setopt( $fp, CURLOPT_TIMEOUT, 10 );

			$data = curl_exec( $fp );
			curl_close( $fp );

			if( $data )
			{
				return $data;
			}
			else
			{
				return false;
			}
		}

		function ParseXML()
		{
			$xml = $this->FetchURL( $this->fetchurl );

			if ( $xml != false )
			{
				require_once(DIR . '/includes/class_xml.php');

				$xmlobj = new vB_XML_Parser( $xml );

				return $xmlobj->parse();
			}
			else
			{
				return false;
			}
		}

	}
?>