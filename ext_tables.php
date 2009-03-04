<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Add a column for storing the login security key to SDU backend
$tempColumns = array (
		'tx_sduconnect_sdusecuritykey' => array (        
			'exclude' => 1,        
			'label' => 'LLL:EXT:sduconnect/locallang_db.xml:be_users.tx_sduconnect_sdusecuritykey',        
			'config' => array (
				'type' => 'input',    
				'size' => '30',
				)
			),
		);


t3lib_div::loadTCA('be_users');
t3lib_extMgm::addTCAcolumns('be_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('be_users','tx_sduconnect_sdusecuritykey;;;;1-1-1');

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';

t3lib_extMgm::addPlugin(array('LLL:EXT:sduconnect/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'), 'list_type');
t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","SDU connect");
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:sduconnect/flexform_ds_pi1.xml');

if (TYPO3_MODE == 'BE')	{
	$extPath = t3lib_extMgm::extPath($_EXTKEY);
	
	if (! isset($TBE_MODULES['txsduconnectM1']))	{
		$temp_TBE_MODULES = array();
		
		foreach($TBE_MODULES as $key => $val) {
			if ($key == 'web') {
				$temp_TBE_MODULES[$key] = $val;
				$temp_TBE_MODULES['txsduconnectM1'] = '';
			} else {
				$temp_TBE_MODULES[$key] = $val;
			}
		}
		$TBE_MODULES = $temp_TBE_MODULES;
	}
	
	t3lib_extMgm::addModule('txsduconnectM1', '', '', t3lib_extMgm::extPath($_EXTKEY). 'mod1/');
	t3lib_extMgm::addModule('txsduconnectM1', 'txsduconnectM2', 'bottom', t3lib_extMgm::extPath($_EXTKEY). 'sso/');
	/* Address functionality tested but may not yet been deployed
	t3lib_extMgm::addModule('txsduconnectM1', 'txsduconnectM3', 'bottom', t3lib_extMgm::extPath($_EXTKEY). 'mod3/');
	*/
	
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_sduconnect_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_sduconnect_pi1_wizicon.php';
}

?>