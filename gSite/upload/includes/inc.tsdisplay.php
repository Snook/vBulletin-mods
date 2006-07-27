<?php
// Teamspeak Display Preview Release 1
// Copyright (C) 2005  Guido van Biemen (aka MrGuide@NL)
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

class teamspeakDisplayClass {

	// Removes subsequent end of line charachter from the right part of a string
	function _stripEOL($evalString) {
		$newLen = strlen($evalString);
		while (((substr($evalString, $newLen - 1, 1) == "\r")) || ((substr($evalString, $newLen - 1, 1) == "\n"))) {
			$newLen--;
		}
		return substr($evalString, 0, $newLen);
	}

	// Opens a connection to the teamspeak server
	function _openConnection(&$socket, $host, $port, $timeout) {
		@$socket = fsockopen($host, $port, $errno, $errstr, $timeout);
		if ($socket and ($this->_stripEOL(fgets($socket, 4096)) == "[TS]")) {
			return true;
		} else {
			return false;
		}
	}

	// Closes the connection to the Teamspeak server
	function _closeConnection($socket) {
		fputs($socket, "quit\n");
		fclose($socket);
	}

	// Returns the part of evalString until a tab (or the end of a string) and deletes the
	// returned part from evalString (including the possible tab that follows)
	function _stripPartFromString(&$evalString) {
		$pos = strpos($evalString, "\t");
		if(is_integer($pos)) {
			$result = substr($evalString, 0, $pos);
			$evalString = substr($evalString, $pos + 1);
		} else {
			$result = $evalString;
			$evalString = "";
		}
		return $result;
	}

	// Removes the surrounding quotes from evalString and returns the result
	function _stripQuotes($evalString) {
		if(strpos($evalString, '"') == 0) $evalString = substr($evalString, 1, strlen($evalString) - 1);
		if(strrpos($evalString, '"') == strlen($evalString) - 1) $evalString = substr($evalString, 0, strlen($evalString) - 1);
		return $evalString;
	}

	// Request, read and parse the server info:
	function _getServerInfo($socket) {
		fputs($socket, "si\n");
		$result = array();
		do {
			$buffer = $this->_stripEOL(fgets($socket, 4096));
			if ($buffer != "OK") {
				$pos = strpos($buffer, '=');
				if ($pos !== False) {
					$result[substr($buffer, 0, $pos)] = substr($buffer, $pos + 1);
				}
			}
		} while (($buffer != "OK") && (!feof($socket)));
		return $result;
	}

	function _setPlayerDisplayImage(&$playerInfo) {
		// Determine the right userpicture:
		if (($playerInfo["attribute"] & 32) == 32) { $playerImage = "mutespeakers"; }
		else if (($playerInfo["attribute"] & 16) == 16) { $playerImage = "mutemicrophone"; }
		else if (($playerInfo["attribute"] & 8) == 8) { $playerImage = "away"; }
		else if (($playerInfo["attribute"] & 1) == 1) { $playerImage = "channelcommander"; }
		else { $playerImage = "normal"; }
		$playerInfo["displayimage"] = $playerImage;
	}

	function _setPlayerDisplayName(&$playerInfo) {
		// Determine the player status (U = Unregistered, R = Registered, SA = Server Admin,
		// CA = Channel Admin, AO = Auto-Operator, AV = Auto-Voice, O = Operator, V = Voice)
		if (($playerInfo["userstatus"] & 4) == 4) { $playerstatus = "R"; } else { $playerstatus = 'U'; }
		if (($playerInfo["userstatus"] & 1) == 1) { $playerstatus .= " SA"; }
		if (($playerInfo["privileg"] & 1) == 1) { $playerstatus .= " CA"; }
		if (($playerInfo["privileg"] & 8) == 8) { $playerstatus .= " AO"; }
		if (($playerInfo["privileg"] & 16) == 16) { $playerstatus .= " AV"; }
		if (($playerInfo["privileg"] & 2) == 2) { $playerstatus .= " O"; }
		if (($playerInfo["privileg"] & 4) == 4) { $playerstatus .= " V"; }
		if (($playerInfo["attribute"] & 64) == 64) { $playerstatus .= " Rec"; }

		// Determine the player attributes to be listed behind the player status (WV = Want Voice)
		if (($playerInfo["attribute"] & 2) == 2) { $playerattributes = ' WV'; } else { $playerattributes = ''; }

//		$playerInfo["displayname"] = $playerInfo["playername"] . " (" . $playerstatus . ")" . $playerattributes;
		$playerInfo["displayname"] = $playerInfo["playername"];
	}

	function _getPlayerList($socket) {
		// Request, read and parse the player list
		fputs($socket, "pl\n");
		$buffer = fgets($socket, 4096);
		$result = array();
		do {
			$buffer = $this->_stripEOL(fgets($socket, 4096));
			if ($buffer != "OK") {
				$playerid = $this->_stripPartFromString($buffer);
				$result[$playerid] = array(
					"playerid" => $playerid,
					"channelid" => $this->_stripPartFromString($buffer),
					"receivedpackets" => $this->_stripPartFromString($buffer),
					"receivedbytes" => $this->_stripPartFromString($buffer),
					"sentpackets" => $this->_stripPartFromString($buffer),
					"sentbytes" => $this->_stripPartFromString($buffer),
					"paketlost" => $this->_stripPartFromString($buffer) / 100,
					"pingtime" => $this->_stripPartFromString($buffer),
					"totaltime" => $this->_stripPartFromString($buffer),
					"idletime" => $this->_stripPartFromString($buffer),
					"privileg" => $this->_stripPartFromString($buffer),
					"userstatus" => $this->_stripPartFromString($buffer),
					"attribute" => $this->_stripPartFromString($buffer),
					"ip" => $this->_stripPartFromString($buffer),
					"playername" => $this->_stripQuotes($this->_stripPartFromString($buffer)),
					"loginname" => $this->_stripQuotes($this->_stripPartFromString($buffer))
				);
				$this->_setPlayerDisplayImage($result[$playerid]);
				$this->_setPlayerDisplayName($result[$playerid]);
			}
		} while (($buffer != "OK") && (!feof($socket)));
		return $result;
	}

	function _setChannelDisplayName(&$channelInfo) {
		if ($channelInfo["parent"] == "") {
			$channelInfo["displayname"] = $channelInfo["channelname"];
		} else {
			// Determine the channel status (U = Unregisterd, R = Registered, M = Moderated,
			// P = Passworded, S = Sub-channels, D = Default).
			if (($channelInfo["flags"] & 1) == 1) {$channelstatus ='U';} else {$channelstatus ='R';}
			if (($channelInfo["flags"] & 2) == 2) {$channelstatus .='M';}
			if (($channelInfo["flags"] & 4) == 4) {$channelstatus .='P';}
			if (($channelInfo["flags"] & 8) == 8) {$channelstatus .='S';}
			if (($channelInfo["flags"] & 16) == 16) {$channelstatus .='D';}
			//$channelInfo["displayname"] = $channelInfo["channelname"] . " (" . $channelstatus . ")";
			$channelInfo["displayname"] = $channelInfo["channelname"];
		}
	}

	function _getChannelList($socket) {
		// Request, read and parse the channel list
		fputs($socket, "cl\n");
		$buffer = fgets($socket, 4096);
		$result = array();
		do {
			$buffer = $this->_stripEOL(fgets($socket, 4096));
			if ($buffer != "OK") {
				$channelid = $this->_stripPartFromString($buffer);
				$result[$channelid] = array(
					"channelid" => $channelid,
					"codec" => $this->_stripPartFromString($buffer),
					"parent" => $this->_stripPartFromString($buffer),
					"order" => $this->_stripPartFromString($buffer),
					"maxplayers" => $this->_stripPartFromString($buffer),
					"channelname" => $this->_stripQuotes($this->_stripPartFromString($buffer)),
					"flags" => $this->_stripPartFromString($buffer),
					"password" => $this->_stripPartFromString($buffer),
					"topic" => $this->_stripQuotes($this->_stripPartFromString($buffer))
				);
				$this->_setChannelDisplayName($result[$channelid]);
			}
		} while (($buffer != "OK") && (!feof($socket)));
		return $result;
	}

	function _selectServer($socket, $port) {
		// Request the server to select the server which is hosted on  the port set in serverUDPPort
		fputs($socket, "sel ".$port . "\n");

		// Read server response on request to select a server
		return ($this->_stripEOL(fread($socket, 4096)) == "OK");
	}

	// Queries the Teamspeak server
	function queryTeamspeakServer($serverAddress, $serverUDPPort, $serverQueryPort) {
		$result = array();

		// Try to establish a connection to the teamspeak server
		if (! $this->_openConnection($socket, $serverAddress, $serverQueryPort, 0.3)) {
			$result["queryerror"] = 1;
		} else if (! $this->_selectServer($socket, $serverUDPPort)) {
			$result["queryerror"] = 2;
			$this->_closeConnection($socket);
		} else {
			$result["queryerror"] = 0;
			$result["serverinfo"] = $this->_getServerInfo($socket);
			$result["playerlist"] = $this->_getPlayerList($socket);
			$result["channellist"] = $this->_getChannelList($socket);
			$this->_closeConnection($socket);
		}
		return $result;
	}

	// Emulation of teamspeaks quirkish sorting comparison method:
	function _orderAlpha($str1, $str2) {
		if (strlen($str1) > strlen($str2)) { $limit = strlen($str2); } else { $limit = strlen($str1); }

		for ($i = 0; $i < $limit; $i++) {
			if ((substr($str1, $i, 1) == " ") && (substr($str2, $i, 1) != " ")) { return 1; }
			else if ((substr($str1, $i, 1) != " ") && (substr($str2, $i, 1) == " ")) { return -1; }
			else if (substr($str1, $i, 1) > substr($str2, $i, 1)) { return 1; }
			else if (substr($str1, $i, 1) < substr($str2, $i, 1)) { return -1; }
		}

		if (strlen($str1) > strlen($str2)) { return -1; }
		else if (strlen($str1) < strlen($str2)) { return 1; }

		return 0;
	}

	function _compareChannel($a, $b) {
		if ($a["order"] != $b["order"]) { return ($a["order"] < $b["order"]) ? -1 : 1; }
		else { return $this->_orderAlpha($a["channelname"], $b["channelname"]); }
	}

	function _comparePlayer($a, $b) {
		// Determine userlevel (0 = Not server admin, 1 = Server admin)
		$userlevela = $a["userstatus"] & 1;
		$userlevelb = $b["userstatus"] & 1;
		if ($userlevela != $userlevelb) { return ($userlevela < $userlevelb) ? 1 : -1; }
		else { return $this->_orderAlpha($a["playername"], $b["playername"]); }
	}

	function sortServerInfo(&$serverInfo) {
		usort($serverInfo["channellist"], array($this, "_compareChannel"));
		usort($serverInfo["playerlist"], array($this, "_comparePlayer"));
	}


	function _formatTime($totaltime) {
		$hours = round($totaltime / 3600);
		$minutes = round(($totaltime % 3600) / 60);
		return (($hours < 10) ? "0" : "") . $hours . ":" . (($minutes < 10) ? "0" : "") . $minutes;
	}

	// Returns the codec name
	function _getCodecName($codec) {
		if ($codec == 0) { return "CELP 5.1 Kbit"; }
		else if ($codec == 1) { return "CELP 6.3 Kbit"; }
		else if ($codec == 2) { return "GSM 14.8 Kbit"; }
		else if ($codec == 3) { return "GSM 16.4 Kbit"; }
		else if ($codec == 4) { return "CELP Windows 5.2 Kbit"; }
		else if ($codec == 5) { return "Speex 3.4 Kbit"; }
		else if ($codec == 6) { return "Speex 5.2 Kbit"; }
		else if ($codec == 7) { return "Speex 7.2 Kbit"; }
		else if ($codec == 8) { return "Speex 9.3 Kbit"; }
		else if ($codec == 9) { return "Speex 12.3 Kbit"; }
		else if ($codec == 10) { return "Speex 16.3 Kbit"; }
		else if ($codec == 11) { return "Speex 19.5 Kbit"; }
		else if ($codec == 12) { return "Speex 25.9 Kbit"; }
		else { return "Unknown (" . $codec . ")";	}
	}

	// Main function (queries, sorts and displays the teamspeak serverinfo). Its code is not
	// very readable... well what shall I say about it... it was hard to write so it should
	// be hard to read >:)
	function displayTeamspeak($serverAddress, $serverUDPPort=8767, $serverQueryPort=51234) {
		$serverInfo = $this->queryTeamspeakServer($serverAddress, $serverUDPPort, $serverQueryPort);

		$tsdisplayout .= "<div id=\"teamspeakdisplay\" name=\"teamspeakdisplay\">\n";
		if ($serverInfo["queryerror"] != 0) {
			$popupInfo = "Server address: " . $serverAddress . (($serverUDPPort != 8767) ? (":" . $serverUDPPort): "");
			if ($serverInfo["queryerror"] == 1) {
				$popupInfo .= ", Error: could not connect to query port";
			} else {
				$popupInfo .= ", Error: no server running on port " . $serverUDPPort;
			}
			$tsdisplayout .= "<table><tr><td>";
			$tsdisplayout .= "<img src=\"/images/ts/teamspeak_offline.gif\" alt=\"\" title=\"" . $popupInfo . "\">";
			$tsdisplayout .= "</td><td class=\"teamspeakserver\" title=\"" . $popupInfo . "\">";
			$tsdisplayout .= "Server offline";
			$tsdisplayout .= "</td></tr></table>\n";
		} else {
			$this->sortServerInfo($serverInfo);

			$popupInfo = "Server address: " . $serverAddress . (($serverUDPPort != 8767) ? (":" . $serverUDPPort): "") . ", Max players: " . $serverInfo["serverinfo"]["server_maxusers"] . ", Uptime: " . $this->_formatTime($serverInfo["serverinfo"]["server_uptime"]);

			// Print the topmost element of the teamspeak tree
			$tsdisplayout .= "<table><tr><td>";
			$tsdisplayout .= "<img src=\"/images/ts/teamspeak_online.gif\" alt=\"\" title=\"\">";
			$tsdisplayout .= "</td><td class=\"teamspeakserver\" title=\"\">";
			$tsdisplayout .= str_replace(" ", "&nbsp;", htmlspecialchars($serverInfo["serverinfo"]["server_name"]));
			$tsdisplayout .= "</td></tr></table>\n";

			// Count the number of channels to be listed:
			$currentchannels = 0;
			foreach($serverInfo["channellist"] as $channelInfo) {
				if ($channelInfo["parent"] == -1) {
					$currentchannels++;
				}
			}

			// Initialize the channelcounter to zero
			$counter = 0;

			// Loop through all channels:
			foreach($serverInfo["channellist"] as $channelInfo) { if ($channelInfo["parent"] == -1) {

				// determine number of players in channel
				$currentplayers = 0;
				foreach($serverInfo["playerlist"] as $playerInfo) {
					if($playerInfo[channelid] == $channelInfo["channelid"]) $currentplayers++;
				}

				// Count the number of channels to be listed:
				$currentplayersandsubchannels = $currentplayers;
				foreach($serverInfo["channellist"] as $subchannelInfo) {
					if ($subchannelInfo["parent"] == $channelInfo["channelid"]) {
						$currentplayersandsubchannels++;
					}
				}

				$popupInfo = "Max players: " . $channelInfo["maxplayers"] . ", Codec: " . $this->_getCodecName($channelInfo["codec"]);
				if ($channelInfo["topic"] != "") { $popupInfo = $popupInfo . ", Topic: " . $channelInfo["topic"]; }

				// Display channel:
				$tsdisplayout .= "<table><tr><td>";
				$tsdisplayout .= "<img src=\"/images/ts/treeimage" . ((($counter + 1) == $currentchannels) ? "3" : "2") . ".gif\" alt=\"\">";
				$tsdisplayout .= "<img src=\"/images/ts/channel.gif\" alt=\"\" title=\"" . $popupInfo . "\">";
				$tsdisplayout .= "</td><td class=\"teamspeakchannel\" title=\"" . $popupInfo . "\">";
				$tsdisplayout .= str_replace(" ", "&nbsp;", htmlspecialchars($channelInfo["displayname"]));
				$tsdisplayout .= "</td></tr></table>\n";

				// Initialize the playercounter for this channel to zero
				$counter_playerandsubchannels = 0;

				// Loop through all players in the current channel:
				foreach($serverInfo["playerlist"] as $playerInfo) {

					// Is the current player in the current channel?
					if ($playerInfo[channelid] == $channelInfo[channelid]) {

						$forum_id = explode(".", $playerInfo["loginname"]);

						$popupInfo = "Time online: " . $this->_formatTime($playerInfo["totaltime"]) . ", Time idle: " . $this->_formatTime($playerInfo["idletime"]) . ", Ping: " . $playerInfo["pingtime"] . "ms";

						// Display player:
						$tsdisplayout .= "<table><tr><td>";
						$tsdisplayout .= "<img src=\"/images/ts/treeimage" . ((($counter + 1) == $currentchannels) ? "4" : "1") . ".gif\" alt=\"\">";
						$tsdisplayout .= "<img src=\"/images/ts/treeimage" . ((($counter_playerandsubchannels + 1) == $currentplayersandsubchannels) ? "3" : "2") . ".gif\" alt=\"\">";
						$tsdisplayout .= "<img src=\"/images/ts/player_" . $playerInfo["displayimage"] . ".gif\" alt=\"" . $playerInfo["displayimage"] . "\" title=\"" . $popupInfo . "\">";
						$tsdisplayout .= "</td><td class=\"teamspeakplayer\" title=\"" . $popupInfo . "\"><a href=\"member.php?u=" . $forum_id[1] . "\">";
						$tsdisplayout .= str_replace(" ", "&nbsp;", htmlspecialchars($playerInfo["displayname"]));
						$tsdisplayout .= "</a></td></tr></table>\n";

						// Increase the player counter:
						$counter_playerandsubchannels++;
					}
				}

				// Loop through all channels:
				foreach($serverInfo["channellist"] as $subchannelInfo) { if ($subchannelInfo["parent"] == $channelInfo["channelid"]) {
					// determine number of players in channel
					$currentplayers = 0;
					foreach($serverInfo["playerlist"] as $playerInfo) {
						if($playerInfo[channelid] == $subchannelInfo["channelid"]) $currentplayers++;
					}

					$popupInfo = "Max players: " . $subchannelInfo["maxplayers"] . ", Codec: " . $this->_getCodecName($subchannelInfo["codec"]);
					if ($subchannelInfo["topic"] != "") { $popupInfo = $popupInfo . ", Topic: " . $subchannelInfo["topic"]; }

					// Display channel:
					$tsdisplayout .= "<table><tr><td>";
					$tsdisplayout .= "<img src=\"/images/ts/treeimage" . ((($counter + 1) == $currentchannels) ? "4" : "1") . ".gif\" alt=\"\">";
					$tsdisplayout .= "<img src=\"/images/ts/treeimage" . ((($counter_playerandsubchannels + 1) == $currentplayersandsubchannels) ? "3" : "2") . ".gif\" alt=\"\">";
					$tsdisplayout .= "<img src=\"/images/ts/channel.gif\" alt=\"\" title=\"" . $popupInfo . "\">";
					$tsdisplayout .= "</td><td class=\"teamspeaksubchannel\" title=\"" . $popupInfo . "\">";
					//$tsdisplayout .= "<a class=\"teamspeaksubchannel\" href=\"javascript:enterSubChannel_" . $jsTeamspeakId . "('" . $channelInfo["channelname"] . "', " . (($channelInfo["password"]) == "1" ? "true" : "false") . ", '" . $subchannelInfo["channelname"] . "');\">";
					$tsdisplayout .= str_replace(" ", "&nbsp;", htmlspecialchars($subchannelInfo[channelname]));
					//$tsdisplayout .= "</a>";
					$tsdisplayout .= "</td></tr></table>\n";

					// Initialize the playercounter for this channel to zero
					$counter_player = 0;

					// Loop through all players in the current channel:
					foreach($serverInfo["playerlist"] as $playerInfo) {

						// Is the current player in the current channel?
						if ($playerInfo[channelid] == $subchannelInfo[channelid]) {

							$forum_id = explode(".", $playerInfo["loginname"]);

							$popupInfo = "Time online: " . $this->_formatTime($playerInfo["totaltime"]) . ", Time idle: " . $this->_formatTime($playerInfo["idletime"]) . ", Ping: " . $playerInfo["pingtime"] . "ms";

							// Display player:
							$tsdisplayout .= "<table><tr><td>";
							$tsdisplayout .= "<img src=\"/images/ts/treeimage" . ((($counter + 1) == $currentchannels) ? "4" : "1") . ".gif\" alt=\"\">";
							$tsdisplayout .= "<img src=\"/images/ts/treeimage" . ((($counter_playerandsubchannels + 1) == $currentplayersandsubchannels) ? "4" : "1") . ".gif\" alt=\"\">";
							$tsdisplayout .= "<img src=\"/images/ts/treeimage" . ((($counter_player + 1) == $currentplayers) ? "3" : "2") . ".gif\" alt=\"\">";
							$tsdisplayout .= "<img src=\"/images/ts/player_" . $playerInfo["displayimage"] . ".gif\" alt=\"" . $playerInfo["displayimage"] . "\" title=\"" . $popupInfo . "\">";
							$tsdisplayout .= "</td><td class=\"teamspeakplayer\" title=\"" . $popupInfo . "\"><a href=\"member.php?u=" . $forum_id[1] . "\">";
							$tsdisplayout .= str_replace(" ", "&nbsp;", htmlspecialchars($playerInfo["displayname"]));
							$tsdisplayout .= "</a></td></tr></table>\n";

							// Increase the player counter:
							$counter_player++;
						}
					}

					// Increase the channelcounter
					$counter_playerandsubchannels++;
				} }

				// Increase the channelcounter
				$counter++;
			} }
		}
		$tsdisplayout .= "</div>\n";

		return $tsdisplayout;
	}
}

// Create an instance of the Teamspeak Display Class
$teamspeakDisplay = new teamspeakDisplayClass;

?>
