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

$this->startSetup();

$this->run("

DROP TABLE IF EXISTS {$this->getTable('itoris_mwishlists')};
CREATE TABLE {$this->getTable('itoris_mwishlists')} (
`multiwishlist_id` int(10) unsigned NOT NULL auto_increment,
`multiwishlist_name` VARCHAR(255) NOT NULL,
`multiwishlist_customer_id` INT UNSIGNED NOT NULL,
`multiwishlist_editable` BOOLEAN NOT NULL,
`multiwishlist_is_main` BOOLEAN NULL,
PRIMARY KEY  (`multiwishlist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE {$this->getTable('itoris_mwishlists')} ADD FOREIGN KEY ( `multiwishlist_customer_id` ) REFERENCES {$this->getTable('customer_entity')} (
`entity_id`
) ON DELETE CASCADE ON UPDATE CASCADE ;

DROP TABLE IF EXISTS {$this->getTable('itoris_mwishlist_items')};
CREATE TABLE {$this->getTable('itoris_mwishlist_items')} (
`item_id` INT(10) UNSIGNED NOT NULL ,
`multiwishlist_id` INT(10) UNSIGNED NOT NULL ,
CONSTRAINT `FK_item_id` FOREIGN KEY (`item_id`) REFERENCES `{$this->getTable('wishlist_item')}` (`wishlist_item_id`) ON DELETE CASCADE ON UPDATE CASCADE,
CONSTRAINT `FK_multiwishlist_id` FOREIGN KEY (`multiwishlist_id`) REFERENCES `{$this->getTable('itoris_mwishlists')}` (`multiwishlist_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

");

$this->endSetup();
?>
