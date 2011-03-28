<?php

########################################################################
# Extension Manager/Repository config file for ext "wt_cart".
#
# Auto generated 18-01-2011 03:06
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shopping Cart for TYPO3',
	'description' => 'Adds shopping cart to your TYPO3 installation and utilizes powermail for checkout',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '1.2.2',
	'dependencies' => 'powermail',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Alex Kellner; Bjoern Jacob; Daniel Lorenz; Dirk Wildt',
	'author_email' => 'alexander.kellner@einpraegsam.net; bjoern.jacob@tritum.de; daniel.lorenz@capsicumnet.de; http://wildt.at.die-netzmacher.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'powermail' => '',
			'typo3' => '4.3.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:20:{s:9:"ChangeLog";s:4:"e6fb";s:12:"ext_icon.gif";s:4:"ca97";s:17:"ext_localconf.php";s:4:"6982";s:14:"ext_tables.php";s:4:"0a63";s:16:"locallang_db.xml";s:4:"854e";s:14:"doc/manual.sxw";s:4:"f31a";s:21:"doc/marker_change.txt";s:4:"36d4";s:19:"files/css/setup.txt";s:4:"77b0";s:25:"files/img/icon_delete.gif";s:4:"ad76";s:26:"files/static/constants.txt";s:4:"ba78";s:22:"files/static/setup.txt";s:4:"23ae";s:25:"files/templates/cart.html";s:4:"04f3";s:31:"files/templates/cart_table.html";s:4:"0c8e";s:27:"lib/class.tx_wtcart_div.php";s:4:"7e0e";s:38:"lib/class.tx_wtcart_dynamicmarkers.php";s:4:"1c9e";s:33:"lib/class.tx_wtcart_powermail.php";s:4:"1390";s:33:"lib/user_wtcart_powermailCart.php";s:4:"b28d";s:29:"lib/user_wtcart_userfuncs.php";s:4:"e1cd";s:27:"pi1/class.tx_wtcart_pi1.php";s:4:"d4e0";s:17:"pi1/locallang.xml";s:4:"06ef";}',
	'suggests' => array(
	),
);

?>