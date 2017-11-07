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

class Itoris_Mwishlist_Block_Admin_Index_Index_Content_Form extends Mage_Adminhtml_Block_System_Config_Form {

	/** @var null|Itoris_MWishlist_Model_Settings */
	protected $settings = null;

	protected function _construct() {
		parent::_construct();
		$this->settings = Mage::getModel('itoris_mwishlist/settings');
		$website = Mage::getModel('core/website')->load($this->getRequest()->getParam('website', 0))->getId();
		$store = Mage::getModel('core/store')->load($this->getRequest()->getParam('store', 0))->getId();
		$this->settings->load($website, $store);
	}

	/**
	 * Retrieve store id by store code from the request
	 *
	 * @return int
	 */
	protected function getStoreId() {
		if ($this->getStoreCode()) {
			return Mage::app()->getStore($this->getStoreCode())->getId();
		}
		return 0;
	}

	/**
	 * Retrieve website id by website code from the request
	 *
	 * @return int
	 */
	protected function getWebsiteId() {
		if ($this->getWebsiteCode()) {
			return Mage::app()->getWebsite($this->getWebsiteCode())->getId();
		}
		return 0;
	}

	protected function _prepareForm() {
		$useWebsite = (bool)$this->getStoreId();
		if (!$useWebsite) {
			$useDefault = (bool)$this->getWebsiteId();
		} else {
			$useDefault = false;
		}

		$form_center = new Varien_Data_Form();
		$fieldset = $form_center->addFieldset('my_fieldset', array('legend' => Mage::helper('adminhtml')->__('Settings')));

		$fieldset->addField('enabled', 'select', array(
			'name'  => 'settings[enabled][value]',
			'label' => Mage::helper('adminhtml')->__('Extension Enabled'),
			'title' => Mage::helper('adminhtml')->__('ExtensionEnabled'),
			'values' => array(
				array(
					'value' => 1,
					'label' => Mage::helper('adminhtml')->__('Yes'),
				),
				array(
					'value' => 0,
					'label' => Mage::helper('adminhtml')->__('No'),
				),
			),
			'use_default' => $useDefault,
			'use_website' => $useWebsite,
			'use_parent_value' => $this->settings->isParentValue('enabled', $useWebsite),
		))->getRenderer()->setTemplate('itoris/mwishlist/index/index/element.phtml');

		$fieldset->addField('after_add_to_cart', 'select', array(
			'name'   => 'settings[after_add_to_cart][value]',
			'label'  => $this->__('After add to Cart'),
			'title'  => $this->__('After add to Cart'),
			'values' => array(
				array(
					'value' => Itoris_MWishlist_Model_Settings::AFTER_ADD_TO_CART_REMOVE,
					'label' => $this->__('Remove product(s) from Wishlist'),
				),
				array(
					'value' => Itoris_MWishlist_Model_Settings::AFTER_ADD_TO_CART_LEAVE,
					'label' => $this->__('Leave product(s) in Wishlist'),
				),
			),
			'use_default' => $useDefault,
			'use_website' => $useWebsite,
			'use_parent_value' => $this->settings->isParentValue('after_add_to_cart', $useWebsite),
		));

		$fieldset->addField('after_wishlist_selected', 'select', array(
			'name'   => 'settings[after_wishlist_selected][value]',
			'label'  => $this->__('After wishlist selected'),
			'title'  => $this->__('After wishlist selected'),
			'values' => array(
				array(
					'value' => Itoris_MWishlist_Model_Settings::AFTER_WISHLIST_SELECTED_OPEN_WISHLIST,
					'label' => $this->__('Redirect to wishlist'),
				),
				array(
					'value' => Itoris_MWishlist_Model_Settings::AFTER_WISHLIST_SELECTED_STAY_ON_PAGE,
					'label' => $this->__(' Stay on current page'),
				),
			),
			'use_default' => $useDefault,
			'use_website' => $useWebsite,
			'use_parent_value' => $this->settings->isParentValue('after_wishlist_selected', $useWebsite),
		));

		$fieldset->addField('responsive_width', 'text', array(
			'name'     => 'settings[responsive_width][value]',
			'label'    => $this->__('Switch to mobile view if browser width less than (px)'),
			'title'    => $this->__('Switch to mobile view if browser width less than (px)'),
			'use_default' => $useDefault,
			'use_website' => $useWebsite,
			'use_parent_value' => $this->settings->isParentValue('responsive_width', $useWebsite),
		));

		$form_center->setValues($this->settings->getDefaultData());
		$form_center->setAction($this->getUrl('*/*/savesettings', array('website' => $this->getRequest()->getParam('website'), 'store' => $this->getRequest()->getParam('store'))));
		$form_center->setMethod('post');
		$form_center->setUseContainer(true);
		$form_center->setId('edit_form');
		$this->setForm($form_center);
	}
}

?>