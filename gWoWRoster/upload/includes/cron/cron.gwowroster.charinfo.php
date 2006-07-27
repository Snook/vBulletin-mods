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
	// $Id: cron.gwowroster.charinfo.php 1167 2009-04-17 01:08:46Z ghryphen $
	// $Rev: 1167 $
	// $LastChangedBy: ghryphen $
	// $Date: 2009-04-16 18:08:46 -0700 (Thu, 16 Apr 2009) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);
	if (!is_object($vbulletin->db))
	{
		exit;
	}

	// ########################## REQUIRE BACK-END ############################
	require_once('./includes/functions_gwowroster.php');

	$res = $vbulletin->db->query("SELECT COUNT(*) AS count FROM " . TABLE_PREFIX . "gwowroster_charinfo");
	$count = $vbulletin->db->fetch_array($res);
	//$limit = ceil($count['count'] / 1400);
	$limit = ceil($count['count'] / 70);

	if ($limit == '0')
	{
		$limit = '2';
	}

  $res = $vbulletin->db->query("SELECT r.locale, r.realm, r.name, r.charname, r.charurl, c.updated
  	FROM " . TABLE_PREFIX . "gwowroster_rosterinfo r
    LEFT JOIN " . TABLE_PREFIX . "gwowroster_charinfo c ON (r.locale = c.locale AND r.realm = c.realm AND r.charname = c.charname)
    ORDER BY c.updated ASC LIMIT 0, " . $limit);

	while ($char = $vbulletin->db->fetch_array($res))
	{
		require_once('./includes/class_gwowarmory.php');
		$x = new gWoWArmory;
		$x->SetLocale($char['locale']);
		$char['charname'] = utf8_encode($char['charname']);
		$x->SetRealmName($char['realm']);
		$x->SetCharName($char['charname']);

		$character['sheet'] = $x->FetchCharacterSheet();
		
		if ($character['sheet'] != false)
		{	
			$talents_array = $character['sheet']['characterInfo']['characterTab']['talentSpecs'];
	
			if(isset($talents_array['talentSpec']['0']))
			{
				if($talents_array['talentSpec']['0']['active'])
				{
					$talentspec = $talents_array['talentSpec']['0']['treeOne'] . "," . $talents_array['talentSpec']['0']['treeTwo'] . "," . $talents_array['talentSpec']['0']['treeThree'];
				}
				else
				{
					$talentspec = $talents_array['talentSpec']['1']['treeOne'] . "," . $talents_array['talentSpec']['1']['treeTwo'] . "," . $talents_array['talentSpec']['1']['treeThree'];
				}
			}
			else
			{
				$talentspec = $talents_array['talentSpec']['treeOne'] . "," . $talents_array['talentSpec']['treeTwo'] . "," . $talents_array['talentSpec']['treeThree']; 
			}
		
			$char['charname'] = utf8_decode($char['charname']);

			$charinfo_query = "
				locale = '" . $char['locale'] . "' ,
				realm = '" . addslashes($char['realm']) . "' ,
				name = '" . addslashes($char['name']) . "' ,
				charname = '" . $char['charname'] . "' ,
				talentspec = '" . $talentspec . "' ,
				talenttree = '0' ,
			";

			if (trim($char['charname']) != '')
			{
				$vbulletin->db->query("REPLACE INTO " . TABLE_PREFIX . "gwowroster_charinfo SET " . $charinfo_query . " updated = '" . TIMENOW . "'");
			}

			unset($skill, $prof, $professions);

			if ($_REQUEST['do'] == 'runcron')
			{
				//echo $char['charname'] . '<pre>';
				//print_r($character['sheet']['characterInfo']['skillTab']['skillCategory']);
				//print_r($professions);
				//print_r($character['sheet']);
				//echo '<pre><hr />';
			}

			$char_updated .= $char['charname'] . ", ";
			//log_cron_action($char['charname'], $nextitem, 1);

		}
		elseif (trim($char['charname']) != '')
		{
			$vbulletin->db->query("UPDATE " . TABLE_PREFIX . "gwowroster_charinfo SET updated = '" . TIMENOW . "' WHERE locale = '" . $char['locale'] . "' AND realm = '" . addslashes($char['realm']) . "' AND charname = '" . $char['charname'] . "'");
			$char_updated .= "Not Found: " . $char['charname'] . ", ";
			//log_cron_action("Not Found: " . $char['charname'], $nextitem, 1);
		}

	}
	$char_updated = substr($char_updated,0,-2);
	log_cron_action($char_updated, $nextitem, 1);
	echo $char_updated;

?>