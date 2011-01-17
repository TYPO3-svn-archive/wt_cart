<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Alexander Kellner <alexander.kellner@einpraegsam.net>
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
 * ************************************************************* */

require_once(t3lib_extMgm::extPath('wt_cart') . 'lib/class.tx_wtcart_div.php'); // file for div functions

/**
 * Plugin 'Cart' for the 'wt_cart' extension.
 *
 * @author	Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package	TYPO3
 * @subpackage	tx_wtcart_powermail
 */
class tx_wtcart_powermail extends tslib_pibase {

	/**
	 * Don't show powermail form if session is empty
	 *
	 * @param	string			$content: html content from powermail
	 * @param	array			$piVars: piVars from powermail
	 * @param	object			$pObj: piVars from powermail
	 * @return	void
	 */
	public function PM_MainContentAfterHook($content, $piVars, &$pObj) {
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.'];
		$piVars = t3lib_div::GPvar('tx_powermail_pi1');

		if ($piVars['mailID'] > 0 || $piVars['sendNow'] > 0) {
			return false; // stop
		}

		if ($conf['powermailContent.']['uid'] > 0 && intval($conf['powermailContent.']['uid']) == $pObj->cObj->data['uid']) { // if powermail uid isset and fits to current CE
			$div = t3lib_div::makeInstance('tx_wtcart_div'); // Create new instance for div functions
			$products = $div->getProductsFromSession(); // get products from session

			if (!is_array($products) || count($products) == 0) { // if there are no products in the session
				$pObj->content = ''; // clear content
			}
		}
	}

	/**
	 * Clear cart after submit
	 *
	 * @param	string			$content: html content from powermail
	 * @param	array			$conf: TypoScript from powermail
	 * @param	array			$session: Values in session
	 * @param	boolean			$ok: if captcha not failed
	 * @param	object			$pObj: Parent object
	 * @return	void
	 */
	public function PM_SubmitLastOneHook($content, $conf, $session, $ok, $pObj) {
		$piVars = t3lib_div::GParrayMerged('tx_powermail_pi1');
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.'];

		if ($piVars['mailID'] == $conf['powermailContent.']['uid']) { // current content uid fits to given uid in constants
			$div = t3lib_div::makeInstance('tx_wtcart_div'); // Create new instance for div functions
			$products = $div->removeAllProductsFromSession(); // clear complete cart
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_powermail.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_powermail.php']);
}
?>