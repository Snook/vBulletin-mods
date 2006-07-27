<?php
	// ########################################################################
	//
	// gXboxLive, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gXboxLive, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: gxboxlive.php 1219 2009-09-23 21:53:36Z ghryphen $
	// $Rev: 1219 $
	// $LastChangedBy: ghryphen $
	// $Date: 2009-09-23 14:53:36 -0700 (Wed, 23 Sep 2009) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gxboxlive');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array('gxbl');

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'bbcode_code',
		'GXBL',
		'gxbl_bit',
		'gxbl_bit_game',
		'gxbl_headinclude',
		'gxbl_stats',
		'gxbl_top_avatars',
		'gxbl_top_games',
		'gxbl_top_players',
		'gxbl_top_zones',
		'gxbl_vbnav_navbarlink',
		'gxbl_vbnav_quicklink',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');
	require_once('./includes/functions_gxboxlive.php');
	
		function gxboxlive_parse($url)
		{
			$xml = @file_get_contents($url);

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

	// ######################### PERMS ############################
	if (!$vbulletin->options['gxbl_user_field'] || !$gxblprod['active'])
	{
		if(!$vbulletin->options['gxbl_user_field'])
		{
			eval(standard_error(fetch_error('invalidid', 'field id', $vbulletin->options['contactuslink'])));
		}
		else
		{
			eval('standard_error("gXboxLive is currently disabled.");');
		}
		exit;
	}

	if (!gxbl_has_access())
	{
		print_no_permission();
		exit;
	}

	// ######################### CLEAN GPC ############################
	$perpage = $vbulletin->input->clean_gpc('r', 'perpage', TYPE_UINT);
	$pagenumber = $vbulletin->input->clean_gpc('r', 'page', TYPE_UINT);
	$sortfield = $vbulletin->input->clean_gpc('r', 'sortfield', TYPE_STR);
	$sortorder = $vbulletin->input->clean_gpc('r', 'sortorder', TYPE_STR);
	$highlight['gamertagid'] =  $db->escape_string(strtolower(urldecode($vbulletin->input->clean_gpc('r', 'gt', TYPE_NOHTML))));

	//find ranked gamertag
	if($highlight['gamertagid'])
	{
		$res = $db->query_first("SELECT rank FROM `" . TABLE_PREFIX . "gxboxlive` AS gxboxlive WHERE gamertag = '" . $highlight['gamertagid'] . "'");

		if($res['rank'] > 0)
		{
			$pagenumber = ceil($res['rank'] / $vbulletin->options['gxbl_show_perpage']);
		}
		else
		{
			unset ($highlight['gamertagid']);
		}
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gxboxlive_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gXboxLive', $copyrightyear);

	// Return Url used for Xbox.com
	$gxbl['ru'] = urlencode($vbulletin->options['bburl'] . '/gxboxlive.php');

	if ($vbulletin->options['gxbl_rep_stars'] == '0')
	{
		$gbxl['rep_path'] = $stylevar['imgdir_misc'] . '/gxboxlive/rep/';
		$gbxl['rep_suffix'] = '.png';
	}
	elseif ($vbulletin->options['gxbl_rep_stars'] == '1')
	{
		$gbxl['rep_path'] = 'http://gamercard.xbox.com/xweb/lib/images/gc_repstars_external_';
		$gbxl['rep_suffix'] = '.gif';
	}
	elseif ($vbulletin->options['gxbl_rep_stars'] == '2')
	{
		$gbxl['rep_path'] = 'http://gamercard.xbox.com/xweb/lib/images/gc_repstars_nav_';
		$gbxl['rep_suffix'] = '.gif';
	}

	$show['gxbl_friend'] = iif($vbulletin->options['gxbl_show_friend'] == '0', '0', '1');
	$show['gxbl_message'] = iif($vbulletin->options['gxbl_show_message'] == '0', '0', '1');
	$show['gxbl_markup'] = iif($vbulletin->options['gxbl_show_markup'] == '0', '0', '1');
	$show['gxbl_stats'] = iif($vbulletin->options['gxbl_show_stats'] == '0', '0', '1');

	$totalcols = 7 + (ceil(($show['gxbl_message'] + $show['gxbl_friend'])/2));
	if($vbulletin->options['gxbl_xaged_api'])
	{
		$totalcols++;
	}

	// ######################### PAGE NAV ############################
	// Make sure all these variables are cool
	sanitize_pageresults($vbulletin->userstats['gxbl']['usercount'], $pagenumber, $perpage, 100, $vbulletin->options['gxbl_show_perpage']);

	// Default lower and upper limit variables
	$limitlower = ($pagenumber - 1) * $perpage + 1;
	$limitupper = $pagenumber * $perpage;
	if ($limitupper > $vbulletin->userstats['gxbl']['usercount'])
	{
		// Too many for upper limit
		$limitupper = $vbulletin->userstats['gxbl']['usercount'];
		if ($limitlower > $vbulletin->userstats['gxbl']['usercount'])
		{
			// Too many for lower limit
			$limitlower = $vbulletin->userstats['gxbl']['usercount'] - $perpage;
		}
	}
	if ($limitlower <= 0)
	{
		// Can't have negative or null lower limit
		$limitlower = 1;
	}

	// ######################### SETUP SORT DIRECTION ############################
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

	switch ($switchSort) {
		case "n":
			$sort_query = "user.username " . $deasc;
			break;
		case "gt":
			$sort_query = "gxboxlive.gamertag " . $deasc;
			break;
		case "s":
		default : $sort_query = "gxboxlive.score " . $deasc . ", gxboxlive.gamertag ASC";
			break;
		case "r":
			$sort_query = "gxboxlive.reputation " . $deasc . ", gxboxlive.score " . $deasc . ", gxboxlive.gamertag ASC";
			break;
		case "z":
			$sort_query = "gxboxlive.zone " . $deasc . ", gxboxlive.score DESC, gxboxlive.gamertag ASC";
			break;
		case "u":
			$sort_query = "gxboxlive.updated " . $deasc . ", gxboxlive.score DESC, gxboxlive.gamertag ASC";
			break;
	}

	// ######################### CONSTRUCT PAGE NAV ############################
	$pagenav = construct_page_nav($pagenumber, $perpage, $vbulletin->userstats['gxbl']['usercount'], 'gxboxlive.php?' . $vbulletin->session->vars['sessionurl'] . 'do=getall', ''
		. (!empty($sortfield) ? "&amp;sortfield=$sortfield" : "")
		. (!empty($sortorder) ? "&amp;sortorder=$sortorder" : "")
	);

	// ######################### SETUP QUERY ############################

	// setup field id
	if ($vbulletin->options['gxbl_user_field'])
	{
		$gxbl_user_field = "field" . $vbulletin->options['gxbl_user_field'];
	}

	$main_query = "SELECT
		user.userid,
		user.username,
		user.posts,
		user.lastvisit,
		user.usergroupid,
		user.membergroupids,
		userfield.field" . $vbulletin->options['gxbl_user_field'] . ",
		gxboxlive.*
	FROM
		`" . TABLE_PREFIX . "user` AS user
	LEFT JOIN
		`" . TABLE_PREFIX . "userfield` AS userfield ON (user.userid = userfield.userid)
	LEFT JOIN
		`" . TABLE_PREFIX . "gxboxlive` AS gxboxlive ON (userfield.userid = gxboxlive.userid)
	WHERE
		user.usergroupid NOT IN(" . gxbl_bannedgroups() . ")
		" . gxbl_displaygroups() . "
	AND
		field" . $vbulletin->options['gxbl_user_field'] . " != ''
	AND
  		user.posts >= '" . $vbulletin->options['gxbl_required_posts'] . "'
	AND
		user.lastvisit >= '" . iif($vbulletin->options['gxbl_user_timeout'] == '0', '0', (TIMENOW - ($vbulletin->options['gxbl_user_timeout'] * 86400)) ) . "'
	AND
		gxboxlive.strikes = '0'
	AND
		gxboxlive.score >= '" . iif($vbulletin->options['gxbl_show_unranked'] == '0', '1', '0') . "'
	ORDER BY
		" . $sort_query . "
	LIMIT
		" . ($limitlower - 1) . "," . $perpage;

	$res = $db->query_read($main_query);

	if ($pagenumber != '1')
	{
		$errorcheck = ($pagenumber * $vbulletin->options['gxbl_show_perpage']) - $vbulletin->options['gxbl_show_perpage'] + 1;
	} else {
		$errorcheck = 1;
	}

	while ($player = $db->fetch_array ($res))
	{
		if($player['rank'] != $errorcheck && $player['score'] != '0' && (trim($sortfield) == '' || ($sortfield == 's' && $sortorder == 'desc')))
		{
			$vbulletin->db->query_write("UPDATE `" . TABLE_PREFIX . "gxboxlive` SET `rank` = '" . $errorcheck . "' WHERE userid = '" . $player['userid'] . "'");
			$player['rank'] = $errorcheck;
		}
		$errorcheck++;

		$updated['date'] = vbdate($vbulletin->options['dateformat'], $player['updated'], 1);
		$updated['time'] = vbdate($vbulletin->options['timeformat'], $player['updated']);

		$player['rank'] = iif( $player['rank'] > '0', $player['rank'], '--' );
		$player['ranksuff'] = iif( $player['rank'] > '0', gxbl_suffix($player['rank']), '' );

		$player['username'] = iif( $show['gxbl_markup'] == '1', fetch_musername($player), $player['username'] );

		$player['url_gamertag'] = urlencode($player['gamertag']);

		$player['gamertagid'] = strtolower($player['gamertag']);

		$player['lastplayed'] = unserialize($player['lastplayed']);

		if (is_array($player['lastplayed']))
		{
			foreach ($player['lastplayed'] as $game)
			{
				eval('$player[games] .= "' . fetch_template('gxbl_bit_game') . '";');
			}
		}
		else
		{
			$player['games'] = '&nbsp;';
		}
		
		// Live info
		if($vbulletin->options['gxbl_xaged_api'])
		{
		if($liveinfo = gxboxlive_parse('./xbox_cache/' . $player['gamertagid'] . '_presence.xml'))
		{ 
			$live = $liveinfo['GamerPresence'];

			if($live['online'] == '1' && ( $live['status'] == '0' || $live['status'] == '2' || $live['status'] == '3' ))
			{
				$player['status'] = 'online';
				
				if($live['status'] == '2')
				{
					$player['status'] = 'idle';
				}
				if($live['status'] == '3')
				{
					$player['status'] = 'busy';
				}
			}
			else
			{	
				$player['status'] = 'offline';
			}
		}
		else
		{	
			$player['status'] = 'offline';
		}
		}

		eval('$gxbl_bit .= "' . fetch_template('gxbl_bit') . '";');
	}

	($hook = vBulletinHook::fetch_hook('gxbl_stats')) ? eval($hook) : false;

	eval ('print_output("' . fetch_template('GXBL') . '");');
?>