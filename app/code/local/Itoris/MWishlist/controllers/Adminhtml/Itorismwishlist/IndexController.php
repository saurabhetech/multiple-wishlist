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

class Itoris_MWishlist_Adminhtml_Itorismwishlist_IndexController extends Itoris_MWishlist_Controller_Admin_Controller {

	public function indexAction() {
		$this->_getSession()->setBeforUrl(Mage::helper('core/url')->getCurrentUrl());
		/**@var $website Mage_Core_Model_Website*/
		$website = Mage::getModel('core/website')->loadConfig($this->getRequest()->getParam('website'));
		if ($this->getRequest()->getParam('website')) {
		}

		$this->loadLayout();
		$this->renderLayout();
	}

	public function saveSettingsAction() {
		$websiteId = $this->getDataHelper()->getWebsiteId();
		$storeId = $this->getDataHelper()->getStoreId();
		if ($storeId) {
			$scope = 'store';
			$scopeId = (int)$storeId;
		} elseif ($websiteId) {
			$scope = 'website';
			$scopeId = $websiteId;
		} else {
			$scope = 'default';
			$scopeId = 0;
		}
		$data = $this->getRequest()->getPost();

		try {
			if (isset($data['settings'])) {
				$settings = $data['settings'];
				$model = Mage::getModel('itoris_mwishlist/settings');
				$model->save($settings, $scope, $scopeId);
				$this->_getSession()->addSuccess($this->__('Settings have been saved'));
			} else {
				$this->_redirect('*/*');
				return;
			}
		} catch (Exception $e) {
			$this->_getSession()->addError($this->__('Settings have not been saved'));
			Mage::logException($e);
		}

		$this->_redirectReferer($this->_getSession()->getBeforUrl());
	}
	
	protected function _isAllowed() {
		return Mage::getSingleton('admin/session')->isAllowed('admin/system/itoris_extensions/mwishlist');
	}
}
?>