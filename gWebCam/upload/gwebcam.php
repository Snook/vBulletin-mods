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
	// $Id: gwebcam.php 698 2007-09-10 23:10:52Z ghryphen $
	// $Rev: 698 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-09-10 16:10:52 -0700 (Mon, 10 Sep 2007) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gcam');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array();

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');

	// ######################### PERMS ############################
	if (!is_member_of($vbulletin->userinfo, explode(',',$vbulletin->options['gcam_usergroup'])))
	{
		print_no_permission();
		exit;
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gcam_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2007 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gWebCam', $copyrightyear);

	// ########################################################################
	// ######################### START MAIN SCRIPT ############################
	// ########################################################################


		eval('$gwebcam .= "' . fetch_template('gcam_cam_bit') . '";');

		eval('print_output("' . fetch_template('gcam_main') . '");');

	}

?>