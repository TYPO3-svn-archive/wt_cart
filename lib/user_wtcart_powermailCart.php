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


require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('wt_cart') . 'lib/class.tx_wtcart_div.php'); // file for div functions
require_once(t3lib_extMgm::extPath('wt_cart') . 'lib/class.tx_wtcart_dynamicmarkers.php'); // file for dynamicmarker functions

/**
 * Plugin 'Cart' for the 'powermail' extension.
 *
 * @author	Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package	TYPO3
 * @subpackage	user_wtcart_powermailCart
 */
class user_wtcart_powermailCart extends tslib_pibase {

	public $prefixId = 'tx_wtcart_pi1';
	// Same as class name
	public $scriptRelPath = 'pi1/class.tx_wtcart_pi1.php';
	// Path to any file in pi1 for locallang
	public $extKey = 'wt_cart';
	// The extension key.
	private $content;
	private $tmpl = array();
	private $outerMarkerArray = array();
	private $markerArray = array();

	/**
	 * Read and return cart from session
	 *
	 * @return	string		cart content
	 */
	public function showCart($content = '', $conf = array()) {
		global $TSFE;
		$local_cObj = $TSFE->cObj; // cObject
		$this->pi_loadLL();
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']; // get ts
		$this->conf = array_merge((array) $this->conf, (array) $conf);
		$this->tmpl['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['main.']['template']), '###WTCART_POWERMAIL###'); // Load HTML Template
		$this->tmpl['item'] = $this->cObj->getSubpart($this->tmpl['all'], '###ITEM###'); // work on subpart 2
		$this->div = t3lib_div::makeInstance('tx_wtcart_div'); // Create new instance for div functions
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_wtcart_dynamicmarkers'); // Create new instance for dynamicmarker function
		$content_item = '';
		$cartNet = $cartGross = $cartTaxReduced = $cartTaxNormal = 0;
		$shipping_option = '';
		$payment_option = '';

		// read all products from session
		$this->product = $this->div->getProductsFromSession();
		if (count($this->product) > 0) { // if there are products in the session
			foreach ((array) $this->product as $product) { // one loop for every product in session
				$product['price_total'] = $product['price'] * $product['qty']; // price total
				$local_cObj->start($product, $this->conf['db.']['table']); // enable .field in typoscript
				foreach ((array) $this->conf['settings.']['powermailCart.']['fields.'] as $key => $value) { // one loop for every param of the current product
					if (!stristr($key, '.')) { // no .
						$this->markerArray['###' . strtoupper($key) . '###'] = $local_cObj->cObjGetSingle($this->conf['settings.']['powermailCart.']['fields.'][$key], $this->conf['settings.']['powermailCart.']['fields.'][$key . '.']); // write to marker
					}
				}
				$content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['item'], $this->markerArray); // add inner html to variable

				$cartGross += $product['price_total'];
				$cartNet += ( $product['price_total'] - $local_cObj->cObjGetSingle($this->conf['settings.']['fields.']['tax'], $this->conf['settings.']['fields.']['tax.']));

				if ($product['tax'] == 1) { // reduced tax
					$cartTaxReduced += $local_cObj->cObjGetSingle($this->conf['settings.']['fields.']['tax'], $this->conf['settings.']['fields.']['tax.']); // add tax from this product to overall
				} else { // normal tax
					$cartTaxNormal += $local_cObj->cObjGetSingle($this->conf['settings.']['fields.']['tax'], $this->conf['settings.']['fields.']['tax.']); // add tax from this product to overall
				}
			}

			$subpartArray['###CONTENT###'] = $content_item; // work on subpart 3

			$cartGrossNoService = $cartGross;
			$cartNetNoService = $cartNet;

			// calculate pice incl. shipping
			$shipping_id = $this->div->getShippingFromSession();

			if ($shipping_id) {
				$shipping_gross = floatval($this->conf['shipping.']['options.'][$shipping_id . '.']['extra']);
				$cartGross += $shipping_gross;

				if ($this->conf['shipping.']['options.'][$shipping_id . '.']['tax'] == 'reduced') { // reduced tax
					$shipping_net = $shipping_gross / (1.0 + $this->conf['tax.']['reducedCalc']); // add tax from this product to overall
					$cartNet += $shipping_net;
					$cartTaxReduced += ( $shipping_gross - $shipping_net);
				} else { // normal tax
					$shipping_net = $shipping_gross / (1.0 + $this->conf['tax.']['normalCalc']); // add tax from this product to overall
					$cartNet += $shipping_net;
					$cartTaxNormal += ( $shipping_gross - $shipping_net);
				}
				$shipping_option = $this->conf['shipping.']['options.'][$shipping_id . '.']['title'] . ' (' . str_replace('.', ',', $this->conf['shipping.']['options.'][$shipping_id . '.']['extra']) . ' ' . $this->conf['main.']['currencySymbol'] . ')';
			}

			// calculate pice incl. payment
			$payment_id = $this->div->getPaymentFromSession();

			if ($payment_id) {
				$payment_gross = floatval($this->conf['payment.']['options.'][$payment_id . '.']['extra']);
				$cartGross += $payment_gross;

				if ($this->conf['payment.']['options.'][$payment_id . '.']['tax'] == 'reduced') { // reduced tax
					$payment_net = $payment_gross / (1.0 + $this->conf['tax.']['reducedCalc']); // add tax from this product to overall
					$cartNet += $payment_net;
					$cartTaxReduced += ( $payment_gross - $payment_net);
				} else { // normal tax
					$payment_net = $payment_gross / (1.0 + $this->conf['tax.']['normalCalc']); // add tax from this product to overall
					$cartNet += $payment_net;
					$cartTaxNormal += ( $payment_gross - $payment_net);
				}
				$payment_option = $this->conf['payment.']['options.'][$payment_id . '.']['title'] . ' (' . str_replace('.', ',', $this->conf['payment.']['options.'][$payment_id . '.']['extra']) . ' ' . $this->conf['main.']['currencySymbol'] . ')';
			}

			$paymentNote = $this->conf['payment.']['options.'][$payment_id . '.']['note'];

			$outerArr = array(
				'service_cost_net' => $shipping_net + $payment_net,
				'service_cost_gross' => $shipping_gross + $payment_gross,
				'cart_gross' => $cartGross,
				'cart_gross_no_service' => $cartGrossNoService,
				'cart_net' => $cartNet,
				'cart_net_no_service' => $cartNetNoService,
				'cart_tax_reduced' => $cartTaxReduced,
				'cart_tax_normal' => $cartTaxNormal,
				'payment_note' => $paymentNote,
				'shipping_option' => $shipping_option,
				'payment_option' => $payment_option
			);
			$local_cObj->start($outerArr, $this->conf['db.']['table']); // enable .field in typoscript
			foreach ((array) $this->conf['settings.']['powermailCart.']['overall.'] as $key => $value) {
				if (!stristr($key, '.')) { // no .
					$this->outerMarkerArray['###' . strtoupper($key) . '###'] = $local_cObj->cObjGetSingle($this->conf['settings.']['powermailCart.']['overall.'][$key], $this->conf['settings.']['powermailCart.']['overall.'][$key . '.']);
				}
			}

			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['all'], $this->outerMarkerArray, $subpartArray); // Get html template
			$this->content = $this->dynamicMarkers->main($this->content, $this); // Fill dynamic locallang or typoscript markers
			$this->content = preg_replace('|###.*?###|i', '', $this->content); // Finally clear not filled markers
		} else { // no products in session
			$this->content = ''; // clear content
		}

		return $this->content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/user_wtcart_powermailCart.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/user_wtcart_powermailCart.php']);
}
?>
