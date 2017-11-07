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

class Itoris_MWishlist_Model_Mwishlistnames extends Varien_Object {

	const WISHLIST_EDITABLE = 1;
	const WISHLIST_NOT_EDITABLE = 0;

	protected $table = 'itoris_mwishlists';
	protected $itemsTable = 'itoris_mwishlist_items';
	protected $pricingdisplaycheckTable = 'itoris_mwishlists_pricing_display';
	protected $wishlistTable = 'wishlist_item';
	protected $tableCatalogProductEntity = 'catalog_product_entity';
	protected $tableCatalogProductEntityVarchar = 'catalog_product_entity_varchar';
	protected $tableEavAttribute = 'eav_attribute';
	protected $tableWishlist = 'wishlist';
	/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
	protected $db = null;

	protected function _construct() {
		parent::_construct();
		$this->db = Mage::getSingleton('core/resource')->getConnection('core_write');
		$this->table = Mage::getSingleton('core/resource')->getTableName($this->table);
		$this->pricingdisplaycheckTable = Mage::getSingleton('core/resource')->getTableName($this->pricingdisplaycheckTable);
		$this->itemsTable = Mage::getSingleton('core/resource')->getTableName($this->itemsTable);
		$this->wishlistTable = Mage::getSingleton('core/resource')->getTableName($this->wishlistTable);
		$this->tableCatalogProductEntity = Mage::getSingleton('core/resource')->getTableName($this->tableCatalogProductEntity);
		$this->tableCatalogProductEntityVarchar = Mage::getSingleton('core/resource')->getTableName($this->tableCatalogProductEntityVarchar);
		$this->tableEavAttribute = Mage::getSingleton('core/resource')->getTableName($this->tableEavAttribute);
		$this->tableWishlist = Mage::getSingleton('core/resource')->getTableName($this->tableWishlist);
	}

	public function getname($name) {
		$tableName = $this->table;
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		$name = $db->quote($name);
		$customerId = (int)Mage::getSingleton('customer/session')->getCustomerId();
		return $db->fetchRow("SELECT *  FROM $tableName WHERE `multiwishlist_name` LIKE $name and `multiwishlist_customer_id` = {$customerId}");
	}

	public function getNameById($id) {
		$tableName = $this->table;
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$id = (int)$id;
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		$result['name'] = $db->fetchRow("SELECT *  FROM $tableName WHERE `multiwishlist_id` = $id");
		return $result['name'];
	}

	public function getnamecollection($customerId = null, $editableOnly = false) {
		$tableName = $this->table;
		$customerId = $customerId ? $customerId : (int)Mage::getSingleton('customer/session')->getCustomerId();
		$this->checkMainWishlist($customerId);
		$editable = $editableOnly ? 'and multiwishlist_editable = 1' : '';
		$result = $this->db->fetchAll("SELECT *  FROM $tableName WHERE `multiwishlist_customer_id` = {$customerId} {$editable} order by multiwishlist_name");
		return $result;
	}

	public function checkMainWishlist($customerId) {
		$mainWishlist = $this->db->fetchOne("SELECT multiwishlist_id  FROM {$this->table} WHERE `multiwishlist_customer_id` = {$customerId}  and multiwishlist_is_main = 1");
		if (empty($mainWishlist)) {
			$editable = Itoris_MWishlist_Model_Mwishlistnames::WISHLIST_EDITABLE;
			$this->db->query("INSERT INTO {$this->table} (`multiwishlist_name`, `multiwishlist_customer_id`, `multiwishlist_editable`, `multiwishlist_is_main`) VALUES ('Main', $customerId, {$editable}, 1)");
			$mainWishlist = $this->db->lastInsertId();
		}
		return $mainWishlist;
	}

	public function setName($name, $editable = Itoris_MWishlist_Model_Mwishlistnames::WISHLIST_EDITABLE, $customerId = null) {
		$tableName = $this->table;
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		$name = $db->quote($name);
		$customerId = $customerId ? $customerId : Mage::getSingleton('customer/session')->getCustomerId();
		$db->query("INSERT INTO $tableName (`multiwishlist_name`, `multiwishlist_customer_id`, `multiwishlist_editable`) VALUES ($name, $customerId, {$editable});");
		return (int)$db->fetchOne("SELECT LAST_INSERT_ID()");
		//return $db->fetchRow("SELECT `multiwishlist_id`  FROM $tableName WHERE `multiwishlist_name` LIKE $name");
	}

	public function copyItemsBetweenLists($itemsId, $listID) {
		$tableItems = $this->itemsTable;
		$sqlstring = '';
		$temparray = array();
		$listID = (int)$listID;
		if (is_array($itemsId)) {
			foreach ($itemsId as $key => $value) {
				if (!$this->changeQtyIfProductInWishlist($value, $listID, false)) {
					$sqlstring = "($value,$listID)";
					$temparray[$key] = $sqlstring;
				}
			}
			$sqlstring = implode(',', $temparray);
		} else {
			$itemsId = (int)$itemsId;
			if (!$this->changeQtyIfProductInWishlist($itemsId, $listID, false)) {
				$sqlstring = "($itemsId,$listID)";
			}
		}
		if (!empty($sqlstring)) {
			/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
			$db = Mage::getSingleton('core/resource')->getConnection('core_write');
			$db->query("INSERT INTO $tableItems (`item_id`, `multiwishlist_id`) VALUES $sqlstring");
		}
	}

	public function moveItemsBetweenLists($itemId, $listID) {
		$wishlistItemTable = $this->wishlistTable;
		$tableItems = $this->itemsTable;
		$sqlstring = '';
		$temparray = array();
		$listID = (int)$listID;
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		if (is_array($itemId)) {
			foreach ($itemId as $key => $value) {
				if (!$this->changeQtyIfProductInWishlist($value, $listID)) {
					$sqlstring = "(`item_id` = $value)";
					$temparray[$key] = $sqlstring;
				}
			}
			$sqlstring = implode('OR', $temparray);
		} else {
			$itemId = (int)$itemId;
			if (!$this->changeQtyIfProductInWishlist($itemId, $listID)) {
				$sqlstring = "`item_id` = $itemId";
			}
		}
		if (!empty($sqlstring)) {
			$db->query("UPDATE $tableItems SET `multiwishlist_id` = $listID WHERE $sqlstring");
		}
		/** @var $wishlist Mage_Wishlist_Model_Wishlist */
		$wishlist = Mage::getModel('wishlist/wishlist');
		$wishlist->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer());
		$newItemsId = $this->checkingForNewItems($wishlist->getId());
		foreach ($newItemsId as $newItemId) {
			$item = Mage::getModel('wishlist/item')->load($newItemId);
			if ($item->getId()) {
				$item->delete();
			}
		}
	}

	public function checkingForNewItems($wishlistId) {
		$wishlistId = (int)$wishlistId;
		$tableItems = $this->itemsTable;
		$wishlistItemTable = $this->wishlistTable;
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		return $db->fetchAll("SELECT `wishlist_item_id` FROM $wishlistItemTable WHERE `wishlist_item_id` not in (SELECT `item_id` FROM $tableItems) And `wishlist_id` = $wishlistId");
	}

	public function insertItemsInList($itemsId, $listID) {
		$tableItems = $this->itemsTable;
		$sqlString = '';
		$temparray = array();
		$listID = (int)$listID;
		if (is_array($itemsId)) {
			foreach ($itemsId as $value) {
				if (isset($value['wishlist_item_id'])) {
					$wishlistItemId = (int)$value['wishlist_item_id'];
					if (!$this->changeQtyIfProductInWishlist($wishlistItemId, $listID)) {
						$sqlString = "($wishlistItemId,$listID)";
						$temparray[] = $sqlString;
					}
				}
			}

			$sqlString = implode(',', $temparray);
		} else {
			if (!$this->changeQtyIfProductInWishlist($itemsId, $listID)) {
				$itemsId = (int)$itemsId;
				$sqlString = "($itemsId,$listID)";
			}
		}
		if (!empty($sqlString)) {
			/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
			$db = Mage::getSingleton('core/resource')->getConnection('core_write');
			$db->query("INSERT INTO $tableItems (`item_id`,`multiwishlist_id`) VALUES $sqlString");
			Mage::dispatchEvent('itoris_wishlist_update', array('wishlist' => $this));
		}
	}

	protected function changeQtyIfProductInWishlist($itemId, $multiwishlistId, $deleteItemIfExists = true) {
		/** @var $itemModel Mage_Wishlist_Model_Item */
		$itemModel = Mage::getModel('wishlist/item');
		if (method_exists($itemModel, 'loadWithOptions')) {
			$itemModel->loadWithOptions($itemId);
		} else {
			$itemModel->load($itemId);
		}
		$productId = $itemModel->getProductId();
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		/** @var $wishlist Mage_Wishlist_Model_Wishlist */
		$wishlist = Mage::getModel('wishlist/wishlist');
		$wishlist->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer());
		$items = $wishlist->getItemCollection()->addFieldToFilter('product_id', array('eq' => $productId));
		if ($items->getSize()) {
			foreach ($items as $item) {
				$result = $db->fetchRow("select * from {$this->itemsTable} where `item_id`={$item->getId()} and `multiwishlist_id`={$multiwishlistId}");
				if (!empty($result)) {
					if (method_exists($item, 'loadWithOptions')) {
						$item->loadWithOptions($item->getId());
						$origQty = $item->getQty();
						$item->setQty($itemModel->getQty());
						$isExistsEqual = $this->isRepresent($itemModel, $itemModel->getProduct(), $item->getBuyRequest());
						$item->setQty($origQty);
					} else {
						$isExistsEqual = true;
					}
					if ($isExistsEqual) {
						$item->setQty($item->getQty() + $itemModel->getQty());
						$item->setSkipOrigSetQty(true);
						$item->save();
						if ($deleteItemIfExists) {
							$itemModel->delete();
						}
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Check if item represent product with buyrequest
	 * (for rewrite standard magento function _compareOptions)
	 *
	 * @param $item
	 * @param $product
	 * @param $buyRequest
	 * @return bool
	 */
	public function isRepresent($item, $product, $buyRequest) {
		if ($item->getProductId() != $product->getId()) {
			return false;
		}

		$selfOptions = $item->getBuyRequest()->getData();

		if (empty($buyRequest) && !empty($selfOptions)) {
			return false;
		}
		if (empty($selfOptions) && !empty($buyRequest)) {
			if (!$product->isComposite()){
				return true;
			} else {
				return false;
			}
		}

		$requestArray = $buyRequest->getData();

		if(!$this->_compareOptions($requestArray, $selfOptions)){
			return false;
		}
		if(!$this->_compareOptions($selfOptions, $requestArray)){
			return false;
		}
		return true;
	}

	/**
	 * Like standard item->_compareOptions
	 * but added check on empty values
	 *
	 * @param $options1
	 * @param $options2
	 * @return bool
	 */
	protected function _compareOptions($options1, $options2) {
		$skipOptions = array('id', 'qty', 'return_url');
		foreach ($options1 as $code => $value) {
			if (in_array($code, $skipOptions)) {
				continue;
			}
			if (empty($value)) {
				continue;
			}
			if (is_array($value)) {
				$allEmpty = true;
				foreach ($value as $subValue) {
					if (!empty($subValue)) {
						$allEmpty = false;
						break;
					}
				}
				if ($allEmpty) {
					continue;
				}
			}
			if (!isset($options2[$code]) || $options2[$code] != $value) {
				return false;
			}
		}
		return true;
	}

	public function removeItem($itemId) {
		$wishlistItemTable = $this->wishlistTable;
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		$itemId = (int)$itemId;
		$result = $db->query("DELETE FROM $wishlistItemTable WHERE `wishlist_item_id` = $itemId");
		Mage::dispatchEvent('itoris_wishlist_update', array('wishlist' => $this));
		return $result;
	}

	public function deleteWishlist($wishlistId) {
		$tableName = $this->table;
		$wishlistItemTable = $this->wishlistTable;
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		$wishlistId = (int)$wishlistId;
		$db->query("DELETE FROM $wishlistItemTable WHERE `wishlist_item_id` in (SELECT `item_id` FROM $this->itemsTable WHERE `multiwishlist_id` = $wishlistId)");
		$db->query("DELETE FROM $tableName WHERE `multiwishlist_id` = $wishlistId");
		Mage::dispatchEvent('itoris_wishlist_update', array('wishlist' => $this));
	}

	public function isProductInWishlist($productId) {
		$itemsTable = $this->itemsTable;
		$wishlistItemTable = $this->wishlistTable;
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		$productId = (int)$productId;
		return $db->fetchRow("SELECT * FROM $wishlistItemTable Inner Join  $itemsTable ON $wishlistItemTable .`wishlist_item_id`= $itemsTable.`item_id` where `product_id` = $productId limit 1");
	}

	public function getWishlistItems($id) {
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		return $db->fetchAll("SELECT e.item_id as id, pv.value as name FROM {$this->itemsTable} as e
					   inner join {$this->wishlistTable} as w
					   on w.wishlist_item_id = e.item_id
					   inner join {$this->tableCatalogProductEntity} as p
					   on p.entity_id = w.product_id
					   inner join {$this->tableEavAttribute} as a
					   on a.entity_type_id = p.entity_type_id and a.attribute_code = 'name'
					   inner join {$this->tableCatalogProductEntityVarchar} as pv
					   on pv.entity_id = p.entity_id and pv.attribute_id = a.attribute_id
					   where e.multiwishlist_id = {$id}
					   group by e.item_id
					   order by pv.value
		");
	}

	public function isItemInWishlist($customerId, $productId) {
		return $this->db->fetchOne("select w.wishlist_item_id from {$this->tableWishlist} as e
							 inner join {$this->wishlistTable} as w
							 on w.wishlist_id = e.wishlist_id and w.product_id = {$productId}
							 where e.customer_id = {$customerId}
		");
	}

	public function getWishlistIdByItemId($itemId) {
		return (int)$this->db->fetchOne("select multiwishlist_id from {$this->itemsTable}
							where item_id = {$itemId}
		");
	}

	public function countWishlistItemsByCustomerId($customerId = null) {
		/**@var $db Varien_Db_Adapter_Pdo_Mysql*/
		$db = Mage::getSingleton('core/resource')->getConnection('core_write');
		if (!($customerId = (int)$customerId)) {
			if (!($customer = Mage::registry('current_customer'))) {
				$customer = Mage::getSingleton('customer/session')->getCustomer();
			}
			if ($customer) {
				$customerId = $customer->getId();
			}
		}
		$items = array();
		if ($customerId) {
			$items = $db->fetchAll("select * from {$this->table} as e inner join {$this->itemsTable} as i on i.multiwishlist_id = e.multiwishlist_id where e.multiwishlist_customer_id = {$customerId}");
		}

		return count($items);
	}

	public function setPricingDisplay($data) {
		$customerId = $data['customerId'];
		$wishlistId = $data['wishlistId'];
		$display_price = $data['display_price'];
		$display_price_feature = $this->db->fetchOne("SELECT display_price_id  FROM {$this->pricingdisplaycheckTable} WHERE `multiwishlist_customer_id` = {$customerId}  and multiwishlist_id = {$wishlistId}");
		if (empty($display_price_feature)) {
			$this->db->query("INSERT INTO {$this->pricingdisplaycheckTable} (`multiwishlist_id`, `multiwishlist_customer_id`, `multiwishlist_display_price`) VALUES ($wishlistId, $customerId, {$display_price})");
			return true;
		} else {
			$this->db->query("UPDATE {$this->pricingdisplaycheckTable} SET `multiwishlist_display_price` = {$display_price} WHERE `display_price_id` = {$display_price_feature}");
			return true;
		}
	}

	public function getPricingDisplay($data) {
		$customerId = $data['customerId'];
		$wishlistId = $data['wishlistId'];
		$display_price = $data['display_price'];
		$display_price_feature = $this->db->fetchOne("SELECT multiwishlist_display_price  FROM {$this->pricingdisplaycheckTable} WHERE `multiwishlist_customer_id` = {$customerId}  and multiwishlist_id = {$wishlistId}");
		if (empty($display_price_feature)) {
			return false;
		} else {
			return $display_price_feature;
		}
	}
}
?>