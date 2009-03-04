<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009   Ben van Kruistum <ben@ooip.nl>
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

unset($MCONF);

$_EXTKEY = 'sduconnect';
require_once('conf.php');

require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

require_once(t3lib_extMgm::extPath('sduconnect') .'lib/lib.php');

$LANG->includeLLFile('EXT:sduconnect/sso/locallang.xml');

require_once (PATH_t3lib.'class.t3lib_scbase.php');
require_once (PATH_t3lib.'class.t3lib_tstemplate.php');
require_once (PATH_t3lib.'class.t3lib_parsehtml.php');
require_once(PATH_t3lib.'class.t3lib_timetrack.php');
require_once(PATH_t3lib.'class.t3lib_div.php');
$BE_USER->modAccess($MCONF,1);    // This checks permissions and exits if the users has no permission for entry.

/**
 * Module 'sso' for the 'sduconnect' extension.
 *
 * @author     <Ben van >
 * @package    TYPO3
 * @subpackage    tx_sduconnect
 */

class  tx_sduconnect_module2 extends t3lib_SCbase {
	var $pageinfo;
	var $localLang;
	var $localUser ; 
	private $ssoSduAuthUrl = 'http://staging.sduconnect.nl/index.php?auth_action=login' ;//'http://admin.sduconnect.nl/index.php?auth_action=login';
	private $ssoLocalReferer;
	public $ssolaunchUrl = 'http://staging.sduconnect.nl/index.php?auth_action=login';
	private $ssoKey = false;
	private $ssoUserVerified = false;
	private $ssoErrors;
	private $ssoMessages;
	public $sduAccountId = false;
	public $ssoUserName = false;
	public $ssoUserPass = false;
	public $ssoCustomerIdKey = false;
	public $serverRequestTimeout = 15;
	var $MCONF;
	
	private $settings;
	private $settingsName = 'sso_settings';
	/**
	 * Initializes the Module
	 * @return    void
	 */
	function init()    {
		global $LANG,$BE_USER,$BACK_PATH,$TCA,$TYPO3_CONF_VARS,$TYPO3_DB ;

		$this->MCONF = $GLOBALS['MCONF'];
		
		$this->localLang = $LANG;
		$this->localUser = $BE_USER;
		$this->ssoLocalReferer = t3lib_div::getIndpEnv('SERVER_NAME');
				
		$GLOBALS['TYPO3_DB']->debugOutput = 1;
		
		$this->include_once[]=PATH_t3lib.'class.t3lib_tcemain.php';
		parent::init();
		
		/*
		if (t3lib_div::_GP('clear_all_cache'))    {
		    $this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
		}
		*/
	}
	
	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return    void
	 */
	function menuConfig()    {
		$this->MOD_MENU = Array (
				'function' => Array (
					'1' => $this->localLang->getLL('function1')
					)
				);
		parent::menuConfig();
	}
	
	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return    [type]        ...
	 */
	function main()	{
		
		global $BE_USER,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;	
		
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
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
			
			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$this->localLang->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);
			
			$this->content.=$this->doc->startPage($this->localLang->getLL('title'));
			$this->content.=$this->doc->header($this->localLang->getLL('title'));
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
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->content.=$this->doc->startPage($this->localLang->getLL('title'));
			$this->content.=$this->doc->header($this->localLang->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}	
	
	/**
	 * Prints out the module HTML
	 *
	 * @return    void
	 */
	function printContent(){
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	
	/**
	 * Generates the module content
	 *
	 * @return    void
	 */
	function moduleContent(){
		switch($this->MOD_SETTINGS['function'])    {
			case 1:
				$content = $this->drawSectionOne();
				$this->content.=$this->doc->section($this->localLang->getLL('function1'),$content,0,1);
				break;
			case 2:
				$content = $this->drawCheckForm();
				$content='<div align=center><strong>Menu item #2...</strong></div>';
				$this->content.=$this->doc->section('Message #2:',$content,0,1);
				break;
			case 3:
				$content='<div align=center><strong>Menu item #3...</strong></div>';
				$this->content.=$this->doc->section('Message #3:',$content,0,1);
				break;
		}
	}
	
	protected function drawCheckForm(){
		$markers['###SDU_ACCOUNT_ID###'] = $this->settings['accountId'] ? $this->settings['accountId'] : t3lib_div::_GP('ooip_accountId');

		$markers['###SDUSECKEY_TEXT###'] = $this->localLang->getLL('loginForm.SDUSeckey_Text');
		$markers['###SDUUSER_TEXT###'] = $this->localLang->getLL('loginForm.SDUUserText');
		$markers['###SDUPASS_TEXT###'] = $this->localLang->getLL('loginForm.SDUPassText');
		
		$markers['###SDUSECKEY###'] = t3lib_div::_GP('ooip_secKey');
		$markers['###SDUUSER###'] = t3lib_div::_GP('ooip_sduUser');
		$markers['###SDUPASS###'] = t3lib_div::_GP('ooip_sduPass');
		$markers['###LOGIN_VERIFY###'] = $this->localLang->getLL('loginForm_verifyButton');
		
		$completeHTML = $this->loadHTML('resources/login.html');
		$myHtml = t3lib_parsehtml::getSubpart($completeHTML, '###LOGIN_FORM###');
		$myHtml.= $this->drawMessages() .' '. $this->drawErrors();

		return $this->parseHTML($myHtml , $markers);
	}
	function drawLaunchForm(){
		$markers['###LAUNCH_BUTTON###'] = $this->localLang->getLL('launchForm_launchButton');
		$markers['###LOGIN_CHECK###'] = $this->localLang->getLL('loginForm_checkButton');
		$markers['###LOGIN_UNREGISTER###'] = $this->localLang->getLL('loginForm_unregisterButton');
		$markers['###SDU_LAUNCH_URL###'] = $this->ssolaunchUrl .'&auth_typo3_hash='.$this->ssoKey.'&auth_typo3_key='.$this->settings['accountId'];
		
		$completeHTML = $this->loadHTML('resources/login.html');
		$myHtml = t3lib_parsehtml::getSubpart($completeHTML, '###CHECK_FORM###');		
		$myHtml.= $this->drawMessages() .' '. $this->drawErrors();
		return $this->parseHTML($myHtml , $markers);
	}
	
	function drawErrors(){
		$completeHTML = $this->loadHTML('resources/login.html');
		$myHTML = t3lib_parsehtml::getSubpart($completeHTML, '###ERROR_MESSAGE###');
		$retHTML ='';
		foreach($this->ssoErrors as $myError){
			$aMarkerSet['###MESSAGE###'] = $myError;
			$retHTML.= t3lib_parsehtml::substituteMarkerArray($myHTML, $aMarkerSet);
		}
		return $retHTML;
	}
	
	function drawMessages(){
		$completeHTML = $this->loadHTML('resources/login.html');
		$myHTML = t3lib_parsehtml::getSubpart($completeHTML, '###SYSTEM_MESSAGE###');
		$retHTML ='';
		foreach($this->ssoMessages as $myMessage){
			$aMarkerSet['###MESSAGE###'] = $myMessage;
			$retHTML.= t3lib_parsehtml::substituteMarkerArray($myHTML, $aMarkerSet);
		}
		return $retHTML;
	}
		
	protected function drawSectionOne(){
		
		$this->settings = sduconnectLibraries::loadSettings($this->settingsName);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_sduconnect_sdusecuritykey','be_users','uid='.$this->localUser->user['uid']);
		list($this->ssoKey) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		
		if(t3lib_div::_GP('ooip_sduUnregister')){
			if($this->unregisterSSO()){
				$this->ssoMessages[] = $this->localLang->getLL('message.loginForm.unRegisterSucces');
			}
			$this->invalidateSSOKey();
		}
		if(t3lib_div::_GP('ooip_sduCheck')){
			$this->postSSOCheckRequest();
		}
		
		if($this->setSsoSduUserName(t3lib_div::_GP('ooip_sduUser')) && $this->setSsoSduUserPass(t3lib_div::_GP('ooip_sduPass')) && $this->setSsoSduCustomerIdKey(t3lib_div::_GP('ooip_secKey')) && $this->setSsoAccountId(t3lib_div::_GP('ooip_accountId'))){		
			if($this->checkSSOregistration()){
				if($this->ssoUserVerified){
					$this->registerSSO();
				}
				else{
					$this->invalidateSSOKey();
					$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.checkSDUFail');
				}	
			}
			else{
				$this->invalidateSSOKey();
				$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.checkSDUFail');
			}
			$this->settings['accountId'] = $this->sduAccountId;
			sduconnectLibraries::saveSettings($this->settingsName,$this->settings);
		}
		
		if($this->ssoKey){
			return $this->drawLaunchForm();
		}
		else {
			return $this->drawCheckForm();
		}
	}
	
	public function loadHTML($aFileLocation){
		//Overwrite current backpath with extension location, this is used in the doc->getHtmlTemplate
		$this->doc->backPath = t3lib_extMgm::extPath('sduconnect');
		$myHTML = ($this->doc->getHtmlTemplate($aFileLocation));
		//Restore backpath
		$this->doc->backPath = $BACK_PATH;
		return $myHTML;
	}
	
	public function parseHTML($aHTML , $aMarkerSet){
		$myHTML = t3lib_parsehtml::substituteMarkerArray($aHTML, $aMarkerSet);
		foreach ($subParts as $subPart => $subContent) {
			$myHTML = t3lib_parsehtml::substituteSubpart($myHTML, $subPart, $subContent);
		}
		return $myHTML;
	}
	
	private function unregisterSSO(){
		return $GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_users', 'uid='.$this->localUser->user['uid'], array('tx_sduconnect_sdusecuritykey'=>''),FALSE);
	}
	
	private function registerSSO(){
		if(!$this->ssoKey){
			return false;
		}

		return $GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_users', 'uid='.$this->localUser->user['uid'], array('tx_sduconnect_sdusecuritykey'=>$this->ssoKey),FALSE);
	}
	
	private function encryptLogin($aStr) { 
		if(!$this->ssoCustomerIdKey){
			return false;
		}
		
		$num = 19;
		$iv = mcrypt_create_iv($num, MCRYPT_RAND);
		
		$strl = strlen($aStr); 
		$fin = ''; 
		for($i =0; $i < $strl; $i++){         	
			$fin .= dechex(ord($aStr[$i])); 
		} 	
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$this->ssoCustomerIdKey,$fin,MCRYPT_MODE_ECB,$iv)); 
		
	} 
	
	/**
	* private function checkSSOregistration()
	* Triggers the post request to the sdu auth url. if 200 received user with pass and key is authenticated
	* 
	* @return boolean
	**/
	private function checkSSOregistration(){
		if(!$this->ssoUserName || !$this->ssoUserPass){
			$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.check.noLoginOrPass');
			return false;
		}
		
		if(!$this->settings || !strlen($this->settings['accountId'])){
			$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.check.noAccountId');
			return  false;
		}
		
		if(!$this->setSsoKey($this->encryptLogin(serialize(array('username'=>$this->ssoUserName,'password'=>$this->ssoUserPass))))){
			return false;
		}
		
		return $this->postSSOCheckRequest();
		
	}
	
	/**
	* private function PostRequest($aUrl, $aReferer, $_data)
	* Creates a post request to a url
	* 
	* @param $aUrl string url for the post
	* @param $aReferer string url for the post
	* @return array
	**/
	private function postSSOCheckRequest() {
		if(!$this->ssoKey){
			$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.check.noSSOKey');
			return false;
		}
		if(!$this->settings['accountId']){
			$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.check.noAccountId');
			return false;
		}
		$_data = array('auth_action' => 'login' , 'auth_typo3_hash' => $this->ssoKey,'auth_typo3_key' => $this->settings['accountId']);
		$data = array();
		while(list($n,$v) = each($_data)){
			$data[] = $n.'='.$v;
		}    
		$data = implode('&', $data);
		
		$myUrl = parse_url($this->ssoSduAuthUrl);
		
		if ($myUrl['scheme'] != 'http') { 
			die('Only HTTP request are supported !');
		}
		
		$host = $myUrl['host'];
		$path = $myUrl['path'];
		
		$fp = fsockopen($host, 80 , $errNo , $errString, $this->serverRequestTimeout);
		fputs($fp, "POST $path HTTP/1.1\r\n");
		fputs($fp, "Host: $host\r\n");
		fputs($fp, "Referer: $this->ssoLocalReferer\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: ". strlen($data) ."\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);

		if($fp){
			
			$out = "GET / HTTP/1.1\r\n";
			$out .= "Host: www.example.com\r\n";
			$out .= "Connection: Close\r\n\r\n";
			fwrite($fp, $out);
						
			$HTTP_FIRST_LINE = fgets($fp,128);
			fclose($fp);
			$this->setUserVerified($HTTP_FIRST_LINE);
			return true;
		}
		else{	
			$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.checkSDUConnectionFail') . ' ' . $errString;
			return false;
		}
	}
	
	
	
	
	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return    array    all available buttons as an assoc. array
	 */
	protected function getButtons()    {	
		$buttons = array('csh' => '','shortcut' => '','save' => '');
		// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);
		// SAVE button
		$buttons['save'] = '<input type="image" class="c-inputButton" name="submit" value="Update"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/savedok.gif', '') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1) . '" />';
		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())    {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}
		
		return $buttons;
	}
	
	private function setUserVerified($aHttpStatusCode){
		if($aHttpStatusCode){
			
			//According to http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html
			//6.1 Status-Line
			//The first line of a Response message is the Status-Line, 
			//consisting of the protocol version followed by a numeric status
			//code and its associated textual phrase, with each element separated 
			//by SP characters. No CR or LF is allowed except in the final CRLF sequence.
			//Status-Line = HTTP-Version SP Status-Code SP Reason-Phrase CRLF

			$httpArr = explode(' ' , $aHttpStatusCode);
			$HTTP_CODE = intval($httpArr['1']);
			
			switch($HTTP_CODE){
				default :
					$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.checkSDUConnectionFail') . ' ' . $aHttpStatusCode;
					$this->ssoUserVerified = false;
					return false;
					break;
				case 200:
				case 302:
					$this->ssoMessages[] = $this->localLang->getLL('message.loginForm.checkSDUConnectionSucces');
					$this->ssoUserVerified = true;
					return true;
					break;
			}
		}
		else {
			$this->ssoErrors[] = $this->localLang->getLL('error.loginForm.checkSDUConnectionFail') . ' ' . $aHttpStatusCode;
			$this->ssoUserVerified = false;
			return false;
		}		
	}
	
	public function setSsoSduUserName($aSduUserName){
		if($aSduUserName){
			$this->ssoUserName = $aSduUserName;
			return true;
		}
		else {
			$this->ssoUserName = false;
			return false;
		}
	}
	
	public function setSsoSduUserPass($aSduUserPass){
		if($aSduUserPass){
			$this->ssoUserPass = $aSduUserPass;
			return true;
		}
		else {
			$this->ssoUserPass = false;
			return false;
		}
	}
	
	public function setSsoAccountId($aSduAccountId){
		$aSduAccountId = intval($aSduAccountId);
		if($aSduAccountId){
			$this->sduAccountId = $aSduAccountId;
			return true;
		}
		else {
			$this->sduAccountId = false;
			return false;
		}
	}
	
	private function setSsoKey($aSsoKey){
		if($aSsoKey){
			$this->ssoKey = $aSsoKey;
			return true;
		}
		else {
			$this->ssoKey = false;
			return false;
		}
	}
	
	public function setSsoSduCustomerIdKey($aSsoCustomerIdKey){
		if($aSsoCustomerIdKey){
			$this->ssoCustomerIdKey = $aSsoCustomerIdKey;
			return true;
		}
		else {
			$this->ssoCustomerIdKey = false;
			return false;
		}
	}	
	
	public function invalidateSSOKey(){
		$this->ssoKey = false;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sduconnect/sso/index.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sduconnect/sso/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_sduconnect_module2');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)    include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>