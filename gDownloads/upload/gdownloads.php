<?php
	// ########################################################################
	//
	// gDownloads, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gDownloads, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: gdownloads.php 733 2007-10-02 21:11:42Z ghryphen $
	// $Rev: 733 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-10-02 14:11:42 -0700 (Tue, 02 Oct 2007) $

	// ######################## SET PHP ENVIRONMENT ###########################
	error_reporting(E_ALL & ~E_NOTICE);

	// ##################### DEFINE IMPORTANT CONSTANTS #######################
	define('THIS_SCRIPT', 'gdownloads');

	// #################### PRE-CACHE TEMPLATES AND DATA ######################
	$phrasegroups = array();

	$specialtemplates = array();

	$globaltemplates = array(
		'navbar',
		'GDL',
		'gdl_bit',
	);

	$actiontemplates = array();

	// ########################## REQUIRE BACK-END ############################
	require_once('./global.php');

	// ######################### PERMS ############################
	if (!is_member_of($vbulletin->userinfo, explode(',',$vbulletin->options['gdl_usergroup'])))
	{
		print_no_permission();
		exit;
	}

	// ######################### NAVBAR ############################
	$navbits = array();
	$navbits[$parent] = $vbphrase['gdl_title'];

	$navbits = construct_navbits($navbits);
	eval('$navbar = "' . fetch_template('navbar') . '";');

	// ######################### SETUP ############################
	$vbphrase['gllc_x_copy_x'] = construct_phrase('%s, Copyright &copy; 2006 - %s, <a href="https://github.com/ghryphen" target="_blank">Ghryphen</a>', 'gDownloads', $copyrightyear);

	// ########################################################################
  function scan_Dir($dir)
  {
    $arrfiles = array();
    if (is_dir($dir)) {
      if ($handle = opendir($dir)) {
        chdir($dir);
        while (false !== ($file = readdir($handle))) {
          if ($file != "." && $file != ".." && substr($file, -4) != ".php") {
            if (is_dir($file)) {
              $arr = scan_Dir($file);
              foreach ($arr as $value) {
                $arrfiles[] = $dir."/".$value;
              }
            } else {
              $arrfiles[] = $dir."/".$file;
            }
          }
        }
        chdir("../");
      }
      closedir($handle);
    }
    sort($arrfiles);
    return $arrfiles;
  }

  function _filesize($size) {
    if($size >= 1073741824) {
      $retval = round($size/1073741824,1);
      if(strlen($retval) > 3) $retval = round($size/1073741824);
      return $retval.'G';
    } elseif($size >= 1048576) {
      $retval = round($size/1048576,1);
      if(strlen($retval) > 3) $retval = round($size/1048576);
      return $retval.'M';
    } elseif($size >= 1024) {
      $retval = round($size/1024,1);
      if(strlen($retval) > 3) $retval = round($size/1024);
      return $retval.'k';
    } else
      return $size;
  }
	// ########################################################################

  $dl = scan_Dir("download");

  if (!empty($dl)) {
    foreach ($dl as $key => $gdlfile) {
    	$file['link'] = $gdlfile;
    	$file['name'] = str_replace("download/", "", $gdlfile);
    	$file['size'] = _filesize(filesize($gdlfile));
			$file['date'] = vbdate($vbulletin->options['dateformat'], filemtime($gdlfile), 1);
			$file['time'] = vbdate($vbulletin->options['timeformat'], filemtime($gdlfile));

			eval('$gdl_bits .= "' . fetch_template('gdl_bit') . '";');
    }
  }

	eval('print_output("' . fetch_template('GDL') . '");');

?>