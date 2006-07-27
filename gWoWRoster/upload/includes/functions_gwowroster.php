<?php
	// ########################################################################
	//
	// gWoWRoster, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gWoWRoster, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: functions_gwowroster.php 1035 2008-09-22 20:20:57Z ghryphen $
	// $Rev: 1035 $
	// $LastChangedBy: ghryphen $
	// $Date: 2008-09-22 13:20:57 -0700 (Mon, 22 Sep 2008) $

// #############################################################################
/**
	* gwr_percent_members: Percent of total members
	*
  * @return  number
  */
	function gwr_percent_members($number, $members)
	{
		if($number > 0)
		{
			$number = ($number / $members) * 100;
		}

		return number_format($number, 2);
	}
?>