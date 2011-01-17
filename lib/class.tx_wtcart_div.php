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

require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Plugin 'Cart' for the 'wt_cart' extension.
 *
 * @author  Alex Kellner <alexander.kellner@einpraegsam.net>
 * @package TYPO3
 * @subpackage  tx_wtcart
 * @version 1.2.2
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
   * @param array   $parray: product array like
   *    array (
   *      'title' => 'this is the title',
   *      'amount' => 2,
   *      'price' => '1,49',
   *      'tax' => 1,
   *      'puid' => 234
   *    )
   * @param array   $pobj: Parent Object
   * @return  void
   */
  public function addProduct2Session($parray, $pObj) {
    
      // RETURN without price or without title
    if (empty($parray['price']) || empty($parray['title'])) 
    {
      return false;
    }
      // RETURN without price or without title

      // Variations
    $arr_variation['puid'] = $parray['puid'];
      // Add variation keys from ts settings.variations array,
      //   if there is a corresponding key in GET or POST
    if(is_array($pObj->conf['settings.']['variation.']))
    {
      $arr_get  = t3lib_div::_GET();
      $arr_post = t3lib_div::_POST();
      foreach($pObj->conf['settings.']['variation.'] as $key => $tableField)
      {
        list($table, $field) = explode('.', $tableField);
        if(isset($arr_get[$table][$field]))
        {
          $arr_variation[$tableField] = mysql_escape_string($arr_get[$table][$field]);
        }
        if(isset($arr_post[$table][$field]))
        {
          $arr_variation[$tableField] = mysql_escape_string($arr_post[$table][$field]);
        }
      }
      // Add variation keys from ts settings.variations array,
    }
      // Variations
    
    $sesArray = array();
      // get already exting products from session
    $sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart');
      // check if this puid already exists and when delete it
    foreach ((array) $sesArray as $key => $value) { // one loop for every product
      if (is_array($value)) {
          // 12278, 110116, dwildt - start
//        if ($value['puid'] == $parray['puid']) { // current product found
//          unset($sesArray[$key]); // delete old value
//        }
          // Counter for condition. Every condition has to be true
        $int_counter = 0;
        
          // Loop for every condition
        foreach($arr_variation as $key_variation => $value_variation)
        {
            // Condition fits
          if ($value[$key_variation] == $value_variation)
          {
            $int_counter++;
          }
        }
          // Loop for every condition

          // All conditions fit
        if($int_counter == count($arr_variation))
        {
            // Remove product 
          unset($sesArray[$key]);
        }
          // 12278, 110116, dwildt - end
      }
    }

    if (isset($parray['price'])) {
      $parray['price'] = str_replace(',', '.', $parray['price']); // comma to point
    }
    
      // Remove puid from variation array
    unset($arr_variation[0]);

      // Add variation key/value pairs to the current product
      // 12278, 110116, dwildt
    if(!empty($arr_variation))
    {
      foreach($arr_variation as $key_variation => $value_variation)
      {
        $parray[$key_variation] = $value_variation;
      }
    }
      // Add variation key/value pairs to the current product

      // Add product to the session array
    $sesArray[] = $parray;

      // Generate session with session array
    $GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', $sesArray);
      // Save session
    $GLOBALS['TSFE']->storeSessionData();
  }

  /**
   * Remove product from session with given uid
   *
   * @param array   $pobj: Parent Object
   * @return  void
   * @version 1.2.2
   */

    // 12278, 110116, dwildt
  //public function removeProductFromSession($uid) {
  public function removeProductFromSession($pObj)
  {
      // 12278, 110116, dwildt - start
      // Variations
      // Add variation key/value pairs from piVars
    $arr_variation = $this->get_variation_from_piVars($pObj);
      // Add product id to variation array
    $arr_variation['puid'] = $pObj->piVars['del'];
      // 12278, 110116, dwildt - end

      // Get products from session array
    $sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

      // Loop for every product
    foreach ((array) $sesArray as $key => $value) 
    {
        // 12278, 110116, dwildt - start
//      if ($sesArray[$key]['puid'] == intval($uid)) { // uid fits
//        unset($sesArray[$key]); // delete old value
//      }
        // Counter for condition
      $int_counter = 0;
      
        // Loop through conditions
      foreach($arr_variation as $key_variation => $value_variation)
      {
          // Condition fits
        if ($sesArray[$key][$key_variation] == $value_variation) 
        {
          $int_counter++;
        }
      }
        // Loop through conditions

        // All conditions fit
      if($int_counter == count($arr_variation))
      {
          // Remove product from session
        unset($sesArray[$key]);
      }
        // 12278, 110116, dwildt - end
    }
      // Loop for every product

      // Generate new session
    $GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', $sesArray);
      // Save session
    $GLOBALS['TSFE']->storeSessionData();
  }

  /**
   * Clear complete session
   *
   * @return  void
   */
  public function removeAllProductsFromSession() {
    $GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', array()); // Generate new session with empty array
    $GLOBALS['TSFE']->storeSessionData(); // Save session
  }

  /**
   * Change quantity of a product in session
   *
   * @param array   $pobj: Parent Object
   * @return  void
   * @version 1.2.2
   */

    // 12278, 110116, dwildt
  //public function changeQtyInSession($arr) {
  public function changeQtyInSession($pObj)
  {
      // 12278, 110116, dwildt - start
      // Variations
      // Add variation key/value pairs from piVars
    $arr_variation = $this->get_variation_from_qty($pObj);
    if(!is_array($arr_variation))
    {
        // We need the one element at least for the loop below 
      $arr_variation = array('dummy');
    }
      // 12278, 110116, dwildt - end

      // get products from session
    $sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart');

      // Loop for every product
    foreach ((array) $sesArray as $key_session => $value)
    {
        // 12278, 110116, dwildt - start
        // Current product id
      $session_puid = $sesArray[$key_session]['puid'];
      
        // Loop for every variation
      $arr_variation_backup = $arr_variation;
      foreach($arr_variation as $key_variation => $arr_condition)
      {
        if(!isset($arr_variation[$key_variation]['puid']))
        {
            // Without variation
          $curr_puid = key($pObj->piVars['qty']);
        }
        if(isset($arr_variation[$key_variation]['puid']))
        {
          $curr_puid = $arr_variation[$key_variation]['puid'];
        }
        if(!isset($arr_variation[$key_variation]['qty']))
        {
            // Without variation
          $int_qty = intval($pObj->piVars['qty'][$curr_puid]);
        }
        if(isset($arr_variation[$key_variation]['qty']))
        {
          $int_qty = intval($arr_variation[$key_variation]['qty']);
        }

          // Counter for condition
        $int_counter = 0;
          // puid: condition fits
        if ($session_puid == $curr_puid) 
        {
          //$prompt_305 = 'div 305: true - session_key : ' . $key_session . ', puid : ' . $session_puid;
          $int_counter++;
        }

          // Loop through conditions
        foreach($arr_condition as $key_condition => $value_condition)
        {
            // Workaround (it would be better, if qty and puid won't be elements of $arr_condition
          if(in_array($key_condition, array('qty', 'puid')))
          {
              // Workaround: puid and qty should fit in every case
            $int_counter++;
          }
            // Workaround (it would be better, if qty and puid won't be elements of $arr_condition
          if(!in_array($key_condition, array('qty', 'puid')))
          {
              // variants: condition fits
            if ($sesArray[$key_session][$key_condition] == $value_condition) 
            {
              //$prompt_315[] = 'div 315: true - session_key : ' . $key_session . ', ' . $key_condition . ' : ' . $value_condition;
              $int_counter++;
            }
          }
        }
          // Loop through conditions
  
          // All conditions fit
        if($int_counter == (count($arr_condition) + 1))
        {
          //var_dump('div 325: all conditions are true', $prompt_305, $prompt_315);
          switch (true)
          {
            case($int_qty > 0):
                // Update quantity
              $sesArray[$key_session]['qty'] = $int_qty;
              break;
            default:
                // Remove product from session
              $this->removeProductFromSession($sesArray[$key_session]['puid']);
                // Remove product from current session array
              unset($sesArray[$key_session]);
          }
        }
          // 12278, 110116, dwildt - end
        //unset($prompt_305);
        //unset($prompt_315);

      }
      $arr_variation = $arr_variation_backup;
        // Loop for every variation
    }
      // Loop for every product

      // Generate new session
    $GLOBALS['TSFE']->fe_user->setKey('ses', $this->extKey . '_cart', $sesArray);
      // Save session 
    $GLOBALS['TSFE']->storeSessionData();
  }

  /**
   * Change the shipping method in session
   *
   * @param array     $arr: array to change
   * @return  void
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
   * @param array     $arr: array to change
   * @return  void
   */
  public function getShippingFromSession() {
    $sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

    return $sesArray['shipping'];
  }

  /**
   * Change the payment method in session
   *
   * @param array     $arr: array to change
   * @return  void
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
   * @param array     $arr: array to change
   * @return  void
   */
  public function getPaymentFromSession() {
    $sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session

    return $sesArray['payment'];
  }

  /**
   * Read products from session
   *
   * @return  array   $arr: array with all products from session
   */
  public function getProductsFromSession() {
    $sesArray = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->extKey . '_cart'); // get already exting products from session
    unset($sesArray['shipping']);
    unset($sesArray['payment']);
    return $sesArray;
  }

  /**
   * Read productdetails (title, price from table)
   * The method getProductDetails of version 1.2.1 became getProductDetails_ts from version 1.2.2
   *
   * @param array   $gpvar: array with product uid, title, tax, etc...
   * @param array   $pobj: Parent Object
   * @return  array   $arr: array with title and price
   * @author dwildt
   * @version 1.2.2
   * @since 1.2.2
   */
  public function getProductDetails($gpvar, $pObj) {

      // 12278, 110116, dwildt

      // SQL property has precedence!
      // handle query by db.sql
    if(!empty($pObj->conf['db.']['sql'])) {
      return $this->getProductDetails_sql($gpvar, $pObj);
    }
      // handle query by db.sql

      // handle query by db.table and db.fields
    return $this->getProductDetails_ts($gpvar, $pObj);
  }

  /**
   * Read productdetails by a manually configured sql query
   *
   * @param array   $gpvar: array with product uid, title, tax, etc...
   * @param array   $pobj: Parent Object
   * @return  array   $arr: array with title and price
   * @author dwildt
   * @version 1.2.2
   * @since 1.2.2
   */
  public function getProductDetails_sql($gpvar, $pObj) {

      // 12278 + 12283, 110116, dwildt

      // RETURN: There aren't any GET nor POST data
    $bool_return = true;
    $arr_get = t3lib_div::_GET();
    if(!empty($arr_get))
    {
      $bool_return = false;
    }
    $arr_post = t3lib_div::_POST();
    if(!empty($arr_post))
    {
      $bool_return = false;
    }
    if($bool_return) 
    {
      return;
    }
      // RETURN: There aren't any GET nor POST data

      // Replace gp:marker and enable_fields:marker in $pObj->conf['db.']['sql']
    $this->zz_replace_marker_in_sql($gpvar, $pObj);
      // Get the SQL query from ts
    $query  = $pObj->conf['db.']['sql'];
    //var_dump('div 319', $query);
      // Execute the query
    $res    = $GLOBALS['TYPO3_DB']->sql_query($query);
    $error  = $GLOBALS['TYPO3_DB']->sql_error();  // 12254, 110116, dwildt

      // EXIT in case of error
    if(!empty($error)) {
      $str_header  = '<h1 style="color:red">wt_cart: SQL-Error</h1>';
      $str_prompt  = '<p style="font-family:monospace;font-size:smaller;padding-top:2em;">'.$error.'</p>';
      $str_prompt .= '<p style="font-family:monospace;font-size:smaller;padding-top:2em;">'.$query.'</p>';
      echo $str_header . $str_prompt;
      exit;
    }
      // EXIT in case of error

      // RETURN the row
    if ($res) {
      while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) 
      {
        if($row['title'] != null)
        {
          break;
        }
      }  
      //$row['tax']   = 2;
      $row['puid']  = $gpvar['puid'];
//var_dump('div 344', $str_wrap);
      return $row;
    }
      // RETURN the row

    return false;
  }

  /**
   * Read productdetails (title, price from table)
   *
   * @param array   $gpvar: array with product uid, title, tax, etc...
   * @param array   $pobj: Parent Object
   * @return  array   $arr: array with title and price
   */
  public function getProductDetails_ts($gpvar, $pObj) {

    if (!empty($gpvar['title']) && !empty($gpvar['price']) && !empty($gpvar['tax'])) { // all values already filled via POST or GET param
      return $gpvar;
    }

    if (intval($gpvar['puid']) === 0) { // stop if no puid given
      return false;
    }

      // 12254, 110116, dwildt -start
    //$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
    //        $pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['title'] . ', ' . $pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['price'] . ', ' . $pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['tax'],
    //        $pObj->conf['db.']['table'],
    //        $where_clause = $pObj->conf['db.']['table'] . '.uid = ' . intval($gpvar['puid']) . tslib_cObj::enableFields($pObj->conf['db.']['table']),
    //        $groupBy = '',
    //        $orderBy = '',
    //        $limit = 1
    //);
    $select_fields = $pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['title'] . ', ' . 
                     $pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['price'] . ', ' . 
                     $pObj->conf['db.']['table'] . '.' . $pObj->conf['db.']['tax'];
    $from_table    = $pObj->conf['db.']['table'];
    $where_clause  = $pObj->conf['db.']['table'] . '.uid = ' . intval($gpvar['puid']) . tslib_cObj::enableFields($pObj->conf['db.']['table']);
    $groupBy       = '';
    $orderBy       = '';
    $limit         = 1;

    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit);
      // 12254, 110116, dwildt - end


    if ($res) { // If there is a result
      $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
      $arr = array(
        'title' => $row[$pObj->conf['db.']['title']],
        'price' => $row[$pObj->conf['db.']['price']],
        'tax'   => $row[$pObj->conf['db.']['tax']],
        'puid'  => $gpvar['puid']
      );

      return $arr;
    }

      // 12254, 110116, dwildt - start
      // Check, if there is an error. Prompt it!
    if (!$res) {
      $query = $GLOBALS['TYPO3_DB']->SELECTquery($select_fields,$from_table,$where_clause,$groupBy,$orderBy,$limit,$uidIndexField="");
      $res   = $GLOBALS['TYPO3_DB']->sql_query($query);
      $error = $GLOBALS['TYPO3_DB']->sql_error();
      if(!empty($error)) {
        $str_header  = '<h1 style="color:red">wt_cart: SQL-Error</h1>';
        $str_prompt  = '<p style="font-family:monospace;font-size:smaller;padding-top:2em;">'.$error.'</p>';
        $str_prompt .= '<p style="font-family:monospace;font-size:smaller;padding-top:2em;">'.$query.'</p>';
        echo $str_header . $str_prompt;
        exit;
      }
    }
      // 110115, dwildt - end
  }

  /**
   * Add flexform values to conf array
   *
   * @param array   $pobj: Parent Object
   * @return  void
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
   * @param string    $str: Message to show
   * @param int     $pos: Is this a positive message? (0,1,2)
   * @param boolean   $die: Process should be died now
   * @param boolean   $prefix: Activate or deactivate prefix "$extKey: "
   * @param string    $id: id to add to the message (maybe to do some javascript effects)
   * @return  string    $string: Manipulated string
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
  /**
   * add_variation_gpvar_to_imagelinkwrap():  Adds all table.field of the variation to
   *                                          imageLinkWrap.typolink.additionalParams.wrap
   *
   * @param array   $product: array with product uid, title, tax, etc...
   * @param string   $ts_key: key of the current TypoScript configuration array
   * @param array   $ts_conf: the current TypoScript configuration array
   * @param array   $pobj: Parent Object
   * @return  array  $ts_conf: configuration array added with the varaition gpvars
   * @author dwildt
   * @version 1.2.2
   * @since 1.2.2
   */
  public function add_variation_gpvar_to_imagelinkwrap($product, $ts_key, $ts_conf, $pObj)
  {
      // RETURN there isn't any variation
    if(!is_array($pObj->conf['settings.']['variation.']))
    {
      return $ts_conf;
    }
      // RETURN there isn't any variation

      // Get all variation key/value pairs from the current product
    $array_add_gpvar = $this->get_variation_from_product($product, $pObj);
      
      // Add variation key/value pairs to imageLinkWrap
    foreach((array) $array_add_gpvar as $key => $value)
    {
      $str_wrap = $ts_conf['imageLinkWrap.']['typolink.']['additionalParams.']['wrap'];
      $str_wrap = $str_wrap . '&' . $this->prefixId . '[' . $key . ']=' . $value;
      $ts_conf['imageLinkWrap.']['typolink.']['additionalParams.']['wrap'] = $str_wrap;
    }
      // Add variation key/value pairs to imageLinkWrap
    
    return $ts_conf;
  }

  /**
   * add_qty_marker():  Allocates to the global markerArray a value for ###QTY_NAME###
   *                          in case of variation
   *                          It returns in aray with hidden fields like
   *                          <input type="hidden" 
   *                                 name="tx_wtcart_pi1[puid][20][]" 
   *                                 value="tx_wtcart_pi1[tx_org_calentrance.uid]=4|tx_wtcart_pi1[qty]=91" />
   *
   * @param array   $products: array with products with elements uid, title, tax, etc...
   * @param array   $markerArray: current marker array
   * @param array   $pobj: Parent Object
   * @return  array   $markerArray: with added element ###VARIATIONS### in case of variations
   * @author dwildt
   * @version 1.2.2
   * @since 1.2.2
   */
  public function add_qtyname_marker($product, $markerArray, $pObj)
  {
      // Default name for QTY. It is compatible with version 1.2.1
    $markerArray['###QTY_NAME###'] = 'tx_wtcart_pi1[qty][' . $product['puid'] . ']';

      // RETURN there isn't any variation
    if(!is_array($pObj->conf['settings.']['variation.']))
    {
      return $markerArray;
    }

//                      name="tx_wtcart_pi1[qty][puid=###PUID###|tx_org_calentrance.uid=4]" 

    $str_marker = null;
      // Get all variation key/value pairs from the current product
    $array_add_gpvar = $this->get_variation_from_product($product, $pObj);
    $array_add_gpvar['puid']  = $product['puid'];
      // Generate the marker array
    foreach((array) $array_add_gpvar as $key => $value)
    {
      $str_marker = $str_marker . '[' . $key . '=' . $value . ']';
    }
    $markerArray['###QTY_NAME###'] = 'tx_wtcart_pi1[qty]'. $str_marker;

    return $markerArray;
  }

  /**
   * get_variation_from_product():  Get an array with the variation values
   *                                out of the current product
   *
   * @param array   $product: array with product uid, title, tax, etc...
   * @param array   $pobj: Parent Object
   * @return  array   $arr_variations: array with variation key/value pairs
   * @author dwildt
   * @version 1.2.2
   * @since 1.2.2
   */
  private function get_variation_from_product($product, $pObj)
  {
    $arr_variations = null;

      // RETURN there isn't any variation
    if(!is_array($pObj->conf['settings.']['variation.']))
    {
      return $arr_variations;
    }
      // RETURN there isn't any variation

      // Loop through ts array variation
    foreach($pObj->conf['settings.']['variation.'] as $key_variation)
    {
        // product contains variation key from ts 
      if(in_array($key_variation, array_keys($product)))
      {
        $arr_variations[$key_variation] = $product[$key_variation];
        if(empty($arr_variations[$key_variation]))
        {
          unset($arr_variations[$key_variation]);
        }
      }
    }
      // Loop through ts array variation
      
    return $arr_variations;
  }

  /**
   * get_variation_from_piVars(): Get variation values from piVars
   *                              Variation values have to be content of
   *                              ts array variation and of piVars
   *
   * @param array   $product: array with product uid, title, tax, etc...
   * @param array   $pobj: Parent Object
   * @return  array   $arr_variations: array with variation key/value pairs
   * @author dwildt
   * @version 1.2.2
   * @since 1.2.2
   */
  private function get_variation_from_piVars($pObj)
  {
    $arr_variation = null;

      // RETURN there isn't any variation
    if(!is_array($pObj->conf['settings.']['variation.']))
    {
      return $arr_variation;
    }
      // RETURN there isn't any variation
      
      // Loop through ts variation array
    foreach($pObj->conf['settings.']['variation.'] as $key => $tableField)
    {
      list($table, $field) = explode('.', $tableField);
        // piVars contain variation key 
      if(!empty($pObj->piVars[$tableField]))
      {
        $arr_variation[$tableField] = mysql_escape_string($pObj->piVars[$tableField]);
      }
    }
      // Loop through ts variation array

    return $arr_variation;
  }
  /**
   * get_variation_from_qty(): Get variation values out of the name of the qty field
   *                              Variation values have to be content of
   *                              ts array variation and of qty field
   *
   * @param array   $product: array with product uid, title, tax, etc...
   * @param array   $pobj: Parent Object
   * @return  array   $arr_variations: array with variation key/value pairs
   * @author dwildt
   * @version 1.2.2
   * @since 1.2.2
   */
  private function get_variation_from_qty($pObj)
  {
    $arr_variation = null;

      // RETURN there isn't any variation
    if(!is_array($pObj->conf['settings.']['variation.']))
    {
      return $arr_variation;
    }
      // RETURN there isn't any variation


      // Example for the piVars['qty']
//  ["qty"]=>
//  array(1) {
//    ["tx_org_calentrance.uid=4"]=>
//    array(1) {
//      ["puid=20"]=>
//      string(3) "123"
//    }
//  }


    $int_counter = 0;
    foreach($pObj->piVars['qty'] as $key => $value)
    {
      $arr_qty[$int_counter]['qty'][$key] = $value;
      $int_counter++;
    }

    foreach($arr_qty as $key => $piVarsQty)
    {
        // Iterator object
      $data     = new RecursiveArrayIterator( $piVarsQty['qty'] );
      $iterator = new RecursiveIteratorIterator( $data, true );
        // Top level of ecursive array
      $iterator->rewind();
   
        // Get all variation key/value pairs from qty name
      foreach ($iterator as $key_iterator => $value_iterator)
      {
          // I.e for a key: tx_org_calentrance.uid=4
        list($key_variation, $value_variation) = explode('=', $key_iterator);
        if($key_variation == 'puid')
        {
          $arr_variation[$key]['puid'] = $value_variation;
        }
          // I.e arr_var[tx_org_calentrance.uid] = 4
        $arr_from_qty[$key][$key_variation] = $value_variation;
        if(is_array($value_iterator))
        {
          list($key_variation, $value_variation) = explode('=', key($value_iterator));
          if($key_variation == 'puid')
          {
            $arr_variation[$key]['puid'] = $value_variation;
          }
          $arr_from_qty[$key][$key_variation] = $value_variation;
        }
          // Value is the value of the field qty in every case
        if(!is_array($value_iterator))
        {
          $arr_variation[$key]['qty'] = $value_iterator;
        }
      }
//var_dump('div 855', $arr_variation[$key]['qty']);
        // Get all variation key/value pairs from qty name

        // Loop through ts variation array
      foreach($pObj->conf['settings.']['variation.'] as $key_variation => $tableField)
      {
        list($table, $field) = explode('.', $tableField);
          // piVars contain variation key 
        if(!empty($arr_from_qty[$key][$tableField]))
        {
          $arr_variation[$key][$tableField] = mysql_escape_string($arr_from_qty[$key][$tableField]);
        }
      }
        // Loop through ts variation array
  
    }
    return $arr_variation;
  }

  /**
   * zz_replace_marker_in_sql(): Replace marker in the SQL query
   *                             MARKERS are
   *                             - GET/POST markers
   *                             - enable_field markers
   *                             SYNTAX is
   *                             - ###GP:TABLE###
   *                             - ###GP:TABLE.FIELD###
   *                             - ###ENABLE_FIELD:TABLE.FIELD###
   *
   * @param array   $gpvar: array with product uid, title, tax, etc...
   * @param array   $pobj: Parent Object
   * @return  void
   * @author dwildt
   * @version 1.2.2
   * @since 1.2.2
   */
  private function zz_replace_marker_in_sql($gpvar, $pObj) {

      // 12278 + 12283, 110116, dwildt
      
      // Set marker array with values from GET
    foreach(t3lib_div::_GET() as $table => $arr_fields) 
    {
      if(is_array($arr_fields))
      {
        foreach($arr_fields as $field => $value)
        {
          $tableField = strtoupper($table . '.' . $field);
          $marker['###GP:' . strtoupper($tableField) . '###'] = mysql_escape_string($value);
        }
      }
      if(!is_array($arr_fields))
      {
        $marker['###GP:' . strtoupper($table) . '###'] = mysql_escape_string($arr_fields);
      }
    }
      // Set marker array with values from GET

      // Set and overwrite marker array with values from POST
    foreach(t3lib_div::_POST() as $table => $arr_fields) 
    {
      if(is_array($arr_fields))
      {
        foreach($arr_fields as $field => $value)
        {
          $tableField = strtoupper($table . '.' . $field);
          $marker['###GP:' . strtoupper($tableField) . '###'] = mysql_escape_string($value);
        }
      }
      if(!is_array($arr_fields))
      {
        $marker['###GP:' . strtoupper($table) . '###'] = mysql_escape_string($arr_fields);
      }
    }
      // Set and overwrite marker array with values from POST

      // Get the SQL query from ts
    $query = $pObj->conf['db.']['sql'];
    
      // Get all gp:marker out of the query
    $arr_gpMarker = array();
    preg_match_all('|###GP\:(.*)###|U', $query, $arr_result, PREG_PATTERN_ORDER);
    if(isset($arr_result[0])) {
      $arr_gpMarker = $arr_result[0];
    } 
      // Get all gp:marker out of the query

      // Get all enable_fields:marker out of the query
    $arr_efMarker = array();
    preg_match_all('|###ENABLE_FIELDS\:(.*)###|U', $query, $arr_result, PREG_PATTERN_ORDER);
    if(isset($arr_result[0])) {
      $arr_efMarker = $arr_result[0];
    }
      // Get all enable_fields:marker out of the query
    
      // Replace gp:marker
    foreach($arr_gpMarker as $str_gpMarker)
    {
      $value = null;
      if(isset($marker[$str_gpMarker])) 
      {
        $value = $marker[$str_gpMarker];
      }
      $query = str_replace($str_gpMarker, $value, $query);
    }
      // Replace gp:marker

      // Replace enable_fields:marker
    foreach($arr_efMarker as $str_efMarker)
    {
        // ###ENABLE_FIELDS:TX_ORG_PRODUCTION### -> enable_fields:tx_org_production
      $str_efTable = trim(strtolower($str_efMarker), '#');
      list($dummy, $str_efTable) = explode(':', $str_efTable);
      $andWhere_ef = tslib_cObj::enableFields($str_efTable);
      $query = str_replace($str_efMarker, $andWhere_ef, $query);
    }
      // Replace enable_fields:marker 
      //var_dump('div 584', $marker, $arr_gpMarker, $arr_efMarker, $query);

    $pObj->conf['db.']['sql'] = $query;
  }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_div.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_div.php']);
}
?>