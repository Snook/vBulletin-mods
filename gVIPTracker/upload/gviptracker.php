<?php
	// ########################################################################
	//
	// gVIPTracker, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gVIPTracker, please contact me at ghryphen@gmail.com for collaboration.
	// I appreciate your kind consideration.
	//
	// This work is licensed under the Creative Commons
	// Attribution-Noncommercial-No Derivative Works 2.5 License.
	// To view a copy of this license, visit
	// http://creativecommons.org/licenses/by-nc-nd/2.5/ or send a letter to
	// Creative Commons, 543 Howard Street, 5th Floor,
	// San Francisco, California, 94105, USA.
	//
	// ########################### SVN info ###################################
	// $Id: gviptracker.php 534 2007-03-14 21:25:04Z ghryphen $
	// $Rev: 534 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-03-14 14:25:04 -0700 (Wed, 14 Mar 2007) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################

	define('THIS_SCRIPT', 'gviptracker');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array('gvipt', 'forumdisplay');

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'gvipt_index',
		'gvipt_threadbit',
		'gvipt_threadbit_none',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');

	// #################### HARD CODE JAVASCRIPT PATHS ########################
	$headinclude = str_replace('clientscript', $vbulletin->options['bburl'] . '/clientscript', $headinclude);

	// ######################### PERMS ############################
	if ($vbulletin->options['gvipt_usergroup_access'] != '0' && !is_member_of($vbulletin->userinfo, explode(',',$vbulletin->options['gvipt_usergroup_access'])))
	{
		print_no_permission();
		exit;
	}

	// ######################### CLEAN GPC ############################
	$perpage = $vbulletin->input->clean_gpc('r', 'perpage', TYPE_UINT);
	$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);
	$select_vipid = $vbulletin->input->clean_gpc('r', 'vipid', TYPE_UINT);
	$select_vipfid = $vbulletin->input->clean_gpc('r', 'vipfid', TYPE_UINT);
	$show_feed = $vbulletin->input->clean_gpc('r', 'feed', TYPE_STR);

	iif($vbulletin->options['gvipt_forum_limit'], ',' . $vbulletin->options['gvipt_forum_limit'], ',0');
	iif($vbulletin->options['gvipt_forum_exlude'], ',' . $vbulletin->options['gvipt_forum_exlude'], ',0');

	$show['gvipt_vipselect'] = $vbulletin->options['gvipt_show_vipselect'];
	$show['gvipt_fidselect'] = $vbulletin->options['gvipt_show_fidselect'];
	$show['gvipt_rss'] = $vbulletin->options['gvipt_enable_rss'];
	$show['show_feed'] = iif($show_feed == 'rss2' && $show['gvipt_rss'], '1', '0');

	if($show['show_feed'])
	{
		define('NOSHUTDOWNFUNC', 1);
		define('SKIP_SESSIONCREATE', 1);
		define('DIE_QUIETLY', 1);

		$vbulletin->session->vars['sessionurl'] =
		$vbulletin->session->vars['sessionurl_q'] =
		$vbulletin->session->vars['sessionurl_js'] =
		$vbulletin->session->vars['sessionhash'] = '';

		$rss_limit = "LIMIT 25";
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gvipt_title'];

	if(!$show['show_feed'])
	{
		$navbits = construct_navbits($navbits);
		eval('$navbar = "' . fetch_template('navbar') . '";');
	}

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gVIPTracker', $copyrightyear);

	// ########################################################################
	// ######################### START MAIN SCRIPT ############################
	// ########################################################################

	$gviptdate['start'] = iif($vbulletin->options['gvipt_date_start'], strtotime($vbulletin->options['gvipt_date_start']), false);
	$gviptdate['end'] = iif($vbulletin->options['gvipt_date_end'], strtotime($vbulletin->options['gvipt_date_end']), false);

	$vbphrase['gvipt_start'] = $vbphrase['beginning'];
	$vbphrase['gvipt_end'] = $vbphrase['gvipt_now'];

	if($gviptdate['start'])
	{
		$show['gvipt_start'] = 1;
		$vbphrase['gvipt_start'] = vbdate($vbulletin->options['dateformat'], $gviptdate['start'], 1);
		$date_start_query = "AND p.dateline > '" . $gviptdate['start'] . "'";
	}

	if($gviptdate['end'])
	{
		$show['gvipt_end'] = 1;
		$vbphrase['gvipt_end'] = vbdate($vbulletin->options['dateformat'], $gviptdate['end'], 1);
		$date_end_query = "AND p.dateline < '" . $gviptdate['end'] . "'";
	}
	unset($gviptdate);

	$usergroups = iif($vbulletin->options['gvipt_usergroups'], $vbulletin->options['gvipt_usergroups'], '0');

	$groups = explode(",", $usergroups);
	foreach ($groups AS $id)
	{
		$query .= "OR u.usergroupid = '" . $id . "' ";
		if($id != '0')
		{
			$query .= "OR FIND_IN_SET('" . $id . "', u.membergroupids) ";
		}
	}
	$query = substr($query, 3);
	unset($groups, $id, $usergroups);

	$res = $db->query("SELECT u.userid, u.username, u.posts FROM " . TABLE_PREFIX . "user AS u WHERE " . $query . " ORDER BY u.username ASC");

	$userid = array();
	while ($user = $db->fetch_array($res))
	{
		$userid[] = $user['userid'];
		if($user['posts'] > '0')
		{
			$vip_select .= '<option value="' . $user['userid'] . '"' . iif($select_vipid == $user['userid'], ' selected', '') . '>' . $user['username'] . '</option>' . "\n";
		}
	}
	$userids = implode(', ', $userid);
	unset($user);

	// Forum Perms
	$forumperms = array();
	foreach($vbulletin->forumcache AS $forum) {

		$forumperms[$forum["forumid"]] = fetch_permissions($forum['forumid']);
		if (!($forumperms[$forum["forumid"]] & $vbulletin->bf_ugp_forumpermissions['canview']) AND !$vbulletin->options['showprivateforums'])
		{
			$limitfids .= ',' . $forum['forumid'];
		}
	}
	unset($forum, $forumperms);

	// ########################################################################

	if (count($userid) != '0')
	{
		$select_user = iif($select_vipid && in_array($select_vipid, $userid), "= '" . $select_vipid . "'", "IN (" . $userids . ")");

		$select_forumid = iif($select_vipfid && $select_vipfid != '0', "AND t.forumid = '" . $select_vipfid . "'");

		if($vbulletin->options['gvipt_forum_limit'] != '0')
		{
			$forum_limit = "AND (";

			$forums = explode(",", $vbulletin->options['gvipt_forum_limit']);
			foreach($forums AS $forumid)
			{
				$forum_limit .= "t.forumid = '" . $forumid . "' OR ";
			}
			$forum_limit = substr($forum_limit, 0, -3) . ")";
			unset($forums, $forumid);
		}

		$query = "FROM " . TABLE_PREFIX . "post p
			LEFT JOIN " . TABLE_PREFIX . "thread t ON (p.threadid = t.threadid)
			LEFT JOIN " . TABLE_PREFIX . "forum f ON (t.forumid = f.forumid)
			LEFT JOIN " . TABLE_PREFIX . "user u ON (p.userid = u.userid)
			" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? " LEFT JOIN " . TABLE_PREFIX . "threadread AS threadread ON (threadread.threadid = t.threadid AND threadread.userid = " . $vbulletin->userinfo['userid'] . ")" : "") . "
			WHERE p.userid " . $select_user . "
			" . $forum_limit . "
			" . $date_start_query . "
			" . $date_end_query . "
			AND t.forumid NOT IN (0" . $limitfids . $vbulletin->options['gvipt_forum_exlude'] . ")
			AND t.visible = '1'
            AND p.visible = '1'
		";

		if(!$show['show_feed'])
		{
			// Count all log entries for page nav
			$threadcount = $db->query_first("SELECT COUNT(*) AS `threadcount`
				" . $query . "
				" . $select_forumid . "
				ORDER BY p.dateline DESC
			");

			// Make sure all these variables are cool
			sanitize_pageresults($threadcount['threadcount'], $pagenumber, $perpage, 100, $vbulletin->options['gvipt_per_page']);

			// Default lower and upper limit variables
			$limitlower = ($pagenumber - 1) * $perpage + 1;
			$limitupper = $pagenumber * $perpage;
			if ($limitupper > $threadcount['threadcount'])
			{
				// Too many for upper limit
				$limitupper = $threadcount['threadcount'];
				if ($limitlower > $threadcount['threadcount'])
				{
					// Too many for lower limit
					$limitlower = $threadcount['threadcount'] - $perpage;
				}
			}
			if ($limitlower <= 0)
			{
				// Can't have negative or null lower limit
				$limitlower = 1;
			}

			// Finally construct the page nav
			$pagenav = construct_page_nav($pagenumber, $perpage, $threadcount['threadcount'], 'gviptracker.php?' . $vbulletin->session->vars['sessionurl'] . 'do=getall', ''
				. (!empty($select_vipid) ? "&amp;vipid=$select_vipid" : "")
				. (!empty($select_vipfid) ? "&amp;vipfid=$select_vipfid" : "")
			);
		}

		// ########################################################################

		$query_cols = "f.title AS ftitle, t.title, t.forumid, t.open, t.lastpost, p.postid, p.threadid, p.username, p.userid, u.usertitle, p.dateline, p.pagetext AS preview
			" . (($vbulletin->options['threadmarking'] AND $vbulletin->userinfo['userid']) ? ", threadread.readtime AS threadread" : "") . "
		";

		$res = $db->query("SELECT " . $query_cols . "
			" . $query . "
			" . $select_forumid . "
			ORDER BY p.dateline DESC
			" . iif($show['show_feed'], $rss_limit, "LIMIT " . ($limitlower - 1) . ", " . $perpage) . "
		");

		if(!$show['show_feed'])
		{
			// Setup Forum Select
			$fres = $db->query("SELECT " . $query_cols . " " . $query . "	ORDER BY p.dateline DESC");

			$forum_log = array();
			while ($forum = $db->fetch_array($fres))
			{
				if(!in_array($forum['forumid'], $forum_log))
				{
					$vfip_select .= '<option value="' . $forum['forumid'] . '"' . iif($select_vipfid == $forum['forumid'], ' selected', '') . '>' . $forum['ftitle'] . '</option>' . "\n";
				}
				$forum_log[] = $forum['forumid'];
			}
			unset($forum_log, $forum, $fres);
			// End Setup Forum Select
		}

	}

	if(mysql_num_rows($res) == '0' && !$show['show_feed'])
	{
		eval('$gvipt_threadbit .= "' . fetch_template('gvipt_threadbit_none') . '";');
	}
	else
	{
		if(!$show['show_feed'])
		{
			while ($thread = $db->fetch_array($res))
			{
				$thread['statusicon'] = '';

				if (!$thread['open'])
				{
					$thread['statusicon'] = '_lock';
				}

				$lastread = $vbulletin->userinfo['lastvisit'];

				if ($thread['lastpost'] > $lastread)
				{
					if ($vbulletin->options['threadmarking'] AND $thread['threadread'])
					{
						$threadview = $thread['threadread'];
					}
					else
					{
						$threadview = intval(fetch_bbarray_cookie('thread_lastview', $thread['threadid']));
					}

					if ($thread['lastpost'] > $threadview)
					{
						$thread['statusicon'] .= '_new';
					}
				}


				$thread['date'] = vbdate($vbulletin->options['dateformat'], $thread['dateline'], 1);
				$thread['time'] = vbdate($vbulletin->options['timeformat'], $thread['dateline']);

				$thread['preview'] = strip_quotes($thread['preview']);
				$thread['preview'] = htmlspecialchars_uni(fetch_censored_text(fetch_trimmed_title(
					strip_bbcode($thread['preview'], false, true),
					$vbulletin->options['threadpreview']
				)));

				eval('$gvipt_threadbit .= "' . fetch_template('gvipt_threadbit') . '";');
			}
		}
		else
		{
			//Build Feed
			include_once(DIR . "/includes/functions_gviptracker.php");
		}

	}

	if(!$show['show_feed'])
	{
		eval('print_output("' . fetch_template('gvipt_index') . '");');
	}
?>