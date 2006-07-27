<?php
	// ########################################################################
	//
	// gThreeWordStory, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gThreeWordStory, please contact me at ghryphen@gmail.com for collaboration.
	// I appreciate your kind consideration.
	//
	// This work is licensed under the Creative Commons
	// Attribution-Noncommercial-No Derivative Works 2.5 License.
	// To view a copy of this license, visit
	// http://creativecommons.org/licenses/by-nc-nd/2.5/ or send a letter to
	// Creative Commons, 543 Howard Street, 5th Floor,
	// San Francisco, California, 94105, USA.
	//
	// ########################### SVN INFO ###################################
	// $Id: gthreewordstory.php 734 2007-10-02 22:34:06Z ghryphen $
	// $Rev: 734 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-10-02 15:34:06 -0700 (Tue, 02 Oct 2007) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gthreewordstory');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array();

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'GTWS',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');
  require_once('./includes/class_bbcode.php');
  require_once('./includes/class_bbcode_alt.php');

	// ######################### PERMS ############################
	if ($vbulletin->options['gtws_usergroup'] && $vbulletin->options['gtws_usergroup'] != '0' && !is_member_of($vbulletin->userinfo, explode(',',$vbulletin->options['gtws_usergroup'])))
	{
		print_no_permission();
		exit;
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gtws_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gThreeWordStory', $copyrightyear);

	// ########################################################################

	$res = $db->query("SELECT p.*, u.username
	FROM `" . TABLE_PREFIX . "post` p
	LEFT JOIN `" . TABLE_PREFIX . "user` u ON (p.userid = u.userid)
	WHERE `threadid` = '5930' AND `visible` = '1' ORDER BY `dateline` ASC");

	while ($post = $db->fetch_array($res))
	{
		$bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());

		$post['pagetext'] = $bbcode_parser->parse($post['pagetext']);
		$post['date'] = vbdate($vbulletin->options['dateformat'], $post['dateline'], 1);
		$post['time'] = vbdate($vbulletin->options['timeformat'], $post['dateline']);

		$gtws .= ' <acronym title="' . $post['username'] . '">' . $post['pagetext'] . '</acronym>';
//		$gtws .= ' <a href="member.php?u=' . $post['userid'] . '" title="' . $post['username'] . '">' . $post['pagetext'] . '</a>';
  }

	eval('print_output("' . fetch_template('GTWS') . '");');

?>