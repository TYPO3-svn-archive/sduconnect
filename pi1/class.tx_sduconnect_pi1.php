<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Dmitry Dulepov <dmitry.dulepov@gmail.com>
*			Ben van Kruistum <ben@ooip.nl>
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

require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('sduconnect') . 'lib/lib.php');


/**
 * This plugin integrates SDU data with the TYPO3. Work based on the
 * 'Produkt Proxy Script' plugin from the 'sduconnect' extension by
 * Ben van Kruistum, OOIP BV.
 *
 * @author	Ben van Kruistum <ben@ooip.nl>
 * @author	Dmitry Dulepov	<dmitry.dulepov@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_sduconnect
 */
class tx_sduconnect_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_sduconnect_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_sduconnect_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'sduconnect';	// The extension key.
	var $pi_checkCHash = false;	// No piVars -> no pi_checkCHash!
	
	var $sduAccountId = 0; // Account id From SDU Connect
	var $collectionId = 0; // SDU collection Id
	var $productId = 0; // SDU product Id
	var $publishType = 2; // How to publish the collection
	var $asGoogleMap = 0; // Publish as Google map
	var $proclamationCollectionId = 0;
	var $addressTemplateFile = 'EXT:sduconnect/resources/address.html';
	var $addressCollectionId = 0;
	var $googleMapApiKey = 0;
	var $googleMapHeight = 0;
	var $googleMapWidth = 0;
	var $content = '';
	var $local_cObj;
	var $searchLetter;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		
		// We must check cHash! We have to call it manually because we
		// do not have piVars. If we had, tslib_pibase would make this call for us!
		
		$this->checkCHash();
		$this->conf = $conf;
		
		$this->pi_setPiVarDefaults();
		$this->pi_initPIflexForm();
		$this->pi_loadLL();
		$this->gpVar = t3lib_div::_GP($this->extKey);
		
		if (!$this->init()) {
			if ($this->content == '') {
				$this->content = $this->pi_getLL('wrong_configuration');
			}
		}
		else {
			$content = $this->getContent();
			$content = $this->fixLinks($content);
			$this->content = $this->fixCharset($content);
		}
		
		return $this->pi_wrapInBaseClass($this->content);
	}
	
	/**
	 * Init the script
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function init()	{
		$this->setAccountId();
		if(!$this->sduAccountId){
			return false;
		}
		
		$this->setProductId();
		
		// Check which proxy script should be displayed
		if ($this->getStoredValue('ProxyScriptType', 'sheet1') == 1) {
			
			// Retreive and set the collection Id
			$this->setCollectionId();
			if(!$this->collectionId){
				return false;
			}
			
			// Retreive and set the Publish type
			$this->setPublishType();
		}
		elseif ($this->getStoredValue('ProxyScriptType', 'sheet1') == 2) {
			
			$this->setProclamationCollectionId();
			if (!$this->proclamationCollectionId) {
				return false;
			}
			
			if ($this->getStoredValue('enableGoogleMaps','sheet3')) {
				$this->setGoogleApiKey();
				if (!$this->googleMapApiKey) {
					return false;
				}
				$this->googleMapHeight = $this->getStoredValue('googleMapsHeight', 'sheet3');
				$this->googleMapWidth = $this->getStoredValue('googleMapsWidth', 'sheet3');
			}
		}
		elseif ($this->getStoredValue('ProxyScriptType', 'sheet1') == 3) {
			
			$this->local_cObj = t3lib_div::makeInstance('tslib_cObj');
			$this->searchLetter = $this->gpVar['searchLetter'] ? $this->gpVar['searchLetter'] : 'a' ;
			
			$this->setAddressCollectionId();
			if (!$this->addressCollectionId) {
				return false;
			}
			$this->setAddressTemplateFile();
		}
		return true;
	}
	
	/**
	 * Fetches the content from the SDU feed
	 *
	 * @return	string	The content
	 */
	function getContent() {
		$content = '';
		switch($this->getStoredValue('ProxyScriptType', 'sheet1')){
			case "1":
				$content = $this->proxyScriptProducts();
				break;
			case "2":
				$content = $this->proxyScriptProclamation();
				break;
			case "3":
				
				$getMyContent = $this->getXmlAddress();
				$content = $getMyContent;// ($getMyContent) ? $getMyContent : $this->pi_getLL('no_addresses')  ;
				break;								
		}
		
		return $content;
	}
	
	/**
	 * Returns a value set in Flexform or TS, TS higher priority
	 *
	 * @param	string		$variableName: variable
	 * @param	string		$sheetName: From FlexForm Sheet
	 * @return	string
	 */
	function getStoredValue($variableName, $sheetName){
		$code = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $variableName, $sheetName);
		return $code ? $code : $this->cObj->stdWrap($this->conf[$variableName], $this->conf[$variableName.'.']);
	}
	
	
	/**
	 * Returns the Product Proxy result from SDU connect
	 *
	 * @return	 the Product Proxy result from SDU connect
	 */
	function proxyScriptProducts() {
		$urlPrefix = 'product';
		/*
				Type of document that could be fetched
				Currently 4 types are configured
				
				1. E-Loket
				2. E-Balie
				3. Subsidie loket
				4. List A-Z
				
				type 0 is xml and is not a SDU page type, but another url to fetch the xml version
		*/
		// Preset the options
		$get = $_GET;
		foreach (array('id', 'no_cache', 'typo3_user_int', 'top10', 'smarttags') as $var) {
			unset($get[$var]);
		}
		
		$locketType = $this->publishType;
		if (t3lib_div::testInt($get['lokettype'])) {
			$locketType = $get['lokettype'];
		}
		switch ($locketType) {
			case 1:
				$locketType = 1;
				break;
			case 2:
				$locketType = 2;
				break;
			case 3:
				$locketType = false;
				$urlPrefix = 'xml';
				break;
			case 4:
				$locketType = 4;
				break;
			case 5:
				$get['view'] = 'product_list';
				$locketType = 5;
				break;
			case 6:
				$locketType = 6;
				break;
			case 7:
				$locketType = 7;
				
				$opt = explode(',', $this->getStoredValue('lokettype03_options', 'sheet2'));
				
				foreach($opt as $value) {
					switch ($value) {
						case '1':
							$get['top10'] = 1;
							break;
						case '2':
							$get['smarttags'] = 1;
							break;
					}
				}
				
				// Options
				if (!is_array($get['organisatie']) || count($get['organisatie']) == 0) {
					$opt = explode(',', $this->getStoredValue('lokettype03_searchOpt', 'sheet2'));
					$organizations = array();
					foreach($opt as $value) {
						switch ($value) {
							case '1':
								$organizations[count($organizations)] = 'gemeenten';
								break;
							case '2':
								$organizations[count($organizations)] = 'waterschappen';
								break;
							case '3':
								$organizations[count($organizations)] = 'provincies';
								break;
							case '4':
								$organizations[count($organizations)] = 'ministeries';
								break;
						}
					}
					if (count($organizations) > 0) {
						$get['organisatie'] = $organizations;
					}
					else {
						unset($get['organisatie']);
					}
				}
				break;
			case 99:
				$locketType = false;
				$urlPrefix = 'xml';
				$get['view'] = 'collection_product_full';
				break;
		}
		
		$get['account_id'] = $this->sduAccountId;
		$get['product_collection_id'] = $this->collectionId;
		$get['proxy'] = 'true';
		
		if ($locketType) {
			$get['lokettype'] = $locketType;
		}
		
		if ($this->productId > 0) {
			$get['view'] = 'product';
			$get['product_id'] = $this->productId;
		}
		elseif (!isset($get['view'])) {
			$get['view'] = 'product_home';
		}
		
		$url = 'http://'.$urlPrefix.'.sduconnect.nl/product.xml?' .
			t3lib_div::implodeArrayForUrl('',
				t3lib_div::array_merge_recursive_overrule($get, $_POST), '', false, true);
		return t3lib_div::getURL($url);
	}
	
	/**
	 * Returns the Proclamation/news Proxy result from SDU connect
	 *
	 * @return	 the Product Proxy result from SDU connect
	 */
	function proxyScriptProclamation() {
		$get = $_GET;
		foreach (array('id', 'no_cache', 'typo3_user_int', 'top10', 'smarttags') as $var) {
			unset($get[$var]);
		}
		if($this->getStoredValue('enableGoogleMaps', 'sheet3')) {
			$get['google_map_api_key'] = $this->googleMapApiKey;
			$get['mode'] = 'gmap';
			$get['width'] = $this->googleMapWidth . 'px';
			$get['height'] = $this->googleMapHeight . 'px';
		}
		
		$get['account_id'] = $this->sduAccountId;
		$get['news_collection_id'] = $this->proclamationCollectionId;
		$get['proxy'] = 'true';
		//$get['charset'] = $charset;
		
		if (!isset($get['view'])) {
			$get['view'] = 'news_overview';
		}
		
		return t3lib_div::getURL('http://news.sduconnect.nl/news.xml?' .
				t3lib_div::implodeArrayForUrl('', t3lib_div::array_merge_recursive_overrule($get, $_POST), '', false, true));
	}
	
	function createTypolink($aConf){
		$this->local_cObj->setCurrentVal($GLOBALS["TSFE"]->id);
		$this->typolink_conf = $this->conf["typolink."];
		$this->typolink_conf["parameter."]["current"] = 1;
		$this->typolink_conf["additionalParams"] = $this->cObj->stdWrap($this->typolink_conf["additionalParams"],$this->typolink_conf["additionalParams."]);
		unset($this->typolink_conf["additionalParams."]);
		if(is_array($aConf)){
			return array_merge($this->typolink_conf , $aConf);
		}
		return $this->typolink_conf;
	}
	
	/**
	 * Returns the XML for finding addresses
	 *
	 * @return	 the Product Proxy result from SDU connect
	 */
	function getXmlAddress() {
		$get['account_id'] = $this->sduAccountId;
		$get['organisation_collection_id'] = $this->addressCollectionId;
		
		$getAddressListType = $this->getStoredValue('viewType', 'sheet5');		
		$getAddressValues = $this->getStoredValue('viewValue', 'sheet5');
		
		$mainTmpl = $this->cObj->fileResource($this->addressTemplateFile);		
		
		$template['listItem'] = $this->cObj->getSubpart($mainTmpl, '###ADDRESS_LIST_ITEM###');
		$template['navigation'] = $this->cObj->getSubpart($mainTmpl, '###ADDRESS_NAVIGATION###');
		$template['complete'] = $this->cObj->getSubpart($mainTmpl, '###ADDRESS_LIST###');
		
		$content['###NAVIGATION###'] ='';
		
		
		//Back to top Link
		$topName = $this->prefixId.'_backToTop';
		
		
		$backToTop_conf["section"]= $topName;
		
		$backToTop_conf["useCacheHash"] = true;
		$backToTop_conf["target"] = "_top";
		$backToTop_conf["additionalParams"] .= '&sduconnect[searchLetter]='.$this->searchLetter;
		$backToTop_conf = $this->createTypolink($backToTop_conf);
		$backToTop_conf = is_array($this->conf['NavigationBackToTop_stdWrap.']['typolink.'])?  array_merge($backToTop_conf , $this->conf['NavigationBackToTop_stdWrap.']['typolink.']) : $backToTop_conf;
		
		$content['###BACK_TO_TOP_ANCHOR###'] ='<a name="'.$topName.'" />';
		$content['###BACK_TO_TOP###'] = $this->local_cObj->stdWrap($this->local_cObj->typoLink($this->conf['NavigationBackToTop_stdWrap.']['text'] , $backToTop_conf),$this->conf['NavigationBackToTop_stdWrap.']);
		
		switch($getAddressListType){
			case "1":
				$template['complete'] = $this->cObj->getSubpart($mainTmpl, '###ADDRESS_LIST_BROWSE###');
				$myObj = $this->fetchDatabase();	
				
				$content['###NAVIGATION###'] = $this->buildAlphabeticBrowsing();
				
				// Create if needed navigation structure
				$navMarker['###NAVIGATION_LINK###'] = $content['###NAVIGATION###'] ;
				$content['###NAVIGATION###'] = $this->cObj->substituteMarkerArrayCached($template['navigation'], $navMarker);
				
				break;
			case "2":
				$template['complete'] = $this->cObj->getSubpart($mainTmpl, '###ADDRESS_LIST###');
				if(is_null($getAddressValues)){
					return false;
				}
				
				$get['view'] = 'keywordsearch_organisation';
				$get['organisationxml_action'] = 'keywordsearch_organisations';
				$get['organisation_search_keyword'] = $getAddressValues;
				
				$myObj = $this->fetchXML('http://xml.sduconnect.nl/organisation.xml',$get);
				
				break;
			case "3":
				$template['complete'] = $this->cObj->getSubpart($mainTmpl, '###ADDRESS_LIST###');
				if(is_null($getAddressValues)){
					return false;
				}			
				
				$get['view'] = 'idsearch_organisation';
				$get['organisationxml_action'] = 'search_organisations_by_id';
				$get['organisation_ids'] = $getAddressValues;
				
				$myObj = $this->fetchXML('http://xml.sduconnect.nl/organisation.xml',$get);
				
				break;
		}	
		
		t3lib_div::loadTCA('tx_sduconnect_organisation');
		
		$content['###ADDRESS_LIST_ITEMS###'] ='';
		
		$content['###CURRENT###'] = $this->searchLetter;
		
		$countstr = 'abcdefghijklmnopqrstuvwxyz';
		
		$currentPos = strpos($countstr,$this->searchLetter);
		$prev = 'z';
		$next = 'b';
		if($currentPos===false){ //the search letter should be 0-9
			$next = 'a';
			$prev = 'z';
		}
		$currentPos = $currentPos===false?-1:$currentPos;
		switch($currentPos){
			case -1:
				$prev = 'z';
				$next = 'a';
				break;
			case 0:
				$prev = '0-9';
				$next = 'b';
				break;
			case 25 :
				$prev = 'y';
				$next = '0-9';	
				break;
			default:
				$prev = $countstr[$currentPos-1];
				$next = $countstr[$currentPos+1];	
				break;		
		}
		
		
		
		
		//Prev Link
		if(is_array($this->conf['NavigationLinkPrev_stdWrap.']['typolink.'])){
			$temp_prev = $this->conf['NavigationLinkPrev_stdWrap.']['typolink.'];
		}
		else {
			$temp_prev = $temp_conf;
		}
		$temp_prev["useCacheHash"] = true;
		$temp_prev["additionalParams"] .= '&sduconnect[searchLetter]='.$prev;
		$temp_prev = $this->createTypolink($temp_prev);
		
		$content['###PREVIOUS###'] = $this->local_cObj->stdWrap($this->local_cObj->typoLink($prev , $temp_prev),$this->conf['NavigationLinkPrev_stdWrap.']);
		
		//Next Link
		
		if(is_array($this->conf['NavigationLinkNext_stdWrap.']['typolink.'])){
			$temp_next = $this->conf['NavigationLinkNext_stdWrap.']['typolink.'];		
		}
		else {
			$temp_next = $temp_conf;
		}
		$temp_next["useCacheHash"] = true;
		$temp_next["additionalParams"] .= '&sduconnect[searchLetter]='.$next;
		$temp_next = $this->createTypolink($temp_next);
		
		$content['###NEXT###'] = $this->local_cObj->stdWrap($this->local_cObj->typoLink($next , $temp_next),$this->conf['NavigationLinkNext_stdWrap.']);
		
		if(count($myObj->organisations->organisation)==0){
			return $this->cObj->substituteMarkerArrayCached($template['complete'], $content);
		}
		
		foreach($myObj->organisations->organisation as $iObj){
			
			$this->local_cObj->start(sduconnectLibraries::object2array($iObj),'tx_sduconnect_organisation');	
			
			$userMarker = array();
			
			$visitAddress = '';
			$postAddress = '';
			
			foreach($iObj as $iKey => $iVal){
				$iKey = strtolower($iKey);
				switch($iKey){
					case "url":						
						
						
						$breakOn = $this->conf['urlInsertBreakOn'] ? $this->conf['urlInsertBreakOn'] : -1 ;
						$overlap = $this->conf['urlInsertBreakOverlap'] ? $this->conf['urlInsertBreakOverlap']/100 : 1 ;						
						
						if(strlen($iVal)>$breakOn && $breakOn > -1) {
							
							$seperator = '<br />';
							$initCount = $breakOn;
							$breakOn = $breakOn + strlen($seperator);
							/*
							do {
								$iVal = substr_replace($iVal, $seperator, $initCount, 0);
								$initCount+=$breakOn;
							}
							*/
							while ($initCount + ($breakOn*$overlap) < strlen($iVal)){
								$iVal = substr_replace($iVal, $seperator, $initCount, 0);
								$initCount+=$breakOn;							
							}
						}
						
						$userMarker['###'.$iKey.'###'] = $this->local_cObj->stdWrap($iVal , $this->conf[$iKey.'_stdWrap.']);
						break;
					
					case "visit_address_street":
					case "visit_address_number":
					case "visit_address_zip_code":
					case "visit_address_city":
						$visitAddress.= $this->local_cObj->stdWrap($iVal , $this->conf['visit_address.'][$iKey.'.']);
						break;
					
					case "post_address_street":
					case "post_address_number":
					case "post_address_po_box":
					case "post_address_zip_code":
					case "post_address_city":
						$postAddress.= $this->local_cObj->stdWrap($iVal , $this->conf['post_address.'][$iKey.'.']);
						break;					
					
					default:
						$userMarker['###'.$iKey.'###'] = $this->local_cObj->stdWrap($iVal , $this->conf[$iKey.'_stdWrap.']);
						break;
				}
			}
			
			$userMarker['###visit_address###'] = $this->local_cObj->stdWrap($visitAddress , $this->conf['visit_address.']);
			$userMarker['###post_address###'] = $this->local_cObj->stdWrap($postAddress , $this->conf['post_address.']);
			
			$userMarker['###logo###'] = $iVal->logo;
			$content['###ADDRESS_LIST_ITEMS###'].= $this->cObj->substituteMarkerArrayCached($template['listItem'], $userMarker);
		}
		
		return $this->cObj->substituteMarkerArrayCached($template['complete'], $content);
	}	
	
	function fetchDatabase(){
		
		if(strlen($this->searchLetter)==1){
			$query = 'SELECT * FROM `tx_sduconnect_organisation` WHERE `searchstring` LIKE "'.$this->searchLetter.'%" ORDER BY `title`';
		}
		elseif($this->searchLetter=='0-9'){
			$query = 'SELECT * FROM `tx_sduconnect_organisation` WHERE `searchstring` REGEXP "^[0-9]" ORDER BY `title`';
		}
		else {
			$query = 'SELECT * FROM `tx_sduconnect_organisation` ORDER BY `title`';
		}
		
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		$myObj = (object)NULL;
		$myObj->organisations = (object)NULL;
		
		while($row = sduconnectLibraries::array2object($GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))){
			$myObj->organisations->organisation[] = (object)$row;
		}
		return $myObj;
	}
	
	function buildAlphabeticBrowsing(){	
		$myIndex = array('0-9'=>0,'a'=>0,'b'=>0,'c'=>0,'d'=>0,'e'=>0,'f'=>0,'g'=>0,'h'=>0,'i'=>0,'j'=>0,'k'=>0,'l'=>0,'m'=>0,'n'=>0,'o'=>0,'p'=>0,'q'=>0,'r'=>0,'s'=>0,'t'=>0,'u'=>0,'v'=>0,'w'=>0,'x'=>0,'y'=>0,'z'=>0); 
		$query = "SELECT SUBSTRING( `searchstring` , 1, 1 ) AS FirstLetter, count( * ) AS NumItems FROM `tx_sduconnect_organisation` GROUP BY FirstLetter ORDER BY FirstLetter";
		
		$result = $GLOBALS['TYPO3_DB']->sql(TYPO3_db, $query);
		
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)){
			if(preg_match('/[0-9]/',$row['FirstLetter'])){
				$myIndex['0-9'] = $row['NumItems'];
			}		
			else {
				$myIndex[strtolower($row['FirstLetter'])] = $row['NumItems'];
			}
		}
		
		$this->local_cObj->setCurrentVal($GLOBALS["TSFE"]->id);
		$this->typolink_conf = $this->conf["typolink."];
		$this->typolink_conf["parameter."]["current"] = 1;
		$this->typolink_conf["additionalParams"] = $this->cObj->stdWrap($this->typolink_conf["additionalParams"],$this->typolink_conf["additionalParams."]);
		unset($this->typolink_conf["additionalParams."]);
		
		$temp_conf = $this->typolink_conf;
		$temp_conf["useCacheHash"] = true;
		//$temp_conf["no_cache"] = 1;		
		
		$mainTmpl = $this->cObj->fileResource($this->addressTemplateFile);
		$template['main'] = $this->cObj->getSubpart($mainTmpl, '###ADDRESS_NAVIGATION_LIST###');
		$content ='';
		
		foreach($myIndex as $iKey => $iVal){
			
			$temp_conf["title"] = $iVal . ' '. ($iVal==1 ? 'adres' : 'adressen');
			$temp_conf["additionalParams"] .= '&sduconnect[searchLetter]='.$iKey;
			
			trim($iVal);
			/*
						
			NavigationLink_results
			NavigationLink_empty
						
			NavigationLink_results_current			
			NavigationLink_empty_current			
						
			*/
			$getConfPart = ($iVal>0) ? '_results' : '_empty';
			$getConfPart .= ($this->searchLetter == $iKey) ? '_current' : '';
			
			
			
			if(is_array($this->conf['NavigationLink'.$getConfPart.'.']['typolink.'])){
				$temp_conf = array_merge($temp_conf , $this->conf['NavigationLink'.$getConfPart.'.']['typolink.']);
			}
			
			$userMarker['###LINK_ITEM###'] = $this->local_cObj->stdWrap($this->local_cObj->typolink($iKey, $temp_conf) , $this->conf['NavigationLink_stdWrap.']);
			
			$content.= $this->cObj->substituteMarkerArrayCached($template['main'], $userMarker);
		}
		return $content;
	}
	
	
	function fetchXML($aUrl,$aGet){

		$xml = t3lib_div::getURL($aUrl.'?' . t3lib_div::implodeArrayForUrl('', t3lib_div::array_merge_recursive_overrule($aGet, $_POST), '', false, true));
		
		$myXml = new SimpleXMLElement($xml);
		
		if(!is_object($myXml->organisations)){
			return false;
		}
		return $myXml;
	}
	
	/**
	 * Sets the SDU account Id
	 *
	 * @return void
	 */
	
	function setAccountId(){
		$this->sduAccountId = $this->conf['accountId'] ? $this->conf['accountId'] : $this->getStoredValue('accountId', 'sheet4');
		if (!$this->sduAccountId) {
			$this->content = $this->pi_getLL('errorMessage_NoAccountId');
		}
	}
	
	/**
	 * Sets the product collection id
	 *
	 * @return void
	 */
	function setCollectionId(){
		$this->collectionId = $this->conf['productCollection'] ? $this->conf['productCollection'] : $this->getStoredValue('productCollection', 'sheet2');
		if (!$this->collectionId) {
			$this->content = $this->pi_getLL('errorMessage_NoCollectionId');
		}
	}
	
	/**
	 * Sets the product  id
	 *
	 * @return void
	 */
	function setProductId(){
		$this->productId = $this->conf['productId'] ? $this->conf['productId'] : $this->getStoredValue('productId', 'sheet2');
		if ($this->productId == 0) {
			$this->productId = false;
		}
	}
	
	/**
	 * Sets the proclamation / news collection id
	 *
	 * @return void
	 */
	function setProclamationCollectionId(){
		$this->proclamationCollectionId = $this->conf['proclamationCollection'] ? $this->conf['proclamationCollection'] : $this->getStoredValue('proclamationCollection', 'sheet3');
		if (!$this->proclamationCollectionId) {
			$this->content = $this->pi_getLL('errorMessage_NoProclamationCollectionId');
		}
	}
	/**
	 * Sets the address collection id
	 *
	 * @return void
	 */
	function setAddressCollectionId(){
		$this->addressCollectionId = $this->conf['addressCollection'] ? $this->conf['addressCollection'] : $this->getStoredValue('addressCollection', 'sheet5');
		if (!$this->addressCollectionId) {
			$this->content = $this->pi_getLL('errorMessage_NoAddressCollectionId');
		}
	}
	/**
	 * Sets the address template file
	 *
	 * @return void
	 */
	function setAddressTemplateFile(){
		$testManualAddressTemplateSetting = $this->conf['addressTemplateFile'] ? $this->conf['addressTemplateFile'] : $this->getStoredValue('addressTemplateFile', 'sheet5');
		$this->addressTemplateFile = $testManualAddressTemplateSetting ? $testManualAddressTemplateSetting : $this->addressTemplateFile;
		
	}
	
	
	/**
	 * Sets the google maps Api key
	 *
	 * @return void
	 */
	function setGoogleApiKey(){
		$this->googleMapApiKey = $this->getStoredValue('googleMapsApiKey', 'sheet3');
		if(!$this->googleMapApiKey){
			$this->content = $this->pi_getLL('errorMessage_NoGoogleMapApiKey');
		}
	}
	
	/**
	 * Sets the publish type
	 *
	 * @return void
	 */
	function setPublishType() {
		$this->publishType = $this->getStoredValue('publishCollectionType', 'sheet2');
	}
	
	/**
	 * Converts all hard-coded links to proper links using typolink
	 *
	 * @param	string	$content	Content returned by the server
	 * @return	string	Fixed content
	 */
	function fixLinks($content) {
		$host = ($this->conf['host'] ? $this->conf['host'] : t3lib_div::getIndpEnv('HTTP_HOST'));
		$pattern = '/(\'|")((?:(?:http:\/\/' . preg_quote($host, '/') . '\/[^\?"\']*)?\?).*?)\1/';
		$group = 2;
		$matches = array();
		preg_match_all($pattern, $content, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
		$linkCache = array();
		// Set up crawler
		$crawler = null;
		if (!isset($_SERVER['HTTP_X_T3CRAWLER']) &&
				t3lib_extMgm::isLoaded('crawler') &&
				t3lib_div::_GP('view') != 'product_search') {
			// TODO Check page's user group and set it here
			$crawlerConfig = array(
					'procInstrFilter' => 'tx_indexedsearch_reindex, tx_indexedsearch_crawler, tx_indexedsearch_files',
					'cHash' => 1,
					'pidsOnly' => $GLOBALS['TSFE']->id,
					);
			if ($GLOBALS['TSFE']->config['config']['baseURL']) {
				$crawlerConfig['baseURL'] = $GLOBALS['TSFE']->config['config']['baseURL'];
			}
			require_once(t3lib_extMgm::extPath('crawler', 'class.tx_crawler_lib.php'));
			$crawler = t3lib_div::makeInstance('tx_crawler_lib');
			/* @var $crawler tx_crawler_lib */
			$crawler->setID = time();
		}
		$jsLinks = array();
		for ($i = count($matches[$group]) - 1; $i >= 0; $i--) {
			if (isset($linkCache[$matches[$group][$i][0]])) {
				$link = $linkCache[$matches[$group][$i][0]];
			}
			else {
				$params = array();
				$link = htmlspecialchars($this->convertPageLink($matches[$group][$i][0], $params));
				$linkCache[$matches[$group][$i][0]] = $link = t3lib_div::locationHeaderUrl($link);
				if ($matches[$group - 1][$i][0] == '\'') {
					$jsLinks[] = $link;
				}
				if (count($params) && $crawler) {
					$params = t3lib_div::implodeArrayForUrl('', $params);
					$url = t3lib_div::locationHeaderUrl('index.php?id=' . $GLOBALS['TSFE']->id . $params);
					$cHash_array = t3lib_div::cHashParams($params);
					$cHash_calc = t3lib_div::shortMD5(serialize($cHash_array));
					$url .= '&cHash=' . $cHash_calc;
					if (!$this->isQueued($url)) {
						$crawler->setID = crc32($url);
						$crawler->addUrl($GLOBALS['TSFE']->id, $url, $crawlerConfig, 0);
					}
				}
			}
			$content = substr($content, 0, $matches[$group][$i][1]) .
				$link .
				substr($content, $matches[$group][$i][1] + strlen($matches[$group][$i][0]));
		}
		if (count($jsLinks)) {
			$content .= '<div style="display: none">';
			foreach ($jsLinks as $link) {
				$content .= '<a href="' . $link . '">&nbsp;</a>';
			}
			$content .= '</div>';
		}
		//$content = str_replace('method="get"', 'method="post"', $content);
		// does not make sense to change, there is a link at the bottom of search that still contains all search params
		if (($title = $this->extractTitle($content))) {
			$GLOBALS['TSFE']->altPageTitle = $GLOBALS['TSFE']->indexedDocTitle = $title;
		}
		// Fix forms
		$content = $this->fixForms($content);
		return $content;
	}
	
	/**
	 * Fixes form tags: empty action tag and caching.
	 *
	 * @param	string	$content	Content
	 * @return	string	Fixed content
	 */
	function fixForms($content) {
		if (strpos($content, 'action=""') !== false) {
			$content = str_replace('action=""', 'action="' . htmlspecialchars($this->pi_getPageLink($GLOBALS['TSFE']->id)) . '"', $content);
		}
		$content = str_replace('</form>', '<input type="hidden" name="typo3_user_int" value="1" /></form>', $content);
		return $content;
	}
	
	/**
	 * Fixes character set differencies between SDU feed and TYPO3.
	 *
	 * @param	string	$content	Content
	 * @return	string	Fixed content
	 */
	function fixCharset($content) {
		/*
		if ($GLOBALS['TSFE']->renderCharset != 'iso-8859-1') {
			$content = $GLOBALS['TSFE']->csConvObj->conv($content, 'iso-8859-1', $GLOBALS['TSFE']->renderCharset, true);
		}
		*/
		return $content;
	}
	
	/**
	 * Converts hard-coded link to TYPO3 link. Strips host and is part and always uses the current page
	 *
	 * @param	string	$link	Link to convert
	 * @param	array	$params	[OUT] Link parameters
	 * @return	string	Converted link
	 */
	function convertPageLink($link, &$params) {
		list(, $link_params) = explode('?', $link, 2);
		$params = array();
		$conf = array(
				'parameter' => $GLOBALS['TSFE']->id,
				);
		if ($link_params) {
			$link_params = str_replace('&amp;', '&', $link_params);
			foreach (explode('&', $link_params) as $paramset) {
				list($name, $value) = explode('=', $paramset);
				if ($name != 'id' && $name != 'no_cache') {
					$name = rawurldecode($name);
					if (substr($name, -2) == '[]') {
						$params[substr($name, 0, -2)][] = $value;
					}
					else {
						$params[$name] = $value;
					}
				}
			}
			if (count($params) > 0) {
				$conf['additionalParams'] = t3lib_div::implodeArrayForUrl('', $params, '', true);
				$conf['useCacheHash'] = true;
			}
		}
		$url = $this->cObj->typoLink_URL($conf);
		return $url;
	}
	
	/**
	 * Checks that cHash is set when necessary and it is valid. Otherwise TSFE should disable the cache.
	 *
	 * @return	void
	 */
	function checkCHash() {
		$params = array_merge($_GET, $_POST);
		unset($params['id']);
		unset($params['cHash']);
		unset($params['L']);
		if (count($params)) {
			$GLOBALS['TSFE']->reqCHash();
		}
	}
	
	/**
	 * Cheks if URL is queued for crawling
	 *
	 * @param unknown_type $url
	 * @return unknown
	 */
	function isQueued($url) {
		list($row) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('COUNT(*) AS t', 'tx_crawler_queue',
				'set_id=' . crc32($url)
				);
		return ($row['t'] > 0);
	}
	
	/**
	 * Extracts title from the content (from Hx tags)
	 *
	 * @param	string	$content	Content
	 * @return	string	Title or empty string
	 */
	function extractTitle($content) {
		$matches = array();
		foreach (array('h1', 'h2') as $tag) {
			if (preg_match('/<' . $tag . '>(.*?)<\/' . $tag . '>/', $content, $matches)) {
				return trim($matches[1], ':');
			}
		}
		return '';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sduconnect/pi1/class.tx_sduconnect_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sduconnect/pi1/class.tx_sduconnect_pi1.php']);
}

?>