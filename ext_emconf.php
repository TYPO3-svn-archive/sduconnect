<?php

########################################################################
# Extension Manager/Repository config file for ext: "sduconnect"
#
# Auto generated 05-03-2009 02:09
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'SDU Connect',
	'description' => 'Connects to the Dutch database for municipalities and shows information from that database',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.4.9',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1,sso,address',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'be_users',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Dmitry Dulepov [Netcreators]',
	'author_email' => 'dmitry@netcreators.com',
	'author_company' => 'Netcreators BV',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:45:{s:9:"ChangeLog";s:4:"93a8";s:12:"ext_icon.gif";s:4:"8881";s:17:"ext_localconf.php";s:4:"b99d";s:14:"ext_tables.php";s:4:"0ba5";s:14:"ext_tables.sql";s:4:"a5a6";s:24:"ext_typoscript_setup.txt";s:4:"94e1";s:19:"flexform_ds_pi1.xml";s:4:"2c29";s:13:"locallang.xml";s:4:"b76e";s:16:"locallang_db.xml";s:4:"2431";s:42:"address/class.update_sdu_organisations.php";s:4:"5fb4";s:17:"address/clear.gif";s:4:"cc11";s:16:"address/conf.php";s:4:"995b";s:17:"address/group.png";s:4:"3afb";s:17:"address/index.php";s:4:"4d36";s:21:"address/locallang.xml";s:4:"612d";s:24:"address/locallang_db.xml";s:4:"ec46";s:25:"address/locallang_mod.xml";s:4:"41cb";s:22:"address/moduleicon.gif";s:4:"fe67";s:14:"doc/manual.sxw";s:4:"f2d1";s:17:"doc/manual_en.sxw";s:4:"1cd3";s:11:"lib/lib.php";s:4:"3aa9";s:39:"mod1/class.update_sdu_organisations.php";s:4:"5fb4";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"4f43";s:14:"mod1/index.php";s:4:"5aa1";s:18:"mod1/locallang.xml";s:4:"b283";s:21:"mod1/locallang_db.xml";s:4:"ec46";s:22:"mod1/locallang_mod.xml";s:4:"2487";s:19:"mod1/moduleicon.gif";s:4:"4c17";s:14:"pi1/ce_wiz.gif";s:4:"ae5d";s:31:"pi1/class.tx_sduconnect_pi1.php";s:4:"8d12";s:39:"pi1/class.tx_sduconnect_pi1_wizicon.php";s:4:"af97";s:13:"pi1/clear.gif";s:4:"cc11";s:17:"pi1/locallang.xml";s:4:"8703";s:20:"pi1/static/setup.txt";s:4:"80dd";s:22:"resources/address.html";s:4:"0168";s:19:"resources/jquery.js";s:4:"bb12";s:20:"resources/login.html";s:4:"6da7";s:16:"resources/sso.js";s:4:"d41d";s:13:"sso/clear.gif";s:4:"cc11";s:12:"sso/conf.php";s:4:"80f4";s:13:"sso/index.php";s:4:"15d6";s:17:"sso/locallang.xml";s:4:"f7ab";s:21:"sso/locallang_mod.xml";s:4:"81a2";s:18:"sso/moduleicon.gif";s:4:"a9f2";}',
	'suggests' => array(
	),
);

?>