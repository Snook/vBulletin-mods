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
	// Attribution-Noncommercial-No Derivative Works 3.0 United States License.
	// To view a copy of this license, visit
	// http://creativecommons.org/licenses/by-nc-nd/3.0/us/ or send a letter to
	// Creative Commons, 171 Second Street, Suite 300,
	// San Francisco, California, 94105, USA.
	//
	// ########################### SVN info ###################################
	// $Id: functions_gviptracker.php 683 2007-08-22 22:42:32Z ghryphen $
	// $Rev: 683 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-08-22 15:42:32 -0700 (Wed, 22 Aug 2007) $

	error_reporting(E_ALL & ~E_NOTICE);
	if (!is_object($vbulletin->db))
	{
		exit;
	}

	if (!intval($vbulletin->options['externalcache']) OR $vbulletin->options['externalcache'] > 1440)
	{
		$externalcache = 60;
	}
	else
	{
		$externalcache = $vbulletin->options['externalcache'];
	}

	$cachetime = $externalcache * 60;

	$lastmodified = (!empty($log['0']['date']) ? $log['0']['date'] : TIMENOW);
	$expires = TIMENOW + $cachetime;

	$output = '';
	$headers = array();

	$headers[] = 'Cache-control: max-age=' . $expires;
	$headers[] = 'Expires: ' . gmdate("D, d M Y H:i:s", $expires) . ' GMT';
	$headers[] = 'Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastmodified) . ' GMT';
	$headers[] = 'ETag: "' . $cachehash . '"';
	$headers[] = 'Content-Type: text/xml' . ($stylevar['charset'] != '' ? '; charset=' .  $stylevar['charset'] : '');

	$output = '<?xml version="1.0" encoding="' . $stylevar['charset'] . '"?>' . "\r\n\r\n";

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	$rsstag = array(
		'version'       => '2.0',
		'xmlns:dc'      => 'http://purl.org/dc/elements/1.1/',
		'xmlns:content' => 'http://purl.org/rss/1.0/modules/content/'
	);
	$xml->add_group('rss', $rsstag);
		$xml->add_group('channel');
			$xml->add_tag('title', unhtmlspecialchars($vbulletin->options['bbtitle'] . " - " . $vbphrase['gvipt_title']));
			$xml->add_tag('link', $vbulletin->options['bburl']. "/gviptracker.php?vipid=" . $select_vipid . "&vipfid=" . $select_vipfid, array(), false, true);
			$xml->add_tag('description', $vbulletin->options['bbtitle'] . " - " . $vbphrase['gvipt_title']);
			$xml->add_tag('language', $stylevar['languagecode']);
			$xml->add_tag('lastBuildDate', gmdate('D, d M Y H:i:s') . ' GMT');
			$xml->add_tag('generator', 'vBulletin');
			$xml->add_tag('ttl', $externalcache);

	// list returned items
	while ($thread = $db->fetch_array($res))
	{
		require_once(DIR . '/includes/class_postbit.php');
		require_once(DIR . '/includes/class_bbcode_alt.php');
		$bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());
		$message = $bbcode_parser->parse(unhtmlspecialchars($thread['preview']), $thread['forumid'], false);
		unset($bbcode_parser);

		$plaintext_parser =& new vB_BbCodeParser_PlainText($vbulletin, fetch_tag_list());
		$plainmessage = $plaintext_parser->parse($thread['preview'], $thread['forumid']);
		unset($plaintext_parser);

		$xml->add_group('item');
			$xml->add_tag('title', unhtmlspecialchars($thread['title']));
			$xml->add_tag('link', $vbulletin->options['bburl'] . "/showthread.php?postid=" . $thread['postid'] . "#" . $thread[postid], array(), false, true);
			$xml->add_tag('pubDate', gmdate('D, d M Y H:i:s', $thread['dateline']) . ' GMT');
			$xml->add_tag('description', fetch_trimmed_title($plainmessage, $vbulletin->options['threadpreview']));
			$xml->add_tag('content:encoded', $message);

		$xml->add_tag('category', unhtmlspecialchars($vbulletin->forumcache["$thread[forumid]"]['title_clean']), array('domain' => $vbulletin->options['bburl'] . "/forumdisplay.php?f=$thread[forumid]"));
		$xml->add_tag('dc:creator', unhtmlspecialchars($thread['username']));
		$xml->add_tag('guid', $vbulletin->options['bburl'] . "/showthread.php?postid=" . $thread['postid'] . "#" . $thread[postid], array('isPermaLink' => 'true'));

		$xml->close_group('item');
 	}

	$xml->close_group('channel');
	$xml->close_group('rss');
	$output .= $xml->output();
	unset($xml);


	foreach ($headers AS $header)
	{
		header($header);
	}

	echo $output;
?>