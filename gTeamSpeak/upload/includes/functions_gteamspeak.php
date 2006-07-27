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
	// $Id: functions_gteamspeak.php 683 2007-08-22 22:42:32Z ghryphen $
	// $Rev: 683 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-08-22 15:42:32 -0700 (Wed, 22 Aug 2007) $


	// #############################################################################
/**
	* connect: If SA features are enabled, make CyTS connect
	*
  */
	if ($vbulletin->options['gts_sa_enable'] == '1')
	{
		// Some may already be loading the class from other projects, so let's not error.
		if (!class_exists('cyts'))
		{
			include_once(DIR . '/includes/class_cyts.php');
		}

		include_once(DIR . '/includes/class_cytsx.php');

		$sauser = func_gts_sa_info();

		//$cyts_class = new cyts;
		$cyts = new cytsx;
  	$cyts->connect($vbulletin->options['gts_serverip'], $vbulletin->options['gts_serverqueryport'], $vbulletin->options['gts_serverport']);
  	$cyts->slogin($sauser['s_client_name'], $sauser['s_client_password']);
	}

// #############################################################################
/**
	* func_gts_loginname: Creates a simple and unique loginname.
	*
  * @return  loginname
  */
	function func_gts_loginname($name, $id)
	{
		return strtolower(preg_replace('#[^a-zA-Z0-9]+#', '', $name) . "." . $id);
	}

// #############################################################################
/**
	* func_gts_fieldid: Creates properly formatted field id.
	*
  * @return  fieldID
  */
	function func_gts_fieldid($id)
	{
		if (is_numeric($id) && $id != '0')
		{
			return "field" . $id;
		}
		else
		{
			return $id;
		}
	}

// #############################################################################
/**
	* func_gts_info: Returns information for use on the public page.
	*
  * Output:
	* Array
	* (
	*     [username] => alphanumericusername.forumuserid
	*     [forumname] => Forum Username
	*     [forumid] => Forum UserId
	*     [password] => Users password
	*     [serverlabel] => Server Label
	*     [serverip] => Server IP
	*     [serverport] => Server Port
	*     [serveraddress] => Server Address
	* )
  *
  * @return     array public page information
  */
	function func_gts_info()
	{
		global $vbulletin;

		$ts2db = func_gts_dbinfo();

		$ts2info['username'] = func_gts_loginname($vbulletin->userinfo['username'], $vbulletin->userinfo['userid']);
		$ts2info['forumname'] = $vbulletin->userinfo['username'];
		$ts2info['forumid'] = $vbulletin->userinfo['userid'];
		$ts2info['password'] = "";
		$ts2info['serverlabel'] = $vbulletin->options['gts_serverlabel'];
		$ts2info['serverip'] = $vbulletin->options['gts_serverip'];
		$ts2info['serverport'] = $vbulletin->options['gts_serverport'];

		if ($ts2info['serverport'] != '8767')
		{
			$ts2info['serveraddress'] = $vbulletin->options['gts_serveraddress'] . ":" . $ts2info['serverport'];
		}
		else
		{
			$ts2info['serveraddress'] = $vbulletin->options['gts_serveraddress'];
		}

	  $res = func_gts_query("SELECT * FROM " . $ts2db['name'] . "ts2_clients WHERE s_client_name = '" . $ts2info['username'] . "' AND i_client_server_id = '" . $ts2db['serverid'] . "'");

		if ($vbulletin->db->num_rows($res) != '0')
		{
			$row = $vbulletin->db->fetch_array($res);
			$ts2info['password'] = $row['s_client_password'];
		}
		else
		{
			$ts2info['password'] = "";
		}

		return $ts2info;
	}

// #############################################################################
/**
	* func_gts_dbinfo: Returns information for use in querying the database.
	*
  * Output:
	* Array
	* (
	*     [name] => Database name if remote
	*     [serverid] => Server ID issued by MySQL
	* )
	*
  * @return     array db information
  */
	function func_gts_dbinfo()
	{
		global $vbulletin;

		if ($vbulletin->options['gts_db_remote'] == '1')
		{
			$ts2db['name'] = $vbulletin->options['gts_db_db'] . ".";
		}
		else
		{
			$ts2db['name'] = "";
		}

		$ts2db['serverid'] = $vbulletin->options['gts_serverid'];

		return $ts2db;
	}

// #############################################################################
/**
	* func_gts_query: Querys the remote database or vBulletin database depending
	* on settings.
	*
  * Output:
	* Array
	* (
	*     [name] => Database name if remote
	*     [serverid] => Server ID issued by MySQL
	* )
	*
	* @param		string	The text of the SQL query to be executed
	* @param		string	true/false echo commented query string
  * @return		array db information
  */
  function func_gts_query($query, $debug = 'false')
  {
  	global $vbulletin;

		if ($vbulletin->options['gts_db_remote'] == '1')
		{
			$ts_db =& new vB_Database($vbulletin);

			$ts_db->connect($vbulletin->options['gts_db_db'],
			                $vbulletin->options['gts_db_host'],
			                $vbulletin->config['MasterServer']['port'],
			                $vbulletin->options['gts_db_username'],
			                $vbulletin->options['gts_db_password'],
			                $vbulletin->config['MasterServer']['usepconnect']
			                );

			$res = $ts_db->query($query);

			//$ts_db->close();
		}
		else
		{
			$res = $vbulletin->db->query($query);
		}

		if ($debug == 'true')
		{
			echo "<!-- gTeamSpeak: $query -->";
		}

    return $res;
  }

// #############################################################################
/**
	* func_gts_tounix: Convert TeamSpeak timestamp into Unix timestamp
	*
  * @param		TeamSpeak timestamp
  * @return		Unix timestamp
  */
  function func_gts_tounix($stamp)
  {
  	return mktime(substr($stamp,8,2),substr($stamp,10,2),substr($stamp,12,2),substr($stamp,2,2),substr($stamp,0,2),substr($stamp,4,4));
  }

// #############################################################################
/**
	* func_gts_fromunix: Convert Unix timestamp into TeamSpeak timestamp
	*
  * @param		Unix timestamp
  * @return		TeamSpeak timestamp
  */
  function func_gts_fromunix($stamp)
  {
  	//DDMMYYYYHHMMSSsss
  	return date("dmYHis", $stamp)."000";
  }

// #############################################################################
/**
	* func_gts_prune_users: Prunes users who have not logged on in X days
	*
  * @return		vBulletin output
  */
	function func_gts_prune_users()
	{
		global $vbulletin;

		$ts2db = func_gts_dbinfo();

		$prunetime = time() + ($_REQUEST['prunedays']*(-86400));

		$rules = "";

    if ($_REQUEST['ignoresa'] == 'true')
    {
    	$rules .= " AND b_client_privilege_serveradmin = '0'";
    }

    $res = func_gts_query("SELECT * FROM " . $ts2db['name'] . "ts2_clients WHERE i_client_server_id = '" . $ts2db['serverid'] . "' " . $rules . "", "true");

    while ($row = $vbulletin->db->fetch_array($res))
    {

			if (func_gts_tounix($row['dt_client_lastonline']) < $prunetime)
			{

    		if (func_gts_tounix($row['dt_client_lastonline']) == '-1' && $_REQUEST['prunenever'] == 'no')
    		{
    			return;
    		}
    		else
    		{
	    		print_description_row('Pruning: '.  $row['s_client_name'] . ', last online: ' . vbdate($vbulletin->options['dateformat'], func_gts_tounix($row['dt_client_lastonline']), 1) . '');
	    		if ($_REQUEST['testrun'] == 'no')
	    		{
	    			func_gts_delete_user($row['i_client_id']);
	    		}
	    	}

    	}
    }

	}

// #############################################################################
/**
	* func_gts_sa_info: Query's TeamSpeak database for SA username and password
	*
  * Output:
	* Array
	* (
	*     [s_client_name] => SA Username
	*     [s_client_password] => SA Password
	* )
	*
  * @return		Array containing SA login info
  */
	function func_gts_sa_info()
	{
  	global $vbulletin;

  	if(!isset($vbulletin->options['gts_client_name']) || $vbulletin->options['gts_client_name'] == '0' || $vbulletin->options['gts_client_name'] == '' || !isset($vbulletin->options['gts_client_password']) || $vbulletin->options['gts_client_password'] == '0' || $vbulletin->options['gts_client_password'] == '')
  	{

			$ts2db = func_gts_dbinfo();

  		$res = func_gts_query("SELECT s_client_name, s_client_password FROM " . $ts2db['name'] . "ts2_clients WHERE i_client_server_id = '0' AND b_client_privilege_serveradmin = '-1' ORDER BY i_client_id ASC LIMIT 1");

  		if ($vbulletin->db->num_rows($res) == '0')
  		{
  			$sauser["s_client_name"] = "super.admin";
  			$sauser["s_client_password"] = uniqid("");
  			$createtime = func_gts_fromunix(time());

				func_gts_query("INSERT INTO " . $ts2db['name'] . "ts2_clients SET s_client_name = '" . $sauser["s_client_name"] . "', s_client_password = '" . $sauser["s_client_password"] . "', i_client_server_id = '0', b_client_privilege_serveradmin = '-1', dt_client_created = '" . $createtime . "'");
  		}
  		else
  		{
  			$sauser = $vbulletin->db->fetch_array($res);
  		}

			$vbulletin->db->query_write( "REPLACE INTO " . TABLE_PREFIX . "setting VALUES ('gts_client_name', '" . $sauser['s_client_name'] . "', '', 405, 'gts_group2', '0', 0, 1, 'gTeamSpeak', 'free', 0, '');" );
			$vbulletin->db->query_write( "REPLACE INTO " . TABLE_PREFIX . "setting VALUES ('gts_client_password', '" . $sauser['s_client_password'] . "', '', 406, 'gts_group2', '0', 0, 1, 'gTeamSpeak', 'free', 0, '');" );

		}
		else
		{
			$sauser["s_client_name"] = $vbulletin->options['gts_client_name'];
  		$sauser["s_client_password"] = $vbulletin->options['gts_client_password'];
		}

  	return $sauser;
	}

// #############################################################################
/**
	* func_gts_delete_user: Deletes a TeamSpeak user
	*
  * @return		Nothing
  */
  function func_gts_delete_user($tsid)
  {
  	$ts2db = func_gts_dbinfo();

  	func_gts_query("DELETE FROM " . $ts2db['name'] . "ts2_clients WHERE i_client_id = '" . $tsid ."'");
  }

// #############################################################################
/**
	* func_gts_plugin: Parses XML document into an array and then submits the
	* information to the database.
	*
  * @return		vBulletin output
  */
	function func_gts_plugin($xml)
	{
		global $vbulletin;
		global $vbphrase;

		print_dots_start('<b>' . $vbphrase['importing_plugins'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

		require_once(DIR . '/includes/class_xml.php');

		$xmlobj = new vB_XML_Parser($xml);

		if ($xmlobj->error_no == 1)
		{
				print_dots_stop();
				print_stop_message('no_xml_and_no_path');
		}

		if(!$arr = $xmlobj->parse())
		{
			print_dots_stop();
			print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
		}

		// ############## general product information
		$info = array(
			'productid' => substr($arr['productid'], 0, 25),
		);

		$plugin_query = "SET
			`title` = '" . $arr['title'] ."',
			`description` = '" . $arr['description'] ."',
			`version` = '" . $arr['version'] ."',
			`order` = '" . $arr['order'] ."',
			`reqsa` = '" . $arr['reqsa'] ."',
			`enabled` = '" . $arr['enabled'] ."',
			`menu` = '" . trim(addslashes($arr['menuoption'])) ."',
			`code` = '" . trim(addslashes($arr['phpcode'])) ."',
			`function` = '" . trim(addslashes($arr['functions'])) ."'
		";

		$vbulletin->db->query("REPLACE INTO " . TABLE_PREFIX . "gteamspeak_plugin " . $plugin_query . "");

		print_dots_stop();

		print_cp_redirect("gteamspeak.php?do=manageplugins&" . $vbulletin->session->vars['sessionurl'], 0);

	}

// #############################################################################
/**
	* func_gts_vbuserinfo: Querys the vB database for users information
	*
  * Output:
	* Array
	* (
	* 		[userid] => Array
	* 				(
	* 						[username] => Forum User Name
	* 						[email] => Forum Email Address
	* 				)
	* )
	*
  * @return		array user information
  */
	function func_gts_vbuserinfo()
	{
		global $vbulletin;

	  $users = $vbulletin->db->query("SELECT userid, username, email FROM " . TABLE_PREFIX . "user");
	  $vbuser = array();
	  while ($userinfo = $vbulletin->db->fetch_array($users))
	  {
	  	$vbuser[$userinfo['userid']] = array();
	  	$vbuser[$userinfo['userid']]['username'] = $userinfo['username'];
	  	$vbuser[$userinfo['userid']]['email'] = $userinfo['email'];
	  }

	  return $vbuser;
	}

// #############################################################################
/**
	* func_gts_print_r: Adds pre to Array output
	*
  * @param		Array
  * @return		Printed Array
  */
	function func_gts_print_r($array)
	{
		echo '<div align="center"><div style="align: center; width: 80%; height: 400px; overflow: auto; background-color: #000000; color: #ffffff; padding: 10px; margin: 20px; text-align: left;"><pre>';
		print_r($array);
		echo '</pre></div></div>';
	}

// #############################################################################
/**
	* gts_print_footer: Prints copyright in AdminCP
	*
  * @return		Outputs copyright text
  */
	function gts_print_footer()
	{
		global $vbulletin;
		echo '<div align="center">
      <form action="https://www.paypal.com/cgi-bin/webscr" methd="post" target="_blank">
			<div align="center"><a href="https://github.com/ghryphen" target="_blank" class="copyright">gTeamSpeak, Copyright &copy; 2006, Ghryphen</a></div>
			<!-- Begin PayPal Logo -->
      <table><tr><td align="center"><font size="1">If you find gTeamSpeak useful and would like<br />make a donation, please click below. Thank you :)</font></td></tr></table>
      <input type="hidden" name="cmd" value="_xclick">
      <input type="hidden" name="business" value="ghryphen@gmail.com">
      <input type="hidden" name="item_name" value="gTeamSpeak Donation">
      <input type="hidden" name="no_shipping" value="1">
      <input type="hidden" name="return" value="https://github.com/ghryphen/">
      <input type="hidden" name="cancel_return" value="https://github.com/gryphen/">
      <input type="image" src="http://images.paypal.com/images/x-click-but04.gif" border="0" name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!" width="62" height="31">
      <!-- End PayPal Logo -->
      </form>';
		if ($vbulletin->options['gts_sa_enable'] == '1')
		{
    	echo '<div align="center"><a href="http://cyts.midlink.org/" target="_blank" class="copyright">CyTS, Copyright &copy; 2004-2005, Steven Barth</a></div>';
    }
	}
?>