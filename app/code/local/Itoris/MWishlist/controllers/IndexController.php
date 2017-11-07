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

require_once Mage::getModuleDir('controllers', 'Mage_Wishlist') . DS . 'IndexController.php';

class Itoris_MWishlist_IndexController extends Mage_Wishlist_IndexController {

	protected $_localFilter = null;

	/**
	 * Ajax action for create new wishlist, add product to wishlist, remove product from wishlist
	 * copy/move product to other wishlist
	 */
	public function ajaxAction() {
		if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
			$this->getResponse()->setBody("You are not logged in. Please login ");
		} else {
			/** @var $wishlistModel Itoris_MWishlist_Model_Mwishlistnames */
			$wishlistModel = Mage::getModel('itoris_mwishlist/mwishlistnames');
			$blockType = null;
			if (($this->getRequest()->getParam('wlname'))) {
				if ($newId = $this->_setWishlistName($this->getRequest()->getParam('wlname'))) {
					$this->getRequest()->setParam('tabId', $newId['multiwishlist_id']);
				}
				$blockType = 'content';
			} elseif ($this->getRequest()->getParam('newWishlistName')) {
				if ($this->_setWishlistName($this->getRequest()->getParam('newWishlistName'))) {
					$result = $wishlistModel->getname($this->getRequest()->getParam('newWishlistName'));
					$wishlist = Mage::getModel('wishlist/wishlist')
						->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
					$this->getRequest()->setParam('tabId', $result['multiwishlist_id']);
					$wishlistModel->insertItemsInList($wishlistModel->checkingForNewItems($wishlist['wishlist_id']), ($result['multiwishlist_id']));
					$blockType = 'content';
				}
			} elseif ($this->getRequest()->getParam('removeWishlist')) {
				$wishlistModel->deleteWishlist($this->getRequest()->getParam('removeWishlist'));
				$blockType = 'mwishlisttabs';
			} elseif ($this->getRequest()->getParam('list')) {
				$blockType = 'frontview';
				if ($this->getRequest()->getParam('itemsCopy')) {
					$newItems = array();
					foreach ($this->getRequest()->getParam('itemsCopy') as $key => $value) {
						$model = Mage::getModel('wishlist/item');
						if (method_exists($model, 'loadWithOptions')) {
							$model->loadWithOptions($value);
						} else {
							$model->load($value);
						}
						$model->setId(null);
						$model->save();
						$this->_saveItemOptions($model);
						$newItems[$key] = $model->getId();
					}
					$wishlistModel->copyItemsBetweenLists($newItems, ($this->getRequest()->getParam('list')));
				} elseif ($this->getRequest()->getParam('itemCopy')) {
					$itemId = $this->getRequest()->getParam('itemCopy');
					$model = Mage::getModel('wishlist/item');
					if (method_exists($model, 'loadWithOptions')) {
						$model->loadWithOptions($itemId);
					} else {
						$model->load($itemId);
					}
					$model->setId(null);
					$model->save();
					$this->_saveItemOptions($model);
					$wishlistModel->copyItemsBetweenLists($model->getId(), ($this->getRequest()->getParam('list')));
				} elseif ($this->getRequest()->getParam('itemsMove')) {
					$wishlistModel->moveItemsBetweenLists(($this->getRequest()->getParam('itemsMove')), ($this->getRequest()->getParam('list')));
				} elseif ($this->getRequest()->getParam('itemMove')) {
					$wishlistModel->moveItemsBetweenLists(($this->getRequest()->getParam('itemMove')), ($this->getRequest()->getParam('list')));
				} elseif ($this->getRequest()->getParam('itemsDelete')) {
					$items = $this->getRequest()->getParam('itemsDelete');
					if (!is_array($items)) {
						$items = array($items);
					}
					foreach ($items as $itemId) {
						$item = Mage::getModel('wishlist/item')->load($itemId);
						if ($item->getId()) {
							$item->delete();
						}
					}
				} elseif ($this->getRequest()->getParam('tabId')) {
					$wishlist = Mage::getModel('wishlist/wishlist')
						->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
					$wishlistModel->insertItemsInList($wishlistModel->checkingForNewItems($wishlist['wishlist_id']), ($this->getRequest()->getParam('list')));
					$blockType = 'content';
				}
			} elseif ($this->getRequest()->getParam('tabId')) {
				if ($this->getRequest()->getParam('remove')) {
					$wishlistModel->removeItem($this->getRequest()->getParam('remove'));
				}
				$blockType = 'frontview';
			}
			if ($blockType) {
				$block = $this->getLayout()->createBlock('itoris_mwishlist/' . $blockType);
				$this->getResponse()->setBody($block->toHtml());
			}		
		}
	}

	protected function _saveItemOptions($item) {
		$options = $item->getOptions();
		if (is_array($options)) {
			/** @var $option Mage_Wishlist_Model_Item_Option */
			foreach ($options as $option) {
				$option->setId(null);
				$option->setItem($item);
				$option->save();
			}
		}
	}

	/**
	 * Add products from wishlist to cart
	 *
	 * @return Mage_Core_Controller_Varien_Action
	 */
	public function cartAction() {
		if ($this->getDataHelper()->isEnabled()) {
			$wishlistId = $this->getWishlistModel()->getWishlistIdByItemId((int)$this->getRequest()->getParam('item'));
			$wishlistData = $this->getWishlistModel()->getNameById($wishlistId);
			$deleteFromWishlist = ($wishlistData['multiwishlist_editable'] && $this->getDataHelper()->deleteFromWishlist()) ? true : false;
			$version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();
			$qty = (int)$this->getRequest()->getParam('qty', 1);
			if ((int)$version[2] < 4) {
				$wishlist = Mage::getModel('wishlist/wishlist')
					->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
				Mage::register('wishlist', $wishlist);

				$id = (int)$this->getRequest()->getParam('item');
				$item = Mage::getModel('wishlist/item')->load($id);

				if ($item->getWishlistId() == $wishlist->getId()) {
					try {
						$product = Mage::getModel('catalog/product')->load($item->getProductId())->setQty($qty ? $qty : 1);
						$quote = Mage::getSingleton('checkout/cart')
							->addProduct($product)
							->save();
						if ($deleteFromWishlist) {
							$item->delete();
						}
					}
					catch (Exception $e) {
						Mage::getSingleton('checkout/session')->addError($e->getMessage());
						$url = Mage::getSingleton('checkout/session')->getRedirectUrl(true);
						if ($url) {
							$url = Mage::getModel('core/url')->getUrl('catalog/product/view', array(
								'id' => $item->getProductId(),
								'wishlist_next' => 1
							));
							Mage::getSingleton('checkout/session')->setSingleWishlistId($item->getId());
							$this->getResponse()->setRedirect($url);
						}
						else {
							$this->_redirect('*/*/');
						}
						return;
					}
				}

				if (Mage::getStoreConfig('checkout/cart/redirect_to_cart')) {
					$this->_redirect('checkout/cart');
				} else {
					if ($this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL)) {
						$this->getResponse()->setRedirect(
							Mage::helper('core')->urlDecode($this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL))
						);
					} else {
						$this->_redirect('*/*/');
					}
				}

			} else {
				$itemId = (int)$this->getRequest()->getParam('item');

				/* @var $item Mage_Wishlist_Model_Item */
				$item = Mage::getModel('wishlist/item')->load($itemId);

				if (!$item->getId()) {
					return $this->_redirect('*/*');
				}


				if ($qty) {
					$item->setQty($qty);
				}

				$cart = Mage::getSingleton('checkout/cart');

				$redirectUrl = Mage::getUrl('*/*');
				try {
					if ((int)$version[2] >= 5) {
						$options = Mage::getModel('wishlist/item_option')->getCollection()
							->addItemFilter(array($itemId));
						$item->setOptions($options->getOptionsByItem($itemId));

						$buyRequest = Mage::helper('catalog/product')->addParamsToBuyRequest(
							$this->getRequest()->getParams(),
							array('current_config' => $item->getBuyRequest())
						);

						$item->mergeBuyRequest($buyRequest);
						if ($qty) {
							$this->_addQtyToAssociatedProducts($item, $qty);
						}
					}
					$item->addToCart($cart, $deleteFromWishlist);
					$cart->save()->getQuote()->collectTotals();
					if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
						$redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
					} else if ($this->_getRefererUrl()) {
						$redirectUrl = $this->_getRefererUrl();
					}
				} catch (Mage_Core_Exception $e) {
					if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
						Mage::getSingleton('core/session')->addError(Mage::helper('wishlist')->__('This product(s) is currently out of stock'));
					} else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
						Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
						if ((int)$version[2] >= 5) {
							$redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
						} else {
							$redirectUrl = $item->getProductUrl();
						}
					} else {
						Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
						if ((int)$version[2] >= 5) {
							$redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
						} else {
							$redirectUrl = $item->getProductUrl();
						}
					}
				} catch (Exception $e) {
					Mage::getSingleton('core/session')->addException($e, Mage::helper('wishlist')->__('Cannot add item to shopping cart'));
				}

				return $this->_redirectUrl($redirectUrl);
			}
		} else {
			parent::cartAction();
		}
	}

	protected function _addQtyToAssociatedProducts($item, $qty) {
		if ($item->getProduct()->getTypeId() == 'grouped') {
			$superGroup = array();
			if ($item->getBuyRequest()->getSuperGroup()) {
				$superGroup = $item->getBuyRequest()->getSuperGroup();
				if (is_array($superGroup)) {
					foreach ($superGroup as $groupId => $groupQty) {
						$superGroup[$groupId] = $groupQty * $qty;
					}
				}
			} else {
				$associatedProducts = $item->getProduct()->getTypeInstance(true)->getAssociatedProducts($item->getProduct());
				/** @var $associatedProduct Mage_Catalog_Model_Product */
				foreach ($associatedProducts as $associatedProduct) {
					if ($associatedProduct->isSalable()) {
						$superGroup[$associatedProduct->getId()] = $qty;
					}
				}
			}
			if (!empty($superGroup)) {
				$superGroupBuyRequest = new Varien_Object(array('super_group' => $superGroup));
				$item->mergeBuyRequest($superGroupBuyRequest);
			}
		}
		return $item;
	}

	/**
	 * Add all products from wishlist to cart
	 */
	public function allcartAction() {
		if ($this->getDataHelper()->isEnabled()) {
			$wishlist = Mage::getModel('wishlist/wishlist')
				->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
			$wishlistId = (int)$this->getRequest()->getParam('wishlist_id');
			$wishlistData = $this->getWishlistModel()->getNameById($wishlistId);
			$isOwner = ($wishlistData['multiwishlist_editable'] && $this->getDataHelper()->deleteFromWishlist()) ? true : false;
			$messages   = array();
			$addedItems = array();
			$notSalable = array();
			$hasOptions = array();

			$cart = Mage::getSingleton('checkout/cart');
			$collection = $wishlist->getItemCollection();
			if (version_compare(Mage::getVersion(), '1.5.0', '>=')) {
				$collection = $collection->setVisibilityFilter();
			}
			$tableMWishlistItems = Mage::getSingleton('core/resource')->getTableName('itoris_mwishlist_items');
			$collection->getSelect()->join($tableMWishlistItems, "wishlist_item_id = $tableMWishlistItems.item_id");
			$collection->getSelect()->where("multiwishlist_id = $wishlistId");
			if (version_compare(Mage::getVersion(), '1.4.0', '>=')) {
				$qtys = $this->getRequest()->getParam('qty');
				$origGroupedRequest = array();
				foreach ($collection->getItems() as $item) {
					/** @var Mage_Wishlist_Model_Item */
					try {
						$item->unsProduct();

						if (version_compare(Mage::getVersion(), '1.5.0', '>=')) {
							if (isset($qtys[$item->getId()])) {
								$qty = $this->_processLocalizedQty($qtys[$item->getId()]);
								if ($qty) {
									$item->setQty($qty);
									$origGroupedRequest[$item->getId()] = $item->getBuyRequest()->getSuperGroup();
									$this->_addQtyToAssociatedProducts($item, $qty);
								}
							}
						}

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

				//if ($isOwner) {
					$indexUrl = Mage::helper('wishlist')->getListUrl();
				//} else {
				//	$indexUrl = Mage::getUrl('wishlist/shared', array('code' => $wishlist->getSharingCode()));
				//}

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
						if (version_compare(Mage::getVersion(), '1.5.0', '>=')) {
							foreach ($collection->getItems() as $item) {
								if (array_key_exists($item->getId(), $origGroupedRequest)) {
									$oldBuyRequest = $item->getBuyRequest()->getData();
									if ($origGroupedRequest[$item->getId()]) {
										$oldBuyRequest['super_group'] = $origGroupedRequest[$item->getId()];
									} elseif (isset($oldBuyRequest['super_group'])) {
										unset($oldBuyRequest['super_group']);
									}
									$sBuyRequest = serialize($oldBuyRequest);
									$option = $item->getOptionByCode('info_buyRequest');
									if ($option) {
										$option->setValue($sBuyRequest);
									} else {
										$item->addOption(array(
											'code'  => 'info_buyRequest',
											'value' => $sBuyRequest
										));
									}
									$item->save();
								}
							}
						}
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

				if (version_compare(Mage::getVersion(), '1.4.0', '>=')) {
					Mage::helper('wishlist')->calculate();
				}

				$this->_redirectUrl($redirectUrl);
			} else {
				$urls               = array();
				$wishlistIds        = array();
				$notSalableNames    = array(); // Out of stock products message

				$collection->load();

				foreach ($wishlist->getItemCollection() as $item) {
					try {
						$product = Mage::getModel('catalog/product')
							->load($item->getProductId())
							->setQty(1);
						if ($product->isSalable()) {
							Mage::getSingleton('checkout/cart')->addProduct($product);
							if ($isOwner) {
								$item->delete();
							}
						}
						else {
							$notSalableNames[] = $product->getName();
						}
					} catch(Exception $e) {
						$url = Mage::getSingleton('checkout/session')
							->getRedirectUrl(true);
						if ($url) {
							$url = Mage::getModel('core/url')
								->getUrl('catalog/product/view', array(
									'id'            => $item->getProductId(),
									'wishlist_next' => 1
								));

							$urls[]         = $url;
							$messages[]     = $e->getMessage();
							$wishlistIds[]  = $item->getId();
						} else {
							$item->delete();
						}
					}
					Mage::getSingleton('checkout/cart')->save();
				}

				if (count($notSalableNames) > 0) {
					Mage::getSingleton('checkout/session')
						->addNotice($this->__('This product(s) is currently out of stock:'));
					array_map(array(Mage::getSingleton('checkout/session'), 'addNotice'), $notSalableNames);
				}

				if ($urls) {
					Mage::getSingleton('checkout/session')->addError(array_shift($messages));
					$this->getResponse()->setRedirect(array_shift($urls));

					Mage::getSingleton('checkout/session')->setWishlistPendingUrls($urls);
					Mage::getSingleton('checkout/session')->setWishlistPendingMessages($messages);
					Mage::getSingleton('checkout/session')->setWishlistIds($wishlistIds);
				}
				else {
					$this->_redirect('checkout/cart');
				}
			}
		} else {
			parent::allcartAction();
		}
	}

	public function addToCartSelectedItemsAction() {
		$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
		$wishlistId = (int)$this->getRequest()->getParam('wishlist_id');
		$wishlistData = $this->getWishlistModel()->getNameById($wishlistId);
		$isOwner = ($wishlistData['multiwishlist_editable'] && $this->getDataHelper()->deleteFromWishlist()) ? true : false;

		$messages   = array();
		$addedItems = array();
		$notSalable = array();
		$hasOptions = array();

		$cart = Mage::getSingleton('checkout/cart');
		$collection = $wishlist->getItemCollection();
		//$collection = $collection->setVisibilityFilter();
		$items = $this->getRequest()->getParam('items');
		if (!is_array($items)) {
			$items = array();
		}
		$items = array_map('intval', $items);
		$tableMWishlistItems = Mage::getSingleton('core/resource')->getTableName('itoris_mwishlist_items');
		$collection->getSelect()->join($tableMWishlistItems, "wishlist_item_id = $tableMWishlistItems.item_id");
		$collection->getSelect()->where("multiwishlist_id = $wishlistId");
			$qtys = $this->getRequest()->getParam('qty');
			$origGroupedRequest = array();
			foreach ($collection->getItems() as $item) {
				if (!in_array($item->getId(), $items)) {
					continue;
				}
				/** @var Mage_Wishlist_Model_Item */
				try {
					$item->unsProduct();
						if (isset($qtys[$item->getId()])) {
							$qty = $this->_processLocalizedQty($qtys[$item->getId()]);
							if ($qty) {
								$item->setQty($qty);
								$origGroupedRequest[$item->getId()] = $item->getBuyRequest()->getSuperGroup();
								$this->_addQtyToAssociatedProducts($item, $qty);
							}
						}


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

			//if ($isOwner) {
				$indexUrl = Mage::helper('wishlist')->getListUrl();
			//} else {
			//	$indexUrl = Mage::getUrl('wishlist/shared', array('code' => $wishlist->getSharingCode()));
			//}

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
					if (version_compare(Mage::getVersion(), '1.5.0', '>=')) {
						foreach ($collection->getItems() as $item) {
							if (array_key_exists($item->getId(), $origGroupedRequest)) {
								$oldBuyRequest = $item->getBuyRequest()->getData();
								if ($origGroupedRequest[$item->getId()]) {
									$oldBuyRequest['super_group'] = $origGroupedRequest[$item->getId()];
								} elseif (isset($oldBuyRequest['super_group'])) {
									unset($oldBuyRequest['super_group']);
								}
								$sBuyRequest = serialize($oldBuyRequest);
								$option = $item->getOptionByCode('info_buyRequest');
								if ($option) {
									$option->setValue($sBuyRequest);
								} else {
									$item->addOption(array(
										'code'  => 'info_buyRequest',
										'value' => $sBuyRequest
									));
								}
								$item->save();
							}
						}
					}
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

	protected function _processLocalizedQty($qty) {
		if (!$this->_localFilter) {
			$this->_localFilter = new Zend_Filter_LocalizedToNormalized(array('locale' => Mage::app()->getLocale()->getLocaleCode()));
		}
		$qty = $this->_localFilter->filter($qty);
		if ($qty < 0) {
			$qty = null;
		}
		return $qty;
	}


	public function _setWishlistName($wishlistName) {
		$namesmodel = Mage::getModel('itoris_mwishlist/mwishlistnames');
		$result = $namesmodel->getname($wishlistName);
		if ($result['multiwishlist_name']) {
			Mage::getSingleton('core/session')->addError('Wishlist with such a name already exists. Please choose a different name.');
			return false;
		} else {
			return $namesmodel->setName($wishlistName);
		}
	}

	public function getItemConfigureUrl($product) {
		if ($product instanceof Mage_Catalog_Model_Product) {
			$id = $product->getWishlistItemId();
		} else {
			$id = $product->getId();
		}
		$params = array('id' => $id);

		return $this->getUrl('wishlist/index/configure/', $params);
	}

	public function updateAction() {
		if ($this->getDataHelper()->isEnabled()) {
			$post = $this->getRequest()->getPost();
			$version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();
			if ((int)$version[2] < 4) {

				if ($post && isset($post['description']) && is_array($post['description'])) {
					$wishlist = Mage::getModel('wishlist/wishlist')
						->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
					Mage::register('wishlist', $wishlist);

					foreach ($post['description'] as $itemId => $description) {
						$item = Mage::getModel('wishlist/item')->load($itemId);
						$description = (string)$description;
						if (!strlen($description) || $item->getWishlistId() != $wishlist->getId()) {
							continue;
						}
						try {
							$item->setDescription($description)
								->save();
						}
						catch (Exception $e) {
							Mage::getSingleton('customer/session')->addError(
								$this->__('Can\'t save description %s', Mage::helper('core')->htmlEscape($description))
							);
						}
					}
				}
			} else {
				if ($post && isset($post['description']) && is_array($post['description'])) {
					$updatedItems = 0;

					foreach ($post['description'] as $itemId => $description) {
						$item = Mage::getModel('wishlist/item')->load($itemId);
						// Extract new values
						$description = (string)$description;
						if (!strlen($description)) {
							$description = $item->getDescription();
						}

						$qty = null;
						if (isset($post['qty'][$itemId])) {
							$qty = $post['qty'][$itemId];
						}
						if (is_null($qty)) {
							$qty = $item->getQty();
							if (!$qty) {
								$qty = 1;
							}
						} elseif (0 == $qty) {
							try {
								$item->delete();
							} catch (Exception $e) {
								Mage::logException($e);
								Mage::getSingleton('customer/session')->addError(
									$this->__('Can\'t delete item from wishlist')
								);
							}
						}
						if (($item->getDescription() == $description) && ($item->getQty() == $qty)) {
							continue;
						}
						try {
							$item->setDescription($description)
								->setQty($qty)
								->save();
							$updatedItems++;
						} catch (Exception $e) {
							Mage::getSingleton('customer/session')->addError(
								$this->__('Can\'t save description %s', Mage::helper('core')->htmlEscape($description))
							);
						}
					}
				}
			}

			$this->_redirect('*', array('tabId' => $this->getRequest()->getParam('mwishlist_id')));
		} else {
			parent::updateAction();
		}
	}

	public function shareAction() {
		if ($this->getDataHelper()->isEnabled()) {
			Mage::getSingleton('customer/session')->addData(array('share_wishlist_id' => (int)$this->getRequest()->getParam('id')));
			$this->loadLayout();
			$this->_initLayoutMessages('customer/session');
			$this->_initLayoutMessages('wishlist/session');
			$this->renderLayout();
		} else {
			parent::shareAction();
		}
	}

	protected function _getWishlist() {
		try {
			$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
			Mage::register('wishlist', $wishlist, true);
		} catch (Mage_Core_Exception $e) {
			Mage::getSingleton('wishlist/session')->addError($e->getMessage());
		} catch (Exception $e) {
			Mage::getSingleton('wishlist/session')->addException($e,
				Mage::helper('wishlist')->__('Cannot create wishlist.')
			);
			return false;
		}
		return $wishlist;
	}

	public function sendAction() {
		if ($this->getDataHelper()->isEnabled()) {
			if (!$this->_validateFormKey()) {
				return $this->_redirect('*/*/');
			}

			$emails = explode(',', $this->getRequest()->getPost('emails'));
			$message= nl2br(htmlspecialchars((string) $this->getRequest()->getPost('message')));
			$error  = false;
			if (empty($emails)) {
				$error = $this->__('Email address can\'t be empty.');
			}
			else {
				foreach ($emails as $index => $email) {
					$email = trim($email);
					if (!Zend_Validate::is($email, 'EmailAddress')) {
						$error = $this->__('Please input a valid email address.');
						break;
					}
					$emails[$index] = $email;
				}
			}
			if ($error) {
				Mage::getSingleton('wishlist/session')->addError($error);
				Mage::getSingleton('wishlist/session')->setSharingForm($this->getRequest()->getPost());
				$this->_redirect('*/*/share');
				return;
			}

			$translate = Mage::getSingleton('core/translate');
			/* @var $translate Mage_Core_Model_Translate */
			$translate->setTranslateInline(false);

			try {
				$customer = Mage::getSingleton('customer/session')->getCustomer();
				$wishlist = $this->_getWishlist();

				/*if share rss added rss feed to email template*/
				if ($this->getRequest()->getParam('rss_url')) {
					$rss_url = $this->getLayout()->createBlock('wishlist/share_email_rss')->toHtml();
					$message .=$rss_url;
				}
				$wishlistBlock = $this->getLayout()->createBlock('itoris_mwishlist/share_items')->toHtml();

				$emails = array_unique($emails);
				/* @var $emailModel Mage_Core_Model_Email_Template */
				$emailModel = Mage::getModel('core/email_template');
				$shareWishlistId = urlencode(base64_encode(Mage::getSingleton('customer/session')->getData('share_wishlist_id')));
				foreach($emails as $email) {
					$emailModel->sendTransactional(
						Mage::getStoreConfig('wishlist/email/email_template'),
						Mage::getStoreConfig('wishlist/email/email_identity'),
						$email,
						null,
						array(
							'customer'      => $customer,
							'salable'       => $wishlist->isSalable() ? 'yes' : '',
							'items'         => $wishlistBlock,
							'addAllLink'    => Mage::getUrl('*/shared/allcart', array(
								'code' => $wishlist->getSharingCode(),
								'mw'   => $shareWishlistId,
							)),
							'viewOnSiteLink'=> Mage::getUrl('*/shared/index', array(
								'code' => $wishlist->getSharingCode(),
								'mw'   => $shareWishlistId,
							)),
							'message'       => $message
						));
				}

				$wishlist->setShared(1);
				$wishlist->save();

				$translate->setTranslateInline(true);

				Mage::dispatchEvent('wishlist_share', array('wishlist'=>$wishlist));
				Mage::getSingleton('customer/session')->addSuccess(
					$this->__('Your Wishlist has been shared.')
				);
				$this->_redirect('*/*');
			}
			catch (Exception $e) {
				$translate->setTranslateInline(true);

				Mage::getSingleton('wishlist/session')->addError($e->getMessage());
				Mage::getSingleton('wishlist/session')->setSharingForm($this->getRequest()->getPost());
				$this->_redirect('*/*/share');
			}
		} else {
			parent::sendAction();
		}
	}

	public function cancelAddingAction() {
		$namesmodel = Mage::getModel('itoris_mwishlist/mwishlistnames');
		try {
			$wishlist = Mage::getModel('wishlist/wishlist')
				->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);

			$newItems = $namesmodel->checkingForNewItems($wishlist->getId());
			foreach ($newItems as $itemId) {
				$item = Mage::getModel('wishlist/item')->load($itemId['wishlist_item_id']);
				if ($item->getId()) {
					$item->delete();
				}
			}
			$result = array('success' => true);
		} catch (Exception $e) {
			Mage::logException($e);
			$result = array('error' => 'Error');
		}

		$this->getResponse()->setBody(Zend_Json::encode($result));
	}

	public function itemCountAction() {
		$helper = Mage::helper('wishlist');
		$count = Mage::helper('wishlist')->getItemCount();
		if ($count > 1) {
			$text = $helper->__('My Wishlist (%d items)', $count);
		} else if ($count == 1) {
			$text = $helper->__('My Wishlist (%d item)', $count);
		} else {
			$text = $helper->__('My Wishlist');
		}
		$result = array('link_text' => $text);
		$this->getResponse()->setBody(Zend_Json::encode($result));
	}

	public function updateItemOptionsAction() {
		if ($this->getDataHelper()->isEnabled()) {
			$id = (int) $this->getRequest()->getParam('id');
			$mwishlist = $this->getWishlistModel();
			$mwishlistId = $mwishlist->getWishlistIdByItemId($id);
			Mage::register('update_items_action', true);
			parent::updateItemOptionsAction();
			/** @var $wishlist Mage_Wishlist_Model_Wishlist */
			$wishlist = Mage::registry('wishlist');
			$items = $mwishlist->checkingForNewItems($wishlist->getId());
			if (!empty($items)) {
				$mwishlist->insertItemsInList($items, $mwishlistId);
			} else {
				$qty = (int)$this->getRequest()->getParam('qty');
				$qty = $qty ? $qty : 1;
				if (Mage::registry('update_items')) {
					$mwishlistItems = $mwishlist->getWishlistItems($mwishlistId);
					$mwishlistItemIds = array();
					foreach ($mwishlistItems as $mwishlistItem) {
						$mwishlistItemIds[] = $mwishlistItem['id'];
					}
					$itemUpdated = false;
					$product = null;
					foreach (Mage::registry('update_items') as $item) {
						$product = $item->getProduct();
						if (in_array($item->getId(), $mwishlistItemIds)) {
							$this->_updateItemQty($item, $qty + $item->getQty());
							$itemUpdated = true;
						}
					}
					if (!$itemUpdated) {
						foreach ($mwishlistItemIds as $mwishlistItemId) {
							$itemModel = Mage::getModel('wishlist/item')->loadWithOptions($mwishlistItemId);
							if ($itemModel->representProduct($product)) {
								$this->_updateItemQty($itemModel, $qty + $itemModel->getQty());
							}
						}
					}
				} else {
					/** @var $itemModel Mage_Wishlist_Model_Item */
					$itemModel = Mage::getModel('wishlist/item')->load($id);
					if ($itemModel->getId()) {
						$this->_updateItemQty($itemModel, $qty);
					}
				}
			}
			$this->_redirect('*/*', array('tabId' => $mwishlistId));
		} else {
			parent::updateItemOptionsAction();
		}
	}

	public function fromcartAction() {
		$wishlist = $this->_getWishlist();
		if (!$wishlist) {
			return $this->norouteAction();
		}
		$itemId = (int) $this->getRequest()->getParam('item');

		/* @var Mage_Checkout_Model_Cart $cart */
		$cart = Mage::getSingleton('checkout/cart');
		$item = $cart->getQuote()->getItemById($itemId);
		$productName = $item->getProduct()->getName();
		if (!$this->getRequest()->getParam('qty')) {
			$this->getRequest()->setParam('qty', $item->getQty());
			$this->getRequest()->setParam('product', $item->getProductId());
		}
		parent::fromcartAction();
		/** @var $session Mage_Checkout_Model_Session */
		$session = Mage::getSingleton('checkout/session');
		$successMessages = $session->getMessages()->getItemsByType('success');
		if (count($successMessages)) {
			foreach ($successMessages as $message) {
				$session->getMessages()->deleteMessageByIdentifier($message->getIdentifier());
			}
			$session->addSuccess(
				Mage::helper('wishlist')->__("%s has been moved to wishlist %s", $productName, '')
			);
		}
	}

	public function renameWishlistAction() {
		$result = array();
		try {
			$wishlistId = $this->getRequest()->getParam('id');
            $newName = $this->getRequest()->getParam('name');
            if ($newName) {
                $newName = trim($newName);
                if ($this->getWishlistModel()->isWishlistExistsByName($newName)) {
                    $result['error'] = $this->__('Wishlist with such a name already exists. Please choose a different name.');
                } else {
                    $wishlist = $this->getWishlistModel();
                    $currentName = $wishlist->loadById($wishlistId);
                    if ($currentName) {

                        if ($newName && $currentName['multiwishlist_editable'] && $currentName['multiwishlist_name'] != trim($newName)) {
                            $wishlist->updateName($wishlistId, trim($newName));
                            $result['ok'] = true;
                        }
                    } else {
                        $result['error'] = $this->__('Wishlist not found');
                    }
                }
            } else {
                $result['error'] = $this->__('Please enter a new name');
            }
		} catch(Exception $e) {
			$result['error'] = $this->__('Wishlist has not been renamed');
		}
		$this->getResponse()->setBody(Zend_Json::encode($result));
	}

	/**
	 * @return Itoris_MWishlist_Model_Wishlist
	 */
	private function getWishlistModel() {
		return Mage::getModel('itoris_mwishlist/wishlist');
	}

	/**
	 * @return Itoris_MWishlist_Helper_Data
	 */
	protected function getDataHelper() {
		return Mage::helper('itoris_mwishlist');
	}

	protected function _updateItemQty($item, $qty) {
		$item->setQty($qty);
		$item->setIsUpdateAction(true);
		$item->save();
	}

	public function resetquantityAction() {
		if ($this->getDataHelper()->isEnabled()) {
			$id = (int) $this->getRequest()->getParam('id');
			$mwishlist = $this->getWishlistModel();
			$mwishlistId = $id;//$mwishlist->getWishlistIdByItemId($id);
			Mage::register('update_items_action', true);

			$mwishlistItems = $mwishlist->getWishlistItems($mwishlistId);
			$mwishlistItemIds = array();
			foreach ($mwishlistItems as $mwishlistItem) {
				$mwishlistItemIds[] = $mwishlistItem['id'];
			}
			$itemUpdated = false;
			$product = null;
			if (!$itemUpdated) {
				foreach ($mwishlistItemIds as $mwishlistItemId) {
					$itemModel = Mage::getModel('wishlist/item')->loadWithOptions($mwishlistItemId);
					$this->_updateItemQty($itemModel, 0);
				}
			}
			$this->_redirect('*/*', array('tabId' => $mwishlistId));
		}
	}

	public function clearcartAction() {
		try {
			$mwishlistId = (int) $this->getRequest()->getParam('id');
            $this->_getCart()->truncate()->save();
            $this->_getCheckoutSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $exception) {
            $this->_getCheckoutSession()->addError($exception->getMessage());
        } catch (Exception $exception) {
            $this->_getCheckoutSession()->addException($exception, $this->__('Cannot update shopping cart.'));
        }
		$this->_redirect('*/*', array('tabId' => $mwishlistId));
	}

	protected function _getCart() {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
    }

	public function setPricingDisplayAction() {
		$post = $this->getRequest()->getPost();
		$wishlistId = $post['wishlistId'];
		$display_price = $post['display_price'];
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
			$post['customerId'] = $customerId;
		}
		$wishlistModel = Mage::getModel('itoris_mwishlist/mwishlistnames');
		$data = $wishlistModel->setPricingDisplay($post);
		$blockType = 'content';//content, mwishlisttabs, frontview
		$block = $this->getLayout()->createBlock('itoris_mwishlist/' . $blockType);
		$this->getResponse()->setBody($block->toHtml());
	}
}
?>