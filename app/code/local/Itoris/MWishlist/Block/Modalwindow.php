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

class Itoris_MWishlist_Block_Modalwindow extends Mage_Core_Block_Template {

	public function __construct() {
		parent::__construct();
		$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer(), true);
		/** @var $itorisWishlist Itoris_MWishlist_Model_Wishlist */
		$itorisWishlist = Mage::getModel('itoris_mwishlist/wishlist');
		$mainWishlistId = $itorisWishlist->getMainWishlistId(Mage::getSingleton('customer/session')->getCustomer()->getId());
		$newItems = $itorisWishlist->checkingForNewItems($wishlist->getId());
		if ($newItems) {
			if (Mage::getSingleton('customer/session')->getProductParam()) {
				$this->setTemplate('itoris/mwishlist/index/ajax/modalwindow.phtml');
				Mage::getSingleton('customer/session')->setProductParam(null);
			} else {
				$itorisWishlist->insertItemsInList($newItems, $mainWishlistId);
			}
		}
		if (Mage::getSingleton('customer/session')->getWishlistTabId()) {
			$this->getRequest()->setParam('tabId', Mage::getSingleton('customer/session')->getWishlistTabId());
		}
	}
}
?>