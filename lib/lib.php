<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ben van Kruistum <ben@ooip.nl>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
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