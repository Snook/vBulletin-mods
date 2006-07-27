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
	// $Id: geditorial_postbit.php 784 2007-12-20 21:45:26Z ghryphen $
	// $Rev: 784 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-12-20 13:45:26 -0800 (Thu, 20 Dec 2007) $

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'geditorialpostbit');

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_bigthree.php');
require_once(DIR . '/includes/class_postbit.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// #######################################################################
// ############################# SHOW POST ###############################
// #######################################################################

if (!$postinfo['postid'])
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
}

if ((!$postinfo['visible'] OR $postinfo ['isdeleted']) AND !can_moderate($threadinfo['forumid']))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
}

if ((!$threadinfo['visible'] OR $threadinfo['isdeleted']) AND !can_moderate($threadinfo['forumid']))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}

$forumperms = fetch_permissions($threadinfo['forumid']);
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']))
{
	print_no_permission();
}
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) AND ($threadinfo['postuserid'] != $vbulletin->userinfo['userid'] OR $vbulletin->userinfo['userid'] == 0))
{
	print_no_permission();
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

$hook_query_fields = $hook_query_joins = '';

$post = $db->query_first_slave("
	SELECT
		post.*, post.username AS postusername, post.ipaddress AS ip, IF(post.visible = 2, 1, 0) AS isdeleted,
		user.*, userfield.*, usertextfield.*,
		" . iif($foruminfo['allowicons'], 'icon.title as icontitle, icon.iconpath,') . "
		IF(displaygroupid=0, user.usergroupid, displaygroupid) AS displaygroupid, infractiongroupid
		" . iif($vbulletin->options['avatarenabled'], ',avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline,customavatar.width AS avwidth,customavatar.height AS avheight,') . "
		" . ((can_moderate($threadinfo['forumid'], 'canmoderateposts') OR can_moderate($threadinfo['forumid'], 'candeleteposts')) ? 'spamlog.postid AS spamlog_postid,' : '') . "
		editlog.userid AS edit_userid, editlog.username AS edit_username, editlog.dateline AS edit_dateline, editlog.reason AS edit_reason, editlog.hashistory,
		postparsed.pagetext_html, postparsed.hasimages,
		sigparsed.signatureparsed, sigparsed.hasimages AS sighasimages,
		sigpic.userid AS sigpic, sigpic.dateline AS sigpicdateline, sigpic.width AS sigpicwidth, sigpic.height AS sigpicheight
		" . iif(!($permissions['genericpermissions'] & $vbulletin->bf_ugp_genericpermissions['canseehiddencustomfields']), $vbulletin->profilefield['hidden']) . "
		$hook_query_fields
	FROM " . TABLE_PREFIX . "post AS post
	LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = post.userid)
	LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield ON(userfield.userid = user.userid)
	LEFT JOIN " . TABLE_PREFIX . "usertextfield AS usertextfield ON(usertextfield.userid = user.userid)
	" . iif($foruminfo['allowicons'], "LEFT JOIN " . TABLE_PREFIX . "icon AS icon ON(icon.iconid = post.iconid)") . "
	" . iif($vbulletin->options['avatarenabled'], "LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON(avatar.avatarid = user.avatarid) LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON(customavatar.userid = user.userid)") . "
	" . ((can_moderate($threadinfo['forumid'], 'canmoderateposts') OR can_moderate($threadinfo['forumid'], 'candeleteposts')) ? "LEFT JOIN " . TABLE_PREFIX . "spamlog AS spamlog ON(spamlog.postid = post.postid)" : '') . "
	LEFT JOIN " . TABLE_PREFIX . "editlog AS editlog ON(editlog.postid = post.postid)
	LEFT JOIN " . TABLE_PREFIX . "postparsed AS postparsed ON(postparsed.postid = post.postid AND postparsed.styleid = " . intval(STYLEID) . " AND postparsed.languageid = " . intval(LANGUAGEID) . ")
	LEFT JOIN " . TABLE_PREFIX . "sigparsed AS sigparsed ON(sigparsed.userid = user.userid AND sigparsed.styleid = " . intval(STYLEID) . " AND sigparsed.languageid = " . intval(LANGUAGEID) . ")
	LEFT JOIN " . TABLE_PREFIX . "sigpic AS sigpic ON(sigpic.userid = post.userid)
	$hook_query_joins
	WHERE post.postid = $postid
");

// Tachy goes to coventry
if (in_coventry($threadinfo['postuserid']) AND !can_moderate($threadinfo['forumid']))
{
	// do not show post if part of a thread from a user in Coventry and bbuser is not mod
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}
if (in_coventry($post['userid']) AND !can_moderate($threadinfo['forumid']))
{
	// do not show post if posted by a user in Coventry and bbuser is not mod
	eval(standard_error(fetch_error('invalidid', $vbphrase['post'], $vbulletin->options['contactuslink'])));
}

// check for attachments
if ($post['attach'])
{
	$attachments = $db->query_read_slave("
		SELECT dateline, thumbnail_dateline, filename, filesize, visible, attachmentid, counter,
			postid, IF(thumbnail_filesize > 0, 1, 0) AS hasthumbnail, thumbnail_filesize,
			attachmenttype.thumbnail AS build_thumbnail, attachmenttype.newwindow
		FROM " . TABLE_PREFIX . "attachment
		LEFT JOIN " . TABLE_PREFIX . "attachmenttype AS attachmenttype USING (extension)
		WHERE postid = $postid
		ORDER BY attachmentid
	");
	while ($attachment = $db->fetch_array($attachments))
	{
		if (!$attachment['build_thumbnail'])
		{
			$attachment['hasthumbnail'] = false;
		}
		$post['attachments']["$attachment[attachmentid]"] = $attachment;
	}
}

if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['cangetattachment']))
{
	$vbulletin->options['viewattachedimages'] = 0;
	$vbulletin->options['attachthumbs'] = 0;
}

$show['inlinemod'] = false;

$show['lightbox'] = ($vbulletin->options['lightboxenabled'] AND $vbulletin->options['usepopups']);

$saveparsed = ''; // inialise

$show['spacer'] = false;

$postbit_factory =& new vB_Postbit_Factory();
$postbit_factory->registry =& $vbulletin;
$postbit_factory->forum =& $foruminfo;
$postbit_factory->thread =& $threadinfo;
$postbit_factory->cache = array();
$postbit_factory->bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());

$postbit_obj =& $postbit_factory->fetch_postbit('geditorial');
$postbit_obj->cachable = (!$post['pagetext_html'] AND $vbulletin->options['cachemaxage'] > 0 AND (TIMENOW - ($vbulletin->options['cachemaxage'] * 60 * 60 * 24)) <= $threadinfo['lastpost']);

$postbits = $postbit_obj->construct_postbit($post);

// save post to cache if relevant
if ($postbit_obj->cachable)
{
	/*insert query*/
	$db->shutdown_query("
		REPLACE INTO " . TABLE_PREFIX . "postparsed (postid, dateline, hasimages, pagetext_html, styleid, languageid)
		VALUES (
			$post[postid], " .
			intval($threadinfo['lastpost']) . ", " .
			intval($postbit_obj->post_cache['has_images']) . ", '" .
			$db->escape_string($postbit_obj->post_cache['text']) . "', " .
			intval(STYLEID) . ", " .
			intval(LANGUAGEID) . "
			)
	");
}

// *********************************************************************************
// update views counter
if ($vbulletin->options['threadviewslive'])
{
	// doing it as they happen; for optimization purposes, this cannot use a DM!
	$db->shutdown_query("
		UPDATE " . TABLE_PREFIX . "thread
		SET views = views + 1
		WHERE threadid = " . intval($threadinfo['threadid'])
	);
}
else
{
	// or doing it once an hour
	$db->shutdown_query("
		INSERT INTO " . TABLE_PREFIX . "threadviews (threadid)
		VALUES (" . intval($threadinfo['threadid']) . ')'
	);
}

//eval('print_output("' . fetch_template('ged_showpost') . '");');
?>