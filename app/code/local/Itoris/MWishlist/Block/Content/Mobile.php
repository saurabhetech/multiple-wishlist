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
 * @copyright  Copyright (c) 2014 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

class Itoris_MWishlist_Block_Content_Mobile extends Itoris_MWishlist_Block_Frontview {

	protected $dataBlock = null;

	public function setDataBlock($block) {
		$this->dataBlock = $block;
		return $this;
	}

	public function getDataBlock() {
		return $this->dataBlock;
	}

	protected function _beforeToHtml() {
		$this->includeMobileView = false;
		if (!$this->getDataBlock()) {
			parent::_beforeToHtml();
		}
		$this->setTemplate('itoris/mwishlist/index/index/wishlist_mobile.phtml');
		return $this;
	}

	public function getWishlistItems() {
		if ($this->getDataBlock()) {
			return $this->getDataBlock()->getWishlistItems();
		}
		return parent::getWishlistItems();
	}

	public function getTabId() {
		if ($this->getDataBlock()) {
			return $this->getDataBlock()->getTabId();
		}
		return parent::getTabId();
	}

	public function getHasWishlistItems() {
		if ($this->getDataBlock()) {
			return $this->getDataBlock()->getHasWishlistItems();
		}
		return parent::getHasWishlistItems();
	}
}
?>
