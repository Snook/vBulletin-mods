<?php
  // ########################################################################
  //
  //  CyTSx, Copyright © 2006, Ghryphen (github.com/ghryphen)
  //
  // ########################### SVN info ###################################
	// $Id: class_cytsx.php 300 2006-08-25 19:59:31Z ghryphen $
	// $Rev: 300 $
	// $LastChangedBy: ghryphen $
	// $Date: 2006-08-25 12:59:31 -0700 (Fri, 25 Aug 2006) $

class cytsx extends cyts {
/**
  * sadmin_UserChangeSA: Changes the Server Admin status of a user<br />
  *       Note: This function requires a login with a server admin account
  *
  * @author     Ghryphen
  * @access		public
  * @param		integer	$uID	UserID
  * @param		boolean	$uSA	Server admin status (1 - True, 0 - False)
  * @return     boolean success
  */
	function sadmin_UserChangeSA($uID, $uSA = false) {
		$uSA = ($uSA) ? 1 : 0;

		// Attempt to change their status if they are online.
		$uData = $this->admin_getDBUserData($uID);
		$pId = $this->info_getPlayerByLoginName($uData[5]);

		if ($pId != -1) {
			$this->sadmin_sppriv($pId, "privilege_serveradmin", $uSA);
		} else {
			// Just make the Database Update
			$this->admin_dbUserChangeSA($uID, $uSA);
		}
	}

/**
  * info_getPlayerByLoginName: Returns the PlayerID of the player with the loginname $pLoginName or -1 if no user $pLoginName is online
  *
  * @author     Ghryphen
  * @access		public
  * @param      string	$pLoginName	The Player login name
  * @return     integer PlayerID, -1 at failure
  */
	function info_getPlayerByLoginName($pLoginName) {
		if (!$uList = $this->info_playerList()) return -1;
		foreach ($uList as $player) {
			if ($player[16] == $pLoginName) return $player[1];
		}
		return -1;
	}

}
?>