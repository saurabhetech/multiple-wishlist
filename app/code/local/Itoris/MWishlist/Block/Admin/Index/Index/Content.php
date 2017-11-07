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

class Itoris_MWishlist_Block_Admin_Index_Index_Content extends Mage_Adminhtml_Block_Widget_Form_Container {

	public function __construct() {
		parent::__construct();
		$this->_addButton('save', array(
									   'label' => Mage::helper('adminhtml')->__('Save Config'),
									   'onclick' => 'editForm.submit();',
									   'class' => 'save',
								  ), 1);
		$this->_removeButton('reset');
		$this->_removeButton('back');
	}

	public function getHeaderText() {
		return Mage::helper('adminhtml')->__('Multiple Wishlists Configuration');
	}

	protected function _prepareLayout() {
		$this->setChild('form', $this->getLayout()->createBlock('itoris_mwishlist/admin_index_index_content_form'));
		return parent::_prepareLayout();
	}
}

?>