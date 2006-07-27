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
	// $Id: gteamspeak.php 683 2007-08-22 22:42:32Z ghryphen $
	// $Rev: 683 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-08-22 15:42:32 -0700 (Wed, 22 Aug 2007) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array('style', 'plugins');
	$specialtemplates = array('products');

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');
	require_once(DIR . '/includes/adminfunctions_template.php');
	require_once(DIR . '/includes/functions_gteamspeak.php');

	print_cp_header('gTeamSpeak');

	// ########################## SUPERADMIN CHECK ############################
	if (!in_array($vbulletin->userinfo['userid'], preg_split('#\s*,\s*#s', $vbulletin->config['SpecialUsers']['superadministrators'], -1, PREG_SPLIT_NO_EMPTY)) )
	{
		print_cp_no_permission();
	}

	$ts2db = func_gts_dbinfo();

	$this_script = 'gteamspeak';

	// Index
	if ( empty($_REQUEST['do']) ) {

		if ($vbulletin->options['gts_sa_enable'] == '1')
		{
		//	func_gts_serverInfo();
		}

		print_form_header($this_script, 'manageusers', 0, 1);
		print_table_header('Manage Users');
		print_description_row('Promote, Demote, Delete or Ban users.');
		print_submit_row('Manage Users', 0);

		print_form_header($this_script, 'manageplugins', 0, 1);
		print_table_header('Manage Plugins');
		print_description_row('Add / Remove / Disable Plugins.');
		print_submit_row('Manage Plugins', 0);

	}

	// Get Plugin List
	if ($vbulletin->options['gts_sa_enable'] == '0')
	{
		$reqsa_query = "AND `reqsa` = '0'";
	} else {
		$reqsa_query = "";
	}

	$plugin_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "gteamspeak_plugin WHERE `enabled` = '1' " . $reqsa_query . " ORDER BY `order` ASC, `title` ASC");

	while ($plugin = $db->fetch_array($plugin_query)) {
		if ($vbulletin->options['gts_sa_enable'] == '0' && $plugin['reqsa'] == '1')
		{
			return;
		} else {
			eval($plugin["function"]);

			if ( empty($_REQUEST['do']) ) {
				eval($plugin["menu"]);
			}

			eval($plugin["code"]);
		}
	}
		// End Get Plugin List

	// User Moderation
	if ( $_REQUEST['do'] == 'manageusers' ) {

		$vbulletin->input->clean_array_gpc('r', array(
			'moderate' => TYPE_STR,
			'tsid' => TYPE_INT,
			'userid' => TYPE_INT
		));

		if ($vbulletin->GPC['moderate'] == 'sagrant')
		{
			func_gts_query("UPDATE " . $ts2db['name'] . "ts2_clients SET b_client_privilege_serveradmin = '-1' WHERE i_client_id = '" . $vbulletin->GPC['tsid'] ."'");
			if ($vbulletin->options["gts_sa_enable"] == '1')
			{
				$cyts->sadmin_UserChangeSA($vbulletin->GPC['tsid'], 1);
			}
		}

		if ($vbulletin->GPC['moderate'] == 'sarevoke')
		{
			func_gts_query("UPDATE " . $ts2db['name'] . "ts2_clients SET b_client_privilege_serveradmin = '0' WHERE i_client_id = '" . $vbulletin->GPC['tsid'] ."'");
			if ($vbulletin->options["gts_sa_enable"] == '1')
			{
				$cyts->sadmin_UserChangeSA($vbulletin->GPC['tsid'], 0);
			}
		}

		if ($vbulletin->GPC['moderate'] == 'delete')
		{
 			func_gts_delete_user($vbulletin->GPC['tsid']);
		}

		if ($vbulletin->GPC['moderate'] == 'ban')
		{
 			func_gts_delete_user($vbulletin->GPC['tsid']);
  		$db->query("UPDATE " . TABLE_PREFIX . "userfield SET " . func_gts_fieldid($vbulletin->options['gts_userfiedid']) . " = 'no' WHERE userid = '" . $vbulletin->GPC['userid'] ."'");
			if ($vbulletin->options["gts_sa_enable"] == '1')
			{
				$cyts->admin_kick($vbulletin->GPC['tsid'], "Banned");
			}
		}

	  $result = func_gts_query("SELECT * FROM " . $ts2db['name'] . "ts2_clients WHERE ts2_clients.i_client_server_id = '" . $ts2db['serverid'] . "' ORDER BY ts2_clients.b_client_privilege_serveradmin ASC, ts2_clients.s_client_name ASC", "true");

		print_form_header();
		print_table_header('Manage Users', '7');

		print_cells_row(array(
			"SA",
			"UserName",
			"LoginName",
			"Registered",
			"Last On",
			"Delete",
			"Ban"
		), 0, 'thead');

		$vbuser = func_gts_vbuserinfo();

		while ($r = $db->fetch_array($result)) {
			list(, $userid) = explode('.', $r['s_client_name']);

    	if ($vbuser[$userid]['username'] == '')
    	{
    		$vbuser[$userid]['username'] = '<b><font color="red">Deleted User?</font></b>';
    	}

    	$javascript_username = str_replace ("'", "\\'", strip_tags($vbuser[$userid]['username']));

			$date['createddate'] = vbdate($vbulletin->options['dateformat'], func_gts_tounix($r['dt_client_created']), 1);
	  	$date['createdtime'] = vbdate($vbulletin->options['timeformat'], func_gts_tounix($r['dt_client_created']));

    	if (trim($r['dt_client_lastonline']) != '')
    	{
				$date['lastondate'] = vbdate($vbulletin->options['dateformat'], func_gts_tounix($r['dt_client_lastonline']), 1);
	  		$date['lastontime'] = vbdate($vbulletin->options['timeformat'], func_gts_tounix($r['dt_client_lastonline']));
    	} else {
    	  $date['lastondate'] = "Never";
    	  $date['lastontime'] = "";
    	}

    	if ($r['b_client_privilege_serveradmin'] == '-1')
    	{
    	  $serveradmin = '(<a href="'.$this_script.'.php?do=manageusers&moderate=sarevoke&tsid=' . $r['i_client_id'] . '" onClick="return confirm(\'Are you sure you want to revoke SA for user: ' . $javascript_username . '\');">SA</a>)';
    	} else {
    	  $serveradmin = '(<a href="'.$this_script.'.php?do=manageusers&moderate=sagrant&tsid=' . $r['i_client_id'] . '" onClick="return confirm(\'Are you sure you want to grant SA for user: ' . $javascript_username . '\');">R</a>)';
    	}

			$cell = array();
			$cell[] = '' . $serveradmin . '';
			$cell[] = '<a href="user.php?do=edit&u=' . $userid . '">' . $vbuser[$userid]['username'] . '</a>';
			$cell[] = '' . $r['s_client_name'] . '';
			$cell[] = '' . $date['createddate'] . ' <span class="time">' . $date['createdtime'] . '</span>';
			$cell[] = '' . $date['lastondate'] . ' <span class="time">' . $date['lastontime'] . '</span>';
			$cell[] = '<a href="'.$this_script.'.php?do=manageusers&moderate=delete&tsid=' . $r['i_client_id'] . '&userid=' . $userid . '" onClick="return confirm(\'Are you sure you want to delete user: ' . $javascript_username . '\');">X</a>';
			$cell[] = '<a href="'.$this_script.'.php?do=manageusers&moderate=ban&tsid=' . $r['i_client_id'] . '&userid=' . $userid . '" onClick="return confirm(\'Are you sure you want to ban user: ' . $javascript_username . '\');">O</a>';

			print_cells_row($cell, 0, '', -2);
		}

		print_table_footer(7);

	}

	// Manage Plugins
	if ($_POST['do'] == 'doimport')
	{
		$vbulletin->input->clean_array_gpc('p', array(
			'serverfile' => TYPE_STR,
		));

		$vbulletin->input->clean_array_gpc('f', array(
			'pluginfile' => TYPE_FILE
		));

		// got an uploaded file?
		if (file_exists($vbulletin->GPC['pluginfile']['tmp_name']))
		{
			$xml = file_read($vbulletin->GPC['pluginfile']['tmp_name']);
			func_gts_plugin($xml);
		}
		// no uploaded file - got a local file?
		else if (file_exists($vbulletin->GPC['serverfile']))
		{
			$xml = file_read($vbulletin->GPC['serverfile']);
			func_gts_plugin($xml);
		}
		// no uploaded file and no local file - ERROR
		else
		{
			print_stop_message('no_file_uploaded_and_no_local_file_found');
		}
	}

	// Manage Plugins
	if ( $_REQUEST['do'] == 'manageplugins' ) {

		$vbulletin->input->clean_array_gpc('r', array(
			'moderate' => TYPE_STR,
			'pluginid' => TYPE_STR,
			'enabled' => TYPE_INT,
			'order' => TYPE_ARRAY
		));

		if ($vbulletin->GPC['moderate'] == 'delete')
		{
			$db->query("DELETE FROM " . TABLE_PREFIX . "gteamspeak_plugin WHERE `title` = '" . $vbulletin->GPC['pluginid'] ."'");
		}

		if ($vbulletin->GPC['moderate'] == 'disable')
		{
			if ($vbulletin->GPC['enabled'] == '1')
			{
				$enabledisable = '0';
			} else {
				$enabledisable = '1';
			}

			$db->query("UPDATE " . TABLE_PREFIX . "gteamspeak_plugin SET `enabled` = '" . $enabledisable . "' WHERE `title` = '" . $vbulletin->GPC['pluginid'] ."'");
		}

		if (is_array($vbulletin->GPC['order']))
		{
			foreach ($vbulletin->GPC['order'] AS $key => $value)
			{
				$db->query("UPDATE " . TABLE_PREFIX . "gteamspeak_plugin SET `order` = '" . $value . "' WHERE `title` = '" . $key ."'");
			}
		}


		print_form_header($this_script, 'manageplugins', 0, 1);
		print_table_header('Manage Plugins', '7');

		print_cells_row(array(
			"Title",
			"Version",
			"Description",
			"SA",
			"Order",
			"Enable",
			"Delete",
		), 0, 'thead');

		// Manage Plugins
		$plugin_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "gteamspeak_plugin ORDER BY `order` ASC, `title` ASC");

		while ($plugin = $db->fetch_array($plugin_query)) {
			$title = $plugin['enabled'] ? $plugin['title'] : "<strike>" . $plugin['title'] . "</strike>";
			$enabledisable = $plugin['enabled'] ? "Disable" : "Enable";

			$cell = array();
			$cell[] = '' . $title . '';
			$cell[] = '' . $plugin['version'] . '';
			$cell[] = '' . $plugin['description'] . '';
			$cell[] = '' . $plugin['reqsa'] . '';
			$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$plugin[title]]\" value=\"$plugin[order]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />";
			$cell[] = '<a href="'.$this_script.'.php?do=manageplugins&moderate=disable&pluginid=' . $plugin['title'] . '&enabled=' . $plugin['enabled'] . '" onClick="return confirm(\'Are you sure you want to ' . $enabledisable . ' plugin:\n'.$plugin['title'].'?\');">O</a>';
			$cell[] = '<a href="'.$this_script.'.php?do=manageplugins&moderate=delete&pluginid=' . $plugin['title'] . '" onClick="return confirm(\'Are you sure you want to delete plugin:\n'.$plugin['title'].'?\');">X</a>';

			print_cells_row($cell, 0, '', -2);

		}

		print_table_footer(7, "<input type=\"submit\" class=\"button\" tabindex=\"1\" value=\"" . $vbphrase['save_display_order'] . "\" accesskey=\"s\" />");

		// Import
		print_form_header($this_script, 'doimport', 1, 1, 'uploadform" onsubmit="return js_confirm_upload(this, this.pluginfile);');
		print_table_header($vbphrase['import_plugin_xml_file']);
		print_upload_row($vbphrase['upload_xml_file'], 'pluginfile', 999999999);
		print_input_row($vbphrase['import_xml_file'], 'serverfile', './admincp/gtsplugin/plugins.xml');
		print_submit_row($vbphrase['import'], 0);
	}

	if ($vbulletin->options['gts_sa_enable'] == '1')
	{
		if ($vbulletin->options['gts_sa_debug'] == '1')
		{
			func_gts_print_r($cyts->debug());
		}
		$cyts->disconnect();
	}

	// Copyright
	gts_print_footer();

	print_cp_footer();
?>