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

class Itoris_MWishlist_Adminhtml_Itorismwishlist_WishlistController extends Itoris_MWishlist_Controller_Admin_Controller {

	/**
	 * Add wishlist action
	 */
	public function addAction() {
		$wishlistName = $this->getRequest()->getParam('wishlist_name');
		$wishlistEditable = ($this->getRequest()->getParam('wishlist_editable') == 'true') ? 1 : 0;
		$customerId = (int)$this->getRequest()->getParam('customer_id');
		$wishlistId = $this->getWishlistModel()->setname($wishlistName, $wishlistEditable, $customerId);
		echo Zend_Json::encode($this->getWishlistModel()->getNameById($wishlistId));
		exit;
	}

	/**
	 * Get json wishlist action
	 */
	public function getWishlistsAction() {
		$customerId = (int)$this->getRequest()->getParam('customer_id');
		$result = $this->getWishlistModel()->getnamecollection($customerId);
		echo Zend_Json::encode($result);
		exit;
	}

	public function deleteWishlistAction() {
		$id = (int)$this->getRequest()->getParam('wishlist_id');
		$this->getWishlistModel()->deleteWishlist($id);
		exit;
	}

	/**
	 * Get products json
	 */
	public function getProductsAction() {
		$id = (int)$this->getRequest()->getParam('wishlist_id');
		try{
			$products = $this->getWishlistModel()->getWishlistItems($id);
		} catch (Exception $e) {
			echo $e->getMessage();
			exit;
		}
		echo Zend_Json::encode($products);
		exit;
	}

	/**
	 * Add product to wishlist action
	 */
	public function addProductAction() {
		$productId = (int)$this->getRequest()->getParam('product_id');
		$wishlistId = (int)$this->getRequest()->getParam('wishlist_id');
		$customerId = (int)$this->getRequest()->getParam('customer_id');
		$customer = Mage::getModel('customer/customer')->load($customerId);
		/** @var $wishlistModel Mage_Wishlist_Model_Wishlist */
		$wishlistModel = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer, true);

		/** @var $product  Mage_Catalog_Model_Product */
		$product = Mage::getModel('catalog/product')->load($productId);
		$storeIds =  $product->getStoreIds();
		$params = array(
					'product'  => $productId,
					'qty'      => 1,
					'store_id' => isset($storeIds[0]) ? $storeIds[0] : 0,
		);
		$product->setWishlistStoreId($params['store_id']);
		$buyRequest = new Varien_Object($params);

		if (version_compare(Mage::getVersion(),'1.5.0', '<')) {
			 if ($this->getWishlistModel()->isItemInWishlist($customerId, $productId)) {
				 $result = array('error' => Mage::helper('itoris_mwishlist')->__('Product has been added in other wishlist!'));
				 echo Zend_Json::encode($result);
				 exit;
			 }
			 $item = Mage::getModel('wishlist/item');
             $newItem = $item->setProductId($productId)
                				->setWishlistId($wishlistModel->getId())
                				->setAddedAt(now())
               					 ->setStoreId($params['store_id'])
               					 ->save();
		} else {
			$itemId = (int)$this->getWishlistModel()->isItemInWishlist($customerId, $productId);
			if ($itemId) {
				$newItem = Mage::getModel('wishlist/item')->load($itemId);
				$newItem->setId(null);
				$newItem->setQty(1);
				$newItem->save();
			} else {
				$newItem = $wishlistModel->addNewItem($product, $buyRequest);
				//$wishlistModel->save();
			}
		}
		$this->getWishlistModel()->insertItemsInList($newItem->getId(), $wishlistId);
		$result = array();
		$result[] = array(
				'id'   => $newItem->getId(),
				'name' => $product->getName(),
		);
		echo Zend_Json::encode($result);
		exit;
	}

	/**
	 * Delete item from wishlist
	 */
	public function deleteItemAction() {
		$itemId = (int)$this->getRequest()->getParam('item_id');
		$this->getWishlistModel()->removeItem($itemId);
		exit;
	}

	public function renameWishlistAction() {
		$result = array();
		try {
			$wishlistId = $this->getRequest()->getParam('id');
			$wishlist = $this->getWishlistModel();
			$currentName = $wishlist->loadById($wishlistId);
			if ($currentName) {
				$newName = $this->getRequest()->getParam('name');
				if ($newName && $currentName['multiwishlist_name'] != trim($newName)) {
					$wishlist->updateName($wishlistId, trim($newName));
					$result['ok'] = true;
				}
			} else {
				$result['error'] = $this->__('Wishlist not found');
			}
		} catch(Exception $e) {
			$result['error'] = $this->__('Wishlist has not been renamed');
		}
		$this->getResponse()->setBody(Zend_Json::encode($result));
	}

    /**
     * Ajax load product grid
     */
    public function productGridAction() {
        $this->loadLayout('itoris_mwishlist_product_grid');
        $this->renderLayout();
    }

	/**
	 * @return Itoris_MWishlist_Model_Wishlist
	 */
	private function getWishlistModel() {
		return Mage::getModel('itoris_mwishlist/wishlist');
	}
	
	protected function _isAllowed() {
		return true;
	}
}
?>