<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_sduconnect_pi1.php', '_pi1', 'list_type', 1);

/**
 * Adds configuration to RealURL
 *
 * @return	void
 */
function sduconnect_addRealURLConf() {
	$template = array(
		'view' => array(
			array(
				'GETvar' => 'view',
				'value' => 'product',
			),
			array(
				'GETvar' => 'product_id',
				'cond' => array(
					'prevValueInList' => 'product',
				),
			),
		),
		'breadcrum' => array(
			array(
				'GETvar' => 'breadcrum',
				'default' => '',
			),
		),
		'meta' => array(
			array(
				'GETvar' => 'meta_data_value_id',
			),
		),
		'proxy' => array(
			array(
				'GETvar' => 'proxy',
				'default' => '',
			),
		),
	);
	foreach (array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']) as $domain) {
		if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$domain]['postVarSets'])) {
			$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$domain]['postVarSets'] = array('_DEFAULT' => array());
		}
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'][$domain]['postVarSets']['_DEFAULT'] += $template;
	}
}

if (t3lib_extMgm::isLoaded('realurl')) {
	sduconnect_addRealURLConf();
}

?>