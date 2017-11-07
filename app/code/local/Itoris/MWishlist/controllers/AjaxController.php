<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_MWISHLIST
 * @copyright  Copyright (c) 2013 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

require_once Mage::getModuleDir('controllers', 'Mage_Wishlist') . DS . 'IndexController.php';

class Itoris_MWishlist_AjaxController extends Mage_Wishlist_IndexController {

	public function preDispatch() {
		$this->_skipAuthentication = true;
		parent::preDispatch();
	}

	public function AddProductAction() {
		$result = array(
			'success'  => false,
			'error'    => null,
			'redirect' => null,
		);
		/** @var $session Mage_Customer_Model_Session */
		$session = Mage::getSingleton('customer/session');
		if ($session->isLoggedIn()) {
			$errors = array();
			$wishlist = $this->_getWishlist();
			if (!$wishlist) {
				$errors[] = $this->__('Wishlist is not loaded');
			}
			if (empty($errors)) {
				$productId = (int) $this->getRequest()->getParam('product');
				if (!$productId) {
					$errors[] = $this->__('Please specify product');
				}

				$product = Mage::getModel('catalog/product')->load($productId);
				if (!$product->getId() || !$product->isVisibleInCatalog()) {
					$errors[] = $this->__('Cannot specify product.');
				}
				if (empty($errors)) {
					try {
						$mwishlist = $this->getWishlistModel();
						$mwishlistId = $this->getRequest()->getParam('mwishlist_id');
						if ($this->getRequest()->getParam('is_new')) {
							$mwishlistId = $mwishlist->createWishlist($mwishlistId);
						} else if ($mwishlistId == 'main') {
							$mwishlistId = $mwishlist->getMainWishlistId($session->getCustomerId());
						}

						$requestParams = $this->getRequest()->getParams();
						if ($session->getBeforeWishlistRequest()) {
							$requestParams = $session->getBeforeWishlistRequest();
							$session->unsBeforeWishlistRequest();
						}
						$buyRequest = new Varien_Object($requestParams);

						$result = $wishlist->addNewItem($product, $buyRequest);
						if (is_string($result)) {
							Mage::throwException($result);
						}
						$wishlist->save();

						Mage::dispatchEvent(
							'wishlist_add_product',
							array(
								'wishlist'  => $wishlist,
								'product'   => $product,
								'item'      => $result
							)
						);
						$mwishlist->insertItemsInList($result->getId(), $mwishlistId);
						Mage::helper('wishlist')->calculate();

						$message = $this->__('%1$s has been added to your wishlist %2$s.', $product->getName(), $mwishlist->getWishlistNameById($mwishlistId));
						$result['message'] = $message;
						$result['success'] = true;
					} catch (Mage_Core_Exception $e) {
						$errors[] = $this->__('An error occurred while adding item to wishlist: %s', $e->getMessage());
					} catch (Exception $e) {
						$errors[] = $this->__('An error occurred while adding item to wishlist.');
					}
				}
			}
			if (!empty($errors)) {
				$result['error'] = implode(' ', $errors);
			}
		} else {
			$result['redirect'] = Mage::getUrl('customer/account/login');
			if ($this->getRequest()->isAjax()) {
				$session->setBeforeAjaxAddReferer($this->_getRefererUrl());
			}
			$session->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_current' => true)));
			$session->setAfterAuthUrl(Mage::getUrl('*/*/*', array('_current' => true)));
			if (!$session->getBeforeWishlistUrl()) {
				$session->setBeforeWishlistUrl($this->_getRefererUrl());
			}
			$session->setBeforeWishlistRequest($this->getRequest()->getParams());
		}
		if (!$this->getRequest()->isAjax()) {
			if (isset($result['message'])) {
				Mage::getSingleton('core/session')->addSuccess($result['message']);
			}
			if ($result['error']) {
				Mage::getSingleton('core/session')->addError($result['error']);
			}
			$ajaxReferer = $session->getAjaxAddProductWishlistReferer(true);
			if ($ajaxReferer) {
				$this->_redirectUrl($ajaxReferer);
			} else {
				$this->_redirect('wishlist');
			}
		} else {
			$session->setAjaxAddProductWishlistReferer($this->_getRefererUrl());
            header('Content-Type: application/javascript');
            echo 'window.mWishlistObject.addProductSilentAdded('.Zend_Json::encode($result).');';
            exit;
			//$this->getResponse()->setBody('window.mWishlistObject('.Zend_Json::encode($result).');');
		}
	}

	/**
	 * @return Itoris_MWishlist_Model_Wishlist
	 */
	private function getWishlistModel() {
		return Mage::getModel('itoris_mwishlist/wishlist');
	}
}
?>
