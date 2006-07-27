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
	// $Id: cron.gxboxstatus.php 1197 2009-08-19 18:47:48Z ghryphen $
	// $Rev: 1197 $
	// $LastChangedBy: ghryphen $
	// $Date: 2009-08-19 11:47:48 -0700 (Wed, 19 Aug 2009) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);
	if (!is_object($vbulletin->db))
	{
		exit;
	}

	// ########################## REQUIRE BACK-END ############################
	require_once(DIR . '/includes/class_gxboxstatus.php');

	if (is_numeric($vbulletin->options['gxbs_user_field']) && $vbulletin->options['gxbs_user_field'] != '0')
	{
		$gxbs_user_field = "field" . $vbulletin->options['gxbs_user_field'];
	} else {
		$gxbs_user_field = $vbulletin->options['gxbs_user_field'];
	}

	if ($vbulletin->options['gxbs_user_timeout'] != '0')
	{
		$timeout = TIMENOW - ($vbulletin->options['gxbs_user_timeout'] * 86400);
		$gxbs_user_timeout = "AND " . TABLE_PREFIX . "user.lastvisit >= '" . $timeout . "'";
	}

  $res = $vbulletin->db->query("SELECT
  	" . TABLE_PREFIX . "user.userid,
  	" . TABLE_PREFIX . "user.username,
  	" . TABLE_PREFIX . "user.posts,
  	" . TABLE_PREFIX . "user.lastvisit,
  	" . TABLE_PREFIX . "userfield." . $gxbs_user_field . "
  FROM
  	`" . TABLE_PREFIX . "userfield`
  LEFT JOIN
  	`" . TABLE_PREFIX . "user` ON " . TABLE_PREFIX . "userfield.userid = " . TABLE_PREFIX . "user.userid
  WHERE
		" . TABLE_PREFIX . "user.posts >= '" . $vbulletin->options['gxbs_required_posts'] . "'
  AND
  	" . TABLE_PREFIX . "userfield." . $gxbs_user_field . " != '' AND " . TABLE_PREFIX . "userfield." . $gxbs_user_field . " != 'User does not exist' " . $gxbs_user_timeout);

	while ($user = $vbulletin->db->fetch_array($res))
	{
		if($user[$gxbs_user_field] != 'User does not exist')
		{
			$gamertag = trim(strtolower($user[$gxbs_user_field]));

			$x = new XboxData;
			$x->SetGUID( $vbulletin->options['gxbs_xaged_api'] );
			$x->SetGamertag( $gamertag );

			$presence = $x->GetPresence();
			//$profile = $x->GetGamercard();

			if($profile['gamertag'] == '??????')
			{
				//$vbulletin->db->query("UPDATE " . TABLE_PREFIX . "userfield SET " . $gxbs_user_field . " = 'User does not exist' WHERE userid = '" . $user['userid'] . "'");
			}
		}
	}

?>