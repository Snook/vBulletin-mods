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
	// $Id: miner.gxboxlive.php 1085 2008-11-19 16:29:08Z ghryphen $
	// $Rev: 1085 $
	// $LastChangedBy: ghryphen $
	// $Date: 2008-11-19 08:29:08 -0800 (Wed, 19 Nov 2008) $

	// ####################### SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);
	if (!is_object($vbulletin->db))
	{
		exit;
	}

	// ########################################################################
	// ######################### START MAIN SCRIPT ############################
	// ########################################################################

	require_once(DIR . '/includes/class_rss_poster.php');
	require_once(DIR . '/includes/functions_gxboxlive.php');

	$miner['updated'] = 0;

	if (!$vbulletin->options['gxbl_user_field'])
	{
		$log_message = 'Error: Create a GamerTag user profile field and enter the field ID into the <a href="index.php?loc=options.php%3Fdo%3Doptions%26dogroup%3Dgxbl_group" target="main">gXBL options</a>.';

		if (VB_AREA == 'AdminCP')
		{
			echo $log_message . "<br />";
		}

		log_cron_action($log_message, $nextitem, 1);

		exit;
	}

	$main_query = "SELECT
		user.userid as vbuserid,
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
		(gxboxlive.updated IS NULL OR DATE_FORMAT( FROM_UNIXTIME( gxboxlive.updated ), '%Y-%m-%d' ) != '" . date("Y-m-d", TIMENOW) . "')
	ORDER BY
		gxboxlive.updated ASC,
		vbuserid ASC";

	$fullresult = $vbulletin->db->query_read($main_query);

	$querylimit = ceil($vbulletin->db->num_rows($fullresult) / ceil((strtotime(date("Y-m-d", strtotime("+1 day"))) - TIMENOW) / 60) ) + 5;

	$res = $vbulletin->db->query_read($main_query . " LIMIT 0, " . $querylimit);

	while ($user = $vbulletin->db->fetch_array($res))
	{
		$fetch_url = "http://gamercard.xbox.com/en-US/" . rawurlencode($user["field" . $vbulletin->options['gxbl_user_field']]) . ".card";

		$card_string = fetch_file_via_socket($fetch_url);
		
		if ($card_string === false OR empty($card_string['body']))
		{ // error returned
			if (VB_AREA == 'AdminCP')
			{
				$log_message .= 'Unable to fetch URL: <a href="' . $fetch_url . '" target="_blank">' . $fetch_url . '</a> - <a href="user.php?do=edit&u=' . $user['vbuserid'] . '">' . $user['username'] . '</a> - Possibly not a real gamertag entered.<br />';
			}
		}

		if($player = gxbl_parsegamercard($card_string['body'], $user))
		{
			$miner['updated'] = gxbl_updategamer($player);
			$miner['tags'] .= $player['gamertag'] . ', ';
		}

	}

	//UPDATE RANKS
	$res = $vbulletin->db->query_first("SELECT error FROM `" . TABLE_PREFIX . "gxboxlive` AS gxboxlive WHERE error = '1' ORDER BY error DESC");

	if($res['error'])
	{
		gxbl_updateranks();

		$log_message = "Updated Accounts: " . substr($miner['tags'], 0, -2) . ".<br /><br />Process Time: " . vb_number_format((time() - TIMENOW), 0) . "s.<br /><br />Total Queries: " . $vbulletin->db->querycount . ".";

		if( $miner['updated'] > 0 )
		{
			log_cron_action($log_message, $nextitem, 1);
		}
	}

	if (VB_AREA == 'AdminCP')
	{
		echo $log_message . "<br />";
	}
?>