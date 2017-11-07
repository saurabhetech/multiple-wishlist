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

class Itoris_MWishlist_Model_Mysql4_Item_Collection extends Mage_Wishlist_Model_Mysql4_Item_Collection {

	protected $_productAttributesJoined = array();
	protected $_productFields = array('sku', 'type_id', 'created_at', 'updated_at');

	protected function _joinProductAttributeTable($attributeCode) {
		if (!in_array($attributeCode, $this->_productAttributesJoined)) {
			$entityTypeId = Mage::getResourceModel('catalog/config')->getEntityTypeId();
			$attribute = Mage::getModel('catalog/entity_attribute')->loadByCode($entityTypeId, $attributeCode);

			if ($attribute->getId() && $attribute->getBackendTable()) {
				$storeId = Mage::app()->getStore()->getId();
				$attributeTableAlias = 'product_'. $attributeCode .'_table';
				$this->getSelect()
					->join(
						array($attributeTableAlias => $attribute->getBackendTable()),
						$attributeTableAlias . '.entity_id=main_table.product_id' .
						' AND ('. $attributeTableAlias .'.store_id=' . $storeId . ' or '. $attributeTableAlias .'.store_id=0)' .
						' AND '. $attributeTableAlias .'.attribute_id=' . $attribute->getId().
						' AND '. $attributeTableAlias .'.entity_type_id=' . $entityTypeId,
						array()
					);

				$this->_productAttributesJoined[] = $attributeCode;
			} else {
				return false;
			}
		}
		return $this;
	}

	protected function _joinProductPriceTable() {
		$attributeTableAlias = 'product_price_table';
		$customerGroupId = (int)Mage::getSingleton('customer/session')->getCustomer()->getGroupId();
		$websiteId = (int)Mage::app()->getWebsite()->getId();
		$this->getSelect()
			->joinLeft(
				array($attributeTableAlias => Mage::getSingleton('core/resource')->getTableName('catalog_product_index_price')),
				$attributeTableAlias . '.entity_id=main_table.product_id' .
				' AND '. $attributeTableAlias .'.customer_group_id=' . $customerGroupId .
				' AND '. $attributeTableAlias .'.website_id=' . $websiteId,
				array()
			);

		return $this;
	}

	protected function _joinProductTable() {
		$tableAlias = 'product_table';
		$this->getSelect()
			->join(
				array($tableAlias => Mage::getSingleton('core/resource')->getTableName('catalog_product_entity')),
				$tableAlias . '.entity_id=main_table.product_id',
				array()
			);

		return $this;
	}

	public function setOrderByProductAttribute($attributeCode, $dir) {
		$attributeCode = trim($attributeCode);
		if ($attributeCode == 'price') {
			$this->_joinProductPriceTable();
			$this->getSelect()->order('product_price_table.min_price ' . $dir);
		} elseif (in_array($attributeCode, $this->_productFields)) {
			$this->_joinProductTable();
			$this->getSelect()->order('product_table.' . $attributeCode . ' ' . $dir);
		} else {
			$this->_joinProductAttributeTable($attributeCode);
			$attributeTableAlias = 'product_'. $attributeCode .'_table';
			$this->getSelect()->order($attributeTableAlias . '.value ' . $dir);
		}

		return $this;
	}
}
?>