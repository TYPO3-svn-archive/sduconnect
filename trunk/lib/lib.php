<?php
/*
 ======================================================================
 lastRSS 0.9.1
 
 Simple yet powerfull PHP class to parse RSS files.
 
 by Vojtech Semecky, webmaster @ webdot . cz
 
 Latest version, features, manual and examples:
 	http://lastrss.webdot.cz/

 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ======================================================================
*/

/**
* lastRSS
* Simple yet powerfull PHP class to parse RSS files.
*/
class sduconnectLibraries {
	
	static public function object2array($aObj) {
		
		$Ret = Array();
		
		if( is_array( $aObj ) )
		{
			foreach( $aObj as $key => $value )
			{
				$Ret[$key] = self::object2array( $value );
			}
		}
		else
		{
			$tmpVar = get_object_vars( $aObj );
			
			if( $tmpVar )
			{
				foreach( $tmpVar as $key => $value)
				{
					$Ret[$key] = self::object2array( $value );
				}
			}
			else
			{
				return $aObj;
			}
		}
		
		return $Ret;
	}
	
	static public function array2object($aArray){
		if (!is_array($aArray)) {
			return $aArray;
		}
		
		foreach ($aArray as $key => $value) {
			$aArray[$key] = self::array2object($value);
		}
		return (object)$aArray;		
	}
		
	public static function loadSettings($aSettingsName){
		$query = 'SELECT * FROM `tx_sduconnect_accountsettings` WHERE `settingsName` = "'.$aSettingsName.'"';
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		$mySettings = false;
		if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
			$mySettings = unserialize($row['settings']);
		}
		return $mySettings;
		
		//$this->setAccountId($this->settings->accountId);
		//$this->setCollectionId($this->settings->collectionId);
	}
	
	public static function saveSettings($aSettingsName,$aSettings){
		$dbArr['settings'] = serialize($aSettings);
		$dbArr['settingsName'] = $aSettingsName;
		
		$query = 'SELECT count(`uid`) AS `total` FROM `tx_sduconnect_accountsettings` WHERE `settingsName` = "'.$aSettingsName.'"';
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		$totals = 0;
		
		if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
			$totals = $row['total'];
		}
		
		if($totals==1){
			$query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_sduconnect_accountsettings','`settingsName` ="'.$aSettingsName.'" ',$dbArr);
		}
		else {
			$query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_sduconnect_accountsettings', $dbArr);
		}
		$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
	}
	
	public static function debug_printLine($aLine){
		echo $aLine .'<br />';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sduconnect/lib/lib.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sduconnect/lib/lib.php']);
}
?>