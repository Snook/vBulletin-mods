<?php
	// ########################################################################
	//
	// gArticle, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gArticle, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: garticle.php 683 2007-08-22 22:42:32Z ghryphen $
	// $Rev: 683 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-08-22 15:42:32 -0700 (Wed, 22 Aug 2007) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'garticle_main');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array();

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'garticle_main',
		'garticle_edit',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');
  require_once('./includes/class_bbcode.php');
  require_once('./includes/class_bbcode_alt.php');
  require_once('./includes/diff.class.php');

	if (empty($_REQUEST['do']))
	{
		$_REQUEST['do'] = 'showpage';
	}

	// #################### HARD CODE JAVASCRIPT PATHS ########################
	$headinclude = str_replace('clientscript', $vbulletin->options['bburl'] . '/clientscript', $headinclude);

	// ######################### CLEAN GPC ############################
	$vbulletin->input->clean_array_gpc('r', array(
		'page'            => TYPE_STR,
		'diff'            => TYPE_INT,
		'oldid'           => TYPE_INT,
	));

	$vbulletin->input->clean_array_gpc('p', array(
		'oldid'           => TYPE_INT,
		'title'           => TYPE_STR,
		'content'         => TYPE_STR,
		'contentmd5'      => TYPE_STR,
		'summary'         => TYPE_STR,
	));

	if($_POST['do'] == 'editpage' && trim($vbulletin->GPC['content']) != '')
	{
		$insert_contentmd5 = md5(trim(addslashes($vbulletin->GPC['content'])));

		if($insert_contentmd5 != $vbulletin->GPC['contentmd5'])
		{
			$includes = preg_match_all("/\{\{([^\/]+)\}\}/U", $vbulletin->GPC['content'], $matches, PREG_PATTERN_ORDER);

			foreach($matches['1'] AS $match)
			{
				$included .= $match . ',';
			}

			$category = preg_match_all("/\[\[Category:([^\/]+)\]\]/U", $vbulletin->GPC['content'], $matches, PREG_PATTERN_ORDER);

			foreach($matches['1'] AS $match)
			{
				$categorys .= $match . ',';
			}

			$db->query_write("INSERT INTO " . TABLE_PREFIX . "garticle SET
				oldid = '" . $vbulletin->GPC['oldid'] . "',
				title = '" . pagetitle_query($vbulletin->GPC['title']) . "',
				content = '" . trim(addslashes($vbulletin->GPC['content'])) . "',
				contentmd5 = '" . $insert_contentmd5 . "',
				summary = '" . addslashes($vbulletin->GPC['summary']) . "',
				dateline = '" . TIMENOW . "',
				includes = '" . substr($included, 0, -1) . "',
				category = '" . substr($categorys, 0, -1) . "',
				userid='" . $vbulletin->userinfo['userid'] . "'
			");

		}

		exec_header_redirect('garticle.php?page=' . pagetitle_link($vbulletin->GPC['title']));
	}

	if(!preg_match("/Talk:/i", $vbulletin->GPC['page']))
	{
		$show['talk'] = 1;
	}

	$PAGE_TITLE = pagetitle_display($vbulletin->GPC['page']);
	$PAGE_TITLEE = pagetitle_link($vbulletin->GPC['page']);

	if($PAGE_TITLE == '' && $_REQUEST['do'] != 'recent')
	{
		exec_header_redirect('garticle.php?page=Main+Page');
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['garticle_title'] . ' - ' . $PAGE_TITLE;

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gArticle', $copyrightyear);

	// ########################################################################
	// ######################### START MAIN SCRIPT ############################
	// ########################################################################

	if($_REQUEST['do'] == 'showpage')
	{
		$article = articlecontent($PAGE_TITLE);

		if(!$article)
		{
			$article['content'] = 'No Content. Be the first to <a href="garticle.php?page=' . $PAGE_TITLEE . '&do=edit">Edit</a> this page.';
		}
	}

	if($_REQUEST['do'] == 'edit')
	{
		$article = articlecontent($PAGE_TITLE, true);
	}

	if($_REQUEST['do'] == 'history')
	{
		$res = $vbulletin->db->query("SELECT article.*, u.username
			FROM " . TABLE_PREFIX . "garticle AS article
			LEFT JOIN " . TABLE_PREFIX . "user u ON (article.userid = u.userid)
			WHERE title = '" . pagetitle_query($PAGE_TITLE) . "'
			ORDER BY dateline DESC
		");

		while($query = $db->fetch_array($res))
		{
			$article['date'] = vbdate($vbulletin->options['dateformat'], $query['dateline'], 1);
			$article['time'] = vbdate($vbulletin->options['timeformat'], $query['dateline']);

			$article['content'] .= $query['id'] . ' - <a href="member.php?u=' . $query['userid'] . '">' . $query['username'] . '</a>  - ' . $article['date'] . ' ' . $article['time'] . '<br />';
		}
	}

	if($_REQUEST['do'] == 'recent')
	{
		$res = $vbulletin->db->query("SELECT article.*, u.username
			FROM " . TABLE_PREFIX . "garticle AS article
			LEFT JOIN " . TABLE_PREFIX . "user u ON (article.userid = u.userid)
			ORDER BY dateline DESC
		");

		while($query = $db->fetch_array($res))
		{
			$article['date'] = vbdate($vbulletin->options['dateformat'], $query['dateline'], 1);
			$article['time'] = vbdate($vbulletin->options['timeformat'], $query['dateline']);

			$article['content'] .= '
				<a href="http://www.gamecyclone.com/garticle.php?page=' . urlencode($query['title']) . '&do=diff&diff='. $query['id'] . '&oldid=' . $query['oldid'] . '">diff</a>|<a href="http://www.gamecyclone.com/garticle.php?page=' . urlencode($query['title']) . '&do=history">hist</a> -
				<a href="garticle.php?page=' . urlencode($query['title']) . '">' . $query['title'] . '</a> -
				' . $article['date'] . ' ' . $article['time'] . ' -
				<a href="member.php?u=' . $query['userid'] . '">' . $query['username'] . '</a>
				<br />';
		}
	}

	if($_REQUEST['do'] == 'diff')
	{
		$res = $vbulletin->db->query("SELECT article.*, u.username
			FROM " . TABLE_PREFIX . "garticle AS article
			LEFT JOIN " . TABLE_PREFIX . "user u ON (article.userid = u.userid)
			WHERE title = '" . pagetitle_query($PAGE_TITLE) . "'
			AND article.id = '" . $vbulletin->GPC['oldid'] . "'
			ORDER BY dateline DESC
		");

		$query1 = $db->fetch_array($res);

		$res = $vbulletin->db->query("SELECT article.*, u.username
			FROM " . TABLE_PREFIX . "garticle AS article
			LEFT JOIN " . TABLE_PREFIX . "user u ON (article.userid = u.userid)
			WHERE title = '" . pagetitle_query($PAGE_TITLE) . "'
			AND article.id = '" . $vbulletin->GPC['diff'] . "'
			ORDER BY dateline DESC
		");

		$query2 = $db->fetch_array($res);

		$a1 = explode("\n", $query1['content']);
		$a2 = explode("\n", $query2['content']);

		$diff = new Diff($a1, $a2, 1);

		$article['content'] =  '<table width="100%" cellpadding="0" cellspacing="0" class="diff">';
		$article['content'] .= $diff->output;
		$article['content'] .= '</table>';

	}



	eval('print_output("' . fetch_template('garticle_main') . '");');

	// ######################### START FUNCTIONS ############################

	function articlecontent($page, $edit = false)
	{
		global $vbulletin;

		$bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());

		$res = $vbulletin->db->query("SELECT article.*, u.username
			FROM " . TABLE_PREFIX . "garticle AS article
			LEFT JOIN " . TABLE_PREFIX . "user u ON (article.userid = u.userid)
			WHERE title = '" . pagetitle_query($page) . "'
			ORDER BY dateline DESC
		");

		if(mysql_num_rows($res) != '0')
		{
			$article = $vbulletin->db->fetch_array($res);

			if (!$edit)
			{
				$article['content'] = $bbcode_parser->parse(unhtmlspecialchars($article['content']));
				$article['content'] = articleparse($article['content']);
			}
			else
			{
				$article['content'] = page_editor($page, $article);
			}

			$article['date'] = vbdate($vbulletin->options['dateformat'], $article['dateline'], 1);
			$article['time'] = vbdate($vbulletin->options['timeformat'], $article['dateline']);
			$article['pagenamee'] = pagetitle_display($article['pagename']);

		}
		else
		{
			$article['content'] = page_editor($page, $article);
		}

		return $article;
	}

	function articleparse($content)
	{
		$content = preg_replace("/\[\[([^\/]+)\]\]/ie", "'<a href=\"garticle.php?page='.pagetitle_link('\\1').'\">'.'\\1'.'</a>'", $content);

		return $content;
	}

	function pagetitle_display($title)
	{
		$title = ucfirst(urldecode(html_entity_decode($title)));

		return trim($title);
	}

	function pagetitle_link($title)
	{
		$title = str_replace(" ", "+", pagetitle_display($title));
		$title = htmlspecialchars($title);

		return trim($title);
	}

	function pagetitle_query($title)
	{
		$title = addslashes(pagetitle_display($title));

		return trim($title);
	}

	function page_editor($page, $article)
	{
		return '<form action="garticle.php" method="post"><input type="hidden" name="do" value="editpage">
			<input type="hidden" name="oldid" value="' . $article['id'] . '">
			<input type="hidden" name="contentmd5" value="' . $article['contentmd5'] . '">
			<input type="hidden" name="title" value="' . $page . '">
			<textarea name="content" cols="83" rows="30" style="width: 100%;">' . htmlspecialchars($article['content']) . '</textarea><br />
			Summary:<br />
			<input type="text" name="summary" style="width: 100%;"><br /><br />
			<input type="submit" name="submit" value="Save Page" style="width: 50%;"><input type="submit" name="submit" value="Show Preview" style="width: 50%;" disabled></form>';
	}
?>