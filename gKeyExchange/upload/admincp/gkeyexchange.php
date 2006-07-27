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
	// ########################### SVN info ###################################
	// $Id: gkeyexchange.php 733 2007-10-02 21:11:42Z ghryphen $
	// $Rev: 733 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-10-02 14:11:42 -0700 (Tue, 02 Oct 2007) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array('style', 'plugins');
	$specialtemplates = array('products');

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');
	require_once(DIR . '/includes/adminfunctions_template.php');

	print_cp_header('gKeyExchange');

	// ########################## SUPERADMIN CHECK ############################
	if (!can_administer('canadminexchange'))
	{
		print_cp_no_permission();
	}

	$this_script = 'gkeyexchange';

  function cp_message($text)
  {
  	global $vbphrase;
  	print_form_header('', '', 0, 1, 'messageform', '65%');
		print_table_header($vbphrase['vbulletin_message']);
		print_description_row("<blockquote><br />" . $text . "<br /></blockquote>");
		print_table_footer();
  }

	// #############################################################################

	if ($_REQUEST['do'] == 'programs')
	{
		$vbulletin->input->clean_array_gpc('p', array(
    	'pid' => TYPE_INT,
    	'enabled' => TYPE_BOOL,
    	'title' => TYPE_STR,
    	'textid' => TYPE_STR,
    	'keyregex' => TYPE_STR,
    	'description' => TYPE_STR,
    	'request' => TYPE_BOOL,
    	'requestug' => TYPE_STR,
    	'requestpp' => TYPE_INT,
    	'requestposts' => TYPE_INT,
    	'requestreghours' => TYPE_INT,
    	'donate' => TYPE_BOOL,
    	'donateug' => TYPE_STR,
    	'donatepp' => TYPE_INT,
    	'donateposts' => TYPE_INT,
    	'donatereghours' => TYPE_INT
		));

		if($_REQUEST['doadd'])
		{
			$db->query("INSERT INTO `" . TABLE_PREFIX . "gkeyexchange_program` SET
				`enabled` = '" . $vbulletin->GPC['enabled'] . "',
				`title` = '" . $vbulletin->GPC['title'] . "',
				`textid` = '" . $vbulletin->GPC['textid'] . "',
				`keyregex` = '" . $db->escape_string($vbulletin->GPC['keyregex']) . "',
				`description` = '" . $db->escape_string($vbulletin->GPC['description']) . "',
				`request` = '" . $vbulletin->GPC['request'] . "',
				`requestug` = '" . $vbulletin->GPC['requestug'] . "',
				`requestpp` = '" . $vbulletin->GPC['requestpp'] . "',
				`requestposts` = '" . $vbulletin->GPC['requestposts'] . "',
				`requestreghours` = '" . $vbulletin->GPC['requestreghours'] . "',
				`donate` = '" . $vbulletin->GPC['donate'] . "',
				`donateug` = '" . $vbulletin->GPC['donateug'] . "',
				`donatepp` = '" . $vbulletin->GPC['donatepp'] . "',
				`donateposts` = '" . $vbulletin->GPC['donateposts'] . "',
				`donatereghours` = '" . $vbulletin->GPC['donatereghours'] . "'
			");

			cp_message($vbulletin->GPC['title'] . " has been added.");
		}

		if($_REQUEST['domanage'])
		{
			$db->query("UPDATE `" . TABLE_PREFIX . "gkeyexchange_program` SET
				`enabled` = '" . $vbulletin->GPC['enabled'] . "',
				`title` = '" . $vbulletin->GPC['title'] . "',
				`textid` = '" . $vbulletin->GPC['textid'] . "',
				`keyregex` = '" . $db->escape_string($vbulletin->GPC['keyregex']) . "',
				`description` = '" . $db->escape_string($vbulletin->GPC['description']) . "',
				`request` = '" . $vbulletin->GPC['request'] . "',
				`requestug` = '" . $vbulletin->GPC['requestug'] . "',
				`requestpp` = '" . $vbulletin->GPC['requestpp'] . "',
				`requestposts` = '" . $vbulletin->GPC['requestposts'] . "',
				`requestreghours` = '" . $vbulletin->GPC['requestreghours'] . "',
				`donate` = '" . $vbulletin->GPC['donate'] . "',
				`donateug` = '" . $vbulletin->GPC['donateug'] . "',
				`donatepp` = '" . $vbulletin->GPC['donatepp'] . "',
				`donateposts` = '" . $vbulletin->GPC['donateposts'] . "',
				`donatereghours` = '" . $vbulletin->GPC['donatereghours'] . "'
				WHERE `id` = '" . $vbulletin->GPC['pid'] . "'
			");

			cp_message($vbulletin->GPC['title'] . " has been updated.");
		}

		$res = $db->query("SELECT *	FROM `" . TABLE_PREFIX . "gkeyexchange_program`");

		while ($program = $db->fetch_array($res))
		{
			print_form_header($this_script, 'programs', 0, 1);
			construct_hidden_code('domanage', 1);
			construct_hidden_code('pid', $program['id']);
			print_table_header('Manage program ID: ' . $program['id']);
			print_yes_no_row('Enabled', 'enabled', $program['enabled']);
			print_input_row('Title', 'title', $program['title']);
			print_input_row('Simple text id, used for icon', 'textid', $program['textid']);
			print_input_row('Regex validation', 'keyregex', $program['keyregex']);
			print_textarea_row('Description, use bbcode', 'description', $program['description']);
			print_description_row('Requests', $htmlise = false, $colspan = 2, $class = 'tcat', $align = 'center');
			print_yes_no_row('Enabled', 'request', $program['request']);
			print_input_row('Usergroups, comma separated list of usegroups', 'requestug', $program['requestug']);
			print_input_row('Keys per person', 'requestpp', $program['requestpp']);
			print_input_row('Post required', 'requestposts', $program['requestposts']);
			print_input_row('Hours required', 'requestreghours', $program['requestreghours']);
			print_description_row('Donations', $htmlise = false, $colspan = 2, $class = 'tcat', $align = 'center');
			print_yes_no_row('Enabled', 'donate', $program['donate']);
			print_input_row('Usergroups, comma separated list of usegroups', 'donateug', $program['donateug']);
			print_input_row('Keys per person', 'donatepp', $program['donatepp']);
			print_input_row('Post required', 'donateposts', $program['donateposts']);
			print_input_row('Hours required', 'donatereghours', $program['donatereghours']);
			print_submit_row('Update', 0);
		}
	}

	if ($_REQUEST['do'] == 'addprogram')
	{
		print_form_header($this_script, 'programs', 0, 1);
		construct_hidden_code('doadd', 1);
		print_table_header('Add a new program');
		print_yes_no_row('Enabled', 'enabled', $program['enabled']);
		print_input_row('Title', 'title', $program['title']);
		print_input_row('Simple text id, used for icon', 'textid', $program['textid']);
		print_input_row('Regex validation', 'keyregex', $program['keyregex']);
		print_textarea_row('Description, use bbcode', 'description', $program['description']);
		print_description_row('Requests', $htmlise = false, $colspan = 2, $class = 'tcat', $align = 'center');
		print_yes_no_row('Enabled', 'request', $program['request']);
		print_input_row('Usergroups, comma separated list of usegroups', 'requestug', $program['requestug']);
		print_input_row('Keys per person', 'requestpp', $program['requestpp']);
		print_input_row('Post required', 'requestposts', $program['requestposts']);
		print_input_row('Hours required', 'requestreghours', $program['requestreghours']);
		print_description_row('Donations', $htmlise = false, $colspan = 2, $class = 'tcat', $align = 'center');
		print_yes_no_row('Enabled', 'donate', $program['donate']);
		print_input_row('Usergroups, comma separated list of usegroups', 'donateug', $program['donateug']);
		print_input_row('Keys per person', 'donatepp', $program['donatepp']);
		print_input_row('Post required', 'donateposts', $program['donateposts']);
		print_input_row('Hours required', 'donatereghours', $program['donatereghours']);
		print_submit_row('Add program', 0);
	}

	if ($_REQUEST['do'] == 'addkeys')
	{

		if($_REQUEST['doaddkeys'])
		{
			$vbulletin->input->clean_array_gpc('p', array(
    		'pid' => TYPE_INT,
    		'donateid' => TYPE_INT,
    		'skipregex' => TYPE_BOOL,
    		'bulkkeys' => TYPE_STR
			));

			//store regex
			$progregex = array();
			$res = $db->query("SELECT *	FROM `" . TABLE_PREFIX . "gkeyexchange_program`");
			while($program = $db->fetch_array($res))
			{
				$progregex[$program['id']] = $program['keyregex'];
			}

			//get existing key array
			$progkeys = array();
			$res = $db->query("SELECT `key`	FROM `" . TABLE_PREFIX . "gkeyexchange_key` WHERE `pid` = '" . $vbulletin->GPC['pid'] . "'");
			while($keys = $db->fetch_array($res))
			{
				$progkeys[] = $keys['key'];
			}

			//break up the list of keys
			$keys = explode("\n", $vbulletin->GPC['bulkkeys']);

			//iterate through the keys
			foreach ($keys AS $key)
			{
				$key = trim($key);

				if(!in_array($key, $progkeys))
				{
					if ($vbulletin->GPC['skipregex'] || preg_match("/" . $progregex[$vbulletin->GPC['pid']] . "/", $key))
					{
						$db->query("INSERT INTO `" . TABLE_PREFIX . "gkeyexchange_key` SET
							`pid` = '" . $vbulletin->GPC['pid'] . "',
							`donated_byid` = '" . $vbulletin->GPC['donateid'] . "',
							`donated_ondate` = '" . TIMENOW . "',
							`key` = '" . $key . "'
						");

						$progkeys[] = $key;

						$keyaddmessage .= $key. ' <span style="color:green">Added</span><br />';
					}
					else
					{
						$keyaddmessage .= $key. ' <span style="color:red">Failed Regex</span><br />';
					}
				}
				else
				{
					$keyaddmessage .= $key. ' <span style="color:red">Already in DB</span><br />';
				}

			}

			cp_message($keyaddmessage);
		}

		$res = $db->query("SELECT *	FROM `" . TABLE_PREFIX . "gkeyexchange_program`");

		$progselect = array();
		while($program = $db->fetch_array($res))
		{
			$progselect[$program['id']] = $program['title'];
		}
		print_form_header($this_script, 'addkeys', 0, 1);
		construct_hidden_code('doaddkeys', 1);
		print_table_header('Add program keys');
		print_select_row('Select program', 'pid', $progselect);
		print_input_row('User id to assign keys to as donator', 'donateid');
		print_yes_no_row('Ignore Regex validation', 'skipregex', 0);
		print_textarea_row('Keys, one per line', 'bulkkeys');
		print_submit_row('Add keys', 0);
	}

	print_cp_footer();
?>