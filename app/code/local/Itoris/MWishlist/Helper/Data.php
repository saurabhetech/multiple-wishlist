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
class Itoris_MWishlist_Helper_Data extends Mage_Core_Helper_Abstract {

	protected $alias = 'mwishlist';
	/** @var null|Itoris_MWishlist_Model_Settings */
	protected $settings = null;
	protected $isEnabledProductVisibilityFlag = null;
	protected $isEnabledCustomerRewardsFlag = null;

	public function isRegisteredAutonomous($website = null) {
		return true;
	}

	public function getSettings() {
		if (is_null($this->settings)) {
			$currentStore = Mage::app()->getStore()->getId();
			$currentWebsite = Mage::app()->getWebsite()->getId();
			$settingsModel = Mage::getModel('itoris_mwishlist/settings');
			$settingsModel->load($currentWebsite, $currentStore);
			$this->settings = $settingsModel;
		}

		return $this->settings;
	}

	public function isEnabled() {return true;
		return $this->getSettings()->getEnabled() && $this->isRegisteredAutonomous(Mage::app()->getWebsite());
	}

	public function deleteFromWishlist() {
		if ($this->getSettings()->getAfterAddToCart() == Itoris_MWishlist_Model_Settings::AFTER_ADD_TO_CART_LEAVE) {
			return false;
		}

		return true;
	}

	public function getAlias() {
		return $this->alias;
	}

	/**
	 * Get store id by parameter from the request
	 *
	 * @return int
	 */
	public function getStoreId() {
		if (Mage::app()->getRequest()->getParam('store')) {
			return Mage::app()->getStore(Mage::app()->getRequest()->getParam('store'))->getId();
		}
		return 0;
	}

	/**
	 * Get website id by parameter from the request
	 *
	 * @return int
	 */
	public function getWebsiteId() {
		if (Mage::app()->getRequest()->getParam('website')) {
			return Mage::app()->getWebsite(Mage::app()->getRequest()->getParam('website'))->getId();
		}
		return 0;
	}

	/**
	 * Check if enabled Itoris ProductPriceVisibility extension
	 *
	 * @return bool
	 */
	public function isEnabledProductVisibility() {
		if (is_null($this->isEnabledProductVisibilityFlag)) {
			$this->isEnabledProductVisibilityFlag = (bool)$this->isModuleEnabled('Itoris_ProductPriceVisibility');
		}
		return $this->isEnabledProductVisibilityFlag;
	}

	public function getProductVisibilityHelper() {
		if ($this->isEnabledProductVisibility()) {
			return Mage::helper('itoris_productpricevisibility/product');
		}
		throw new Exception('Itoris_ProductPriceVisibility not enabled!');
	}

	public function isEnabledCustomerRewards() {
		if (is_null($this->isEnabledCustomerRewardsFlag)) {
			$this->isEnabledCustomerRewardsFlag = (bool)$this->isModuleEnabled('Itoris_CustomerRewards');
		}
		return $this->isEnabledCustomerRewardsFlag;
	}
}
?>