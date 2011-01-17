<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_wtcart_pi1.php', '_pi1', 'list_type', 0);

# Hook: clear powermail output if session is not filled
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_MainContentHookAfter'][] = 'EXT:wt_cart/lib/class.tx_wtcart_powermail.php:tx_wtcart_powermail';

# Hook: clear cart after submit
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_SubmitLastOne'][] = 'EXT:wt_cart/lib/class.tx_wtcart_powermail.php:tx_wtcart_powermail';
?>