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

class Itoris_MWishlist_Block_Share_Wishlist extends Mage_Wishlist_Block_Share_Wishlist {

	protected function _prepareCollection($collection) {
		parent::_prepareCollection($collection);
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