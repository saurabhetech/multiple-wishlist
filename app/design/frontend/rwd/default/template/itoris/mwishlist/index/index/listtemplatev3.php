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
$namesmodel = Mage::getModel('itoris_mwishlist/mwishlistnames');
$namescollection = $namesmodel->getnamecollection(null, true);
$id = $this->getRequest()->getParam('tabId', $this->getTabId());
$version = version_compare(Mage::getVersion(), '1.10.0.0', '>=') ? '1.9.0.0' : Mage::getVersion();
?>
<div id="central">
	<div class="central_head">
		<?php

		$temp = array();
		$temp = $namesmodel->getNameById($id);
		$wishlistNameame = $temp['multiwishlist_name'];
		$wishlistId = $temp['multiwishlist_id']; ?>
		<table width="100%">
			<tr>
				<td>
					<?php echo $this->__('Wishlist: '); ?>
					<?php if ($temp['multiwishlist_editable']): ?>
						<input type="text" id="mwishlist_new_name" value="<?php echo htmlentities($wishlistName) ?>" />
						<button type="button" class="button" onclick="mwishlistRename(<?php echo $wishlistId ?>);">
							<span><?php echo $this->__('Rename') ?></span>
						</button>
					<?php else: ?>
						<span><?php echo htmlentities($wishlistName) ?></span>
					<?php endif; ?>
				</td>

				<?php if ($temp['multiwishlist_editable'] && !$temp['multiwishlist_is_main']): ?>
					<td>
						<div id='delbutton'>
							<button type="button" title="<?php echo $this->__('Delete') ?>"
									onclick="deleteWishlist(<?php echo $wishlistId ?>)" class="delete">
								<span><?php echo $this->__('Delete wishlist') ?></span></button>
						</div>
					</td>
				<?php endif; ?>
			</tr>
		</table>
	</div>

	<?php if ($this->getWishlistItems()->getSize()): ?>
		<form action="<?php echo $this->getUrl('*/*/update') ?>" method="post">
			<?php echo $this->getBlockHtml('formkey')?>
			<table cellspacing="0" width="100%" class="data-table box-table" id="wishlist-table">
				<?php if ($temp['multiwishlist_editable']): ?>
					<col width="1"/>
				<?php endif; ?>
				<col width="130"/>
				<col/>

				<col width="25%"/>
				<thead>
				<tr>
					<?php if ($temp['multiwishlist_editable']): ?>
						<th class="a-center"><input type="checkbox" name="all" onclick="checkAll(this.form,this.name);"></th>
					<?php endif; ?>
					<th><?php echo $this->__('Product') ?></th>
					<th><?php echo $this->__('Comment') ?></th>
					<th class="a-center">&nbsp;</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($this->getWishlistItems() as $item): ?>
					<tr>
						<?php if ($temp['multiwishlist_editable']): ?>
							<td><input type="checkbox" class="single" name="<?php echo $item->getWishlistItemId() ?>"></td>
						<?php endif; ?>
						<td>
							<div><a href="<?php echo $item->getProductUrl() ?>"><img
										src="<?php echo $this->helper('catalog/image')->init($item, 'small_image')->resize(113, 113); ?>"
										alt="<?php echo $this->htmlEscape($item->getName()) ?>" width="113"/></a></div>
							<div><a href="<?php echo $item->getProductUrl() ?>"
									title="<?php echo $this->htmlEscape($item->getName()) ?>"><?php echo $this->htmlEscape($item->getName()) ?></a>
							</div>
							<?php		$finalPrice = $item->getPrice($item); ?>
							<p class="price-box">
							<span class="regular-price"
								  id="product-price-<?php echo $item->getWishlistItemId()?><?php echo $item->getIdSuffix() ?>">
							<span class="price"><?php echo Mage::helper('core')->currency($finalPrice, true, false); ?></span>
							</p>
						</td>
						<td align="center">
							<textarea name="description[<?php echo $item->getWishlistItemId() ?>]" rows="3" cols="3"
									  style="width:95%;height:160px;" onfocus="focusComment(this)"
									  onblur="focusComment(this)">  <?php echo $item['wishlist_item_description']
									? $item['wishlist_item_description']
									: $this->__('Please, enter your comments...') ?></textarea>
						</td>
						<td class="a-center">
							<?php if ($temp['multiwishlist_editable'] && count($namescollection) > 1): ?>
								<div id="move-block">
									<strong><?php echo $this->__('&nbsp;Move to Wishlist:') ?></strong>
									<select class="input.select" id="select[<?php echo $item->getId() ?>]" style="width:100%;"
											style="margin-bottom:10;" value=""/>
									<?php foreach ($namescollection as $row): ?>
										<?php if ($wishlistId == $row['multiwishlist_id']) continue; ?>
										<option value="<?php echo $row['multiwishlist_id']?>"><?php echo $this->__($row['multiwishlist_name'])?></option>
									<?php endforeach ?>
									</select>
									<button type="button" title="<?php echo $this->__('Move') ?>"
											onclick="moveBetweenLists(<?php echo $item->getWishlistItemId()?>,$('select[<?php echo $item->getId() ?>]').value)"
											class="btn-move"><span><span><?php echo $this->__('Move') ?></span></span></button>
								</div>
							<?php endif; ?>
							<a href="<?php echo Mage::getUrl('wishlist/index/cart', array('item' => $item->getWishlistItemId())); ?>"
							   class="link-cart"><?php echo $this->__('Add to Cart') ?></a><br/>
							<?php if ($temp['multiwishlist_editable']): ?>
								<a style="cursor: pointer" onclick="removeItem(<?php echo $item->getWishlistItemId()?>)"
								   class="link-remove"><?php echo $this->__('Remove Item') ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
			<script type="text/javascript">decorateTable('wishlist-table')</script>
			<div class="button-set">
				<table width="100%">
					<tbody>
					<tr>
						<?php if ($temp['multiwishlist_editable'] && count($namescollection) > 1): ?>
							<td width="40%">
								<div class="div-mwishbottom">
									<select class="input.select" id="select_all" style="width:50%;" value=""/>
									<?php foreach ($namescollection as $row): ?>
										<?php if ($wishlistId == $row['multiwishlist_id']) continue; ?>
										<option value="<?php echo $row['multiwishlist_id']?>"><?php echo $this->__($row['multiwishlist_name'])?></option>
									<?php endforeach ?>
									</select>
									<button type="button" title="<?php echo $this->__('Move') ?>"
											onclick="moveCheckedItemsBetweenLists($('select_all').value)"
											class="btn-movebutton"><span><span><?php echo $this->__('Move') ?></span></span>
									</button>
								</div>


							</td>
						<?php endif; ?>
						<td width="47%">
							<button onclick="setLocation('<?php echo $this->getUrl('wishlist/index/share', array('id' => $wishlistId)) ?>')"
									class="form-button-alt" type="button">
								<span><?php echo $this->__('Share Wishlist') ?></span></button>
							&nbsp;
							<!--        --><?php //if($this->isSaleable()):?>
							<button onclick="addAllWItemsToCart(<?php echo $wishlistId ?>)"
									class="form-button-alt" type="button">
								<span><?php echo $this->__('Add All to Cart') ?></span></button>
							&nbsp;
							<!--        --><?php //endif;?>
							<button type="submit" class="form-button" name="do">
								<span><?php echo $this->__('Update Wishlist') ?></span></button>
						</td>
					</tr>
					</tbody>
				</table>
			</div>

		</form>
	<?php else: ?>
		<p><?php echo $this->__('You have no items in your wishlist.') ?></p>
	<?php endif ?>

</div>

<script type="text/javascript">
	if ($$('.iwishtabs .titles .title.current')[0]) {
		$$('.iwishtabs .titles .title.current')[0].removeClassName('current');
	}
	$('tab_title_<?php echo $id;?>').addClassName('current');
	function checkAll(form, checkname) {
		if (form.elements[checkname].checked == true) {
			for (var i = 1, n = form.elements.length; i < n; i++) {
				if (form.elements[i].type == 'checkbox') {
					form.elements[i].checked = true;
				}
			}
		} else {
			for (i = 1,n = form.elements.length; i < n; i++) {
				if (form.elements[i].type == 'checkbox') {
					form.elements[i].checked = true;
					form.elements[i].checked = form.elements[i].defaultChecked;
				}
			}
		}
	}
	function deleteWishlist(wishlistId) {
		if (confirmRemoveWishlist()) {
			new Ajax.Request('<?php echo Mage::getUrl('wishlist/index/ajax/')?>', { method: 'post',
				parameters: {removeWishlist: wishlistId},
				onSuccess: function(transport) {
					Element.hide('loading-mask');
					$('tabsandcontent').update(transport.responseText);

					initializeTabsSlider()
				},
				onLoading: Element.show('loading-mask')
			});
		}
	}
	function removeItem(itemId) {
		if (confirmRemoveWishlistItem()) {
			new Ajax.Request('<?php echo Mage::getUrl('wishlist/index/ajax/')?>', { method: 'post',
				parameters: {remove: itemId, tabId: $$('.title.current')[0].id.substring(10)},
				onSuccess: function(transport) {
					Element.hide('loading-mask');
					$('central').update(transport.responseText);
					mwishlistUpdateLink();
				},
				onLoading: Element.show('loading-mask')
			});
		}
	}

	function removeItem(itemId) {
		if (confirmRemoveWishlistItem()) {
			new Ajax.Request('<?php echo Mage::getUrl('wishlist/index/ajax/')?>', { method: 'post',
				parameters: {remove: itemId, tabId: $$('.title.current')[0].id.substring(10)},
				onSuccess: function(transport) {
					Element.hide('loading-mask');
					$('central').update(transport.responseText);
					mwishlistUpdateLink();
				},
				onLoading: Element.show('loading-mask')
			});
		}
	}

	function moveBetweenLists(itemId, listId) {
		new Ajax.Request('<?php echo Mage::getUrl('wishlist/index/ajax/')?>', { method: 'post',
			parameters: {itemMove: itemId, list: listId, tabId: listId},
			onSuccess: function(transport) {
				Element.hide('loading-mask');
				$('central').update(transport.responseText);
				mwishlistUpdateLink();
			},
			onLoading: Element.show('loading-mask')
		});
	}
	function moveCheckedItemsBetweenLists(listId) {
		var array = new Array();
		var i = 0;
		$$('.single').each(function(elem) {
			if (elem.checked) {
				array[i] = elem.name;
				i++;
			}
		});
		if (array[0]) {
			new Ajax.Request('<?php echo Mage::getUrl('wishlist/index/ajax/')?>', { method: 'post',
				parameters: {'itemsMove[]': array,
					list: listId, tabId: listId},
				onSuccess: function(transport) {
					Element.hide('loading-mask');
					$('central').update(transport.responseText);
					mwishlistUpdateLink();
				},
				onLoading: Element.show('loading-mask')
			});
		} else {
			alert('<?php echo $this->__('Items not selected') ?>');
		}
	}

	function focusComment(obj) {
		if (obj.value == '<?php echo $this->__('  Please, enter your comments...') ?>') {
			obj.value = '';
		} else if (obj.value == '') {
			obj.value = '<?php echo $this->__('  Please, enter your comments...') ?>';
		}
	}
	function confirmRemoveWishlistItem() {
		return confirm('<?php echo $this->__('Are you sure you want to remove this product from your wishlist?') ?>');
	}
	function confirmRemoveWishlist() {
		return confirm('<?php echo $this->__('Do you really want to delete this Wishlist along with all products in it?') ?>');
	}
	function addAllWItemsToCart(wishlistId) {
		var url = '<?php echo $this->getUrl('*/*/allcart') ?>';
		url += (url.indexOf('?') >= 0) ? '&wishlist_id=' + wishlistId : '?wishlist_id=' + wishlistId;
		setLocation(url);
	}
	function mwishlistRename(wishlistId) {
		var newName = $('mwishlist_new_name').value.strip();
		if (newName.length) {
			if (newName != currentWishlistName) {
				new Ajax.Request('<?php echo $this->getUrl('wishlist/index/renameWishlist') ?>', {
					parameters: {name: newName, id: wishlistId},
					onComplete: function(res) {
						Element.hide('loading-mask');
						var resObj = res.responseText.evalJSON();
						if (resObj.error) {
							alert(resObj.error);
						} else if (resObj.ok) {
							currentWishlistName = newName;
							var titleElm = $$('.iwishtabs .titles .title.current .center')[0];
							if (titleElm) {
								titleElm.update(currentWishlistName);
							}
						}
					},
					onLoading: Element.show('loading-mask')
				});
			}
		} else {
			alert('<?php echo addslashes($this->__('Please enter a new name')) ?>');
		}
	}
	currentWishlistName = '<?php echo addslashes($wishlistName) ?>';
</script>