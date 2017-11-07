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

/**
 * Covering model for deprecated class Itoris_MWishlist_Model_Mwishlistnames
 */

class Itoris_MWishlist_Model_Wishlist extends Itoris_MWishlist_Model_Mwishlistnames {

	/**
	 * Retrieve wishlists for current customer if customerId not specified
	 * only editable wishlists if editableOnly set true
	 *
	 * @param null $customerId
	 * @param bool $editableOnly
	 * @return array
	 */
	public function getWishlists($customerId = null, $editableOnly = false) {
		return parent::getnamecollection($customerId, $editableOnly);
	}

	/**
	 * Create new wishlist and return its id
	 *
	 * @param $name
	 * @param int $editable
	 * @param null $customerId
	 * @return int
	 * @throws Exception
	 */
	public function createWishlist($name, $editable = Itoris_MWishlist_Model_Mwishlistnames::WISHLIST_EDITABLE, $customerId = null) {
		$existsWishlistId = $this->isWishlistNameExists($name);
		if ($existsWishlistId) {
			return $existsWishlistId;
		}

		return parent::setName($name, $editable, $customerId);
	}

	/**
	 * Load wishlist by name
	 *
	 * @param $name
	 * @return mixed
	 */
	public function loadByName($name) {
		return parent::getname($name);
	}

    public function isWishlistExistsByName($name) {
        $result = $this->loadByName($name);
        return isset($result['multiwishlist_name']);
    }

	public function isWishlistNameExists($name) {
		$wishlist = $this->loadByName($name);
		return isset($wishlist['multiwishlist_id']) ? $wishlist['multiwishlist_id'] : null;
	}

	public function getMainWishlistId($customerId) {
		return (int)parent::checkMainWishlist($customerId);
	}

	public function updateName($id, $newName) {
		$newName = $this->db->quote($newName);
		$id = intval($id);
		$this->db->query("update {$this->table} set `multiwishlist_name` = {$newName} where `multiwishlist_id` = {$id}");
		return $this;
	}

	public function loadById($id) {
		return $this->getNameById($id);
	}

	public function getWishlistNameById($id) {
		$wishlist = $this->loadById($id);
		if (isset($wishlist['multiwishlist_name'])) {
			return $wishlist['multiwishlist_name'];
		}
		return null;
	}
	
	public function getProtectedItems() {
		$customerId = (int) Mage::getSingleton('customer/session')->getId();
		$_protectedWishlists = $this->db->fetchAll("select `multiwishlist_id` from {$this->table} where `multiwishlist_customer_id` = {$customerId} and `multiwishlist_editable` = 0");
		$protectedWishlists = array(0);
		foreach($_protectedWishlists as $wishlist) $protectedWishlists[] = $wishlist['multiwishlist_id'];
		$_protectedItems = $this->db->fetchAll("select `item_id` from {$this->itemsTable} where `multiwishlist_id` IN (".implode(',', $protectedWishlists).")");
		$protectedItems = array();
		foreach($_protectedItems as $item) $protectedItems[] = $item['item_id'];
		return $protectedItems;
	}
}
?>