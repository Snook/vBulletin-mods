<?php
	// ########################################################################
	//
	// gDupeIP, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gDupeIP, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: gdupeip.php 688 2007-08-27 21:30:10Z ghryphen $
	// $Rev: 688 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-08-27 14:30:10 -0700 (Mon, 27 Aug 2007) $


// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('cpuser', 'banning', 'forum', 'timezone', 'user', 'cprofilefield', 'subscription');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');

// ######################## CHECK ADMIN PERMISSIONS #######################
if (!can_administer('canadminusers'))
{
	print_cp_no_permission();
}

print_cp_header('gDupeIP');

$vbulletin->input->clean_array_gpc('r', array(
	'limitstart'        => TYPE_UINT,
	'limitnumber'       => TYPE_UINT,
	'lastalt'       => TYPE_STR,
	'lastip'       => TYPE_STR,
	'years'       => TYPE_INT
));

if (empty($vbulletin->GPC['limitstart']))
{
	$vbulletin->GPC['limitstart'] = 0;
}
else
{
	$vbulletin->GPC['limitstart']--;
}

if (empty($vbulletin->GPC['limitnumber']) OR $vbulletin->GPC['limitnumber'] == 0)
{
	$vbulletin->GPC['limitnumber'] = 25;
}

if (empty($vbulletin->GPC['lastalt']))
{
	$vbulletin->GPC['lastalt'] = 'alt1';
}

if (empty($vbulletin->GPC['years']))
{
	$vbulletin->GPC['years'] = 1;
}

$limitfinish = $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'];

$res = $db->query_read("SELECT ipaddress, count(*) as count
	FROM " . TABLE_PREFIX . "user GROUP BY ipaddress HAVING count > 1 ORDER BY ipaddress");

while($user = $db->fetch_array($res))
{
	if ($user['ipaddress'] <> "")
	{
		$x .= "'$user[ipaddress]',";
	}
}
$x .= "'XXX'";

$since = TIMENOW - ($vbulletin->GPC['years'] * 31560000);
$since = 0;

$res = $db->query_read("SELECT u.userid, u.username, u.ipaddress, u.posts, u.joindate, u.lastvisit, b.bandate, b.liftdate, b.reason
	FROM `" . TABLE_PREFIX . "user` u
	LEFT JOIN `" . TABLE_PREFIX . "userban` b ON (u.userid = b.userid)
	WHERE u.ipaddress IN ($x) AND u.joindate > " . $since . "
	ORDER BY u.ipaddress, u.lastvisit DESC, u.posts DESC
	LIMIT " . $vbulletin->GPC['limitstart'] . ", " . $vbulletin->GPC['limitnumber']
);

$countusers = $db->query_first("
	SELECT COUNT(*) AS users
	FROM `" . TABLE_PREFIX . "user` u
	LEFT JOIN `" . TABLE_PREFIX . "userban` b ON (u.userid = b.userid)
	WHERE u.ipaddress IN ($x) AND u.joindate > " . $since . "
");

$header = array();
$header[] = $vbphrase['username'];
$header[] = $vbphrase['ip_address'];
$header[] = $vbphrase['post_count'];
$header[] = $vbphrase['join_date'];
$header[] = $vbphrase['last_activity'];
$header[] = $vbphrase['user_ban_reason'];

$colspan = sizeof($header);

print_form_header('gdupeip');
print_table_header(
	construct_phrase(
		$vbphrase['showing_users_x_to_y_of_z'],
		($vbulletin->GPC['limitstart'] + 1),
		iif($limitfinish > $countusers['users'], $countusers['users'], $limitfinish),
		$countusers['users']
	), $colspan);
print_cells_row($header, 1);

$bgcolor = $vbulletin->GPC['lastalt'];

$ip = $vbulletin->GPC['lastip'];

while($user = $db->fetch_array($res))
{
	$cell = array();


	if ($user['ipaddress'] <> $ip)
	{
		if ($bgcolor == "alt1")
		{
			$bgcolor = "alt2";
		}
		else
		{
			$bgcolor = "alt1";
		}
	}

	if($user['bandate'])
	{
		$userbanned = 'style="text-decoration: line-through;"';
	}
	else
	{
		$userbanned = '';
	}

	$user['joindatedate'] = vbdate($vbulletin->options['dateformat'], $user['joindate'], 1);
	$user['joindatetime'] = vbdate($vbulletin->options['timeformat'], $user['joindate']);

	$user['lastvisitdate'] = vbdate($vbulletin->options['dateformat'], $user['lastvisit'], 1);
	$user['lastvisittime'] = vbdate($vbulletin->options['timeformat'], $user['lastvisit']);

	$cell[] = '<a href="user.php?do=edit&u=' . $user['userid'] . '"><span ' . $userbanned . '>' . $user['username'] . '</span></a>';
	$cell[] = $user['ipaddress'];
	$cell[] = $user['posts'];
	$cell[] = $user['joindatedate'] . ' ' . $user['joindatetime'];
	$cell[] = $user['lastvisitdate'] . ' ' . $user['lastvisittime'];
	$cell[] = $user['reason'];

	print_cells_row($cell, 0, $class = $bgcolor);

	$ip = $user['ipaddress'];
}

if ($vbulletin->GPC['limitstart'] == 0 AND $countusers['users'] > $vbulletin->GPC['limitnumber'])
{
	construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
	construct_hidden_code('lastalt', $ip);
	construct_hidden_code('lastip', $bgcolor);
	print_submit_row($vbphrase['next_page'], 0, $colspan);
}
else if ($limitfinish < $countusers['users'])
{
	construct_hidden_code('limitstart', $vbulletin->GPC['limitstart'] + $vbulletin->GPC['limitnumber'] + 1);
	construct_hidden_code('lastalt', $ip);
	construct_hidden_code('lastip', $bgcolor);
	print_submit_row($vbphrase['next_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
}
else if ($vbulletin->GPC['limitstart'] > 0 AND $limitfinish >= $countusers['users'])
{
	construct_hidden_code('lastalt', $ip);
	construct_hidden_code('lastip', $bgcolor);
	print_submit_row($vbphrase['first_page'], 0, $colspan, $vbphrase['prev_page'], '', true);
}
else
{
	print_table_footer();
}

print_cp_footer();
?>