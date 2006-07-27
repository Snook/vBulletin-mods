<?php

define( "XBOXXML_KEEPTIME", 5 ); // how long to keep downloaded data before grabbing a new copy.

class XboxData {

	var $gamertag; // stores the currently used nickname
	var $type; // holds what type of feed is being parsed

	/**
	 * Sets the gamertag to use for downloading feeds
	 *
	 * @param string $gamertag
	 */
	function SetGamertag( $gamertag )
	{
		$this->gamertag = $gamertag;
	}

	/**
	 * Gets the user's presence information
	 *
	 * @return array Profile / false if failed
	 */
	function GetPresence( ) // Presence
	{
		return $this->ParseFeed( "presence" );
	}

	/**
	 * Gets the user's gamercard information
	 *
	 * @return array Profile / false if failed
	 */
	function GetGamercard( ) // Gamercard
	{
		return $this->ParseFeed( "gamercard" );
	}
	
	/**
	 * Set API GUID
	 *
	 * @param string $guid
	 */
	function SetGUID( $guid ) // GUID
	{
		$this->guid = $guid;
	}

	/**
	 * Downloads a fresh copy of an XML feed from the Xbox servers
	 *
	 * @param string $type
	 */
	function DownloadFeed( $type )
	{
		$fp = curl_init(); // we use curl for this

		$download_url = "http://xboxfeed.xaged.com/?guid=" . $this->guid . "&" . $type . "&tag=" . urlencode ( $this->gamertag ); // construct the download url

		curl_setopt( $fp, CURLOPT_URL, $download_url ); // set the url
		curl_setopt( $fp, CURLOPT_HEADER, 0 ); // do not include the header in the downloaded content
		curl_setopt( $fp, CURLOPT_RETURNTRANSFER, true ); // yes....
		curl_setopt( $fp, CURLOPT_REFERER, "http://" . $download_guid );
		curl_setopt( $fp, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $fp, CURLOPT_TIMEOUT, 5 );

		$xml = curl_exec( $fp ); // get what we need
		curl_close( $fp ); // close the handle

		require_once(DIR . '/includes/class_xml.php');
		$xmlobj = new vB_XML_Parser($xml);
		$xml_parse = $xmlobj->parse();

		if( ( $xml_parse['GamerPresence']['gamertag'] || $xml_parse['GamerInfo']['gamertag'] ) && $h = fopen( "xbox_cache/" . $this->gamertag . "_" . $type . ".xml" , "w" ) ) // can we write?
		{
			fwrite( $h, $xml ); // yes we can
			fclose( $h );
			@chmod("xbox_cache/" . $this->gamertag . "_" . $type . ".xml", 0666);
		}
		else
		{
			//die( "Unable to create file handle. Make sure the file is writable." ); // no we can't
		}
	}

	/**
	 * Parses a specific feed, you can call this directly, or use some of the "helper" functions above. Feed types: [presence, gamertag]
	 *
	 * @param string $type
	 * @return array page / false if error
	 */
	function ParseFeed( $type )
	{

		// we're using XML Caching
		$timenow = date( "U" ); // time now

		if( file_exists( "xbox_cache/" .$this->gamertag . "_" . $type . ".xml" ) == true ) // check file exists.
		{
			if( $timenow > ( filemtime( "xbox_cache/" . $this->gamertag . "_" . $type . ".xml" ) + ( XBOXXML_KEEPTIME * 60 ) ) ) // new download x minutes
			{
				$this->DownloadFeed( $type ); // we need to download
			}
		}
		else
		{
			$this->DownloadFeed( $type ); // yeah we really need to download
		}

		$xml = file_get_contents ( "xbox_cache/" .$this->gamertag . "_" . $type . ".xml" ) ;

		if ($xml != false)
		{
			require_once(DIR . '/includes/class_xml.php');

			$xmlobj = new vB_XML_Parser($xml);

			return $xmlobj->parse();
		}
		else
		{
			return false;
		}

	}
}
/*

if (!$_GET["gamertag"])
{
	$gamertag = "Ghyphon";
}
else
{
	$gamertag = $_GET["gamertag"];
}

$x = new XboxData;
$x->SetGamertag( $gamertag );

$presence = $x->GetPresence();
$profile = $x->GetGamercard();

echo '<pre>';
print_r($presence);
echo '</pre><hr />';
echo '<pre>';
print_r($profile);
echo '</pre><hr />';
*/
?>