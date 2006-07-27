<?php
	// ########################################################################
	//
	// gMinecraft, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gMinecraft, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: $
	// $Rev: $
	// $LastChangedBy: $
	// $Date: $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gminecraft');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array();

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'gmc_main',
		'gmc_users_bit',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');

	// function to sort array case insensitive
	function asorti(array $array)
	{
		$copy = $array;

		$array = array_map('strtolower', $array);
		asort($array);

		foreach ($array as $index => $value)
		{
			$array[$index] = $copy[$index];
		}

		return $array;
	}

	function sec2hms ($sec, $padHours = false)
	  {

		// start with a blank string
		$hms = "";

		// do the hours first: there are 3600 seconds in an hour, so if we divide
		// the total number of seconds by 3600 and throw away the remainder, we're
		// left with the number of hours in those seconds
		$hours = intval(intval($sec) / 3600);

		// add hours to $hms (with a leading 0 if asked for)
		$hms .= ($padHours)
			  ? str_pad($hours, 2, "0", STR_PAD_LEFT). ":"
			  : $hours. ":";

		// dividing the total seconds by 60 will give us the number of minutes
		// in total, but we're interested in *minutes past the hour* and to get
		// this, we have to divide by 60 again and then use the remainder
		$minutes = intval(($sec / 60) % 60);

		// add minutes to $hms (with a leading 0 if needed)
		$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";

		// seconds past the minute are found by dividing the total number of seconds
		// by 60 and using the remainder
		$seconds = intval($sec % 60);

		// add seconds to $hms (with a leading 0 if needed)
		$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

		// done!
		return $hms;

	}


	$reqno =  $db->escape_string(urldecode($vbulletin->input->clean_gpc('r', 'server', TYPE_NOHTML)));

	if ($reqno == 's1Sgxerr')
	{
		$serverno = 'one';
	}
	elseif ($reqno == 's2gGhhmQ')
	{
		$serverno = 'two';
	}
	elseif ($reqno == 's3gaTy4s')
	{
		$serverno = 'three';
	}
	elseif ($reqno == 'smp1asyBiW')
	{
		$serverno = 'smpone';
	}
	else
	{
		unset($serverno);
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gmc_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gMinecraft', $copyrightyear);

	if($serverno == 'one' || $serverno == 'two' || $serverno == 'three' || $serverno == 'smpone' )
	{

        $serverdata['properties'] = @file('/home/servers/minecraft/'. $serverno .'/server.properties');
        $serverdata['externalurl'] = @file('/home/servers/minecraft/'. $serverno .'/externalurl.txt');
        $serverdata['players'] = @file('/home/servers/minecraft/'. $serverno .'/players.txt');
        $serverdata['log'] = @file('/home/servers/minecraft/'. $serverno .'/server.log');
        $serverdata['logstring'] = @file_get_contents('/home/servers/minecraft/'. $serverno .'/server.log');
        $serverdata['banned'] = @file('/home/servers/minecraft/'. $serverno .'/banned.txt');
        $serverdata['banned'] = (file_exists('/home/servers/minecraft/'. $serverno .'/banned.txt') ? @file('/home/servers/minecraft/'. $serverno .'/banned.txt') : @file('/home/servers/minecraft/'. $serverno .'/banned-players.txt'));
        $serverdata['bannedip'] = (file_exists('/home/servers/minecraft/'. $serverno .'/banned-ip.txt') ? @file('/home/servers/minecraft/'. $serverno .'/banned-ip.txt') : @file('/home/servers/minecraft/'. $serverno .'/banned-ips.txt'));
        $serverdata['admins'] = (file_exists('/home/servers/minecraft/'. $serverno .'/admins.txt') ? @file('/home/servers/minecraft/'. $serverno .'/admins.txt') : @file('/home/servers/minecraft/'. $serverno .'/ops.txt'));
        $serverdata['whitelist'] = @file('/home/servers/minecraft/'. $serverno .'/white-list.txt');

        // server id
        $server['serverno'] = $reqno;

        // properties
        foreach ($serverdata['properties'] as $line)
        {
            $server['properties'] .= htmlentities($line);
        }

        // external url
        if ($serverdata['externalurl'])
        {
            foreach ($serverdata['externalurl'] as $line)
            {
                $server['externalurl'] .= '<a href="'. $line .'">'. $line .'</a>';
            }
        }

        if ($serverdata['players'])
        {
            foreach ($serverdata['players'] as $line)
            {
                $server['players'] .= htmlentities($line);
            }
        }

        if ($serverdata['whitelist'])
        {
            foreach ($serverdata['whitelist'] as $line)
            {
                $server['whitelist'] .= htmlentities($line);
            }
        }

        foreach ($serverdata['banned'] as $line)
        {
            $server['banned'] .= htmlentities($line);
        }

        foreach ($serverdata['admins'] as $line)
        {
            $server['admins'] .= htmlentities($line);
        }

        // chat log
        $chatlog = array_reverse($serverdata['log']);
        $chatcount = 0;
        $chatlogarray = array();

        foreach ($chatlog as $line)
        {
            if (preg_match("/says\:/", $line))
            {
                $chatlogarray[] = htmlentities($line);
                $chatcount++;
            }
            elseif(preg_match("/\[INFO\] \</", $line))
            {
                $chatlogarray[] = htmlentities($line);
                $chatcount++;
            }
            if($chatcount == 600)
            {
                break;
            }
        }

        $chatlogarray = array_reverse($chatlogarray);
        foreach ($chatlogarray as $line)
        {
            $server['chatlog'] .= ltrim($line);
        }


        // login logout
        $loginlog = array_reverse($serverdata['log']);
        $logincount = 0;

        foreach ($loginlog as $line)
        {
            if (preg_match("/logged in/", $line) || preg_match("/disconnected/", $line) || preg_match("/Quitting/", $line))
            {
                $server['loginout'] .= htmlentities(ltrim($line));
                $logincount++;
            }
            if($logincount == 100)
            {
                break;
            }
        }

        // determine who is screen on SMP
        if ($reqno == 'smp1asyBiW')
        {
            $playersonline = array();

            $res = $db->query_read("SELECT name, UNIX_TIMESTAMP( NOW( ) ) - UNIX_TIMESTAMP( date_joined ) AS duration FROM mc_visitor WHERE date_left IS NULL ORDER BY duration DESC");

            while ($player = $db->fetch_array ($res))
            {
                $server['players'] .= $player['name'] . ' - ' . sec2hms($player['duration']) . "\n";
            }
        }

        // admin actions
        $adminlog = array_reverse($serverdata['log']);
        $admincount = 0;

        foreach ($adminlog as $line)
        {
            if (preg_match("/admins\:/", $line))
            {
                $server['adminlog'] .= htmlentities(ltrim($line));
                $admincount++;
            }
            elseif (preg_match("/issued server command\:/", $line) || preg_match("/server command\:/", $line) || preg_match("/\[INFO\] [^\<]/", $line) && ( !preg_match("/logged in/", $line) && !preg_match("/Quitting/", $line) ) )
            {
                $server['adminlog'] .= htmlentities($line);
                $logincount++;
            }
            if($admincount == 100)
            {
                break;
            }
        }

        // raw log
        $rawlog = array_reverse($serverdata['log']);
        $rawcount = 0;

        foreach ($rawlog as $line)
        {
            if (!preg_match("/Level saved/", $line) && !preg_match("/lost connection suddenly/", $line))
            {
                $server['rawlog'] .= htmlentities(ltrim($line));
                $rawcount++;
            }

            if($rawcount == 100)
            {
            break;
            }
        }

        // users
        $userlog = array_reverse($serverdata['log']);
        $server['usercache'] = array();

        foreach ($userlog as $line)
        {
            if (preg_match("/logged in as ((?:[A-Za-z0-9_.]+))/", $line, $matches))
            {
                $server['usercache'][$matches[1]] = $matches[1];
            }
            if (preg_match("/\[INFO\] ((?:[A-Za-z0-9_.]+)) .*? logged in/", $line, $matches))
            {
                $server['usercache'][$matches[1]] = $matches[1];
            }
        }

        $server['usercache'] = asorti($server['usercache']);

        foreach ($server['usercache'] as $user)
        {
            $server['userlog'] .= '<a href="http://www.minecraft.net/skin/skin.jsp?user=' . $user . '" target="_blank">' . $user . '</a><br />';
        }

        // determine user history for SMP
        if ($reqno == 'smp1asyBiW')
        {
            $server['userlog'] = "";

            $res = $db->query_read("SELECT name, COUNT( * ) AS num, SUM( UNIX_TIMESTAMP( IFNULL( date_left, NOW( ) ) ) - UNIX_TIMESTAMP( date_joined ) ) AS duration, MAX( date_joined ) AS last_seen, MIN( date_joined ) AS first_seen
                FROM mc_visitor
                GROUP BY name
                HAVING duration >5
                ORDER BY duration DESC , name");

            while ($player = $db->fetch_array ($res))
            {
                $player['duration'] = sec2hms($player['duration']);
                eval('$server[userlog] .= "' . fetch_template('gmc_users_bit') . '";');
            }
        }

	} //close if server

	eval('print_output("' . fetch_template('gmc_main') . '");');

?>