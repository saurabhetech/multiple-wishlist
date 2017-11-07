if (typeof(Itoris) == 'undefined') {
	Itoris = {};
}

Itoris.MWishlists = Class.create();
Itoris.MWishlists.prototype = {
    wishlists : [],
	products : [],
	block : null,
	urls : {},
	addNewWishlist : [],
	customerId : 0,
	translates : null,
    initialize : function(blockId, urls, customerId, translates) {
		this.block = $(blockId);
		this.urls = urls;
		this.customerId = customerId;
		this.translates = translates;
		this.block.appendChild(this.createAddWishlistBlock());
        if ($('itoris_mwishlist_popup_loading_mask')) {
            $('itoris_mwishlist_popup_loading_mask').observe('click', this.hideProductsGridPopup.bind(this))
        }
		this.getWishlists();
	},
	createAddWishlistBlock : function() {
		var block = this.createElement('form');
		block.id = 'add-new-wishlist';
		var labelForName = this.createElement('span');
		labelForName.update(this.translates.wishlistName + ":");
		block.appendChild(labelForName);
		block.appendChild(this.createElement('br'));
		var nameInput = this.createInputElement('text');
		nameInput.name = 'wishlist_name';
		block.appendChild(nameInput);
		this.addNewWishlist['name'] = nameInput;
		block.appendChild(this.createElement('br'));
		var checkbox = this.createInputElement('checkbox');
		checkbox.addClassName('check');
		checkbox.name = 'wishlist_editable';
		block.appendChild(checkbox);
		this.addNewWishlist['editable'] = checkbox;
		var labelForCheckbox = this.createElement('span');
		labelForCheckbox.update(this.translates.manageableCustomer);
		block.appendChild(labelForCheckbox);
		block.appendChild(this.createElement('br'));
		var buttonAdd = this.createElement('button');
		buttonAdd.update(this.translates.addWishlist);
		buttonAdd.writeAttribute('type','button');
		Event.observe(buttonAdd, 'click', this.addWishlist.bind(this, nameInput));
		block.appendChild(buttonAdd);
		return block;
	},
	createElement : function(name) {
		var element = document.createElement(name);
		Element.extend(element);
		return element;
	},
	createInputElement : function(type) {
		var element = this.createElement('input');
		element.type = type;
		return element;
	},
	addWishlist : function(nameInput) {
		var mw = this;
		nameInput.addClassName('required-entry');
		var validator = new Validation('add-new-wishlist');
		if (validator.validate()) {
			var preparedParams = {};
			preparedParams['wishlist_name'] = mw.addNewWishlist['name'].value;
			preparedParams['wishlist_editable'] = mw.addNewWishlist['editable'].checked;
			preparedParams['customer_id'] = mw.customerId;
			new Ajax.Request(mw.urls.add , {
						method : 'post',
						parameters : preparedParams,
						onComplete: function(response) {
							var wishlists = [];
							wishlists.push(response.responseText.evalJSON())
							mw.addWishlists(wishlists);
						}
			});
		}
		nameInput.removeClassName('required-entry');
	},
	addWishlists : function(wishlists) {
		for (var i = 0; i < wishlists.length; i++) {
			this.wishlists.push(new Itoris.Wishlist(wishlists[i], this));
		}
	},
	getWishlists : function() {
		var mw = this;
		var preparedParams = {};
		preparedParams['customer_id'] = mw.customerId;
		new Ajax.Request(mw.urls.getWishlists , {
					method : 'post',
					parameters : preparedParams,
					onComplete: function(response) {
						mw.addWishlists(response.responseText.evalJSON());
					}
		});
	},
    showProductsGridPopup: function(wishlist) {
        $('itoris_mwishlist_popup_loading_mask').show();
        $('itoris_mwishlist_products_grid_popup').show();
        this.addProductToWishlistObj = wishlist;
        new Ajax.Request(this.urls.productGrid, {
            onComplete: function(res) {
                $$('#itoris_mwishlist_products_grid_popup .product-grid-popup-content')[0].update(res.responseText);
            }.bind(this)
        });
    },
    hideProductsGridPopup: function() {
        $('itoris_mwishlist_popup_loading_mask').hide();
        $('itoris_mwishlist_products_grid_popup').hide();
        this.addProductToWishlistObj = null;
    },
    addProductToWishlist: function(productId) {
        if (this.addProductToWishlistObj) {
            this.addProductToWishlistObj.addNewProduct(productId);
            this.hideProductsGridPopup();
        }
    }
}

Itoris.Wishlist = Class.create();
Itoris.Wishlist.prototype = {
	mwishlist : null,
    initialize : function(config, mwishlist) {
		this.mwishlist = mwishlist;
		this.id = config.multiwishlist_id;
		this.name = config.multiwishlist_name;
		this.editable = parseInt(config.multiwishlist_editable);
		this.isMain = config.multiwishlist_is_main;
		this.block = this.addWishlistBlock();
		this.mwishlist.block.appendChild(this.block);
		this.addProductBlock = this.createAddProductBlock();
		this.block.appendChild(this.addProductBlock);
		this.productsBlock = this.mwishlist.createElement('div');
		this.block.appendChild(this.productsBlock);
	},
	addWishlistBlock : function() {
		var block = this.mwishlist.createElement('div');
		block.addClassName('wishlist');
		var head = this.mwishlist.createElement('div');
		head.addClassName('head');
		var title = this.mwishlist.createElement('div');
		this.wishlistNameElm = this.mwishlist.createElement('span');
		this.wishlistNameElm.update(this.name);
		title.appendChild(this.wishlistNameElm);
		title.addClassName('name');
		if (this.editable) {
			var editableText = this.mwishlist.createElement('span');
			editableText.addClassName('additional-info');
			editableText.update(' (' + this.mwishlist.translates.manageableCustomer + ')');
			title.appendChild(editableText);
		}
		var buttons = this.mwishlist.createElement('div');
		buttons.addClassName('buttons');
		if (!this.isMain) {
			var deleteButton = this.mwishlist.createElement('button');
			deleteButton.writeAttribute('type','button');
			deleteButton.addClassName('delete');
			deleteButton.update(this.mwishlist.translates.deleteText);
			buttons.appendChild(deleteButton);
			Event.observe(deleteButton, 'click', this.deleteWishlist.bind(this));
		}
		var editButton = this.mwishlist.createElement('button');
		editButton.writeAttribute('type','button');
		editButton.addClassName('edit');
		editButton.update(this.mwishlist.translates.edit);
		buttons.appendChild(editButton);
		Event.observe(editButton, 'click', this.getProducts.bind(this, editButton));
		head.appendChild(buttons);
		head.appendChild(title);
		block.appendChild(head);
		return block;
	},
	createAddProductBlock : function() {
		var block = this.mwishlist.createElement('div');
		block.addClassName('add-product');
		var renameBlock = this.mwishlist.createElement('div');
		renameBlock.update($$('#mwishlist_html_templates .rename-template')[0].innerHTML);
		renameBlock.select('input')[0].value = this.name;
		Event.observe(renameBlock.select('button')[0], 'click', this.renameWishlist.bind(this, renameBlock.select('input')[0]));
		block.appendChild(renameBlock);

		var button = this.mwishlist.createElement('button');
		button.writeAttribute('type','button');
		button.update(this.mwishlist.translates.addProduct);
		block.appendChild(button);
		Event.observe(button, 'click', this.mwishlist.showProductsGridPopup.bind(this.mwishlist, this));
		block.hide();
		return block;
	},
	addNewProduct : function(productId) {
		if (productId) {
			var w = this;
			var preparedParams = {};
			preparedParams['product_id'] = productId;
			preparedParams['wishlist_id'] = w.id;
			preparedParams['customer_id'] = w.mwishlist.customerId;
			new Ajax.Request(w.mwishlist.urls.addProduct , {
				method : 'post',
				parameters : preparedParams,
				onComplete: function(response) {
					var obj = response.responseText.evalJSON();
					if (obj.error) {
						alert(obj.error);
					} else {
						w.showProducts(obj);
					}
				}
			});
		}
	},
	showProducts : function(products) {
		for (var i = 0; i < products.length; i++) {
			this.productsBlock.appendChild(this.addProduct(products[i]));
		}
		this.addProductBlock.show();
	},
	addProduct : function(config) {
		var block = this.mwishlist.createElement('div');
		block.addClassName('product');
		block.update(config.name);
		var removeLink = this.mwishlist.createElement('div');
		removeLink.update(this.mwishlist.translates.remove);
		removeLink.addClassName('remove');
		block.appendChild(removeLink);
		Event.observe(removeLink, 'click', this.deleteProduct.bind(this, config.id, block));
		return block;
	},
	deleteProduct : function(itemId, block) {
		if (confirm(this.mwishlist.translates.confirmRemoveProduct)) {
			var preparedParams = {};
			preparedParams['item_id'] = itemId;
			var w = this;
			new Ajax.Request(w.mwishlist.urls.deleteItem , {
				method : 'post',
				parameters : preparedParams,
				onComplete: function(response) {
					//w.showProducts(response.responseText.evalJSON());
					block.remove();
				}
			});
		}
	},
	renameWishlist : function(newNameElm) {
		var newName = newNameElm.value.strip();
		if (newName.length && newName != this.name) {
			new Ajax.Request(this.mwishlist.urls.renameWishlist , {
				method : 'post',
				parameters : {id: this.id, name: newName},
				onComplete: function(response) {
					var resObj = response.responseText.evalJSON();
					if (resObj.error) {
						alert(resObj.error);
					} else if (resObj.ok) {
						this.name = newName;
						this.wishlistNameElm.update(newName);
					}
				}.bind(this)
			});
		} else {
			alert(this.mwishlist.translates.enterNewName);
		}
	},
	getProducts : function(button) {
		var w = this;
		if (!w.addProductBlock.visible()) {
			var preparedParams = {};
			preparedParams['wishlist_id'] = this.id;
			new Ajax.Request(w.mwishlist.urls.getProducts , {
				method : 'post',
				parameters : preparedParams,
				onComplete: function(response) {
					w.showProducts(response.responseText.evalJSON());
					button.update(w.mwishlist.translates.minimize);
				}
			});
		} else {
			this.productsBlock.childElements().forEach(function(elm){
				elm.remove();
			});
			w.addProductBlock.hide();
			button.update(w.mwishlist.translates.edit);
		}
	},
	deleteWishlist : function() {
		if (confirm(this.mwishlist.translates.confirmRemoveWishlist)) {
			var w = this;
			var preparedParams = {};
			preparedParams['wishlist_id'] = this.id;
			new Ajax.Request(w.mwishlist.urls.deleteWishlist , {
				method : 'post',
				parameters : preparedParams,
				onComplete: function(response) {
					w.block.remove();
				}
			});
		}
	}
}