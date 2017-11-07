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

class Itoris_MWishlist_Block_Popup extends Mage_Core_Block_Template {

	protected $isRegistered = true;

	protected function _construct() {
		parent::_construct();
		$this->isRegistered = Mage::helper('itoris_mwishlist')->isEnabled();
	}

	protected function _prepareLayout() {
		if ($this->isRegistered) {
			$head = $this->getLayout()->getBlock('head');
			if ($head) {
				$head->addJs('itoris/mwishlist/popup.js');
				$head->addItem('js_css', 'itoris/mwishlist/popup.css');
			}
		}
		return parent::_prepareLayout();
	}

	public function getWishlists() {
		if (Mage::getSingleton('customer/session')->getCustomer()->getId()) {
			$wishlists = Mage::getModel('itoris_mwishlist/wishlist')->getWishlists(null, true);
		} else {
			$wishlists = array(
				array(
					'multiwishlist_name'    => $this->__('Main'),
					'multiwishlist_id'      => 'main',
					'multiwishlist_is_main' => true
				),
			);
		}
		if (!is_array($wishlists)) {
			return array();
		}
		return $wishlists;
	}

	public function getConfigJson() {
		$config = array(
			'stay_on_page'         => Mage::helper('itoris_mwishlist')->getSettings()->getAfterWishlistSelected() == Itoris_MWishlist_Model_Settings::AFTER_WISHLIST_SELECTED_STAY_ON_PAGE
										&& !$this->_isCartPage(),
			'check_wishlist_url'   => Mage::getUrl('wishlist/index/ajax/'),
			'add_product_ajax_url' => Mage::getUrl('wishlist/ajax/addProduct/'),
			'wishlist_url'         => Mage::getUrl('wishlist'),
			'update_wishlist_link_url' => Mage::getUrl('wishlist/index/itemCount'),
			'message_empty_name'   => $this->__('Enter the name of new wishlist'),
			'message_name_exists'  => $this->__('Wishlist with such a name already exists. Please choose a different name.'),
			'route_name'           => (string)Mage::getConfig()->getNode('frontend/routers/wishlist/args')->frontName,
		);

		return Zend_Json::encode($config);
	}

	protected function _isCartPage() {
		return $this->getRequest()->getModuleName() == 'checkout' && $this->getRequest()->getControllerName() == 'cart';
	}

	protected function _toHtml() {
		if ($this->isRegistered) {
			return parent::_toHtml();
		}

		return '';
	}
}
?>