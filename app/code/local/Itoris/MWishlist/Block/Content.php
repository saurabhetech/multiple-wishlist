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

class Itoris_MWishlist_Block_Content extends Mage_Core_Block_Template {

	public function __construct() {
		parent::__construct();
		$this->setTemplate('itoris/mwishlist/index/ajax/fullcontent.phtml');
	}

	public function getJsonWishlistsConfig() {
		$config = array(
			'responsive_width' => Mage::helper('itoris_mwishlist')->getSettings()->getResponsiveWidth(),
		);

		return Zend_Json::encode($config);
	}
}
?>