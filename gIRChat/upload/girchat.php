<?php
	// ########################################################################
	//
	// gIRChat, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gIRChat, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: girchat.php 1034 2008-09-20 08:24:39Z ghryphen $
	// $Rev: 1034 $
	// $LastChangedBy: ghryphen $
	// $Date: 2008-09-20 01:24:39 -0700 (Sat, 20 Sep 2008) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'girchat');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array();

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'GIRCHAT',
		'girchat_login',
		'girchat_java_loggedin',
		'girchat_javascript_loggedin',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');

	// ########################## HANDLE UPDATES ############################
	$matchkey = "1234";
	if ($vbulletin->input->clean_gpc('p', 'action', TYPE_NOHTML) == "statusupdate" && $vbulletin->input->clean_gpc('p', 'key', TYPE_UINT) == $matchkey)
	{
		$channel = $vbulletin->input->clean_gpc('p', 'channel', TYPE_NOHTML);
		$total = $vbulletin->input->clean_gpc('p', 'total', TYPE_UINT);
		$topic = $vbulletin->input->clean_gpc('p', 'topic', TYPE_NOHTML);
		$op = $vbulletin->input->clean_gpc('p', 'op', TYPE_NOHTML);
		$hop = $vbulletin->input->clean_gpc('p', 'hop', TYPE_NOHTML);
		$voice = $vbulletin->input->clean_gpc('p', 'voice', TYPE_NOHTML);
		$normal = $vbulletin->input->clean_gpc('p', 'normal', TYPE_NOHTML);

		$res = $vbulletin->db->query("SELECT * FROM " . TABLE_PREFIX . "girchat");
		$data = $vbulletin->db->fetch_array($res);

		$highest = $data['highest'];

		if ($total > $highest)
		{
			$highest = $total;
		}

		$vbulletin->db->query_write("REPLACE INTO `" . TABLE_PREFIX . "girchat` SET
			`channel` = '" . $channel . "',
			`topic` = '" . addslashes($topic) . "',
			`current` = '" . $total . "',
			`highest` = '" . $highest . "',
			`op` = '" . substr(addslashes($op), 0, -1) . "',
			`hop` = '" . substr(addslashes($hop), 0, -1) . "',
			`voice` = '" . substr(addslashes($voice), 0, -1) . "',
			`normal` = '" . substr(addslashes($normal), 0, -1) . "',
			`date` = '" . TIMENOW . "'");

		exit();
	}

	###### igoogle module ######
	$xmlout = '<?xml version="1.0" encoding="UTF-8" ?>
<Module>
	<ModulePrefs
		title="' .  $vbulletin->options[bbtitle] . ' Chat"
		description="' .  $vbulletin->options[bbtitle] . ' internet relay chat."
		author="Ghryphen"
		author_email="ghryphen+girchat@gmail.com"
		title_url="http://www.google.com/ig/authors?author=ghryphen%40gmail.com"
		directory_title="' .  $vbulletin->options[bbtitle] . ' Chat"
		category="funandgames"
		category2="tools"
		height="300">
		<Require feature="dynamic-height" />
	</ModulePrefs>
	<UserPref name="girchat_name" display_name="Name" required="true" />
	<UserPref name="girchat_server" display_name="Server" required="true" />
	<UserPref name="girchat_channel" display_name="Channel" required="true" />
	<Content type="html" view="home,canvas">
	<![CDATA[
		<div id="atfchat"></div>
		<script type="text/javascript">
		function init__MODULE_ID__()
		{
			var prefs = new _IG_Prefs();
			var girchat_name = prefs.getString("girchat_name");
			var girchat_server = prefs.getString("girchat_server");
			var girchat_channel = prefs.getString("girchat_channel");
			
			if(girchat_server)
			{
				var html = \'<iframe style="border:0;width:100%;height:100%" src="http://embed.mibbit.com/?server=\' + girchat_server + \'&channel=\' + girchat_channel + \'&nick=\' + girchat_name + \'"></iframe>\';
			}
				
			_gel("atfchat").innerHTML = html;
			_IG_AdjustIFrameHeight();
		}
		_IG_RegisterOnloadHandler(init__MODULE_ID__);
		</script>	
	]]>
	</Content>
</Module>';

	if ($_GET['do'] == 'xml')
	{
		header('Content-type: application/xml; charset="utf-8"',true);
		echo $xmlout;
		exit;
	}

	// #################### HARD CODE JAVASCRIPT PATHS ########################
	$headinclude = str_replace('clientscript', $vbulletin->options['bburl'] . '/clientscript', $headinclude);

	// ######################### CLEAN GPC ############################
	$vbulletin->input->clean_array_gpc('p', array(
		'username' => TYPE_NOHTML,
		'server' => TYPE_NOHTML,
		'channel' => TYPE_NOHTML,
		'interface' => TYPE_NOHTML,
		'promptme' => TYPE_NOHTML,
	));

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['girc_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gIRChat', $copyrightyear);

	if($vbulletin->options['girchat_userfield_username'])
	{
		$show['userconfig'] = 1;
	}

	// ######################### FUNCTIONS ############################
	function girc_channel_strip($var)
	{
		return preg_replace("/^#/", "", $var);
	}

	// ########################################################################
	// ######################### START MAIN SCRIPT ############################
	// ########################################################################

	if ($_POST['do'] == 'loggin')
	{
		$chans = explode(",", $vbulletin->GPC['channel']);

		list($irc['server'], $irc['port']) = explode(":", $vbulletin->GPC['server']);

		$irc['username'] = $vbulletin->GPC['username'];

		$irc['ischatting'] = true;
		$irc['promptme'] = $vbulletin->GPC['promptme'];

		if($vbulletin->GPC['interface'] == 'javascript')
		{
			foreach($chans AS $key => $chan)
			{
				$irc['channel'] .= "%23" . girc_channel_strip($chan) . "%2C";
			}

			$irc['channel'] = substr($irc['channel'], 0, -3);

			eval('$girchat = "' . fetch_template('girchat_javascript_loggedin') . '";');
		}
		else
		{
			foreach(array_reverse($chans) AS $key => $chan)
			{
				$num = $key + '1';
				$irc['channel'] .= '<param name="command' . $num . '" value="/join #' . girc_channel_strip($chan) . '">' . "\n";
			}

			eval('$girchat = "' . fetch_template('girchat_java_loggedin') . '";');
		}
	}
	else
	{

		$server = explode(";", $vbulletin->options['girchat_server_chan']);

		$irc['server'] = $server['0'];

		if($vbulletin->options['girchat_join_all'])
		{
			$irc['channel'] = '<input class="bginput" type="text" name="channel" value="' . $server['1'] . '" size="40">' . "\n";
		}
		else
		{

			$chans = explode(",", preg_replace("/,$/", "", $server['1']));

			if(count($chans) > '1')
			{
				$irc['channel'] .= '<select class="bginput" name="channel">' . "\n";

				foreach($chans AS $chan)
				{
					$irc['channel'] .= '<option value="' . girc_channel_strip($chan) . '">#' . girc_channel_strip($chan) . "\n";
				}

				$irc['channel'] .= '</select>' . "\n";
			}
			else
			{
				$irc['channel'] = '<input class="bginput" type="text" name="channel" value="' . girc_channel_strip($chans['0']) . '" size="40">' . "\n";
			}

		}

		if (trim($vbulletin->userinfo[$vbulletin->options['girchat_userfield_username']]) != '')
		{
			$irc['username'] = $vbulletin->userinfo[$vbulletin->options['girchat_userfield_username']];
		}
		else
		{
			$irc['username'] = $vbulletin->userinfo['username'];
		}

		eval('$girchat = "' . fetch_template('girchat_login') . '";');

	}

	eval('print_output("' . fetch_template('GIRCHAT') . '");');

?>