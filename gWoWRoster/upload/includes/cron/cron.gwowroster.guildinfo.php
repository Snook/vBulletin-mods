<?php
	// ########################################################################
	//
	// gWoWRoster, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gWoWRoster, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: cron.gwowroster.guildinfo.php 1176 2009-04-17 02:38:44Z ghryphen $
	// $Rev: 1176 $
	// $LastChangedBy: ghryphen $
	// $Date: 2009-04-16 19:38:44 -0700 (Thu, 16 Apr 2009) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);
	if (!is_object($vbulletin->db))
	{
		exit;
	}

	// ########################## REQUIRE BACK-END ############################
	//require_once('./global.php');
	require_once('./includes/functions_gwowroster.php');

	$histdate = strtotime(date('Y-m-d', TIMENOW));
	$parse_failure = false;

	$guilds = explode("\n", $vbulletin->options['gwr_guild']);

	foreach ($guilds AS $item)
	{
		list($guild['locale'], $guild['realm'], $guild['name']) = explode("|", trim($item));

		$delete['locale'] .= "'" . $guild['locale'] . "',";
		$delete['realm'] .= "'" . addslashes($guild['realm']) . "',";
		$delete['name'] .= "'" . addslashes($guild['name']) . "',";

		$faction = 'Horde';

		require_once('./includes/class_gwowarmory.php');

		$x = new gWoWArmory;
		$x->SetLocale($guild['locale']);
		$x->SetRealmName($guild['realm']);
		$x->SetGuildName($guild['name']);

		$guild_info = $x->FetchGuildInfo();

		if ($guild_info != false)
		{
			$guild_info['guildInfo']['guildHeader']['locale'] = $guild['locale'];

			if (is_array($guild_info['guildInfo']['guild']['members']['character']))
			{

				foreach ($guild_info['guildInfo']['guild']['members']['character'] as $character)
				{

					if ($character['raceId'] == 1 || $character['raceId'] == 3 || $character['raceId'] == 4 || $character['raceId'] == 7 || $character['raceId'] == 11 || $character['raceId'] == 22)
					{
						$faction = 'Alliance';
					}

					$count_level = sprintf("%02d", floor($character['level'] / 10) * 10);

					$guild_info['guildInfo']['guild']['count' . $count_level] = $guild_info['guildInfo']['guild']['count' . $count_level] + 1;

					$guild_info['guildInfo']['guild']['class' . $character['classId']] = $guild_info['guildInfo']['guild']['class' . $character['classId']] +1;

					$guild_info['guildInfo']['guild']['race' . $character['raceId']] = $guild_info['guildInfo']['guild']['race' . $character['raceId']] +1;

					$guild_info['guildInfo']['guild']['racegen' . $character['raceId'] . $character['genderId']] = $guild_info['guildInfo']['guild']['racegen' . $character['raceId'] . $character['genderId']] +1;

					if($character['genderId'] == '0')
					{
						$character['gender'] = 'Male';
					}
					else
					{
						$character['gender'] = 'Female';
					}

					switch ( $character['raceId'] )
					{
						case '1':
							$character['race'] = 'Human';
							break;
						case '2':
							$character['race'] = 'Orc';
							break;
						case '3':
							$character['race'] = 'Dwarf';
							break;
						case '4':
							$character['race'] = 'Night Elf';
							break;
						case '5':
							$character['race'] = 'Undead';
							break;
						case '6':
							$character['race'] = 'Tauren';
							break;
						case '7':
							$character['race'] = 'Gnome';
							break;
						case '8':
							$character['race'] = 'Troll';
							break;
						case '9':
							$character['race'] = 'Goblin';
							break;
						case '10':
							$character['race'] = 'Blood Elf';
							break;
						case '11':
							$character['race'] = 'Draenei';
							break;
						case '22':
							$character['race'] = 'Worgen';
							break;
					}

					switch ( $character['classId'] )
					{
						case '1':
							$character['class'] = 'Warrior';
							break;
						case '2':
							$character['class'] = 'Paladin';
							break;
						case '3':
							$character['class'] = 'Hunter';
							break;
						case '4':
							$character['class'] = 'Rogue';
							break;
						case '5':
							$character['class'] = 'Priest';
							break;
						case '6':
							$character['class'] = 'Death Knight';
							break;
						case '7':
							$character['class'] = 'Shaman';
							break;
						case '8':
							$character['class'] = 'Mage';
							break;
						case '9':
							$character['class'] = 'Warlock';
							break;
						case '11':
							$character['class'] = 'Druid';
							break;
					}

					$rosterinfo_query = "
						locale = '" . $guild_info['guildInfo']['guildHeader']['locale'] . "' ,
						realm = '" . addslashes($guild_info['guildInfo']['guildHeader']['realm']) . "' ,
						name = '" . addslashes($guild_info['guildInfo']['guildHeader']['name']) . "' ,
						charname = '" . addslashes($character['name']) . "' ,
						level = '" . $character['level'] . "' ,
						achpoints = '" . $character['achPoints'] . "' ,
						class = '" . $character['class'] . "' ,
						classid = '" . $character['classId'] . "' ,
						gender = '" . $character['gender'] . "' ,
						genderid = '" . $character['genderId'] . "' ,
						race = '" . $character['race'] . "' ,
						raceid = '" . $character['raceId'] . "' ,
						rank = '" . $character['rank'] . "' ,
						charurl = '" . $character['url'] . "' ,
					";

					// INSERT GUILD ROSTER
					if(trim($guild_info['guildInfo']['guildHeader']['realm']) != '' && trim($guild_info['guildInfo']['guildHeader']['name']) != '' && trim($guild_info['guildInfo']['guildHeader']['locale']) != '')
					{
						$vbulletin->db->query("REPLACE INTO " . TABLE_PREFIX . "gwowroster_rosterinfo SET " . $rosterinfo_query . " updated = '" . TIMENOW . "'");

						// INSERT HISTORICAL ENTRY
						if($vbulletin->options['gwr_guildhistory'])
						{
							$vbulletin->db->query("REPLACE INTO " . TABLE_PREFIX . "gwowroster_hist_rosterinfo SET " . $rosterinfo_query . " date = '" . $histdate . "'");
						}

						$delete['charname'] .= "'" . addslashes($character['name']) . "',";
					}

				}

			}

			$guild_info['guildInfo']['guildHeader']['faction'] = $faction;

			$guild_info['guildInfo']['guild']['count00'] =
				$guild_info['guildInfo']['guild']['members']['memberCount'] -
				$guild_info['guildInfo']['guild']['count80'] -
				$guild_info['guildInfo']['guild']['count70'] -
				$guild_info['guildInfo']['guild']['count60'] -
				$guild_info['guildInfo']['guild']['count50'] -
				$guild_info['guildInfo']['guild']['count40'] -
				$guild_info['guildInfo']['guild']['count30'] -
				$guild_info['guildInfo']['guild']['count20'] -
				$guild_info['guildInfo']['guild']['count10'];

			$quildinfo_query = "
				locale = '" . $guild_info['guildInfo']['guildHeader']['locale'] . "' ,
				realm = '" . addslashes($guild_info['guildInfo']['guildHeader']['realm']) . "' ,
				name = '" . addslashes($guild_info['guildInfo']['guildHeader']['name']) . "' ,
				faction = '" . $guild_info['guildInfo']['guildHeader']['faction'] . "' ,
				membercount = '" . $guild_info['guildInfo']['guild']['members']['memberCount'] . "' ,
				battlegroup = '" . $guild_info['guildInfo']['guildHeader']['battleGroup'] . "' ,
				count80 = '" . $guild_info['guildInfo']['guild']['count80'] . "' ,
				count70 = '" . $guild_info['guildInfo']['guild']['count70'] . "' ,
				count60 = '" . $guild_info['guildInfo']['guild']['count60'] . "' ,
				count50 = '" . $guild_info['guildInfo']['guild']['count50'] . "' ,
				count40 = '" . $guild_info['guildInfo']['guild']['count40'] . "' ,
				count30 = '" . $guild_info['guildInfo']['guild']['count30'] . "' ,
				count20 = '" . $guild_info['guildInfo']['guild']['count20'] . "' ,
				count10 = '" . $guild_info['guildInfo']['guild']['count10'] . "' ,
				count00 = '" . $guild_info['guildInfo']['guild']['count00'] . "' ,
				class1 = '" . $guild_info['guildInfo']['guild']['class1'] . "' ,
				class2 = '" . $guild_info['guildInfo']['guild']['class2'] . "' ,
				class3 = '" . $guild_info['guildInfo']['guild']['class3'] . "' ,
				class4 = '" . $guild_info['guildInfo']['guild']['class4'] . "' ,
				class5 = '" . $guild_info['guildInfo']['guild']['class5'] . "' ,
				class6 = '" . $guild_info['guildInfo']['guild']['class6'] . "' ,
				class7 = '" . $guild_info['guildInfo']['guild']['class7'] . "' ,
				class8 = '" . $guild_info['guildInfo']['guild']['class8'] . "' ,
				class9 = '" . $guild_info['guildInfo']['guild']['class9'] . "' ,
				class11 = '" . $guild_info['guildInfo']['guild']['class11'] . "' ,
				race1 = '" . $guild_info['guildInfo']['guild']['race1'] . "' ,
				race2 = '" . $guild_info['guildInfo']['guild']['race2'] . "' ,
				race3 = '" . $guild_info['guildInfo']['guild']['race3'] . "' ,
				race4 = '" . $guild_info['guildInfo']['guild']['race4'] . "' ,
				race5 = '" . $guild_info['guildInfo']['guild']['race5'] . "' ,
				race6 = '" . $guild_info['guildInfo']['guild']['race6'] . "' ,
				race7 = '" . $guild_info['guildInfo']['guild']['race7'] . "' ,
				race8 = '" . $guild_info['guildInfo']['guild']['race8'] . "' ,
				race10 = '" . $guild_info['guildInfo']['guild']['race10'] . "' ,
				race11 = '" . $guild_info['guildInfo']['guild']['race11'] . "' ,
				racegen11 = '" . $guild_info['guildInfo']['guild']['racegen11'] . "' ,
				racegen10 = '" . $guild_info['guildInfo']['guild']['racegen10'] . "' ,
				racegen21 = '" . $guild_info['guildInfo']['guild']['racegen21'] . "' ,
				racegen20 = '" . $guild_info['guildInfo']['guild']['racegen20'] . "' ,
				racegen31 = '" . $guild_info['guildInfo']['guild']['racegen31'] . "' ,
				racegen30 = '" . $guild_info['guildInfo']['guild']['racegen30'] . "' ,
				racegen41 = '" . $guild_info['guildInfo']['guild']['racegen41'] . "' ,
				racegen40 = '" . $guild_info['guildInfo']['guild']['racegen40'] . "' ,
				racegen51 = '" . $guild_info['guildInfo']['guild']['racegen51'] . "' ,
				racegen50 = '" . $guild_info['guildInfo']['guild']['racegen50'] . "' ,
				racegen61 = '" . $guild_info['guildInfo']['guild']['racegen61'] . "' ,
				racegen60 = '" . $guild_info['guildInfo']['guild']['racegen60'] . "' ,
				racegen71 = '" . $guild_info['guildInfo']['guild']['racegen71'] . "' ,
				racegen70 = '" . $guild_info['guildInfo']['guild']['racegen70'] . "' ,
				racegen81 = '" . $guild_info['guildInfo']['guild']['racegen81'] . "' ,
				racegen80 = '" . $guild_info['guildInfo']['guild']['racegen80'] . "' ,
				racegen101 = '" . $guild_info['guildInfo']['guild']['racegen101'] . "' ,
				racegen100 = '" . $guild_info['guildInfo']['guild']['racegen100'] . "' ,
				racegen111 = '" . $guild_info['guildInfo']['guild']['racegen111'] . "' ,
				racegen110 = '" . $guild_info['guildInfo']['guild']['racegen110'] . "' ,
			";

			// INSERT GUILD INFO
			if(trim($guild_info['guildInfo']['guildHeader']['realm']) != '' && trim($guild_info['guildInfo']['guildHeader']['name']) != '' && trim($guild_info['guildInfo']['guildHeader']['locale']) != '')
			{
				$vbulletin->db->query("REPLACE INTO " . TABLE_PREFIX . "gwowroster_guildinfo SET " . $quildinfo_query . " updated = '" . TIMENOW . "'");

				// INSERT HISTORICAL ENTRY
				if($vbulletin->options['gwr_guildhistory'])
				{
					$vbulletin->db->query("REPLACE INTO " . TABLE_PREFIX . "gwowroster_hist_guildinfo SET " . $quildinfo_query . " date = '" . $histdate . "'");
				}
			}

			if ($_REQUEST['do'] == 'runcron')
			{
				$guild_updated .= $guild_info['guildInfo']['guildHeader']['name'] . ", ";
				//echo '<pre>';
				//print_r($guild_info);
				//echo '</pre><hr />';
			}

			unset($guild_info, $page, $newpage, $xml, $fetch_url, $faction, $quildinfo_query, $rosterinfo_query);

		}
		else
		{
			$parse_failure = true;
		}

	}

	if (!$parse_failure)
	{
		// DELETE INFO
		$delete['locale'] = substr($delete['locale'], 0, -1);
		$delete['realm'] = substr($delete['realm'], 0, -1);
		$delete['name'] = substr($delete['name'], 0, -1);
		$delete['charname'] = substr($delete['charname'], 0, -1);

		if($delete['name'] != "")
		{
			$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "gwowroster_guildinfo WHERE NOT (
				locale IN(" . $delete['locale'] . ") AND
				realm IN(" . $delete['realm'] . ") AND
				name IN(" . $delete['name'] . ")
			)");
		}

		if($delete['charname'] != "")
		{
			$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "gwowroster_rosterinfo WHERE NOT (
				locale IN(" . $delete['locale'] . ") AND
				realm IN(" . $delete['realm'] . ") AND
				charname IN(" . $delete['charname'] . ")
			)");
		}

		if($delete['charname'] != "")
		{
			$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "gwowroster_charinfo WHERE NOT (
				locale IN(" . $delete['locale'] . ") AND
				realm IN(" . $delete['realm'] . ") AND
				name IN(" . $delete['name'] . ") AND
				charname IN(" . $delete['charname'] . ")
			)");
		}

		if ($vbulletin->options['gwr_guildhistory_prune'])
		{
			if($delete['name'] != "")
			{
				$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "gwowroster_hist_guildinfo WHERE NOT (
					locale IN(" . $delete['locale'] . ") AND
					realm IN(" . $delete['realm'] . ") AND
					name IN(" . $delete['name'] . ")
				)");

				$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "gwowroster_hist_rosterinfo WHERE NOT (
					locale IN(" . $delete['locale'] . ") AND
					realm IN(" . $delete['realm'] . ") AND
					name IN(" . $delete['name'] . ")
				)");
			}
		}
	}
	else
	{
		if ($_REQUEST['do'] == 'runcron')
		{
			echo 'No delete, a parse failed.<br />';
		}
	}
	$guild_updated = substr($guild_updated,0,-2);
	log_cron_action($guild_updated, $nextitem, 1);
	echo $guild_updated;
?>