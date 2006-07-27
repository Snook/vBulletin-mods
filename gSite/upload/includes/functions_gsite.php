<?php
  // ########################################################################
  //
  //  gSite, Copyright © 2006, Ghryphen (github.com/ghryphen)
  //
  // ########################### SVN info ###################################
	// $Id: functions_gsite.php 733 2007-10-02 21:11:42Z ghryphen $
	// $Rev: 733 $
	// $LastChangedBy: ghryphen $
	// $Date: 2007-10-02 14:11:42 -0700 (Tue, 02 Oct 2007) $

   function gsite_rand_color($tint) {

    $frag = range(0,255);

    $red = "";
    $green = "";
    $blue = "";

    for (;;) {

      $red = $frag[mt_rand(0, count($frag)-1)];
      $green = $frag[mt_rand(0, count($frag)-1)];
      $blue = $frag[mt_rand(0, count($frag)-1)];

      switch ($tint) {
        case 'light':
          if (($red + $green + $blue / 3) >= 200) break 2;
        break;
        case 'dark' :
        default:
          if (($red + $green + $blue / 3) <= 50) break 2;
        break;
      }
    }
    return sprintf("#%02s%02s%02s", dechex($red), dechex($green),
    dechex($blue));
  }

  function gsite_color_choice($var) {
  	global $color_match_array;
  	if (!is_array($color_match_array)) {
  	  $color_match_array = array();
  	}

  	$text_color = gsite_rand_color($var);

    if (!in_array($text_color, $color_match_array)) {
    	$color_match_array[] = $text_color;
      return $text_color;
    } else {
    	gsite_color_choice($var);
    }
  }
?>