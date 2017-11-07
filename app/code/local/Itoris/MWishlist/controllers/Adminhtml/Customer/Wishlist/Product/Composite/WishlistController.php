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
 * @package	ITORIS_MWISHLIST
 * @copyright  Copyright (c) 2012 ITORIS INC. (http://www.itoris.com)
 * @license	http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Customer' . DS . 'Wishlist' . DS . 'Product' . DS . 'Composite' . DS . 'WishlistController.php';

class Itoris_MWishlist_Adminhtml_Customer_Wishlist_Product_Composite_WishlistController
	extends Mage_Adminhtml_Customer_Wishlist_Product_Composite_WishlistController
{

	public function updateAction() {
		$this->_initData();
		$itemId = (int)$this->_wishlistItem->getId();
		$product = clone $this->_wishlistItem->getProduct();
		$product->setWishlistStoreId($this->_wishlistItem->getStoreId());
		$beforeMwishlistId = (int)$this->getBeforeMWishlistId($itemId);
		$params = new Varien_Object();
		$params =  $params->setCurrentConfig($this->_wishlistItem->getBuyRequest());

		$buyRequest = new Varien_Object($this->getRequest()->getParams());
		$buyRequest = Mage::helper('catalog/product')->addParamsToBuyRequest($buyRequest, $params);
        $cartCandidates = $product->getTypeInstance(true)->processConfiguration($buyRequest, $product);
        foreach ($cartCandidates as $candidate) {
            if ($candidate->getParentProductId()) continue;
			$storeId = $product->hasWishlistStoreId() ? $product->getWishlistStoreId() : $product->getStoreId();
			$candidate->setWishlistStoreId($storeId);
			$qty = $candidate->getQty() ? $candidate->getQty() : 1;
            $item = Mage::getModel('wishlist/item');
            $item->setProductId($product->getId())
                ->setWishlistId($this->_wishlist->getId())
                ->setAddedAt($this->_wishlistItem->getAddedAt())
                ->setStoreId($storeId)
                ->setOptions($product->getCustomOptions())
                ->setProduct($product)
                ->setQty($qty)
                ->save();
			$this->_wishlistItem->isDeleted(true);
			$this->_wishlistItem->save();
			$this->updateItemId($item->getId(), $beforeMwishlistId);
			break;
		}

		$updateResult = new Varien_Object();
		$updateResult->setOk(true);
        $updateResult->setJsVarName($this->getRequest()->getParam('as_js_varname'));
        Mage::getSingleton('adminhtml/session')->setCompositeProductResult($updateResult);
        $this->_redirect('*/catalog_product/showUpdateResult');
		
		return false;
	}

	protected function updateItemId($itemId, $wishlistId) {
		$tableItoris = Mage::getSingleton('core/resource')->getTableName('itoris_mwishlist_items');
		$connection = Mage::getSingleton('core/resource')->getConnection('write');

		$connection->query("insert into {$tableItoris} (item_id, multiwishlist_id) values ({$itemId}, {$wishlistId})");
		return $this;
	}

	public function getBeforeMWishlistId($itemId) {
		$tableItoris = Mage::getSingleton('core/resource')->getTableName('itoris_mwishlist_items');
		$connection = Mage::getSingleton('core/resource')->getConnection('write');
		return $connection->fetchOne("select multiwishlist_id from {$tableItoris}  where item_id = {$itemId}");
	}
}

?>
