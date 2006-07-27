<?php
  // ########################### SVN info ###################################
	// $Id: news.php 946 2008-02-20 21:43:57Z ghryphen $
	// $Rev: 946 $
	// $LastChangedBy: ghryphen $
	// $Date: 2008-02-20 13:43:57 -0800 (Wed, 20 Feb 2008) $

  // ######################## SET PHP ENVIRONMENT ###########################
  error_reporting(E_ALL & ~E_NOTICE);

  // ##################### DEFINE IMPORTANT CONSTANTS #######################
  // change the line below to the actual filename without ".php" extention.
  // the reason for using actual filename without extention as a value of this constant is to ensure uniqueness of the value throughout every PHP file of any given vBulletin installation.

  define('THIS_SCRIPT', 'gSiteNews');

  // #################### PRE-CACHE TEMPLATES AND DATA ######################
  // get special phrase groups
  $phrasegroups = array();

  // get special data templates from the datastore
  $specialtemplates = array();

  // pre-cache templates used by all actions
  $globaltemplates = array(
    // change the lines below to the list of actual templates used in the script
	'navbar',
    'bbcode_code',
    'gsite_main',
    'gsite_news_bit',
  );

  // pre-cache templates used by specific actions
  $actiontemplates = array();

  // ########################## REQUIRE BACK-END ############################
  require_once('./global.php');
  require_once('./includes/class_bbcode.php');
  require_once('./includes/inc.tsdisplay.php');
  $tsdisplayout = $teamspeakDisplay->displayTeamspeak("69.57.142.11", "8767", "51234");

	function grealmstatus_parse($url)
	{
		ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)');

		$xml = @file_get_contents($url);

		if ($xml != false)
		{
			require_once(DIR . '/includes/class_xml.php');

			$xmlobj = new vB_XML_Parser($xml);

			return $xmlobj->parse();
		}
		else
		{
			return false;
		}
	}

  // ########################################################################
  // ######################### START MAIN SCRIPT ############################
  // ########################################################################

	$navbits = array();
	$navbits[$parent] = $vbulletin->options['gsitenewsname'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	//Xfire data
	($hook = vBulletinHook::fetch_hook('gxs_whos_online')) ? eval($hook) : false;

	//Xbox data
	($hook = vBulletinHook::fetch_hook('gxbls_whos_online')) ? eval($hook) : false;

	$wowalert = @file_get_contents("http://launcher.worldofwarcraft.com/alert");
	$wowalert = trim($wowalert);

	$strip = array(
		"<html>",
		"</html>",
		"<HTML>",
		"</HTML>",
		"<body>",
		"</body>",
		"<BODY>",
		"</BODY>",
		"<p>",
		"</p>",
		"<P>",
		"</P>",
		"<br/>",
		"<BR/>",
	);

	$wowalert = nl2br(str_replace($strip, "", $wowalert));

	$evestatus_array = grealmstatus_parse("http://www.eve.is/motd.asp?s=xml");

	$everealm['m'] = nl2br(trim($evestatus_array['item']['0']['description']));

	$handle = @fsockopen( "87.237.38.200", 26000, $errno, $errstr, 2 );
	if (!$handle)
	{
		$everealm['n'] = 'Tranquility';
		$everealm['s'] = 'down';
		$everealm['u'] = '0';
	}
	else
	{
		$buffer = fgets($handle, 35);
		fclose($handle);
		$everealm['n'] = 'Tranquility';
		$everealm['s'] = 'up';
		$everealm['u'] = number_format(ord($buffer[20]) + ord($buffer[21]) * 256);
	}

	eval('$eveserverstatus .= "' . fetch_template('gsite_evestatus_bit') . '";');

	if ($vbulletin->options['gwr_guild'])
	{
		$guild_array = explode("\n", $vbulletin->options['gwr_guild']);

		foreach ($guild_array as $item)
		{
			list($guild['locale'], $guild['realm'], $guild['name']) = explode("|", trim($item));

			$highlight1[] = $guild['realm'];
			$highlight2[] = '<span class="highlight">' . $guild['realm'] . '</span>';
		}

		$wowalert = str_replace($highlight1, $highlight2, $wowalert);
	}

	if ($highlight1)
	{
		$status_array = grealmstatus_parse("http://www.worldofwarcraft.com/realmstatus/status.xml");

		if (is_array($status_array))
		{

			$realmtype['0'] = 'RPPVP';
			$realmtype['1'] = 'Normal';
			$realmtype['2'] = 'PVP';
			$realmtype['3'] = 'RP';

			$realmload['0'] = '-';
			$realmload['1'] = 'Low';
			$realmload['2'] = 'Medium';
			$realmload['3'] = 'High';
			$realmload['4'] = 'Max (Queued)';

			foreach ($status_array['r'] as $realm)
			{
				if (in_array(trim($realm['n']), $highlight1))
				{
					if ($realm['s'] == '1')
					{
						$realm['s'] = 'up';
					}
					else
					{
						$realm['s'] = 'down';
					}
					$realm['t'] = $realmtype[$realm['t']];
					$realm['l']	= $realmload[$realm['l']];

					eval('$realmstatus .= "' . fetch_template('gsite_realmstatus_bit') . '";');
				}
			}

		}
	}

	$newsid = $vbulletin->input->clean_gpc('r', 'newsid', TYPE_INT);

	if($newsid != '0')
	{
		$get_news = " AND thread.threadid = '" . $newsid . "' ";
	}

	$vba_options['portal_news_showsubscribed'] = 1;
	$vba_options['portal_news_showattachments'] = 1;

	$res = $db->query("
			SELECT
			thread.threadid, thread.title, replycount, postusername, postuserid, thread.dateline AS postdateline, thread.lastposter, thread.lastpost, IF(views<=replycount, replycount+1, views) AS views, forumid, post.postid, pagetext, allowsmilie
  		" . iif ($vba_options['portal_news_showsubscribed'] AND $bbuserinfo['userid'] , ', NOT ISNULL(subscribethread.subscribethreadid) AS subscribed ') . "
  		" . iif ($vba_options['portal_news_showattachments'], ', attachment.filename, attachment.filesize, attachment.visible, attachmentid, counter, thumbnail, LENGTH(thumbnail) AS thumbnailsize') . "
			FROM " . TABLE_PREFIX . "thread AS thread
			LEFT JOIN " . TABLE_PREFIX . "post AS post ON (post.postid = thread.firstpostid)
  		" . iif ($vba_options['portal_news_showattachments'] , 'LEFT JOIN ' . TABLE_PREFIX . 'attachment AS attachment ON (post.postid = attachment.postid)') . "
  		" . iif ($vba_options['portal_news_showsubscribed'] AND $bbuserinfo['userid'] , ' LEFT JOIN ' . TABLE_PREFIX . 'subscribethread AS subscribethread ON (subscribethread.threadid = thread.threadid AND subscribethread.userid = \'' . $bbuserinfo['userid'] . '\')') . "
			WHERE forumid IN(0," . $vbulletin->options['gsitenewsforum'] . ") AND thread.visible = 1 AND thread.open != 10
			" . $get_news . "
			GROUP BY post.postid
			ORDER BY postdateline DESC
			LIMIT 0, " . $vbulletin->options['gsitenewsnum']);

  $bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());

  while ($news = $db->fetch_array ($res))
  {
		$news["date"] = vbdate($vbulletin->options['dateformat'], $news['dateline'], 1);
		$news["time"] = vbdate($vbulletin->options['timeformat'], $news['dateline']);
		$news["news"] = $bbcode_parser->parse(unhtmlspecialchars($news['pagetext']), $vbulletin->options['gsitenewsforum']);

		eval('$content .= "' . fetch_template('gsite_news_bit') . '";');
  }

	eval('$gsite_nav = "' . fetch_template('gsite_nav') . '";');

  eval ('print_output("' . fetch_template('gsite_main') . '");');
?>
