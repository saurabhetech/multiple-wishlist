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

class Itoris_MWishlist_Model_Observer {

	protected $version = '';
	protected $observedCollections = array();

	public function mwishlistAvailable($observer) {
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$this->version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();
			if ($this->isEnabled()) {
				if ((int)$this->version[2] == 3) {
					$wishlist = Mage::getModel('wishlist/wishlist')
							->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
					Mage::register('wishlist', $wishlist);
				}
				$controller = $observer->getController_action();

				$controller->loadLayout();
				$storage = Mage::getSingleton('customer/session');
				$controller->getLayout()->getMessagesBlock()->addMessages($storage->getMessages(true));
				$storage = Mage::getSingleton('checkout/session');
				$controller->getLayout()->getMessagesBlock()->addMessages($storage->getMessages(true));
				$storage = Mage::getSingleton('catalog/session');
				$controller->getLayout()->getMessagesBlock()->addMessages($storage->getMessages(true));
				$storage = Mage::getSingleton('wishlist/session');
				$controller->getLayout()->getMessagesBlock()->addMessages($storage->getMessages(true));

				$head = $controller->getLayout()->getBlock('head');
				if ((int)$this->version[2] > 3) {
					$head->addItem('js_css', 'itoris/mwishlist/mwishlist.css');
				} else {
					$head->addItem('js_css', 'itoris/mwishlist/mwishlistv3.css');
				}
				$head->addJs('itoris/mwishlist/wishlist.js');

				//$head->addItem('js_css', 'prototype/windows/themes/default.css');
				//$head->addItem('js_css', 'prototype/windows/themes/magento.css');
				//$head->addJS('prototype/window.js');
				//$wishlistModel = Mage::getModel('itoris_mwishlist/wishlist');
				$contentBlock = $controller->getLayout()->getBlock('content');
				$contentBlock->unsetChildren();
				$allContentBlock = $controller->getLayout()->createBlock('itoris_mwishlist/content');
				//$allContentBlock->setNamescollection($wishlistModel->getWishlists());
				$modal = $controller->getLayout()->createBlock('itoris_mwishlist/modalwindow');
				$contentBlock->append($modal);
				$contentBlock->append($allContentBlock);
				$controller->renderLayout();
				Mage::app()->getResponse()->sendResponse();
				exit;
			} else {
				return;
			}
		}
	}

	public function mwishlistAddItem($observer) {
		Mage::getSingleton('customer/session')->setProductParam($observer->getProduct()->getId());
	}

	protected function _insertItemInWishlist() {
		$request = Mage::app()->getRequest();
		$wishlistId = $request->getParam('imw');
		if ($wishlistId) {
			$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
			/** @var $itorisWishlist Itoris_MWishlist_Model_Wishlist */
			$itorisWishlist = Mage::getModel('itoris_mwishlist/wishlist');
			if ($request->getParam('imw_new')) {
				if ($request->getParam('imw')) {
					$wishlistId = $itorisWishlist->createWishlist($request->getParam('imw'));
				}
			}
			if ($wishlistId == 'main') {
				$wishlistId = $itorisWishlist->getMainWishlistId(Mage::getSingleton('customer/session')->getCustomer()->getId());
			}
			if ($wishlistId) {
				$newItems = $itorisWishlist->checkingForNewItems($wishlist->getId());
				$itorisWishlist->insertItemsInList($newItems, $wishlistId);
				Mage::getSingleton('customer/session')->setWishlistTabId($wishlistId);
			}
		}
	}

	/**
	 * After add product to wishlist
	 * always create new item with new options if needed
	 * Because Magento can use the same item for the product
	 *
	 * @param $observer
	 */
	public function unsetItemId($observer) {
		if ($this->isEnabled()) {
			$items = $observer->getItems();
			if (Mage::registry('update_items_action')) {
				if (Mage::registry('update_items')) {
					Mage::unregister('update_items');
				}
				Mage::register('update_items', $items);
				return;
			}
			/** @var $item Mage_Wishlist_Model_Item */
			foreach ($items as $item) {
				if ($item->getOrigData()) {
					if (method_exists($item, 'loadWithOptions')) {
						$item->loadWithOptions($item->getId());
					} else {
						$item->load($item->getId());
					}
					$item->setId(null);
					$qty = Mage::app()->getRequest()->getParam('qty');
					$item->setQty($qty ? $qty : 1);
					$item->save();
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
			}
			$this->_insertItemInWishlist();
		}
	}

	public function setOrigItemQty($observer) {
		if ($this->isEnabled()) {
			/** @var $item Mage_Wishlist_Model_Wishlist */
			$item = $observer->getDataObject();
			if ($item->getIsUpdateAction() || $item->getSkipOrigSetQty()) {
				return;
			}
			$productId = Mage::app()->getRequest()->getParam('product');
			if ($item->getId() && $item->getOrigData('qty') != $item->getQty() && $item->getProductId() == $productId) {
				$item->setQty($item->getOrigData('qty'));
			}
		}
	}

	protected function isEnabled() {
		$currentStore = Mage::app()->getStore()->getId();
		$currentWebsite = Mage::app()->getWebsite()->getId();
		$settingsModel = Mage::getModel('itoris_mwishlist/settings');
		$settingsModel->dataLoad($currentWebsite, $currentStore);

		return $settingsModel->getEnabled() && true;
	}

	public function addProductStatusFilter($observer) {
		$collection = $observer->getCollection();
		if (($collection instanceof Mage_Wishlist_Model_Resource_Item_Collection || $collection instanceof Mage_Wishlist_Model_Mysql4_Item_Collection)
			&& $this->isEnabled()
		) {
			foreach ($this->observedCollections as $_col) {
				if ($_col === $collection) {
					return;
				}
			}
			$this->observedCollections[] = $collection;
			$attributeCode = 'status';
			//$attributeTableAlias = 'iproduct_'. $attributeCode .'_table';
			$entityTypeId = (int)Mage::getResourceModel('catalog/config')->getEntityTypeId();
			$attribute = Mage::getModel('catalog/entity_attribute')->loadByCode($entityTypeId, $attributeCode);

			if ($attribute->getId() && $attribute->getBackendTable()) {
				$attributeId = (int)$attribute->getId();
				$storeId = Mage::app()->getStore()->getId();

				/** @var $productIdsSelect Varien_Db_Select */
				$productIdsSelect = clone $collection->getSelect();
				$productIdsSelect->reset(Zend_Db_Select::ORDER);
				$productIdsSelect->reset(Zend_Db_Select::LIMIT_COUNT);
				$productIdsSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
				$productIdsSelect->reset(Zend_Db_Select::COLUMNS);
				$productIdsSelect->joinLeft(array('s_def' => $attribute->getBackendTable()),
					"s_def.entity_id=main_table.product_id and s_def.attribute_id={$attributeId} and s_def.entity_type_id={$entityTypeId} and s_def.store_id=0",
					array('product_status' => 'if(s.value is null, s_def.value, s.value)')
				);
				$productIdsSelect->joinLeft(array('s' => $attribute->getBackendTable()),
					"s.entity_id=main_table.product_id and s.attribute_id={$attributeId} and s.entity_type_id={$entityTypeId} and s.store_id={$storeId}",
					array()
				);
				$productIdsSelect->group('main_table.product_id');
				$productIdsSelect->having('product_status=1');
				$productIdsSelect->columns('product_id', 'main_table');
				$productIds = array();
				$data = $collection->getConnection()->fetchAll($productIdsSelect);
				foreach ($data as $row) {
					$productIds[] = $row['product_id'];
				}
				if (empty($productIds)) {
					$productIdsCondition = '= 0';
				} else {
					$productIds = array_unique($productIds);
					$productIds = array_map('intval', $productIds);
					$productIdsCondition = ' in(' . implode(',', $productIds) . ')';
				}

				$collection->getSelect()
					->where("main_table.product_id {$productIdsCondition}");
			}
		}
	}
}
?>