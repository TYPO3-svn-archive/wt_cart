<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 - Alex Kellner <alexander.kellner@einpraegsam.net>
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
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */
require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('wt_cart') . 'lib/class.tx_wtcart_div.php'); // file for div functions
require_once(t3lib_extMgm::extPath('wt_cart') . 'lib/class.tx_wtcart_dynamicmarkers.php'); // file for dynamicmarker functions

/**
 * Plugin 'Cart to powermail' for the 'wt_cart' extension.
 *
 * @author  Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package TYPO3
 * @subpackage  tx_wtcart
 * @version 1.2.2
 */
class tx_wtcart_pi1 extends tslib_pibase {

  public $prefixId = 'tx_wtcart_pi1';
  // Same as class name
  public $scriptRelPath = 'pi1/class.tx_wtcart_pi1.php';
  // Path to this script relative to the extension dir.
  public $extKey = 'wt_cart';
  // The extension key.
  private $product = array();
  private $newProduct = array();
  private $template = array();
  private $markerArray = array();
  private $outerMarkerArray = array();
  private $gpvar = array();

  /**
   * The main method of the PlugIn
   *
   * @param string    $content: The PlugIn content
   * @param array   $conf: The PlugIn configuration
   * @return  The content that is displayed on the website
   */
  public function main($content, $conf) {
    // config
    global $TSFE;
    $local_cObj = $TSFE->cObj; // cObject
    $this->conf = $conf;
    $this->pi_setPiVarDefaults();
    $this->pi_loadLL();
    $this->pi_USER_INT_obj = 1;
    $this->div = t3lib_div::makeInstance('tx_wtcart_div'); // Create new instance for div functions
    $this->dynamicMarkers = t3lib_div::makeInstance('tx_wtcart_dynamicmarkers'); // Create new instance for dynamicmarker function
    $this->tmpl['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['main.']['template']), '###WTCART###'); // Load HTML Template
    $this->tmpl['empty'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['main.']['template']), '###WTCART_EMPTY###'); // Load HTML Template
    $this->tmpl['item'] = $this->cObj->getSubpart($this->tmpl['all'], '###ITEM###'); // work on subpart 2
    $content_item = '';
    $cartNet = $cartGross = $cartTaxReduced = $cartTaxNormal = 0;

    // new for Shipping and Payment
    $this->tmpl['ship_radio_all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['main.']['template']), '###WTCART_SHIPPING###'); // Load HTML Template
    $this->tmpl['ship_radio_item'] = $this->cObj->getSubpart($this->tmpl['ship_radio_all'], '###ITEM###'); // work on subpart 2

    $this->tmpl['payment_all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['main.']['template']), '###WTCART_PAYMENT###');
    $this->tmpl['payment_item'] = $this->cObj->getSubpart($this->tmpl['payment_all'], '###ITEM###'); // work on subpart 2
    // read variables
    $this->gpvar['title'] = $this->cObj->cObjGetSingle($this->conf['settings.']['title'], $this->conf['settings.']['title.']); // get title
    $this->gpvar['price'] = $this->cObj->cObjGetSingle($this->conf['settings.']['price'], $this->conf['settings.']['price.']); // get price
    $this->gpvar['qty'] = intval($this->cObj->cObjGetSingle($this->conf['settings.']['qty'], $this->conf['settings.']['qty.'])); // get qty
    $this->gpvar['tax'] = $this->cObj->cObjGetSingle($this->conf['settings.']['tax'], $this->conf['settings.']['tax.']); // get tax
    $this->gpvar['puid'] = intval($this->cObj->cObjGetSingle($this->conf['settings.']['puid'], $this->conf['settings.']['puid.'])); // get puid
    if ($this->gpvar['qty'] === 0) { // if no qty given
      $this->gpvar['qty'] = 1; // set to 1
    }

    // debug output
    if ($this->conf['debug']) {
      t3lib_div::debug($this->div->getProductsFromSession(), $this->extKey . ': ' . 'Values in session at the beginning');
      t3lib_div::debug($this->gpvar, $this->extKey . ': ' . 'Given params');
      t3lib_div::debug($this->conf, $this->extKey . ': ' . 'Typoscript configuration');
      t3lib_div::debug($_POST, $this->extKey . ': ' . 'All POST variables');
    }

    // remove product from session
    if (isset($this->piVars['del'])) {
        // 12278, 110116, dwildt
      //$this->div->removeProductFromSession($this->piVars['del']); // remove product
      $this->div->removeProductFromSession($this); // remove product
    }

    // change qty
    if (isset($this->piVars['qty']) && is_array($this->piVars['qty'])) {
        // 12278, 110116, dwildt
      //$this->div->changeQtyInSession($this->piVars['qty']); // change qty
      $this->div->changeQtyInSession($this); // change qty
    }

    // change shipping
    if (isset($this->piVars['shipping'])) {
      $this->div->changeShippingInSession($this->piVars['shipping']); // change shipping
    }

    // change payment
    if (isset($this->piVars['payment'])) {
      $this->div->changePaymentInSession($this->piVars['payment']); // change payment
    }

    // add further product to session
    $this->newProduct = $this->div->getProductDetails($this->gpvar, $this); // get details from product
    if ($this->newProduct !== false) {
      $this->newProduct['qty'] = $this->gpvar['qty'];
      $this->div->addProduct2Session($this->newProduct, $this);
    }

    // read all products from session
    $this->product = $this->div->getProductsFromSession();


      // There are products in the session
    if (count($this->product) > 0) 
    { 
        // Loop for every product in the session
      foreach ((array) $this->product as $product) 
      {
          // price total
        $product['price_total'] = $product['price'] * $product['qty']; 
          // enable .field in typoscript
        $local_cObj->start($product, $this->conf['db.']['table']); 

          // one loop for every param of the current product
        foreach ((array) $this->conf['settings.']['fields.'] as $key => $value)
        {
          if (!stristr($key, '.')) { // no .
              // 12278, 110116, dwildt - start
            //$this->markerArray['###' . strtoupper($key) . '###'] = $local_cObj->cObjGetSingle($this->conf['settings.']['fields.'][$key], $this->conf['settings.']['fields.'][$key . '.']); // write to marker
              // Name of the current field in the TypoScript
            $ts_key   = $this->conf['settings.']['fields.'][$key];
              // Configuration array of the current field in the TypoScript
            $ts_conf  = $this->conf['settings.']['fields.'][$key . '.'];
            switch($key)
            {
              case('delete'):
                $ts_conf = $this->div->add_variation_gpvar_to_imagelinkwrap($product, $ts_key, $ts_conf, $this);
                break;
              default:
                // Nothing to do, there is no default now
            }
            $ts_rendered_value  = $local_cObj->cObjGetSingle($ts_key, $ts_conf);
            $this->markerArray['###' . strtoupper($key) . '###'] = $ts_rendered_value; // write to marker

              // Adds the ###QTY_NAME### marker in case of variations
              // 12278, 110116, dwildt
            $this->markerArray = $this->div->add_qtyname_marker($product, $this->markerArray, $this);
              // 12278, 110116, dwildt - end
          }
        }
          // one loop for every param of the current product

        $content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['item'], $this->markerArray); // add inner html to variable

        $cartGross += $product['price_total'];

          // 12282, 110116, dwildt - start
          // Get the formular with the markers ###TAX## for calculating tax
        $str_wrap = $this->conf['settings.']['fields.']['tax.']['default.']['setCurrent.']['wrap'];
          // Save the formular with marker, we need it later
        $str_wrap_former = $str_wrap;
          // Replace the ###TAX### with current tax rate like 0.07 or 0.19
        $str_wrap = str_replace('###TAX###', $product['tax'], $str_wrap);
          // Assign the forular with tax rates to TypoScript
        $this->conf['settings.']['fields.']['tax.']['default.']['setCurrent.']['wrap'] = $str_wrap;
          // 12282, 110116, dwildt - end

        $cartNet += ( $product['price_total'] - $local_cObj->cObjGetSingle($this->conf['settings.']['fields.']['tax'], $this->conf['settings.']['fields.']['tax.']));

        $curr_tax = $local_cObj->cObjGetSingle($this->conf['settings.']['fields.']['tax'], $this->conf['settings.']['fields.']['tax.']);

          // 12282, 110116, dwildt - start
//        if ($product['tax'] == 1) { // reduced tax
//          $cartTaxReduced += $curr_tax; // add tax from this product to overall
//        } else { // normal tax
//          $cartTaxNormal += $curr_tax; // add tax from this product to overall
//        }
        switch($product['tax'])
        {
          case(0):
              //:TODO: 12280, 12281, 110116, dwildt
            break;
          case(1):
          case($this->conf['tax.']['reducedCalc']):
              //:TODO: 12281, 110116, dwildt
            $cartTaxReduced += $curr_tax; // add tax from this product to overall
            break;
          case(2):
          case($this->conf['tax.']['normalCalc']):
              //:TODO: 12281, 110116, dwildt
            $cartTaxNormal += $curr_tax; // add tax from this product to overall
            break;
          default:
            echo '
              <div style="border:2em solid red;padding:2em;color:red;">
                <h1 style="color:red;">
                  wt_cart Error
                </h1>
                <p>
                  tax is "' . $product['tax'] . '".<br />
                  This is an undefined value in class.tx_wtcart_pi1.php. ABORT!<br />
                  <br />
                  Are you sure, that you included the wt_cart static template?
                </p>
              </div>';
            exit;
        }
          // 12282, 110116, dwildt - start

        $this->conf['settings.']['fields.']['tax.']['default.']['setCurrent.']['wrap'] = $str_wrap_former;
      }
        // Loop for every product in the session

      // item for payment
      $payment_id = $this->div->getPaymentFromSession();
      if ($payment_id) {
        $this->markerArray['###QTY###'] = 1;
        $this->markerArray['###TITLE###'] = $this->conf['payment.']['options.'][$payment_id . '.']['title'];
        $this->markerArray['###PRICE###'] = $this->conf['payment.']['options.'][$payment_id . '.']['extra'];
        $this->markerArray['###PRICE_TOTAL###'] = $this->conf['payment.']['options.'][$payment_id . '.']['extra'];
        $content_item .= $this->cObj->substituteMarkerArrayCached($this->tmpl['special_item'], $this->markerArray); // add inner html to variable
      }

      $subpartArray['###CONTENT###'] = $content_item; // work on subpart 3

      $cartGrossNoService = $cartGross;
      $cartNetNoService = $cartNet;

      // calculate pice incl. shipping
      $shipping_id = $this->div->getShippingFromSession();

      if (!$shipping_id) {
        $shipping_id = intval($this->conf['shipping.']['preset']);
        $this->div->changeShippingInSession($shipping_id);
      }

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

      $payment_id = $this->div->getPaymentFromSession();

      if (!$payment_id) {
        $payment_id = intval($this->conf['payment.']['preset']);
        $this->div->changePaymentInSession($shipping_id);
      }

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

      $outerArr = array(
        'service_cost_net' => $shipping_net + $payment_net,
        'service_cost_gross' => $shipping_gross + $payment_gross,
        'cart_gross' => $cartGross,
        'cart_gross_no_service' => $cartGrossNoService,
        'cart_net' => $cartNet,
        'cart_net_no_service' => $cartNetNoService,
        'cart_tax_reduced' => $cartTaxReduced,
        'cart_tax_normal' => $cartTaxNormal
      );
      $local_cObj->start($outerArr, $this->conf['db.']['table']); // enable .field in typoscript
      foreach ((array) $this->conf['settings.']['overall.'] as $key => $value) {
        if (!stristr($key, '.')) { // no .
          $this->outerMarkerArray['###' . strtoupper($key) . '###'] = $local_cObj->cObjGetSingle($this->conf['settings.']['overall.'][$key], $this->conf['settings.']['overall.'][$key . '.']);
        }
      }

      // Code for Shipping
      $shipping_radio_list = '';
      foreach ((array) $this->conf['shipping.']['options.'] as $key => $value) {
        $checkradio = intval($key) == $shipping_id ? 'checked="checked"' : '';
        $this->smarkerArray['###SHIPPING_RADIO###'] = '<input type="radio" onchange="this.form.submit()" name="tx_wtcart_pi1[shipping]" id="tx_wtcart_pi1_shipping_' . intval($key) . '" value="' . intval($key) . '" ' . $checkradio . '/>'; // write to marker
        $this->smarkerArray['###SHIPPING_TITLE###'] = '<label for="tx_wtcart_pi1_shipping_' . intval($key) . '">' . $value['title'] . ' (' . str_replace('.', ',', $value['extra']) . ' ' . $this->conf['main.']['currencySymbol'] . ')</label>'; // write to marker
        $shipping_radio_list .= $this->cObj->substituteMarkerArrayCached($this->tmpl['ship_radio_item'], $this->smarkerArray);
      }
      $shippingArray['###CONTENT###'] = $shipping_radio_list;
      $subpartArray['###SHIPPING_RADIO###'] = $this->cObj->substituteMarkerArrayCached($this->tmpl['ship_radio_all'], null, $shippingArray);

      // Code for Payment
      $payment_radio_list = '';
      foreach ((array) $this->conf['payment.']['options.'] as $key => $value) {
        $checkradio = intval($key) == $payment_id ? 'checked="checked"' : '';
        $this->smarkerArray['###PAYMENT_RADIO###'] = '<input type="radio" onchange="this.form.submit()" name="tx_wtcart_pi1[payment]" id="tx_wtcart_pi1_payment_' . intval($key) . '"  value="' . intval($key) . '"  ' . $checkradio . '/>'; // write to marker
        $this->smarkerArray['###PAYMENT_TITLE###'] = '<label for="tx_wtcart_pi1_payment_' . intval($key) . '">' . $value['title'] . ' (' . str_replace('.', ',', $value['extra']) . ' ' . $this->conf['main.']['currencySymbol'] . ')</label>'; // write to marker
        $payment_radio_list .= $this->cObj->substituteMarkerArrayCached($this->tmpl['payment_item'], $this->smarkerArray);
      }
      $paymentArray['###CONTENT###'] = $payment_radio_list;
      $subpartArray['###PAYMENT_RADIO###'] = $this->cObj->substituteMarkerArrayCached($this->tmpl['payment_all'], null, $paymentArray);
    }
      // There are products in the session

      // There isn't any product in the session
    if (!(count($this->product) > 0)) 
    { 
      if (!empty($this->tmpl['all'])) { // if template found
        $this->tmpl['all'] = $this->tmpl['empty']; // overwrite normal template with empty template
      } else { // no template - show error
        $this->tmpl['all'] = $this->div->msg($this->pi_getLL('error_noTemplate', 'No Template found'));
      }
    }
      // There isn't any product in the session

    $this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['all'], $this->outerMarkerArray, $subpartArray); // Get html template
    $this->content = $this->dynamicMarkers->main($this->content, $this); // Fill dynamic locallang or typoscript markers
    $this->content = preg_replace('|###.*?###|i', '', $this->content); // Finally clear not filled markers
    return $this->pi_wrapInBaseClass($this->content);
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/pi1/class.tx_wtcart_pi1.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/pi1/class.tx_wtcart_pi1.php']);
}
?>
