<?php
	// ########################################################################
	//
	// gTeamSpeak, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gTeamSpeak, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: gteamspeak.php 981 2008-05-04 22:15:56Z ghryphen $
	// $Rev: 981 $
	// $LastChangedBy: ghryphen $
	// $Date: 2008-05-04 15:15:56 -0700 (Sun, 04 May 2008) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gts_usercp');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array('gts');

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'gts_usercp',
		'gts_usercp_addressbook',
		'gts_usercp_welcome',
		'gts_usercp_login',
		'gts_usercp_loggedin',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');
	require_once('./includes/functions_gteamspeak.php');

	// ######################### PERMS ############################
	$gts_userfiedid = func_gts_fieldid($vbulletin->options['gts_userfiedid']);

	if ( $user[$gts_userfiedid] == 'no' || !is_member_of($vbulletin->userinfo, explode(',',$vbulletin->options['gts_usergroup'])) || $vbulletin->userinfo['posts'] < $vbulletin->options['gts_userposts'] || TIMENOW - ($vbulletin->options['gts_userdays'] * 3600) < $vbulletin->userinfo['joindate'] )
	{
		print_no_permission();
		exit;
	}

	// ######################### CLEAN GPC ############################
	$ts_password = $db->escape_string($vbulletin->input->clean_gpc('p', 'ts2pass', TYPE_NOHTML));

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gts_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gTeamSpeak', $copyrightyear);

	// ########################################################################
	// ######################### START MAIN SCRIPT ############################
	// ########################################################################

	$ts2info = func_gts_info();
	$ts2db = func_gts_dbinfo();

	// ########################################################################
	// Handle Password Update
	if ($_POST['do'] == 'updatepass' && $user[$gts_userfiedid] != 'no')
	{
		if (strlen($ts_password) < '6')
		{
			eval(standard_error($vbphrase['gts_error_password_length']));
		}
		elseif ($ts_password == $ts2info['username'])
		{
			eval(standard_error($vbphrase['gts_error_password_match']));
		}
		else
		{

    	$createtime = func_gts_fromunix(time());

	  	$r = func_gts_query("SELECT * FROM " . $ts2db['name'] . "ts2_clients WHERE s_client_name = '" . $ts2info['username'] . "' AND i_client_server_id = '" . $ts2db['serverid'] . "'");

    	if ($vbulletin->db->num_rows($r) != '0') {
    	  func_gts_query("UPDATE " . $ts2db['name'] . "ts2_clients SET s_client_name = '" . $ts2info['username'] . "', s_client_password = '" . $ts_password . "' WHERE s_client_name = '" . $ts2info['username'] . "' AND i_client_server_id = '" . $ts2db['serverid'] . "'");
    	}
    	else
    	{

				$sausergroups = explode(',',$vbulletin->options['gts_sausergroup']);

				if (is_member_of($vbulletin->userinfo,$sausergroups))
				{
					$sa_status = '-1';
				}
				else
				{
					$sa_status = '0';
				}

    	  func_gts_query("INSERT INTO " . $ts2db['name'] . "ts2_clients SET i_client_server_id = '" . $ts2db['serverid'] . "', b_client_privilege_serveradmin = '" . $sa_status . "', s_client_name = '" . $ts2info['username'] . "', s_client_password='" . $ts_password . "', dt_client_created='" . $createtime . "'");
    	}

		}

		$ts2info = func_gts_info();

	}
	// ########################################################################

	if ($vbulletin->options['gts_display_online'] && $vbulletin->options['gts_sa_enable'] && (is_member_of($vbulletin->userinfo, explode(',',$vbulletin->options['gts_whos_online_usergroup'])) OR $vbulletin->options['gts_whos_online_usergroup'] == '0'))
	{
		($hook = vBulletinHook::fetch_hook('gts_whos_online')) ? eval($hook) : false;

		if ($vbulletin->options['gts_whos_online_hide_empty'] == '0')
		{
			$show['gts_whos_on'] = 1;
			eval('$gts_whos_online = "' . fetch_template('gts_whos_online') . '";');
		}
		elseif (trim($gteamspeak_activeusers) != '')
		{
			$show['gts_whos_on'] = 1;
			eval('$gts_whos_online = "' . fetch_template('gts_whos_online') . '";');
		}
	}

	eval('$gts_welcome = "' . fetch_template('gts_usercp_welcome') . '";');
	eval('$gts_addressbook = "' . fetch_template('gts_usercp_addressbook') . '";');

	if ($vbulletin->userinfo['userid'] && $ts2info['password'] == "") {

		eval('$gts_bit = "' . fetch_template('gts_usercp_login') . '";');

	}
	else
	{

		eval('$gts_bit = "' . fetch_template('gts_usercp_loggedin') . '";');

	}

	eval('print_output("' . fetch_template('gts_usercp') . '");');

?>