<?php
	// ########################################################################
	//
	//  gTeamSpeak, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	//  If you have fixes, improvements or other additions to make to
	//  gTeamSpeak, please contact me at ghryphen@gmail.com for collaboration.
	//	I appreciate your kind consideration.
	//
	//  This work is licensed under the Creative Commons
	//  Attribution-Noncommercial-No Derivative Works 3.0 United States License.
	//  To view a copy of this license, visit
	//  http://creativecommons.org/licenses/by-nc-nd/3.0/us/ or send a letter to
	//  Creative Commons, 171 Second Street, Suite 300,
	//  San Francisco, California, 94105, USA.
	//
	// ########################### SVN INFO ###################################
	// $Id: cron.gteamspeak.php 683 2007-08-22 22:42:32Z ghryphen $
	// $Rev: 683 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-08-22 15:42:32 -0700 (Wed, 22 Aug 2007) $

	// ####################### SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);
	if (!is_object($vbulletin->db))
	{
		exit;
	}

  // ########################################################################
  // ######################### START MAIN SCRIPT ############################
  // ########################################################################

	require_once(DIR . '/includes/functions_gteamspeak.php');

	$banned_in_last_day = TIMENOW - 86400;

	$ts2db = func_gts_dbinfo();

  $res = $vbulletin->db->query("SELECT * FROM " . TABLE_PREFIX . "userban WHERE bandate >= '" . $banned_in_last_day . "'");

	while ($userban = $vbulletin->db->fetch_array($res))
	{

    $res = func_gts_query("SELECT * FROM " . $ts2db['name'] . "ts2_clients WHERE ts2_clients.i_client_server_id = '" . $ts2db['serverid'] . "' AND ts2_clients.s_client_name LIKE '%." . $userban['userid'] . "'");

    $tsid = $vbulletin->db->fetch_array($res);

		if($tsid['i_client_id'] > '0')
		{

			if ($userban['liftdate'] <= '0')
			{
				echo 'Ban: ' . $tsid['i_client_id'] . '<br />';

				$vbulletin->db->query("UPDATE " . TABLE_PREFIX . "userfield SET " . func_gts_fieldid($vbulletin->options['gts_userfiedid']) . " = 'no' WHERE userid = '" . $userban['userid'] ."'");
			}

			if ($vbulletin->options["gts_sa_enable"])
			{
				echo 'Kick: ' . $tsid['i_client_id'] . '<br />';
				$cyts->admin_kick($cyts->info_getPlayerByLoginName($tsid['s_client_name']), "Banned");
			}

			echo 'Delete: ' . $tsid['i_client_id'] . '<br />';

			func_gts_delete_user($tsid['i_client_id']);

			$users_banned .= '<a href="user.php?do=edit&u=' . $userban['userid'] . '">' . $userban['userid'] . '</a>, ';

		}

		unset($tsid);

	}

	if(trim($users_banned) != '')
	{
		log_cron_action(substr($users_banned, 0, -2), $nextitem, 1);
	}

?>