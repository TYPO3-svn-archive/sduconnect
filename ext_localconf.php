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
			),
		),
		'productmod_keywords' => array(
			array(
				'GETvar' => 'productmod_keywords',
			),
		),
		'lokettype' => array(
			array(
				'GETvar' => 'lokettype',
				'valueMap' => array(
					'xml' => 3,
					'e-loket-1.0' => 1,
					'e-loket-2.0' => 6,
					'e-Loket_3.0' => 7,
					'e-balie' => 2,
					'subsidie' => 4,
					'list-a-z' => 5,
					'xml-overzicht' => 99,
				),
				// if map above did not trigger, pass value as is
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