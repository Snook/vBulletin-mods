<?php
	// ########################################################################
	//
	// gWoWEvents, Copyright © 2006, Ghryphen (github.com/ghryphen)
	//
	// If you have fixes, improvements or other additions to make to
	// gWoWEvents, please contact me at ghryphen@gmail.com for collaboration.
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
	// $Id: functions_gwowevents.php 1076 2008-11-13 02:29:35Z ghryphen $
	// $Rev: 1076 $
	// $LastChangedBy: ghryphen $
	// $Date: 2008-11-12 18:29:35 -0800 (Wed, 12 Nov 2008) $

	// #############################################################################
/**
	*
	* Spec Phrase Arrays
	*/
	if ( $vbulletin->options['gwe_localization'] == "enGB" )
	{
		// Death Knight
		$gwephrase['spec']['6']['1'] = 'Blood';
		$gwephrase['spec']['6']['2'] = 'Frost';
		$gwephrase['spec']['6']['3'] = 'Unholy';
		// Druid
		$gwephrase['spec']['11']['1'] = 'Balance';
		$gwephrase['spec']['11']['2'] = 'Feral Combat';
		$gwephrase['spec']['11']['3'] = 'Restoration';
		// Hunter
		$gwephrase['spec']['3']['1'] = 'Beast Mastery';
		$gwephrase['spec']['3']['2'] = 'Marksmanship';
		$gwephrase['spec']['3']['3'] = 'Survival';
		// Mage
		$gwephrase['spec']['8']['1'] = 'Arcane';
		$gwephrase['spec']['8']['2'] = 'Fire';
		$gwephrase['spec']['8']['3'] = 'Frost';
		// Paladin
		$gwephrase['spec']['2']['1'] = 'Holy';
		$gwephrase['spec']['2']['2'] = 'Protection';
		$gwephrase['spec']['2']['3'] = 'Retribution';
		// Priest
		$gwephrase['spec']['5']['1'] = 'Discipline';
		$gwephrase['spec']['5']['2'] = 'Holy';
		$gwephrase['spec']['5']['3'] = 'Shadow';
		// Rogue
		$gwephrase['spec']['4']['1'] = 'Assassination';
		$gwephrase['spec']['4']['2'] = 'Combat';
		$gwephrase['spec']['4']['3'] = 'Subtlety';
		// Shamen
		$gwephrase['spec']['7']['1'] = 'Elemental';
		$gwephrase['spec']['7']['2'] = 'Enhancement';
		$gwephrase['spec']['7']['3'] = 'Restoration';
		// Warlock
		$gwephrase['spec']['9']['1'] = 'Affliction';
		$gwephrase['spec']['9']['2'] = 'Demonology';
		$gwephrase['spec']['9']['3'] = 'Destruction';
		// Warrior
		$gwephrase['spec']['1']['1'] = 'Arms';
		$gwephrase['spec']['1']['2'] = 'Fury';
		$gwephrase['spec']['1']['3'] = 'Protection';
	}
	
	if ( $vbulletin->options['gwe_localization'] == "deDE" )
	{
		// Death Knight
		$gwephrase['spec']['6']['2'] = 'Frost';
		// Druid
		$gwephrase['spec']['11']['1'] = 'Gleichgewicht';
		$gwephrase['spec']['11']['2'] = 'Wilder Kampf';
		$gwephrase['spec']['11']['3'] = 'Wiederherstellung';
		// Hunter
		$gwephrase['spec']['3']['1'] = 'Tierherrschaft';
		$gwephrase['spec']['3']['2'] = 'Treffsicherheit';
		$gwephrase['spec']['3']['3'] = 'Überleben';
		// Mage
		$gwephrase['spec']['8']['1'] = 'Arcan';
		$gwephrase['spec']['8']['2'] = 'Feuer';
		$gwephrase['spec']['8']['3'] = 'Frost';
		// Paladin
		$gwephrase['spec']['2']['1'] = 'Heilig';
		$gwephrase['spec']['2']['2'] = 'Schutz';
		$gwephrase['spec']['2']['3'] = 'Vergeltung';
		// Priest
		$gwephrase['spec']['5']['1'] = 'Disziplin';
		$gwephrase['spec']['5']['2'] = 'Heilig';
		$gwephrase['spec']['5']['3'] = 'Schatten';
		// Rogue
		$gwephrase['spec']['4']['1'] = 'Meucheln';
		$gwephrase['spec']['4']['2'] = 'Kampf';
		$gwephrase['spec']['4']['3'] = 'Täuschung';
		// Shamen
		$gwephrase['spec']['7']['1'] = 'Elementar';
		$gwephrase['spec']['7']['2'] = 'Verstärkung';
		$gwephrase['spec']['7']['3'] = 'Wiederherstellung';
		// Warlock
		$gwephrase['spec']['9']['1'] = 'Gebrechen';
		$gwephrase['spec']['9']['2'] = 'Dämonologie';
		$gwephrase['spec']['9']['3'] = 'Zerstörung';
		// Warrior
		$gwephrase['spec']['1']['1'] = 'Waffen';
		$gwephrase['spec']['1']['2'] = 'Furor';
		$gwephrase['spec']['1']['3'] = 'Schutz';
	}

	if ( $vbulletin->options['gwe_localization'] == "frFR" )
	{
		// Death Knight
		$gwephrase['spec']['6']['2'] = 'Givre';
		// Druid
		$gwephrase['spec']['11']['1'] = 'Equilibre';
		$gwephrase['spec']['11']['2'] = 'Combat sauvage';
		$gwephrase['spec']['11']['3'] = 'Restauration';
		// Hunter
		$gwephrase['spec']['3']['1'] = 'Maîtrise des bêtes';
		$gwephrase['spec']['3']['2'] = 'Précision';
		$gwephrase['spec']['3']['3'] = 'Survie';
		// Mage
		$gwephrase['spec']['8']['1'] = 'Arcanes';
		$gwephrase['spec']['8']['2'] = 'Feu';
		$gwephrase['spec']['8']['3'] = 'Givre';
		// Paladin
		$gwephrase['spec']['2']['1'] = 'Sacré';
		$gwephrase['spec']['2']['2'] = 'Protection';
		$gwephrase['spec']['2']['3'] = 'Vindicte';
		// Priest
		$gwephrase['spec']['5']['1'] = 'Discipline';
		$gwephrase['spec']['5']['2'] = 'Sacré';
		$gwephrase['spec']['5']['3'] = 'Ombre';
		// Rogue
		$gwephrase['spec']['4']['1'] = 'Assassinat';
		$gwephrase['spec']['4']['2'] = 'Combat';
		$gwephrase['spec']['4']['3'] = 'Finesse';
		// Shamen
		$gwephrase['spec']['7']['1'] = 'Élémentaire';
		$gwephrase['spec']['7']['2'] = 'Amélioration';
		$gwephrase['spec']['7']['3'] = 'Restauration';
		// Warlock
		$gwephrase['spec']['9']['1'] = 'Affliction';
		$gwephrase['spec']['9']['2'] = 'Démonologie';
		$gwephrase['spec']['9']['3'] = 'Destruction';
		// Warrior
		$gwephrase['spec']['1']['1'] = 'Armes';
		$gwephrase['spec']['1']['2'] = 'Fureur';
		$gwephrase['spec']['1']['3'] = 'Protection';
	}
?>