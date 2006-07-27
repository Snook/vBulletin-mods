<?php
	// ########################################################################
	//
	// gKeyExchange, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gKeyExchange, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: gkeyexchange.php 683 2007-08-22 22:42:32Z ghryphen $
	// $Rev: 683 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-08-22 15:42:32 -0700 (Wed, 22 Aug 2007) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gkeyexchange');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array('gts');

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'gke_main',
		'gke_welcome',
		'gke_received_bit',
		'gke_program',
		'gke_programs_bit',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');
  require_once('./includes/class_bbcode.php');
  require_once('./includes/class_bbcode_alt.php');
	require_once('./includes/functions_gkeyexchange.php');

	// ######################### PERMS ############################
	if (!$vbulletin->options['gke_enabled'] || !is_member_of($vbulletin->userinfo, explode(',', $vbulletin->options['gke_usergroup'])))
	{
		print_no_permission();
		exit;
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gke_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gKeyExchange', $copyrightyear);

	// ########################################################################
	// ######################### START MAIN SCRIPT ############################
	// ########################################################################
	$vbulletin->input->clean_array_gpc('r', array(
		'programid'    => TYPE_UINT,
	));
	if ($_POST['pid'])
	{
		$vbulletin->GPC['programid'] = $_POST['pid'];
	}

	//Program Query
	$program_res = $db->query("SELECT p.*, k.used_ondate, COUNT(k.used_ondate) AS availablekeys
		FROM `" . TABLE_PREFIX . "gkeyexchange_program` p
		LEFT JOIN `" . TABLE_PREFIX . "gkeyexchange_key` k ON (p.id = k.pid)
		WHERE p.id = '" . $vbulletin->GPC['programid'] . "'
		GROUP BY k.used_ondate
		ORDER BY k.used_ondate ASC
		LIMIT 1
	");
	$program = $db->fetch_array($program_res);

	//Build KeyExchange menu
	$show['programmenu'] = 0;
	$res = $db->query("SELECT * FROM `" . TABLE_PREFIX . "gkeyexchange_program` WHERE `enabled` = '1'");
	while($programmenu = $db->fetch_array($res))
	{
		if( is_member_of($vbulletin->userinfo, explode(',', $programmenu['requestug'])) || is_member_of($vbulletin->userinfo, explode(',', $programmenu['donateug'])) )
		{
			eval('$keyexchange[select] .= "' . fetch_template('gke_programs_bit') . '";');

			$show['programmenu']++;
		}
	}

	//Cache users recieved keys
	$userskeys = cache_userkeys();
	$userskeycount = cache_userkeycount($userskeys);

	// ########################################################################
	//Accept donation POST
	if($_REQUEST['do'] == 'donation')
	{
		$donation['key'] = trim($_POST['don_key']);

		if ( $_POST['don_confirm'] && preg_match("/" . $program['keyregex'] . "/", $donation['key']) && is_member_of($vbulletin->userinfo, explode(',', $program['donateug'])) )
    {
      $res = $db->query("SELECT * FROM `" . TABLE_PREFIX . "gkeyexchange_key` WHERE `pid` = '" . $vbulletin->GPC['programid'] . "' AND `key` = '" . $donation['key'] . "' LIMIT 1");

      if ($db->num_rows($res) == '0')
      {
        $db->query("INSERT INTO `" . TABLE_PREFIX . "gkeyexchange_key` SET `pid` ='" . $vbulletin->GPC['programid'] . "', `donated_byid` = '" . $vbulletin->userinfo['userid'] . "', `donated_ondate` = '" . TIMENOW . "', `key` = '" . $donation['key'] . "'");

        $donation['message'] = "Donation accepted: Thank you for your dontion. Feel free to submit another.";
      }
      else
      {
        $donation['message'] = "Donation not-accepted: This key has already been submitted.";
      }
    }
    else
    {
      $donation['message'] = "An error has occurred, please check your submission and try again.";

      $donation['keyvalue'] = $donation['key'];
    }
	}
	// ########################################################################

	// ########################################################################
	//Accept key request POST
	if($_REQUEST['do'] == 'request')
	{
    if ( $_POST['req_confirm'] && strtolower($_POST['req_key']) == "yes" && $userskeycount[$_POST['pid']]['request'] < $program['requestpp'])
		{
      $res = query("SELECT * FROM `" . TABLE_PREFIX . "gkeyexchange_key` WHERE `used_byid` = '0' AND `used_ondate` = '0' ORDER BY RAND() LIMIT 1");
      $reqkey = $db->fetch_array($res);

      $db->query("UPDATE `" . TABLE_PREFIX . "gkeyexchange_key` SET `used_byid` = '" . $vbulletin->userinfo['userid'] . "', `used_byip` = '" . $vbulletin->userinfo['ipaddress'] . "', `used_ondate` = '" . TIMENOW . "' WHERE `pid` = '" . $_POST['pid'] . "' AND `id` = '" . $reqkey['id'] . "'");
      //setcookie("bbauthenticate", 1, time()+60*60*24*30, "/", ".planetside-universe.com");
    }
    else
    {
      $request['message'] = "An error has occurred, please check your submission and try again.";
		}

		//Update Cache users recieved keys
    $userskeys = cache_userkeys();
    $userskeycount = cache_userkeycount($userskeys);
  }
	// ########################################################################

	if( !$_REQUEST['do'] && $program['enabled'] && (is_member_of($vbulletin->userinfo, explode(',', $program['requestug'])) || is_member_of($vbulletin->userinfo, explode(',', $program['donateug']))) )
	{

		//Request Key Qualification
		$show['request'] = 0;
		if($program['availablekeys'] != '0' && $vbulletin->userinfo['field49'] != 'Banned' && $vbulletin->userinfo['posts'] >= $program['requestposts'] && TIMENOW - ($program['requestreghours'] * 3600) >= $vbulletin->userinfo['joindate'] && ($userskeycount[$program['id']]['request'] < $program['requestpp']))
		{
			$show['request'] = 1;
		}
		else
		{
			if ($vbulletin->userinfo['posts'] < $program['requestposts'])
			{
				$program['fail_reqposts'] = $vbulletin->userinfo['posts'];
			}
			if (TIMENOW - ($program['requestreghours'] * 3600) < $vbulletin->userinfo['joindate'])
			{
				$program['fail_reqreghours'] = sprintf("%01.2f", ((TIMENOW - $vbulletin->userinfo['joindate']) / 3600));
			}
			if ($userskeycount[$program['id']]['request'] >= $program['requestpp'])
			{
				$program['fail_reqpp'] = $userskeycount[$program['id']]['request'];
			}
			if ($vbulletin->userinfo['field49'] == 'Banned')
			{
				$program['fail_reqbanned'] = 1;
			}
			if ($program['availablekeys'] == '0')
			{
				$program['fail_availablekeys'] = 1;
			}
		}

		//Donate Key Qualification
		$show['donate'] = 0;
		if($vbulletin->userinfo['posts'] >= $program['donateposts'] && TIMENOW - ($program['donatereghours'] * 3600) >= $vbulletin->userinfo['joindate'])
		{
			$show['donate'] = 1;
		}
		else
		{
			if ($vbulletin->userinfo['posts'] < $program['requestposts'])
			{
				$program[fail_donposts] = $vbulletin->userinfo['posts'];
			}
			if (TIMENOW - ($program['requestreghours'] * 3600) < $vbulletin->userinfo['joindate'])
			{
				$program[fail_donreghours] = $vbulletin->userinfo['joindate'] - TIMENOW;
			}
		}

		//Set donator listing
		$show['donators'] = 0;
		$res = $db->query("SELECT k.*, u.username, COUNT(u.username) AS count
  	  FROM " . TABLE_PREFIX . "gkeyexchange_key k
  	  LEFT JOIN " . TABLE_PREFIX . "user u ON (u.userid = k.donated_byid)
			WHERE `pid` = '" . $vbulletin->GPC['programid'] . "' AND `used_ondate` != '0' AND u.username != ''
  	  GROUP BY k.donated_byid
			ORDER BY k.donated_ondate DESC
  	");
  	while($keuser = $db->fetch_array($res))
  	{
  		$keyexchange['donators'] .= '<a href="member.php?u=' . $keuser['donated_byid'] . '">' . $keuser['username'] . '</a> (' . $keuser['count'] . '), ';

  		$show['donators']++;
  	}
		$keyexchange['donators'] = substr($keyexchange['donators'], 0, -2);

		//Set recieve listing
		$show['receivers'] = 0;
		$res = $db->query("SELECT k.*, u.username, COUNT(u.username) AS count
  	  FROM " . TABLE_PREFIX . "gkeyexchange_key k
  	  LEFT JOIN " . TABLE_PREFIX . "user u ON (u.userid = k.used_byid)
			WHERE `pid` = '" . $vbulletin->GPC['programid'] . "' AND `used_ondate` != '0' AND u.username != ''
  	  GROUP BY k.used_byid
			ORDER BY k.used_ondate DESC
			LIMIT 0, 100
  	");
  	while($keuser = $db->fetch_array($res))
  	{
  		$keyexchange['receivers'] .= '<a href="member.php?u=' . $keuser['used_byid'] . '">' . $keuser['username'] . '</a>, ';

	  	$show['receivers']++;
  	}
		$keyexchange['receivers'] = substr($keyexchange['receivers'], 0, -2);

		$bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());
		$program['description'] = $bbcode_parser->parse(unhtmlspecialchars($program['description']));

		eval('$keyexchange[contents] .= "' . fetch_template('gke_program') . '";');
	}
	else
	{
		//User recieved keys
		$show['receivedkeys'] = 0;

  	foreach($userskeys as $userkey)
  	{
			$userkey['date'] = vbdate($vbulletin->options['dateformat'], $userkey['used_ondate'], 1);
			$userkey['time'] = vbdate($vbulletin->options['timeformat'], $userkey['used_ondate']);

			eval('$keys_received .= "' . fetch_template('gke_received_bit') . '";');

 			$show['receivedkeys']++;
  	}

		eval('$keyexchange[contents] .= "' . fetch_template('gke_welcome') . '";');
	}

	eval('print_output("' . fetch_template('gke_main') . '");');
?>