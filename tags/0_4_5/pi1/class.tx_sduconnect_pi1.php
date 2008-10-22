<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Dmitry Dulepov <dmitry.dulepov@gmail.com>
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
	var $googleMapApiKey = 0;
	var $googleMapHeight = 0;
	var $googleMapWidth = 0;
	var $content = '';

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
		else {

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
		return true;
	}

	/**
	 * Fetches the content from the SDU feed
	 *
	 * @return	string	The content
	 */
	function getContent() {
		if ($this->getStoredValue('ProxyScriptType', 'sheet1') == 1) {
			$content = $this->proxyScriptProducts();
		}
		else {
			$content = $this->proxyScriptProclamation();
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
					unset($get['organizatie']);
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

	/**
	 * Sets the SDU account Id
	 *
	 * @return void
	 */

	function setAccountId(){
		$this->sduAccountId = $this->getStoredValue('accountId', 'sheet4');
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
		$this->collectionId = $this->getStoredValue('productCollection', 'sheet2');
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
		$this->productId = $this->getStoredValue('productId', 'sheet2');
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
		$this->proclamationCollectionId = $this->getStoredValue('proclamationCollection', 'sheet3');
		if (!$this->proclamationCollectionId) {
			$this->content = $this->pi_getLL('errorMessage_NoProclamationCollectionId');
		}
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
		$pattern = '/(\'|")((?:(?:http:\/\/' . preg_quote($host, '/') . '\/[^\?]*)?\?).*?)\1/';
		$group = 2;
		$matches = array();
		preg_match_all($pattern, $content, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
		$linkCache = array();
		// Set up crawler
		$crawler = null;
		if (!isset($_SERVER['HTTP_X_T3CRAWLER']) &&
				t3lib_extMgm::isLoaded('crawler') &&
				t3lib_div::_GP('view') != 'product_search') {
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
					$params[$name] = $value;
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