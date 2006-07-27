<?php
	// ########################################################################
	//
	// gEditorial, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gEditorial, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: geditorial.php 793 2007-12-21 23:02:21Z ghryphen $
	// $Rev: 793 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-12-21 15:02:21 -0800 (Fri, 21 Dec 2007) $

  // ######################## SET PHP ENVIRONMENT ###########################
  error_reporting(E_ALL & ~E_NOTICE);

  // ##################### DEFINE IMPORTANT CONSTANTS #######################
  // change the line below to the actual filename without ".php" extention.
  // the reason for using actual filename without extention as a value of this constant is to ensure uniqueness of the value throughout every PHP file of any given vBulletin installation.

  define('THIS_SCRIPT', 'gEditorial');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
  // get special phrase groups
	$phrasegroups = array(
		'showthread',
		'postbit',
		'reputationlevel',
		'prefix',
	);

  // get special data templates from the datastore
  $specialtemplates = array(
		'smiliecache',
		'bbcodecache',
	);

  // pre-cache templates used by all actions
  $globaltemplates = array(
		'navbar',
    'ged_main',
    'ged_list',
    'ged_list_bit',
		'ged_postbit',
		'im_aim',
		'im_icq',
		'im_msn',
		'im_yahoo',
		'im_skype',
		'postbit_wrapper',
		'postbit_attachment',
		'postbit_attachmentimage',
		'postbit_attachmentthumbnail',
		'postbit_attachmentmoderated',
		'postbit_ip',
		'postbit_onlinestatus',
		'postbit_reputation',
		'bbcode_code',
		'bbcode_html',
		'bbcode_php',
		'bbcode_quote',
  );

  // pre-cache templates used by specific actions
  $actiontemplates = array();

  // ########################## REQUIRE BACK-END ############################
  require_once('./global.php');
  require_once('./includes/class_bbcode.php');

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['ged_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### NAVBAR ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gEditorial', $copyrightyear);

	if (!$vbulletin->options['ged_prefixid'])
	{
		eval(standard_error(fetch_error('invalidid', "Thread Prefixes")));
	}

	if(!$postid)
	{
		$prefixes = explode(",", $vbulletin->options['ged_prefixid']);
		$prefixescount = count($prefixes);
		$count = 1;

		foreach($prefixes as $prefix)
		{
			if($count > 1 && $count <= $prefixescount)
			{
				$prefixquery .= " OR ";
			}
			$prefixquery .=  TABLE_PREFIX . "thread.prefixid = '" . $prefix . "'";
			$count++;
		}

		$res = $db->query("SELECT * FROM " . TABLE_PREFIX . "thread
			LEFT JOIN " . TABLE_PREFIX . "post ON " . TABLE_PREFIX . "thread.firstpostid = " . TABLE_PREFIX . "post.postid
			WHERE " . $prefixquery . "
			ORDER BY " . TABLE_PREFIX . "thread.dateline DESC");

  	$bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());

  	while ($editorial = $db->fetch_array ($res))
  	{
			$editorial["date"] = vbdate($vbulletin->options['dateformat'], $editorial['dateline'], 1);
			$editorial["time"] = vbdate($vbulletin->options['timeformat'], $editorial['dateline']);
  	  $editorial["news"] = $bbcode_parser->parse(unhtmlspecialchars($editorial['pagetext']), $vbulletin->options['gsitenewsforum']);
			$editorial['prefix_rich'] = $vbphrase["prefix_$editorial[prefixid]_title_rich"];

			$editorial['preview'] = strip_quotes($editorial['pagetext']);
			$editorial['preview'] = htmlspecialchars_uni(fetch_censored_text(fetch_trimmed_title(
				strip_bbcode($editorial['preview'], false, true),
				$vbulletin->options['threadpreview']
			)));

			eval('$editorial_list .= "' . fetch_template('ged_list_bit') . '";');
  	}

		eval('$content = "' . fetch_template('ged_list') . '";');

	}
	else
	{
	  require_once('./includes/geditorial_postbit.php');

		// ######################### NAVBAR ############################
		$navbits = array();
		$navbits[$parent] = $vbphrase['ged_title'] . ' - ' . $post['title'];

		$navbits = construct_navbits($navbits);
		eval('$navbar = "' . fetch_template('navbar') . '";');

		eval('$content = "' . fetch_template('ged_postbit') . '";');

	}

	eval('$gsite_nav = "' . fetch_template('gsite_nav') . '";');

	eval ('print_output("' . fetch_template('ged_main') . '");');
?>