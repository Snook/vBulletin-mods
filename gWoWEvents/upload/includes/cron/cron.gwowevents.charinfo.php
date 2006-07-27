<?php
	// ########################################################################
	//
	// gWoWEvents, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gWoWEvents, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: cron.gwowevents.charinfo.php 1173 2009-04-17 02:05:38Z ghryphen $
	// $Rev: 1173 $
	// $LastChangedBy: ghryphen $
	// $Date: 2009-04-16 19:05:38 -0700 (Thu, 16 Apr 2009) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);
	if (!is_object($vbulletin->db))
	{
		exit;
	}

	// ########################## REQUIRE BACK-END ############################
	require_once('./includes/functions_gwowevents.php');

	$res = $vbulletin->db->query("SELECT * FROM " . TABLE_PREFIX . "gwowevents WHERE `armorydata` != '1' ORDER BY `armorydata` ASC");

	while ($char = $vbulletin->db->fetch_array($res))
	{
		require_once('./includes/class_gwowarmory.php');
		$x = new gWoWArmory;
		$x->SetLocale($char['locale']);
		$char['charname'] = utf8_encode($char['charname']);
		$x->SetRealmName($char['realm']);
		$x->SetCharName($char['character']);

		sleep(5);
		$character = $x->FetchCharacterSheet();
		//sleep(10);
		//$character_skills = $x->FetchCharacterSkills();

		if ($character != false)
		{
			$character['prof1'] = array();
			$character['skill1'] = array();

			if ($character['characterInfo']['characterTab']['professions']['skill']['0'])
			{
				$character['prof1'] = $character['characterInfo']['characterTab']['professions']['skill']['0']['name'];
				$character['skill1'] = $character['characterInfo']['characterTab']['professions']['skill']['0']['value']['0'];
			}
			else
			{
				$character['prof1'] = $character['characterInfo']['characterTab']['professions']['skill']['name'];
				$character['skill1'] = $character['characterInfo']['characterTab']['professions']['skill']['value']['0'];
			}
			/*
			$secondaryskill = array();
			foreach($character_skills['characterInfo']['skillTab']['skillCategory']['1']['skill'] as $skill)
			{
				$secondaryskill[$skill['key']] = array();
				$secondaryskill[$skill['key']] = $skill['value']['0'];
			}
			*/

			$char['charname'] = utf8_decode($char['charname']);

			$talents_array = $character['characterInfo']['characterTab']['talentSpecs'];

			if(isset($talents_array['talentSpec']['0']))
			{
				if($talents_array['talentSpec']['0']['active'])
				{
					$talentspec = $talents_array['talentSpec']['0']['treeOne'] . "," . $talents_array['talentSpec']['0']['treeTwo'] . "," . $talents_array['talentSpec']['0']['treeThree'];
					$talenttreeOne = $talents_array['talentSpec']['0']['treeOne'];
					$talenttreeTwo = $talents_array['talentSpec']['0']['treeTwo'];
					$talenttreeThree = $talents_array['talentSpec']['0']['treeThree'];
				}
				else
				{
					$talentspec = $talents_array['talentSpec']['1']['treeOne'] . "," . $talents_array['talentSpec']['1']['treeTwo'] . "," . $talents_array['talentSpec']['1']['treeThree'];
					$talenttreeOne = $talents_array['talentSpec']['1']['treeOne'];
					$talenttreeTwo = $talents_array['talentSpec']['1']['treeTwo'];
					$talenttreeThree = $talents_array['talentSpec']['1']['treeThree'];
				}
			}
			else
			{
				$talentspec = $talents_array['talentSpec']['treeOne'] . "," . $talents_array['talentSpec']['treeTwo'] . "," . $talents_array['talentSpec']['treeThree'];
				$talenttreeOne = $talents_array['talentSpec']['treeOne'];
				$talenttreeTwo = $talents_array['talentSpec']['treeTwo'];
				$talenttreeThree = $talents_array['talentSpec']['treeThree'];
			}


			$charinfo_query = "
				`charurl` = '" . $character['characterInfo']['character']['charUrl'] . "',
				`level` = '" . $character['characterInfo']['character']['level'] . "',
				`title` = '" . $character['characterInfo']['character']['title'] . "',
				`faction` = '" . $character['characterInfo']['character']['faction'] . "',
				`factionid` = '" . $character['characterInfo']['character']['factionId'] . "',
				`gender` = '" . $character['characterInfo']['character']['gender'] . "',
				`genderid` = '" . $character['characterInfo']['character']['genderId'] . "',
				`race` = '" . $character['characterInfo']['character']['race'] . "',
				`raceid` = '" . $character['characterInfo']['character']['raceId'] . "',
				`class` = '" . $character['characterInfo']['character']['class'] . "',
				`classid` = '" . $character['characterInfo']['character']['classId'] . "',
				`talentspec` = '" . $talentspec . "',
				`talent1` = '" . $talenttreeOne . "',
				`talent2` = '" . $talenttreeTwo . "',
				`talent3` = '" . $talenttreeThree . "',
				`guild` = '" . $character['characterInfo']['character']['guildName'] . "',
				`guildurl` = '" . $character['characterInfo']['character']['guildUrl'] . "',
				`hp` = '" . $character['characterInfo']['characterTab']['characterBars']['health']['effective'] . "',
				`mp` = '" . $character['characterInfo']['characterTab']['characterBars']['secondBar']['effective'] . "',
				`str` = '" . $character['characterInfo']['characterTab']['baseStats']['strength']['effective'] . "',
				`agi` = '" . $character['characterInfo']['characterTab']['baseStats']['agility']['effective'] . "',
				`sta` = '" . $character['characterInfo']['characterTab']['baseStats']['stamina']['effective'] . "',
				`int` = '" . $character['characterInfo']['characterTab']['baseStats']['intellect']['effective'] . "',
				`spi` = '" . $character['characterInfo']['characterTab']['baseStats']['spirit']['effective'] . "',
				`arm` = '" . $character['characterInfo']['characterTab']['baseStats']['armor']['effective'] . "',
				`strbase` = '" . $character['characterInfo']['characterTab']['baseStats']['strength']['base'] . "',
				`agibase` = '" . $character['characterInfo']['characterTab']['baseStats']['agility']['base'] . "',
				`stabase` = '" . $character['characterInfo']['characterTab']['baseStats']['stamina']['base'] . "',
				`intbase` = '" . $character['characterInfo']['characterTab']['baseStats']['intellect']['base'] . "',
				`spibase` = '" . $character['characterInfo']['characterTab']['baseStats']['spirit']['base'] . "',
				`armbase` = '" . $character['characterInfo']['characterTab']['baseStats']['armor']['base'] . "',
				`prof1` = '" . $character['prof1'] . "',
				`skill1` = '" . $character['skill1'] . "',
				`prof2` = '" . $character['characterInfo']['characterTab']['professions']['skill']['1']['name'] . "',
				`skill2` = '" . $character['characterInfo']['characterTab']['professions']['skill']['1']['value']['0'] . "',
				`cooking` = '" . $secondaryskill['cooking'] . "',
				`firstaid` = '" . $secondaryskill['firstaid'] . "',
				`fishing` = '" . $secondaryskill['fishing'] . "',
				`riding` = '" .$secondaryskill['riding'] . "',
			";

			$char['realmchar'] = addslashes($char['realm'] . "." . $char['character']);

			if ($character['characterInfo'] && $character['characterInfo']['characterTab']['characterBars']['health']['effective'])
			{
				$vbulletin->db->query("UPDATE " . TABLE_PREFIX . "gwowevents SET " . $charinfo_query . " `armorydata` = '1' WHERE `realmchar` = '" . $char['realmchar'] . "'");
			}
			else
			{
				$vbulletin->db->query("UPDATE " . TABLE_PREFIX . "gwowevents SET `armorydata` = '2' WHERE `realmchar` = '" . $char['realmchar'] . "'");
			}
		}

		if (VB_AREA == 'AdminCP')
		{
			//echo $char['character'] . '<pre>';
			//print_r($character);
			//print_r($character_skills);
			//echo '<pre><hr />';
		}

		$char_updated .= $char['character'] . ", ";

	}

	echo $char_updated;
?>