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

class Itoris_MWishlist_Block_Share_Items extends Mage_Wishlist_Block_Share_Email_Items {

	public function getWishlistItems() {
		$shareWishlistId = Mage::getSingleton('customer/session')->getData('share_wishlist_id');
        if (is_null($this->_collection)) {
			if (version_compare(Mage::getVersion(), '1.5.0.0', '>=')) {
				$this->_collection = $this->_getWishlist()
                	->getItemCollection()
                	->addStoreFilter(Mage::app()->getStore()->getId());
				$this->_collection
					->getSelect()
					->join(array(
								'wishlists' => Mage::getSingleton('core/resource')->getTableName('itoris_mwishlist_items')
						   ),
						   'main_table.wishlist_item_id = wishlists.item_id and wishlists.multiwishlist_id = ' . $shareWishlistId
				);
			} else {
				$attributes = Mage::getSingleton('catalog/config')->getProductAttributes();
            	$this->_collection = $this->_getWishlist()
                ->getProductCollection()
                ->addAttributeToSelect($attributes)
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addUrlRewrite();
				$this->_collection
					->getSelect()
					->join(array(
								'wishlists' => Mage::getSingleton('core/resource')->getTableName('itoris_mwishlist_items')
						   ),
						   't_wi.wishlist_item_id = wishlists.item_id and wishlists.multiwishlist_id = ' . $shareWishlistId
				);
           		 Mage::getSingleton('catalog/product_visibility')
                	->addVisibleInSiteFilterToCollection($this->_collection);
			}

            $this->_prepareCollection($this->_collection);
        }

        return $this->_collection;
    }
}
?>