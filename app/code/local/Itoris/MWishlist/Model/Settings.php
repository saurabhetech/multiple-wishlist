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

/**
 * @method getAfterWishlistSelected()
 */

class Itoris_MWishlist_Model_Settings extends Varien_Object {

	const AFTER_ADD_TO_CART_REMOVE = 0;
	const AFTER_ADD_TO_CART_LEAVE = 1;
	const AFTER_WISHLIST_SELECTED_OPEN_WISHLIST = 0;
	const AFTER_WISHLIST_SELECTED_STAY_ON_PAGE = 1;

	/** @var Varien_Db_Adapter_Pdo_Mysql */
	protected $_resource;
	protected $_table = 'itoris_mwishlist_settings';
	protected $_scope;
	protected $_scopeId;
	protected $_settings;
	protected $_textOptions = array();
	protected $defaultSettings = array(
		'responsive_width' => 600,
	);

	protected function _construct() {
		$this->_getConnection();
		$this->_table = Mage::getSingleton('core/resource')->getTableName($this->_table);
	}

	/**
	 * Save settings
	 *
	 * @param $settings
	 * @param string $scope
	 * @param int $scopeId
	 */
	public function save($settings, $scope = 'default', $scopeId = 0) {
		$this->_scope = $this->_resource->quote($scope);
		$this->_scopeId = (int)$scopeId;

		$this->_deleteSettings();
		$newSettings = array();
		foreach ($settings as $key => $value) {
			$value = isset($value['value']) ? $value['value'] : 0;
			if (!isset($settings[$key]['use_parent'])  || $scope == 'default') {
				$newSettings[$key] = array('value' => $value, 'type' => 'default');
				if ($this->_isTextOption($key)) {
					$newSettings[$key]['type'] = 'text';
				}
			}
		}

		if (!empty($newSettings)) {
			$this->_saveSettings($newSettings);
		}
		$this->_scope = null;
		$this->_scopeId = null;
	}

	public function dataLoad($websiteId, $storeId) {
		return $this->load($websiteId, $storeId);
	}

	/**
	 * Load settings for a scope view
	 *
	 * @param $websiteId
	 * @param $storeId
	 * @return Itoris_MWishlist_Model_Settings
	 */
	public function load($websiteId, $storeId) {
		$websiteId = (int)$websiteId;
		$storeId = (int)$storeId;
		$settings = $this->_resource->fetchAll("
			SELECT e.key, e.scope, e.value as value
			FROM $this->_table as e
			WHERE (e.scope = 'default' and e.scope_id = 0)
			OR (e.scope = 'website' and e.scope_id = $websiteId)
			OR (e.scope = 'store' and e.scope_id = $storeId)
		");
		$this->_saveSettingsIntoArray($settings);
		return $this;
	}

	private function _saveSettingsIntoArray($settings) {
		foreach($settings as $value) {
			$this->_settings[$value['scope']][$value['key']] = $value['value'];
		}
	}

	public function __call($method, $args) {
		if(substr($method, 0, 3) == 'get') {
			$key = $this->_underscore(substr($method,3));
			if (isset($this->_settings['store'][$key])) {
				return $this->_settings['store'][$key];
			} elseif (isset($this->_settings['website'][$key])) {
				return $this->_settings['website'][$key];
			} elseif (isset($this->_settings['default'][$key])) {
				return $this->_settings['default'][$key];
			} elseif (isset($this->defaultSettings[$key])) {
				return $this->defaultSettings[$key];
			}
			return $this->getData($key, isset($args[0]) ? $args[0] : null);
		} else {
			parent::__call($method,$args);
		}
	}

	/**
	 * Check setting value is value of the parent scope view
	 *
	 * @param $key
	 * @param bool $isStore
	 * @return bool
	 */
	public function isParentValue($key, $isStore = false) {
		if (isset($this->_settings['store'][$key])) {
			return false;
		}
		if (!$isStore) {
			if (isset($this->_settings['website'][$key])) {
				return false;
			}
		}
		return true;
	}

	private function _getConnection() {
		$this->_resource = Mage::getSingleton('core/resource')->getConnection('core_write');
		return $this->_resource;
	}

	private function _deleteSettings() {
		$this->_resource->query("DELETE FROM $this->_table WHERE `scope`=$this->_scope and `scope_id`=$this->_scopeId");
	}

	private function _saveSettings($settings) {
		$settingsValues = '';
		$textValues = array();
		foreach ($settings as $key => $values) {
			$value = 0;
			$type = $values['type'];
			if ($type != 'text') {
				$value = (int)$values['value'];
			} else {
				$textValues[$key] = $this->_resource->quote($values['value']);
			}
			$settingsValues .=  "($this->_scope, $this->_scopeId, '$key', $value),";
		}
		$settingsValues = substr($settingsValues, 0, strlen($settingsValues) - 1);
		$this->_resource->query("INSERT INTO $this->_table (`scope`, `scope_id`, `key`, `value`) VALUES $settingsValues");
	}

	private function _isTextOption($key) {
		foreach ($this->_textOptions as $value) {
			if ($value == $key) {
				return true;
			}
		}
		return false;
	}

	public function _isValid($settings) {
		$errors = array();
		if (empty($errors)) {
			return true;
		}
		return $errors;
	}

	public function getDefaultData() {
		return array(
			'enabled'                 => $this->getEnabled(),
			'after_add_to_cart'       => $this->getAfterAddToCart(),
			'after_wishlist_selected' => $this->getAfterWishlistSelected(),
			'responsive_width'        => $this->getResponsiveWidth(),
		);
	}
}
?>