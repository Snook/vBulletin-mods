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
	// ########################### SVN INFO ###################################
	// $Id: gwowroster.php 1187 2009-06-22 22:30:10Z ghryphen $
	// $Rev: 1187 $
	// $LastChangedBy: ghryphen $
	// $Date: 2009-06-22 15:30:10 -0700 (Mon, 22 Jun 2009) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gwowroster');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array();

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'gwr_roster',
		'gwr_roster_bit',
		'gwr_talnet',
		'gwr_header',
		'gwr_stats',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');
	require_once('./includes/functions_gwowroster.php');

	// ######################### PERMS ############################
	if ($vbulletin->options['gvipt_usergroup_access'] != '0' && !is_member_of($vbulletin->userinfo, explode(',',$vbulletin->options['gwr_usergroup'])))
	{
		print_no_permission();
		exit;
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gwr_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gWoWRoster', $copyrightyear);

	// ########################################################################
	// ######################### START MAIN SCRIPT ############################
	// ########################################################################

	// ######################### CLEAN GPC ############################
	if (empty($_REQUEST['do']))
	{
		$_REQUEST['do'] = 'roster';
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'sortfield'  => TYPE_STR,
		'sortorder'  => TYPE_STR,
		'loc'        => TYPE_STR,
		'realm'      => TYPE_STR,
		'name'       => TYPE_STR,
		'charname'       => TYPE_STR,
	));

	$guild_array = explode("\n", $vbulletin->options['gwr_guild']);

	$guild_count = count($guild_array);

	if ($vbulletin->GPC['loc'] != '' && $vbulletin->GPC['realm'] != '' && $vbulletin->GPC['name'] != '')
	{
		$guild['locale'] = $vbulletin->GPC['loc'];
		$guild['realm'] = urldecode($vbulletin->GPC['realm']);
		$guild['name'] = urldecode($vbulletin->GPC['name']);
	}
	else
	{
		list($guild['locale'], $guild['realm'], $guild['name']) = explode("|", trim($guild_array['0']));
	}
	
	// ######################### GUILD INFO ############################
	$res = $db->query("SELECT *
		FROM " . TABLE_PREFIX . "gwowroster_guildinfo
		WHERE
			realm = '" . addslashes($guild['realm']) . "' AND
			name = '" . addslashes($guild['name']) . "' AND
			locale = '" . $guild['locale'] . "'
	");
	$guild = $db->fetch_array($res);

	$guild['url'] = 'loc=' . $guild['locale'] . '&amp;realm=' . urlencode($guild['realm']) . '&amp;name=' . urlencode($guild['name']);

	$guild['localelwr'] = strtolower($guild['locale']);
	$guild['realmlwr'] = strtolower($guild['realm']);
	$guild['realmlwraz'] = ereg_replace("[^A-Za-z0-9]", "", $guild['realmlwr']);
	$guild['realmwowprog'] = str_replace(array("'", " "), "-", $guild['realmlwr']);
	$guild['nameencode'] = urlencode($guild['name']);
	$guild['namelwr'] = strtolower($guild['name']);

	foreach ($guild_array as $item)
	{
		list($guild_sel['locale'], $guild_sel['realm'], $guild_sel['name']) = explode("|", trim($item));

		$guild_jump .= '<tr><td class="vbmenu_option"><a href="gwowroster.php?loc=' . $guild_sel['locale'] . '&realm=' . urlencode($guild_sel['realm']) . '&name=' . urlencode($guild_sel['name']) . '">' . $guild_sel['locale'] . ': ' . $guild_sel['realm'] . ': ' . $guild_sel['name'] . '</a></td></tr>';
	}

	$armory = strtolower($guild['locale']) . ".wowarmory.com";

	// ######################### SHOW CHARACTER ############################
	if ($_REQUEST['do'] == 'char')
	{
		if ($_REQUEST['show'] == 'talents')
		{
			$res = $db->query("SELECT r.*, c.*
				FROM " . TABLE_PREFIX . "gwowroster_rosterinfo r
				LEFT JOIN " . TABLE_PREFIX . "gwowroster_charinfo c ON (r.locale = c.locale AND r.realm = c.realm AND r.charname = c.charname)
				WHERE
					r.realm = '" . addslashes($guild['realm']) . "' AND
					r.name = '" . addslashes($guild['name']) . "' AND
					r.locale = '" . $guild['locale'] . "' AND
					r.charname = '" . $vbulletin->GPC['charname'] . "'
			");

			$player = $db->fetch_array($res);

			$count_level = floor($player['level'] / 10) * 10;

			if ($count_level == '80')
			{
				$player['avatar'] = 'wow-80';
			}
			elseif ($count_level == '70')
			{
				$player['avatar'] = 'wow-70';
			}
			elseif ($count_level == '60')
			{
				$player['avatar'] = 'wow';
			}
			else
			{
				$player['avatar'] = 'wow-default';
			}

			eval('$gwr[header] = "' . fetch_template('gwr_header') . '";');
			eval('print_output("' . fetch_template('gwr_talent') . '");');

		}
	}
	// ######################### SHOW ROSTER ############################
	elseif($_REQUEST['do'] == 'roster')
	{

		// ######################### SETUP SORT DIRECTION ############################
		$sortfield = $vbulletin->input->clean_gpc('r', 'sortfield', TYPE_STR);
		$sortorder = $vbulletin->input->clean_gpc('r', 'sortorder', TYPE_STR);

		$oppositesort = iif($sortorder == 'asc', 'desc', 'asc');

		if ($sortorder != '') {
			$switchOrder = ($sortorder)?$sortorder:"";
		} else {
			$switchOrder = "";
		}

		switch ($switchOrder) {
			case "asc":
				$deasc = " ASC";
				break;
			case "desc":
				$deasc = " DESC";
				break;
			default:
				$deasc = " DESC";
		}

		// ######################### SETUP SORT ORDER ############################
		if ($sortfield != '') {
			$switchSort = ($sortfield)?$sortfield:"";
		} else {
			$switchSort = "";
		}
		
		if ($vbulletin->options['gwr_rank_achpoints'])
		{
			$sortach = ", r.achpoints DESC";
		}
		else
		{
			$sortach = "";		
		}

		switch ($switchSort) {
			case "name":
				$sort_query = "r.charname " . $deasc;
				break;
			case "level":
				$sort_query = "r.level " . $deasc . $sortach;
				break;
			case "achpoints":
				$sort_query = "r.achpoints " . $deasc;
				break;				
			case "rank":
				$sort_query = "r.rank " . $deasc;
				break;
			case "cl":
				$sort_query = "r.class " . $deasc . ", r.level DESC";
				break;
			case "gender":
				$sort_query = "r.gender " . $deasc;
				break;
			case "race":
				$sort_query = "r.race " . $deasc;
				break;
			default:
				$sort_query = "r.level DESC" . $sortach;
		}

		// ######################### PAGE NAV ############################
		$pagenumber = $vbulletin->GPC['pagenumber'];
		$perpage = $vbulletin->GPC['perpage'];

		$charcount = $db->query_first("SELECT COUNT(*) AS `count`
			FROM " . TABLE_PREFIX . "gwowroster_rosterinfo
			WHERE
				realm = '" . addslashes($guild['realm']) . "' AND
				name = '" . addslashes($guild['name']) . "' AND
				locale = '" . $guild['locale'] . "'");

		// Make sure all these variables are cool
		sanitize_pageresults($charcount['count'], $pagenumber, $perpage, 100, $vbulletin->options['gwr_perpage']);

		// Default lower and upper limit variables
		$limitlower = ($pagenumber - 1) * $perpage + 1;
		$limitupper = $pagenumber * $perpage;
		if ($limitupper > $charcount['count'])
		{
			// Too many for upper limit
			$limitupper = $charcount['count'];
			if ($limitlower > $charcount['count'])
			{
				// Too many for lower limit
				$limitlower = $charcount['count'] - $perpage;
			}
		}
		if ($limitlower <= 0)
		{
			// Can't have negative or null lower limit
			$limitlower = 1;
		}

		// ######################### CONSTRUCT PAGE NAV ############################
		$pagenav = construct_page_nav($pagenumber, $perpage, $charcount['count'], 'gwowroster.php?' . $vbulletin->session->vars['sessionurl'] . $guild['url'] . '', ''
			. (!empty($sortfield) ? "&amp;sortfield=$sortfield" : "")
			. (!empty($sortorder) ? "&amp;sortorder=$sortorder" : "")
		);

		// ######################### / PAGE NAV ############################

		$res = $db->query("SELECT r.*, c.talentspec, c.talenttree
			FROM " . TABLE_PREFIX . "gwowroster_rosterinfo r
			LEFT JOIN " . TABLE_PREFIX . "gwowroster_charinfo c ON (r.locale = c.locale AND r.realm = c.realm AND r.charname = c.charname)
			WHERE
				r.realm = '" . addslashes($guild['realm']) . "' AND
				r.name = '" . addslashes($guild['name']) . "' AND
				r.locale = '" . $guild['locale'] . "'
			ORDER BY
				" . $sort_query . ",
				r.charname ASC
			LIMIT " . ($limitlower - 1) . "," . $perpage
		);

		while ($player = $db->fetch_array($res))
		{
			list($player['specone'], $player['spectwo'], $player['specthree']) = explode(",", $player['talentspec']);

			$gwr_phrase['specialization']['6']['1'] = 'Blood';
			$gwr_phrase['specialization']['6']['2'] = 'Frost';
			$gwr_phrase['specialization']['6']['3'] = 'Unholy';
			$gwr_phrase['specialization']['11']['1'] = 'Balance';
			$gwr_phrase['specialization']['11']['2'] = 'Feral Combat';
			$gwr_phrase['specialization']['11']['3'] = 'Restoration';
			$gwr_phrase['specialization']['3']['1'] = 'Beast Mastery';
			$gwr_phrase['specialization']['3']['2'] = 'Marksmanship';
			$gwr_phrase['specialization']['3']['3'] = 'Survival';
			$gwr_phrase['specialization']['8']['1'] = 'Arcane';
			$gwr_phrase['specialization']['8']['2'] = 'Fire';
			$gwr_phrase['specialization']['8']['3'] = 'Frost';
			$gwr_phrase['specialization']['2']['1'] = 'Holy';
			$gwr_phrase['specialization']['2']['2'] = 'Protection';
			$gwr_phrase['specialization']['2']['3'] = 'Retribution';
			$gwr_phrase['specialization']['5']['1'] = 'Discipline';
			$gwr_phrase['specialization']['5']['2'] = 'Holy';
			$gwr_phrase['specialization']['5']['3'] = 'Shadow';
			$gwr_phrase['specialization']['4']['1'] = 'Assassination';
			$gwr_phrase['specialization']['4']['2'] = 'Combat';
			$gwr_phrase['specialization']['4']['3'] = 'Subtlety';
			$gwr_phrase['specialization']['7']['1'] = 'Elemental';
			$gwr_phrase['specialization']['7']['2'] = 'Enhancement';
			$gwr_phrase['specialization']['7']['3'] = 'Restoration';
			$gwr_phrase['specialization']['9']['1'] = 'Affliction';
			$gwr_phrase['specialization']['9']['2'] = 'Demonology';
			$gwr_phrase['specialization']['9']['3'] = 'Destruction';
			$gwr_phrase['specialization']['1']['1'] = 'Arms';
			$gwr_phrase['specialization']['1']['2'] = 'Fury';
			$gwr_phrase['specialization']['1']['3'] = 'Protection';

			$count_level = floor($player['level'] / 10) * 10;

			if ($count_level == '80')
			{
				$player['avatar'] = 'wow-80';
			}
			elseif ($count_level == '70')
			{
				$player['avatar'] = 'wow-70';
			}
			elseif ($count_level == '60')
			{
				$player['avatar'] = 'wow';
			}
			else
			{
				$player['avatar'] = 'wow-default';
			}

			$talentspecialization = max($player['specone'], $player['spectwo'], $player['specthree']);

			if ($talentspecialization == $player['specone'] && $talentspecialization > '0')
			{
				$player['specialization'] = $gwr_phrase['specialization'][$player['classid']]['1'];
			}
			elseif ($talentspecialization == $player['spectwo'] && $talentspecialization > '0')
			{
				$player['specialization'] = $gwr_phrase['specialization'][$player['classid']]['2'];
			}
			elseif ($talentspecialization == $player['specthree'] && $talentspecialization > '0')
			{
				$player['specialization'] = $gwr_phrase['specialization'][$player['classid']]['3'];
			}
			else
			{
				$player['specialization'] = 'Untalented';
			}
			
			$player['charname'] = utf8_encode($player['charname']);
			$player['charnamelwr'] = strtolower($player['charname']);

			eval('$rosterbits .= "' . fetch_template('gwr_roster_bit') . '";');
		}

		$guild['percent_class6'] = gwr_percent_members($guild['class6'], $guild['membercount']);
		$guild['percent_class11'] = gwr_percent_members($guild['class11'], $guild['membercount']);
		$guild['percent_class3'] = gwr_percent_members($guild['class3'], $guild['membercount']);
		$guild['percent_class8'] = gwr_percent_members($guild['class8'], $guild['membercount']);
		$guild['percent_class2'] = gwr_percent_members($guild['class2'], $guild['membercount']);
		$guild['percent_class5'] = gwr_percent_members($guild['class5'], $guild['membercount']);
		$guild['percent_class4'] = gwr_percent_members($guild['class4'], $guild['membercount']);
		$guild['percent_class7'] = gwr_percent_members($guild['class7'], $guild['membercount']);
		$guild['percent_class9'] = gwr_percent_members($guild['class9'], $guild['membercount']);
		$guild['percent_class1'] = gwr_percent_members($guild['class1'], $guild['membercount']);

		eval('$gwr[header] = "' . fetch_template('gwr_header') . '";');
		eval('$gwr[stats] = "' . fetch_template('gwr_stats') . '";');
		eval('print_output("' . fetch_template('gwr_roster') . '");');

	}
?>