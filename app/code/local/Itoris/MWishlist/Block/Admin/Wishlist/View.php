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

class Itoris_MWishlist_Block_Admin_Wishlist_View extends Mage_Adminhtml_Block_Customer_Edit_Tab_Wishlist {

	protected $table = 'itoris_mwishlist_items';
	protected $tablenames = 'itoris_mwishlists';
	protected $tableWishlistItems = 'wishlist_item';
	protected $version = '';

	public function __construct() {
		parent::__construct();
		$this->setTemplate('itoris/mwishlist/wishlist.phtml');
	}

	protected function _prepareCollection() {
		$this->version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();
		$tableItoris = Mage::getSingleton('core/resource')->getTableName($this->table);
		$tableWishlistItems = Mage::getSingleton('core/resource')->getTableName($this->tableWishlistItems);
		$tableNames = Mage::getSingleton('core/resource')->getTableName($this->tablenames);
		if ((int)$this->version[2] >= 5) {
			/** @var $wishlist Mage_Wishlist_Model_Wishlist */
			$wishlist = Mage::getModel('wishlist/wishlist');
			$collection = $wishlist->loadByCustomer($this->_getCustomer())
					->setSharedStoreIds($wishlist->getSharedStoreIds(false))
					->getItemCollection()
					->resetSortOrder()
					->addDaysInWishlist()
					->addStoreData();
			$collection->getSelect()->join($tableItoris, "main_table.wishlist_item_id = $tableItoris.item_id");
			$collection->getSelect()->join($tableNames, "$tableItoris.multiwishlist_id = $tableNames.multiwishlist_id", array('wlname' => 'multiwishlist_name'));
			$collection->getSelect()->group('main_table.wishlist_item_id');

			$this->setCollection($collection);

			return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
		} elseif ((int)$this->version[2] == 4) {
			$wishlist = Mage::getModel('wishlist/wishlist');
			$collection = $wishlist->loadByCustomer($this->_getCustomer())
					->setSharedStoreIds($wishlist->getSharedStoreIds(false))
					->getProductCollection()
					->resetSortOrder()
					->addAttributeToSelect('name')
					->addAttributeToSelect('price')
					->addAttributeToSelect('small_image')
					->setDaysInWishlist(true)
					->addStoreData();

			$collection->getSelect()->join($tableItoris, "t_wi.wishlist_item_id = $tableItoris.item_id");
			$collection->getSelect()->join($tableNames, "$tableItoris.multiwishlist_id = $tableNames.multiwishlist_id", array(
																															 'wlname' => 'multiwishlist_name'));
			$this->setCollection($collection);

			return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();

		} else {

			$wishlist = Mage::getModel('wishlist/wishlist');
			$collection = $wishlist->loadByCustomer($this->_getCustomer())
					->getProductCollection()
					->addAttributeToSelect('name')
					->addAttributeToSelect('price')
					->addAttributeToSelect('small_image')
					->addStoreData();

			$collection->getSelect()->join($tableItoris, "$tableWishlistItems.wishlist_item_id = $tableItoris.item_id");
			$collection->getSelect()->join($tableNames, "$tableItoris.multiwishlist_id = $tableNames.multiwishlist_id", array(
																															 'wlname' => 'multiwishlist_name'));
			$this->setCollection($collection);

			return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();

		}


	}

	protected function _getCustomer() {
        return Mage::registry('current_customer');
    }

	protected function _prepareColumns() {
		$this->version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();
		if ((int)$this->version[2] >= 4) {
			parent::_prepareColumns();
				$this->addColumnAfter('wlname', array(
													 'header' => Mage::helper('wishlist')->__('Whishlist name'),
													 'index' => 'wlname',
													 'width' => '100px'
												), 'store');
			return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
		} else {

			$this->addColumn('product_name', array(
												  'header' => Mage::helper('customer')->__('Product name'),
												  'index' => 'name'
											 ));

			$this->addColumn('description', array(
												 'header' => Mage::helper('customer')->__('User description'),
												 'index' => 'description',
												 'renderer' => 'adminhtml/customer_edit_tab_wishlist_grid_renderer_description'
											));

			if (!Mage::app()->isSingleStoreMode()) {
				$this->addColumn('store', array(
											   'header' => Mage::helper('customer')->__('Added From'),
											   'index' => 'store_name',
											   'type' => 'store'
										  ));
			}
			$this->addColumn('wlname', array(
											'header' => Mage::helper('wishlist')->__('Whishlist name'),
											'index' => 'wlname',
											'width' => '100px'
									   ));

			$this->addColumn('visible_in', array(
												'header' => Mage::helper('customer')->__('Visible In'),
												'index' => 'store_id',
												'type' => 'store'
										   ));

			$this->addColumn('added_at', array(
											  'header' => Mage::helper('customer')->__('Date Added'),
											  'index' => 'added_at',
											  'gmtoffset' => true,
											  'type' => 'date'
										 ));

			$this->addColumn('days', array(
										  'header' => Mage::helper('customer')->__('Days in Wishlist'),
										  'index' => 'days_in_wishlist',
										  'type' => 'number'
									 ));

			$this->addColumn('action', array(
											'header' => Mage::helper('customer')->__('Action'),
											'index' => 'wishlist_item_id',
											'type' => 'action',
											'filter' => false,
											'sortable' => false,
											'actions' => array(
												array(
													'caption' => Mage::helper('customer')->__('Delete'),
													'url' => '#',
													'onclick' => 'return wishlistControl.removeItem($wishlist_item_id);'
												)
											)
									   ));

			return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();

		}
	}

	protected function _addColumnFilterToCollection($column) {
		if ((int)$this->version[2] >= 5) {
			/* @var $collection Mage_Wishlist_Model_Mysql4_Item_Collection */
			$collection = $this->getCollection();
			$value = $column->getFilter()->getValue();
			if ($collection && $value) {
				switch ($column->getId()) {
					case 'product_name':
						$collection->addProductNameFilter($value);
						break;
					case 'store':
						$collection->addStoreFilter($value);
						break;
					case 'days':
						$collection->addDaysFilter($value);
						break;
					case 'wlname':
						$this->addWishlistNameFilter($value);
						break;
					default:
						//print_r( $column->getFilter()->getCondition());exit;
						$collection()->addFieldToFilter($column->getIndex(), $column->getFilter()->getCondition());
						break;
				}
			}
		} else {
			if ($column->getId() == 'store') {
				$this->getCollection()->addFieldToFilter('item_store_id', $column->getFilter()->getCondition());
				return $this;
			}
			if ($column->getId() == 'wlname') {
				$value = $column->getFilter()->getValue();
				$this->addWishlistNameFilter($value);
				return $this;
			}

			if ($this->getCollection() && $column->getFilter()->getValue()) {
				$this->getCollection()->addFieldToFilter($column->getIndex(), $column->getFilter()->getCondition());
			}


		}


		return $this;
	}

	public function addWishlistNameFilter($wishlistName) {
		$tableNames = Mage::getSingleton('core/resource')->getTableName($this->tablenames);
		$collection = $this->getCollection();
		$collection->getSelect()->where("$tableNames.multiwishlist_name = '$wishlistName'");
		$this->setCollection($collection);
		return $collection;
	}

	protected function _prepareLayout() {
		$this->setChild('form',
            $this->getLayout()->createBlock('itoris_mwishlist/admin_wishlist_form','wishlist.form')
        );
        return parent::_prepareLayout();
	}
}
?>