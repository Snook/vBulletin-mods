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
	// ########################### SVN info ###################################
	// $Id: functions_gkeyexchange.php 683 2007-08-22 22:42:32Z ghryphen $
	// $Rev: 683 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-08-22 15:42:32 -0700 (Wed, 22 Aug 2007) $

	####################################################################
	//Cache users recieved keys
	function cache_userkeys()
	{
		global $vbulletin;

		$userskeys = array();

		$res = $vbulletin->db->query("SELECT k.*, p.*, u.username
			FROM " . TABLE_PREFIX . "gkeyexchange_key k
			LEFT JOIN " . TABLE_PREFIX . "gkeyexchange_program p ON (k.pid = p.id)
			LEFT JOIN " . TABLE_PREFIX . "user u ON (k.donated_byid = u.userid)
			WHERE k.used_byid = '" . $vbulletin->userinfo['userid'] . "'
			ORDER BY p.title ASC, k.used_ondate DESC
 		");
		while ($keys = $vbulletin->db->fetch_array($res))
		{
			$userskeys[] = $keys;
		}

		return $userskeys;
	}

	####################################################################
	//Cache userkey count
	function cache_userkeycount($arr)
	{
		$keycount = array();

		foreach($arr as $key)
		{
			$keycount[$key['pid']]['request']++;
			//$keycount[$keys['pid']]['donate']++;
		}

		return $keycount;
	}

	####################################################################
	function is_qualified()
	{
	  global $setting;
	  global $user;

	  if(($setting['time'] - 86400) >= $user['joindate'] && $user['posts'] >= '2')
	  {
	  	return true;
	  }
	  else
	  {
	  	return false;
		}
	}

	####################################################################
  function has_recieved_key()
  {
  	global $_COOKIE;
  	global $_SERVER;
  	global $user;

    if ($user['field27'] == 'deny')
    {
      return true;
      end;
    }
    else if (mysql_num_rows(query("SELECT * FROM psu_ps_keys WHERE used_byid='$user[userid]'")) != '0')
    {
      return true;
      end;
    } else if ($user['field28'] == 'yes')
    {
      return false;
      end;
    }
    else if (isset($_COOKIE['bbauthenticate']) && $_COOKIE['bbauthenticate'] == '1')
    {
      return true;
      end;
    }
    else if ($user['field27'] == 'yes')
    {
      return false;
      end;
    }
    else if (mysql_num_rows(query("SELECT * FROM psu_ps_keys WHERE used_byip='$_SERVER[REMOTE_ADDR]'")) != '0')
    {
      return true;
      end;
    }
    else
    {
      return false;
    }
  }

	####################################################################
  function has_donated_key()
  {
  	global $user;

  	if (mysql_num_rows(query("SELECT * FROM psu_ps_keys WHERE donated_byid='$user[userid]'")) != '0')
  	{
      return true;
      end;
    }
  }

	####################################################################
  function who_donated()
  {
    $r = query("SELECT * FROM psu_ps_keys GROUP BY donated_byname ORDER BY donated_byname ASC");
    $peeps = "";
    while ($row = mysql_fetch_object($r))
    {
      $peeps .= '<nobr><a href="/forums/member.php?u='.$row->donated_byid.'">'.$row->donated_byname.'</a></nobr>, ';
    }
    return substr(trim($peeps), 0, -1);
  }

	####################################################################
  function update_usedby_membergroupids()
  {
    global $user;

    if ($user['membergroupids'] == '')
    {
      query("UPDATE vb3_user SET membergroupids = '30' WHERE userid = '$user[userid]'");
    }
    else
    {
      if (!preg_match("/30,/i", $user['membergroupids']) && !preg_match("/,30/i", $user['membergroupids']) && $user['membergroupids'] != '30')
      {
        $update_ids = $user['membergroupids'].",30";
        query("UPDATE vb3_user SET membergroupids = '$update_ids' WHERE userid = '$user[userid]'");
      }
    }
  }

	####################################################################
  function update_donatedby_membergroupids()
  {
    global $user;

    if ($user['membergroupids'] == '')
    {
      query("UPDATE vb3_user SET membergroupids = '31' WHERE userid = '$user[userid]'");
    }
    else
    {
      if (!preg_match("/31,/i", $user['membergroupids']) && !preg_match("/,31/i", $user['membergroupids']) &&$user['membergroupids'] != '31')
      {
        $update_ids = $user['membergroupids'].",31";
        query("UPDATE vb3_user SET membergroupids = '$update_ids' WHERE userid = '$user[userid]'");
      }
    }
  }
?>