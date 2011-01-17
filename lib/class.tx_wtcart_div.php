<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Alex Kellner <alexander.kellner@einpraegsam.net>
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

require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Plugin 'Cart' for the 'wt_cart' extension.
 *
 * @author	Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package	TYPO3
 * @subpackage	tx_wtcart
 */
class tx_wtcart_div extends tslib_pibase {

	public $prefixId = 'tx_wtcart_pi1';
	// Same as class name
	public $scriptRelPath = 'pi1/class.tx_wtcart_pi1.php';
	// Path to any file in pi1 for locallang
	public $extKey = 'wt_cart'; // The extension key.

	/**
	 * Add product to session
	 *
	 * @param	array		$parray: product array like
	 * 		array (
	 * 			'title' => 'this is the title',
	 * 			'amount' => 2,
	 * 			'price' => '1,49',
	 * 			'tax' => 1,
	 * 			'puid' => 234
	 * 		)
	 * @param	array		$pobj: Parent Object
	 * @return	void
	 */
	public function addProduct2Session($parray, $pObj) {
		if (empty($parray['price']) || empty($parray['title'])) {
			return false;
		}

		$sesArray = array();
		$sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session
		// check if this puid already exists and when delete it
		foreach ((array) $sesArray as $key => $value) { // one loop for every product
			if (is_array($value)) {
				if ($value['puid'] == $parray['puid']) { // current product found
					unset($sesArray[$key]); // delete old value
				}
			}
		}

		if (isset($parray['price'])) {
			$parray['price'] = str_replace(',', '.', $parray['price']); // comma to point
		}

		$sesArray[] = $parray; // add new values to array

		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', $sesArray); // Generate Session with session array
		$GLOBALS['TSFE']->storeSessionData(); // Save session
	}

	/**
	 * Remove product from session with given uid
	 *
	 * @param	int			$uid: product uid to remove
	 * @return	void
	 */
	public function removeProductFromSession($uid) {
		$sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

		foreach ((array) $sesArray as $key => $value) { // one loop for every product
			if ($sesArray[$key]['puid'] == intval($uid)) { // uid fits
				unset($sesArray[$key]); // delete old value
			}
		}

		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', $sesArray); // Generate new session
		$GLOBALS['TSFE']->storeSessionData(); // Save session
	}

	/**
	 * Clear complete session
	 *
	 * @return	void
	 */
	public function removeAllProductsFromSession() {
		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', array()); // Generate new session with empty array
		$GLOBALS['TSFE']->storeSessionData(); // Save session
	}

	/**
	 * Change quantity of a product in session
	 *
	 * @param	array			$arr: array to change
	 * @return	void
	 */
	public function changeQtyInSession($arr) {
		$sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

		foreach ((array) $sesArray as $key => $value) { // one loop for every product
			if (array_key_exists($sesArray[$key]['puid'], $arr)) { // if current puid exists in given arr
				if ($arr[$sesArray[$key]['puid']] > 0) { // if new qty is > 0
					$sesArray[$key]['qty'] = intval($arr[$sesArray[$key]['puid']]); // overwrite with new qty
				} else { // new qty == 0
					$this->removeProductFromSession($sesArray[$key]['puid']); // remove product complete from session
					unset($sesArray[$key]); // delete from this array (should not be saved again later)
				}
			}
		}

		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', $sesArray); // Generate new session
		$GLOBALS['TSFE']->storeSessionData(); // Save session
	}

	/**
	 * Change the shipping method in session
	 *
	 * @param	array			$arr: array to change
	 * @return	void
	 */
	public function changeShippingInSession($value) {
		$sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

		$sesArray['shipping'] = intval($value); // overwrite with new qty

		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', $sesArray); // Generate new session
		$GLOBALS['TSFE']->storeSessionData(); // Save session
	}

	/**
	 * get the shipping method from session
	 *
	 * @param	array			$arr: array to change
	 * @return	void
	 */
	public function getShippingFromSession() {
		$sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

		return $sesArray['shipping'];
	}

	/**
	 * Change the payment method in session
	 *
	 * @param	array			$arr: array to change
	 * @return	void
	 */
	public function changePaymentInSession($value) {
		$sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

		$sesArray['payment'] = intval($value); // overwrite with new qty

		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', $sesArray); // Generate new session
		$GLOBALS['TSFE']->storeSessionData(); // Save session
	}

	/**
	 * get the shipping method from session
	 *
	 * @param	array			$arr: array to change
	 * @return	void
	 */
	public function getPaymentFromSession() {
		$sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

		return $sesArray['payment'];
	}

	/**
	 * Read products from session
	 *
	 * @return	array		$arr: array with all products from session
	 */
	public function getProductsFromSession() {
		$sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

		unset($sesArray['shipping']);
		unset($sesArray['payment']);
		return $sesArray;
	}

	/**
	 * Read productdetails (title, price from table)
	 *
	 * @param	array		$gpvar: array with product uid, title, tax, etc...
	 * @param	array		$pobj: Parent Object
	 * @return	array		$arr: array with title and price
	 */
	public function getProductDetails($gpvar, $pObj) {

		if (!empty($gpvar['title']) && !empty($gpvar['price']) && !empty($gpvar['tax'])) { // all values already filled via POST or GET param
			return $gpvar;
		}

		if (intval($gpvar['puid']) === 0) { // stop if no puid given
			return false;
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						$pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['title'] . ', ' . $pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['price'] . ', ' . $pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['tax'],
						$pObj->conf['db.']['table'],
						$where_clause = $pObj->conf['db.']['table'] . '.uid = ' . intval($gpvar['puid']) . tslib_cObj::enableFields($pObj->conf['db.']['table']),
						$groupBy = '',
						$orderBy = '',
						$limit = 1
		);
		if ($res) { // If there is a result
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$arr = array(
				'title' => $row[$pObj->conf['db.']['title']],
				'price' => $row[$pObj->conf['db.']['price']],
				'tax' => $row[$pObj->conf['db.']['tax']],
				'puid' => $gpvar['puid']
			);

			return $arr;
		}
	}

	/**
	 * Add flexform values to conf array
	 *
	 * @param	array		$pobj: Parent Object
	 * @return	void
	 */
	public function flex2conf(&$pObj) {
		if (is_array($pObj->cObj->data['pi_flexform']['data'])) { // if there are flexform values
			foreach ($pObj->cObj->data['pi_flexform']['data'] as $key => $value) { // every flexform category
				if (count($pObj->cObj->data['pi_flexform']['data'][$key]['lDEF']) > 0) { // if there are flexform values
					foreach ($pObj->cObj->data['pi_flexform']['data'][$key]['lDEF'] as $key2 => $value2) { // every flexform option
						if ($pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], $key2, $key)) { // if value exists in flexform
							$pObj->conf[$key . '.'][$key2] = $pObj->pi_getFFvalue($pObj->cObj->data['pi_flexform'], $key2, $key); // overwrite $conf
						}
					}
				}
			}
		}
	}

	/**
	 * Returns message with optical flair
	 *
	 * @param	string		$str: Message to show
	 * @param	int			$pos: Is this a positive message? (0,1,2)
	 * @param	boolean		$die: Process should be died now
	 * @param	boolean		$prefix: Activate or deactivate prefix "$extKey: "
	 * @param	string		$id: id to add to the message (maybe to do some javascript effects)
	 * @return	string		$string: Manipulated string
	 */
	public function msg($str, $pos = 0, $die = 0, $prefix = 1, $id = '') {
		// config
		if ($prefix)
			$string = $this->extKey . ($pos != 1 && $pos != 2 ? ' Error' : '') . ': ';  // Add prefix
 $string .= $str; // add string
		$URLprefix = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . '/'; // URLprefix with domain
		if (t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/' != t3lib_div::getIndpEnv('TYPO3_SITE_URL')) { // if request_host is different to site_url (TYPO3 runs in a subfolder)
			$URLprefix .= str_replace(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/', '', t3lib_div::getIndpEnv('TYPO3_SITE_URL')); // add folder (like "subfolder/")
		}

		// let's go
		switch ($pos) {
			default: // error
				$wrap = '<div class="' . $this->extKey . '_msg_error" style="background-color: #FBB19B; background-position: 4px 4px; background-image: url(' . $URLprefix . 'typo3/gfx/error.png); background-repeat: no-repeat; padding: 5px 30px; font-weight: bold; border: 1px solid #DC4C42; margin-bottom: 20px; font-family: arial, verdana; color: #444; font-size: 12px;"';
				if ($id)
					$wrap .= ' id="' . $id . '"'; // add css id
 $wrap .= '>';
				break;

			case 1: // success
				$wrap = '<div class="' . $this->extKey . '_msg_status" style="background-color: #CDEACA; background-position: 4px 4px; background-image: url(' . $URLprefix . 'typo3/gfx/ok.png); background-repeat: no-repeat; padding: 5px 30px; font-weight: bold; border: 1px solid #58B548; margin-bottom: 20px; font-family: arial, verdana; color: #444; font-size: 12px;"';
				if ($id)
					$wrap .= ' id="' . $id . '"'; // add css id
 $wrap .= '>';
				break;

			case 2: // note
				$wrap = '<div class="' . $this->extKey . '_msg_error" style="background-color: #DDEEF9; background-position: 4px 4px; background-image: url(' . $URLprefix . 'typo3/gfx/information.png); background-repeat: no-repeat; padding: 5px 30px; font-weight: bold; border: 1px solid #8AAFC4; margin-bottom: 20px; font-family: arial, verdana; color: #444; font-size: 12px;"';
				if ($id)
					$wrap .= ' id="' . $id . '"'; // add css id
 $wrap .= '>';
				break;
		}

		if (!$die) {
			return $wrap . $string . '</div>'; // return message
		} else {
			die($string); // die process and write message
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_div.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_div.php']);
}
?>