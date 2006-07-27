<?php
	// ########################################################################
	//
	// gWebCam, Copyright © 2007, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gWebCam, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: gwowplayer.php 1065 2008-11-06 22:26:11Z ghryphen $
	// $Rev: 1065 $
	// $LastChangedBy: ghryphen $
	// $Date: 2008-11-06 14:26:11 -0800 (Thu, 06 Nov 2008) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gwowplayers');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array('gwowp');

	$specialtemplates = array();

	$globaltemplates = array(
		'gwowplayer_main',
		'gwowplayer_bit',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');

	// ######################### PERMS ############################
	if (!$vbulletin->options['gwowp_userfield'])
	{
		eval(standard_error(fetch_error('invalidid', 'field id', $vbulletin->options['contactuslink'])));
		exit;
	}
	
	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gwowplayer_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### START TABLE UPDATE #########################

	$main_query = "SELECT
		user.userid,
		user.username,
		user.lastvisit,
		userfield.field" . $vbulletin->options['gwowp_userfield'] . "
	FROM
		`" . TABLE_PREFIX . "user` AS user
	LEFT JOIN
		`" . TABLE_PREFIX . "userfield` AS userfield ON (user.userid = userfield.userid)
	WHERE
		`field" . $vbulletin->options['gwowp_userfield'] . "` LIKE '%|%'
	AND
		`lastvisit` >= '" . iif($vbulletin->options['gwowp_user_lastvisit'] == '0', '0', (TIMENOW - ($vbulletin->options['gwowp_user_lastvisit'] * 86400)) ) . "'

	";

	$res = $db->query($main_query);

	while ($player = $db->fetch_array($res))
	{
		$char_array = explode("\n", $player['field60']);

		foreach ($char_array as $item)
		{
			list($char['locale'], $char['realm'], $char['faction'], $char['name']) = explode("|", trim($item));

			$vbulletin->db->query_write("REPLACE INTO " . TABLE_PREFIX . "gwowplayer SET
				`userid` = '" . $player['userid'] . "',
				`locale` = '" . addslashes(strtoupper(trim($char['locale']))) . "',
				`realm` = '" . addslashes(trim($char['realm'])) . "',
				`faction` = '" . addslashes(ucfirst(strtolower(trim($char['faction'])))) . "',
				`character` = '" . addslashes(ucfirst(strtolower(trim($char['name'])))) . "',
				`timenow` = '" . TIMENOW . "'
			");

		}
	}

	$vbulletin->db->query_write("
		DELETE FROM " . TABLE_PREFIX . "gwowplayer
		WHERE `timenow` != '" . TIMENOW . "'
	");

	// ######################### END TABLE UPDATE #########################

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gWoWPlayer', $copyrightyear);

	$locale = $vbulletin->input->clean_gpc('r', 'locale', TYPE_NOHTML);
	$realm = $vbulletin->input->clean_gpc('r', 'realm', TYPE_NOHTML);
	$faction = $vbulletin->input->clean_gpc('r', 'faction', TYPE_NOHTML);
	$userid = $vbulletin->input->clean_gpc('r', 'userid', TYPE_UINT);
	$perpage = $vbulletin->input->clean_gpc('r', 'perpage', TYPE_UINT);
	$pagenumber = $vbulletin->input->clean_gpc('r', 'page', TYPE_UINT);
	$sortfield = $vbulletin->input->clean_gpc('r', 'sortfield', TYPE_NOHTML);
	$sortorder = $vbulletin->input->clean_gpc('r', 'sortorder', TYPE_NOHTML);
	
	// ######################### FILTERS ############################

	$where_query = "";
	
	if($locale)
	{
		$where_query .= "AND `locale` = '" . $locale . "'";
	}

	if($realm && $locale)
	{
		$where_query .= "AND `realm` = '" . addslashes(urldecode($realm)) . "'";
	}

	if($realm && $locale && $faction)
	{
		$where_query .= "AND `faction` = '" . addslashes(urldecode($faction)) . "'";
	}	
	
	if($userid)
	{
		$where_query .= "AND user.userid = '" . $userid . "'";
	}
	
	if($where_query)
	{
		$where_query = "WHERE " . substr($where_query, 4);
	}

	// ######################### PAGE NAV ############################
	// Make sure all these variables are cool
	$counts = $db->query_first("SELECT COUNT(*) AS `usercount` FROM `" . TABLE_PREFIX . "gwowplayer`" . str_replace("user.", "", $where_query));

	sanitize_pageresults($counts['usercount'], $pagenumber, $perpage, 100, $vbulletin->options['gwowp_show_perpage']);

	// Default lower and upper limit variables
	$limitlower = ($pagenumber - 1) * $perpage + 1;
	$limitupper = $pagenumber * $perpage;
	if ($limitupper > $counts['usercount'])
	{
		// Too many for upper limit
		$limitupper = $counts['usercount'];
		if ($limitlower > $counts['usercount'])
		{
			// Too many for lower limit
			$limitlower = $counts['usercount'] - $perpage;
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
		case "l":
		default : $sort_query = "`locale` " . $deasc . ", `realm` ASC, `faction` ASC, `character` ASC, `username` ASC";
			break;
		case "r":
			$sort_query = "`locale` DESC, `realm` " . $deasc . ", `faction` ASC, `character` ASC, `username` ASC";
			break;
		case "f":
			$sort_query = "`locale` DESC, `faction` " . $deasc . ", `realm` ASC, `character` ASC, `username` ASC";
			break;
		case "c":
			$sort_query = "`locale` DESC, `character` " . $deasc . ", `realm` ASC, `faction` ASC, `username` ASC";
			break;
		case "u":
			$sort_query = "`locale` DESC, `username` " . $deasc . ", `realm` ASC, `faction` ASC, `character` ASC";
			break;
	}

	// ######################### CONSTRUCT PAGE NAV ############################
	$pagenav = construct_page_nav($pagenumber, $perpage, $counts['usercount'], 'gwowplayer.php?' . $vbulletin->session->vars['sessionurl'] . 'do=getall', ''
		. (!empty($sortfield) ? "&amp;sortfield=$sortfield" : "")
		. (!empty($sortorder) ? "&amp;sortorder=$sortorder" : "")
		. (!empty($locale) ? "&amp;locale=$locale" : "")
		. (!empty($realm) ? "&amp;realm=$realm" : "")
		. (!empty($faction) ? "&amp;faction=$faction" : "")
		. (!empty($userid) ? "&amp;userid=$userid" : "")
	);

	// ######################### MAIN DISPLAY ############################

	$main_query = "SELECT
		gwowplayer.*,
		user.userid,
		user.username
	FROM
		`" . TABLE_PREFIX . "gwowplayer` AS gwowplayer
	LEFT JOIN
		`" . TABLE_PREFIX . "user` AS user ON (user.userid = gwowplayer.userid)
	" . $where_query . "
	ORDER BY
		" . $sort_query . "
	LIMIT
		" . ($limitlower - 1) . "," . $perpage;

	$res = $db->query($main_query);

	while ($character = $db->fetch_array ($res))
	{
		//echo $character['locale'] . ' - ' . $character['realm'] . ' - ' . $character['faction'] . ' - ' . $character['character'] . ' (<a href="member.php?u=' . $character['userid'] . '">' . $character['username'] . '</a>)<br />';
		eval('$gwowp_bits .= "' . fetch_template('gwowplayer_bit') . '";');
	}

	eval ('print_output("' . fetch_template('gwowplayer_main') . '");');
?>