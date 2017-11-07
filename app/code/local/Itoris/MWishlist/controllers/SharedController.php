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
 * @copyright  Copyright (c) 2012 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

require_once Mage::getModuleDir('controllers', 'Mage_Wishlist') . DS . 'SharedController.php';

class Itoris_MWishlist_SharedController extends Mage_Wishlist_SharedController {

	public function allcartAction() {
		$wishlist   = $this->_getWishlist();
		if (!$wishlist) {
			$this->_forward('noRoute');
			return ;
		}
		$isOwner    = $wishlist->isOwner(Mage::getSingleton('customer/session')->getCustomerId());

		$messages   = array();
		$addedItems = array();
		$notSalable = array();
		$hasOptions = array();

		$cart       = Mage::getSingleton('checkout/cart');
		$collection = $wishlist->getItemCollection()
			->setVisibilityFilter();
		if ($this->getRequest()->getParam('mw', '')) {
			$this->prepareCollection($collection);
		}
		$qtys = $this->getRequest()->getParam('qty');
		foreach ($collection as $item) {
			/** @var Mage_Wishlist_Model_Item */
			try {
				$item->unsProduct();

				// Set qty
				if (isset($qtys[$item->getId()])) {
					$qty = $this->_processLocalizedQty($qtys[$item->getId()]);
					if ($qty) {
						$item->setQty($qty);
					}
				}

				// Add to cart
				if ($item->addToCart($cart, $isOwner)) {
					$addedItems[] = $item->getProduct();
				}

			} catch (Mage_Core_Exception $e) {
				if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
					$notSalable[] = $item;
				} else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
					$hasOptions[] = $item;
				} else {
					$messages[] = $this->__('%s for "%s".', trim($e->getMessage(), '.'), $item->getProduct()->getName());
				}
			} catch (Exception $e) {
				Mage::logException($e);
				$messages[] = Mage::helper('wishlist')->__('Cannot add the item to shopping cart.');
			}
		}

		if ($isOwner) {
			$indexUrl = Mage::helper('wishlist')->getListUrl();
		} else {
			$indexUrl = Mage::getUrl('wishlist/shared', array('code' => $wishlist->getSharingCode()));
		}
		if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
			$redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
		} else if ($this->_getRefererUrl()) {
			$redirectUrl = $this->_getRefererUrl();
		} else {
			$redirectUrl = $indexUrl;
		}

		if ($notSalable) {
			$products = array();
			foreach ($notSalable as $item) {
				$products[] = '"' . $item->getProduct()->getName() . '"';
			}
			$messages[] = Mage::helper('wishlist')->__('Unable to add the following product(s) to shopping cart: %s.', join(', ', $products));
		}

		if ($hasOptions) {
			$products = array();
			foreach ($hasOptions as $item) {
				$products[] = '"' . $item->getProduct()->getName() . '"';
			}
			$messages[] = Mage::helper('wishlist')->__('Product(s) %s have required options. Each of them can be added to cart separately only.', join(', ', $products));
		}

		if ($messages) {
			$isMessageSole = (count($messages) == 1);
			if ($isMessageSole && count($hasOptions) == 1) {
				$item = $hasOptions[0];
				if ($isOwner) {
					$item->delete();
				}
				$redirectUrl = $item->getProductUrl();
			} else {
				$wishlistSession = Mage::getSingleton('wishlist/session');
				foreach ($messages as $message) {
					$wishlistSession->addError($message);
				}
				$redirectUrl = $indexUrl;
			}
		}

		if ($addedItems) {
			// save wishlist model for setting date of last update
			try {
				$wishlist->save();
			}
			catch (Exception $e) {
				Mage::getSingleton('wishlist/session')->addError($this->__('Cannot update wishlist'));
				$redirectUrl = $indexUrl;
			}

			$products = array();
			foreach ($addedItems as $product) {
				$products[] = '"' . $product->getName() . '"';
			}

			Mage::getSingleton('checkout/session')->addSuccess(
				Mage::helper('wishlist')->__('%d product(s) have been added to shopping cart: %s.', count($addedItems), join(', ', $products))
			);
		}
		// save cart and collect totals
		$cart->save()->getQuote()->collectTotals();

		Mage::helper('wishlist')->calculate();

		$this->_redirectUrl($redirectUrl);
	}

	protected function prepareCollection($collection) {
		$shareWishlistId = (int)base64_decode(urldecode($this->getRequest()->getParam('mw', '')));
		if ($shareWishlistId) {
			if (version_compare(Mage::getVersion(), '1.5.0.0', '>=')) {
				$collection->getSelect()
					->join(array(
						'wishlists' => Mage::getSingleton('core/resource')->getTableName('itoris_mwishlist_items')
					),
					'main_table.wishlist_item_id = wishlists.item_id and wishlists.multiwishlist_id = ' . $shareWishlistId
				);
			} else {
				$collection->getSelect()
					->join(array(
						'wishlists' => Mage::getSingleton('core/resource')->getTableName('itoris_mwishlist_items')
					),
					't_wi.wishlist_item_id = wishlists.item_id and wishlists.multiwishlist_id = ' . $shareWishlistId
				);
			}
		}

		return $this;
	}
}
?>