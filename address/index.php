<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 sduconnect <sduconnect>
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


// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile('EXT:sduconnect/address/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
// DEFAULT initialization of a module [END]
if (!defined(PATH_tslib)) {
	if (file_exists(PATH_site.'tslib/'."class.tslib_content.php")) {
		define('PATH_tslib', PATH_site.'tslib/');
	} else {
		define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
	}
}
require_once (PATH_tslib."class.tslib_content.php"); # for getting the cObj (needed for reading the template file
require_once (PATH_tslib."class.tslib_pibase.php");


/**
 * Module 'Sdu Connect' for the 'sduconnect' extension.
 *
 * @author	yasar <yasar@ooip.nl>
 * @package	TYPO3
 * @subpackage	tx_sduconnect
 */
class  tx_sduconnect_address extends t3lib_SCbase {
	var $pageinfo;
	var $isUpdating=false;
	var $settings;
	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		$this->cObj = t3lib_div::makeInstance("tslib_cObj");
		$this->pibase = t3lib_div::makeInstance("tslib_pibase");
		
		parent::init();
		
		
		/*
		if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
		}
		*/
	}
	
	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
				'function' => Array (
					'1' => $LANG->getLL('function1'),
					'2' => $LANG->getLL('function2'),							
					)
				);
		parent::menuConfig();
	}
	
	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		
		//$query = 'DROP TABLE `tx_sduconnect_organisation` ';
		//$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);	
		
		//$query = 'DROP TABLE `tx_sduconnect_accountsettings` ';
		//$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);	
		
		$this->pibase->pi_initPIflexform(); // Init and get the flexform data of the plugin
		$piFlexForm = $this->cObj->data['pi_flexform'];
		//print_r($TCA);
		
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
		$this->loadSettings();
		
		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			
			// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';
			
			// JavaScript
			$this->doc->JScode = '
					<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
					document.location = URL;
					}
					</script>
					';
			$this->doc->postCode='
					<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
					</script>
					';
			
			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);
			
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
			$this->content.=$this->doc->divider(5);
			
			// Render content:
			$this->moduleContent();
			
			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}
			
			$this->content.=$this->doc->spacer(10);
		} else {
			// If no access or if ID == zero
			
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			
			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}			
	function deleteCollections($aCollectionset){
		if($aCollectionset){
			$myCollections = explode(',',$aCollectionset);
			foreach($myCollections as $iVal){
				$iVal = (int) $iVal;
				if(update_sdu_organisations::truncate_Collection($iVal)){
					$this->content .='adres collection '.$iVal.' is verwijdert';
				}
				
			}
		}
		else {
			if(update_sdu_organisations::truncate_Collection(false)){
				$this->content .='Alle adres collecties zijn geleegt';
			}
		}
	}
	function printContent()	{
		
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	
	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		switch((string)$this->MOD_SETTINGS['function'])	{
			case 1:
				$content=$this->drawSettingsPage();
				//'GET:'.t3lib_div::view_array($_GET).'<br />'.
				//'POST:'.t3lib_div::view_array($_POST).'<br />'.
				'';
				$this->content .= $this->doc->section('SDUCONNECT ADRESSEN UPDATE:',$content,0,1);
				break;
			case 2:
				$content=$this->drawOverviewPage();
				$this->content .=$this->doc->section('Overzichten:',$content,0,1);
				break;		 				
		}
	}
	
	function drawOverviewPage(){
		$this->loadSettings();
		
		$query = "SELECT count( `organisation_id` ) AS `total` , `collectionId` FROM `tx_sduconnect_organisation` GROUP BY `collectionId`";
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		
		$content ='';
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
			$content .= 'Collectie ' . $row['collectionId'] . ' totaal: ' . $row['total'] . '<br />';
		}
		$content .= 'Laatste update van adressen : ' . date('d-m-Y H:i:s',$this->settings->lastUpdated) .' <br />';
		return $content;
		
	}
	
	function drawSettingsPage(){
		
		if(t3lib_div::_GP('action') &&  t3lib_div::_GP('action') == 'update') {
			$this->settings->accountId = t3lib_div::_GP('accountId');
			$this->settings->collectionId = t3lib_div::_GP('collectionId');
			
			$this->saveSettings();
			$this->loadSettings();
		}
		
		require_once('class.update_sdu_organisations.php');
		if(t3lib_div::_GP('deleteList') &&  t3lib_div::_GP('deleteList') == 'true') {
			$this->_update = new update_sdu_organisations();
			$this->deleteCollections(t3lib_div::_GP('deleteCollections'));
			
		}
		
		if(t3lib_div::_GP('updateList') &&  t3lib_div::_GP('updateList') == 'true') 
		{
			$this->isUpdating = true;
			$this->saveSettings();
			$this->content.=$this->updateCollections();
		}
		
		$accountIdNotice ='geen account id opgegeven!';
		$collectionIdNotice ='nog geen collectie opgegeven!';
		
		if($this->settings->accountId){
			$accountIdNotice = '';
		}
		if($this->settings->collectionId){	
			$collectionIdNotice = '';
		}		
		
		$content ='<form name="update" method="post" action="">
				<input type="hidden" name="action" value="update"/>
				<dl>
				<dt>Account id</dt><dd><input type="text" name="accountId" value="'.$this->settings->accountId.'">'.$accountIdNotice.'</dd>
				<dt>Collectie id (csv)</dt><dd><input type="text" name="collectionId" value="'.$this->settings->collectionId.'">'.$collectionIdNotice.'</dd>
				</dl>
				<div>
				<dl>
				<dt><label for="updateList">Lijsten updaten?</label></dt>
				<dd><input type="checkbox" value="true" name="updateList" id="updateList" /></dd>
				<dt><label for="deleteList">lijsten wissen?</label></dt>
				<dd><input type="checkbox" id="deleteList" value="true" name="deleteList" id="updateList" onchange="doConfirm(this);" /></dd>						
				<dt id="deleteCollectionsDt" style="display:none;">Wis collecties:</dt>
				<dd id="deleteCollectionsDd" style="display:none;"><input type="text" name="deleteCollections" /></dd>
				</dl>
				
				<br /><input type="submit" value="Update" onclick=""></div>								
				</form>
				<script>
				function doConfirm(aObj){
				if(confirm(\'Weet u zeker dat u de lijst wilt wissen?\')){
				aObj.checked = true;
				document.getElementById(\'deleteCollectionsDt\').style.display = "block";
				document.getElementById(\'deleteCollectionsDd\').style.display = "block";
				}
				else {
				aObj.checked = false						
				document.getElementById(\'deleteCollectionsDt\').style.display = "none";
				document.getElementById(\'deleteCollectionsDd\').style.display = "none";							
				}
				}
				</script>
				<hr />';
		return $content;
	}
	
	function updateCollections(){		
		if(!$this->settings->accountId)
		{
			return '<div style="color:red;font-size:15px;font-weight:bold;">Geen account id!</div>';
		}
		
		if(!$this->settings->collectionId)
		{
			return '<div style="color:red;font-size:15px;font-weight:bold;">Geen collection id!</div>';
		}
		
		$this->_update = new update_sdu_organisations();
		$this->_update->account_id = $this->settings->accountId;
		$this->_update->collection_id = $this->settings->collectionId;
		$myContent = '';
		$myCollections = explode(',',$this->settings->collectionId);
		
		foreach($myCollections as $iCollection){
			$this->_update->collection_id = $iCollection;
			$myInfo = $this->_update->update();
			$myContent .= 'Collectie: '.$iCollection.' totaal:'. $myInfo->countFeed .' new:'.$myInfo->countNew.' updates:'.$myInfo->countUpdate .'<br />';			
		}
		$myContent .='<p>Adres collecties zijn geupdate!</p>';
		return $myContent;
	}
	
	function loadSettings(){
		$query = "SELECT * FROM `tx_sduconnect_accountsettings` WHERE `settingsName` = 'updateSettings'";
		
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		
		if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
			$this->settings = unserialize($row['settings']);
		}
		
		$this->setAccountId($this->settings->accountId);
		$this->setCollectionId($this->settings->collectionId);
	}
	
	function saveSettings(){
		if($this->isUpdating){
			$this->settings->lastUpdated = time();
		}
		
		$dbArr['settings'] = serialize($this->settings);
		$dbArr['settingsName'] = 'updateSettings';
		
		$query = "SELECT count(`uid`) AS `total` FROM `tx_sduconnect_accountsettings` WHERE `settingsName` = 'updateSettings'";
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		$totals = 0;
		
		if($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
			$totals = $row['total'];
		}
		
		if($totals==1){
			$query = $GLOBALS['TYPO3_DB']->UPDATEquery('tx_sduconnect_accountsettings','`settingsName` ="updateSettings" ',$dbArr);
		}
		else {
			$query = $GLOBALS['TYPO3_DB']->INSERTquery('tx_sduconnect_accountsettings', $dbArr);
		}
		$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
	}
	
	function setAccountId($aId=null){
		if($aId!=null){
			$this->settings->accountId = $aId;
		}
	}
	
	function setCollectionId($aId=null){
		if($aId!=null){
			$this->settings->collectionId = $aId;
		}		
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sduconnect/address/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sduconnect/address/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_sduconnect_address');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
