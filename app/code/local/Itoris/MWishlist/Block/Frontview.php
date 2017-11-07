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

class Itoris_MWishlist_Block_Frontview extends Mage_Core_Block_Template {

	protected $table = 'itoris_mwishlist_items';
	private $tabId = 0;
	private $hasWishlistItems = false;
	protected $productRewardsHelper = null;
	protected $wishlistItemsCollection = null;
	protected $includeMobileView = true;
	protected $editableWishlists = null;

	protected function _beforeToHtml() {
		$version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();
		$this->_prepareCollection();
		parent::_beforeToHtml();
		if (!$this->getTemplate()) {
			if ((int)$version[2] >= 4) {
				$this->setTemplate('itoris/mwishlist/index/index/wishlisttemplate.phtml');
			} elseif ((int)$version[2] == 3) {
				$this->setTemplate('itoris/mwishlist/index/index/listtemplatev3.php');
			}
		}
		return $this;
	}

	protected function _toHtml() {
		$html = parent::_toHtml();
		if ($this->includeMobileView) {
			$mobileBlock = $this->getLayout()->createBlock('itoris_mwishlist/content_mobile');
			$mobileBlock->setDataBlock($this);
			$html .= $mobileBlock->toHtml();
		}
		return $html;
	}

	protected function _prepareCollection() {
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$customerId = $customer->getId();
		$wishlist = Mage::getModel('wishlist/wishlist');
		$wishlist->loadByCustomer($customer, true);

		$tableItoris = Mage::getSingleton('core/resource')->getTableName($this->table);
		$version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();

		$tabId = (int)$this->getRequest()->getParam('tabId');
		if (!$tabId) {
			/** @var $wishlistModel Itoris_MWishlist_Model_Mwishlistnames */
			$wishlistModel = Mage::getModel('itoris_mwishlist/mwishlistnames');

			$tabId = (int)$wishlistModel->checkMainWishlist($customerId);
		}
		$this->tabId = $tabId;

		$collection = null;
		if ((int)$version[2] >= 4) {
			$collection = Mage::getResourceModel('itoris_mwishlist/item_collection');
			$collection->addWishlistFilter($wishlist);
			$collection->getSelect()->join($tableItoris, "wishlist_item_id = $tableItoris.item_id");
			$collection->getSelect()->where("multiwishlist_id = $tabId");
			$collection->setOrderByProductAttribute('name', 'asc');
			$collection->getSelect()->group('main_table.wishlist_item_id');
		} elseif ((int)$version[2] == 3) {
			$collection = $wishlist->getProductCollection()
						->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
						->addStoreFilter()
						->addAttributeToSort('added_at', 'desc')
						->addUrlRewrite();
			$collection->getSelect()->join($tableItoris, "wishlist_item_id = $tableItoris.item_id");

			$collection->getSelect()->where("multiwishlist_id = $tabId");
			Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
			Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($collection);
		}
		if ($collection) {
			if ($collection->getSize()) {
				$this->hasWishlistItems = true;
			} else {
				$this->hasWishlistItems = false;
			}
			$this->wishlistItemsCollection = $collection;
		}
		return $this;
	}

	public function getWishlistItems() {
		return $this->wishlistItemsCollection;
	}

	public function getTabId() {
		return $this->tabId;
	}

	public function getHasWishlistItems() {
		return $this->hasWishlistItems;
	}

	public function getEditableWishlists() {
		if ($this->editableWishlists === null) {
			$this->editableWishlists = $this->getMwishlistModel()->getWishlists(null, true);
		}

		return $this->editableWishlists;
	}

	/**
	 * @return Itoris_MWishlist_Model_Wishlist
	 */
	public function getMwishlistModel() {
		return Mage::getModel('itoris_mwishlist/wishlist');
	}

	public function getDetailsLinkHtml($item) {
		$block = $this->getLayout()->createBlock('wishlist/customer_wishlist_item_options');
		if ($block) {
			if (method_exists($block, 'getOptionsRenderCfg')) {
				$block->setItem($item);
				return $block->toHtml();
			}
			return $this->_getDetailsHtml($item, $block);
		}
		return '';
	}

	/**
	 * For magento <1.7
	 *
	 * @param Mage_Wishlist_Model_Item $item
	 * @return string
	 */
	protected function _getDetailsHtml(Mage_Wishlist_Model_Item $item, $block) {
		$wishlist = $this->getLayout()->createBlock('wishlist/customer_wishlist');
		if ($wishlist) {
			$wishlist->setChild('item_options', $block);
			return $wishlist->getDetailsHtml($item);
		}
		return '';
	}

	public function getProductPriceVisibilityConfig($product) {
		return $this->getDataHelper()->getProductVisibilityHelper()->getPriceVisibilityConfig($product);
	}

	public function getPointsPrice($product, $finalPrice) {
		if ($this->getDataHelper()->isEnabledCustomerRewards()) {
			return $this->getProductRewardsHelper()->getProductPointPrice($product, $finalPrice);
		}

		return null;
	}

	public function getProductRewardsHelper() {
		if (is_null($this->productRewardsHelper)) {
			$this->productRewardsHelper = Mage::helper('itoris_customerrewards/product');
		}
		return $this->productRewardsHelper;
	}

	public function getProductUrl($product) {
		$version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();
		if ((int)$version[2] >= 5) {
			return Mage::getModel('catalog/product')->load($product->getId())->getProductUrl();
			//return $product->getProductUrl();
		}

		return Mage::getSingleton('catalog/product_url')->getUrl($product);
	}

	public function getAddToCartItemUrl($item) {
		return $this->getUrl('wishlist/index/cart', array(
			'item' => $item->getWishlistItemId(),
			'qty'  => '{{qty}}',
		));
	}

	/**
	 * @return Itoris_MWishlist_Helper_Data
	 */
	public function getDataHelper() {
		return Mage::helper('itoris_mwishlist');
	}
}
?>