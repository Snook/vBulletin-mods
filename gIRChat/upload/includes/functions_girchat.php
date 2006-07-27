<?php
	// ########################################################################
	//
	// gIRChat, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gIRChat, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: functions_girchat.php 991 2008-06-26 21:18:25Z ghryphen $
	// $Rev: 991 $
	// $LastChangedBy: ghryphen $
	// $Date: 2008-06-26 14:18:25 -0700 (Thu, 26 Jun 2008) $

	function mircize($data)
	{
		$color["color:0;"] = "color:white;";
		$color["color:1;"] = "color:black;";
		$color["color:2;"] = "color:navy;";
		$color["color:3;"] = "color:green;";
		$color["color:4;"] = "color:red;";
		$color["color:5;"] = "color:maroon;";
		$color["color:6;"] = "color:purple;";
		$color["color:7;"] = "color:orange;";
		$color["color:8;"] = "color:yellow;";
		$color["color:9;"] = "color:lime;";
		$color["color:10;"]= "color:teal;";
		$color["color:11;"]= "color:aqua;";
		$color["color:12;"]= "color:blue;";
		$color["color:13;"]= "color:fuchsia;";
		$color["color:14;"]= "color:gray;";
		$color["color:15;"]= "color:silver;";
		$ctrl->etx = chr(03);
		$ctrl->si = chr(017);

		//$data = htmlspecialchars($data);
		$data = str_replace("  "," &nbsp;",$data);

		$data = ereg_replace("$ctrl->etx([[:digit:]]{1,2}),([[:digit:]]{1,2})",
			"</span><span style=\"color:\\1;background-color:\\2;\">",
			$data);
		$data = ereg_replace("$ctrl->etx([[:digit:]]{1,2})",
			"</span><span style=\"color:\\1;\">",
			$data);
		$data = str_replace($ctrl->etx,
			"</span>",
			$data);
		$data = str_replace($ctrl->si,
			"</span>",
			$data);
		$data = strtr($data,$color);

		return $data;
	}
?>