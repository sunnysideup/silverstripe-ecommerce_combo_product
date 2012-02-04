<?php
/**
 * A combination product combines several products into one new product.
 *
 * The way this works is that the combo product links to zero or more products (many many relationship)
 *
 * When you add the combo product, the individual products are added.  A modifier adds the "discount" for the combo.
 * It also deletes all the products as soon as you delete one.
 *
 * There are two restrictions to keep in mind:
 * (a) it only applies to products and not all buyables.
 * (b) all products need to return true for canPuarchase. This means that if the product is available as part of a combo, it should also be available by itself.
 *
 * We use the sort order for the order attribute to group it...
 *
 * @package: ecommerce
 * @sub-package: products
 *
 **/


class CombinationProduct extends Product {

	public static $many_many = array(
		'Components' => 'Product'
	);

	public static $defaults = array(
		'AllowPurchase' => false
	);

	public static $searchable_fields = array(
		'ID',
		'Title',
		'InternalItemID',
		'Price'
		'ListOfProducts'
	);

	public static $casting = array(
		"ListOfProducts" => "Title"
	);

	public static $singular_name = "Combination Product";
		function i18n_singular_name() { return _t("CombinationProduct.COMBINATIONPRODUCT", "Combination Product");}

	public static $plural_name = "Combination Products";
		function i18n_plural_name() { return _t("CombinationProduct.COMBINATIONPRODUCTS", "Combination Products");}

	public static $default_parent = 'ProductGroup';

	public static $default_sort = '"Title" ASC';

	public static $icon = 'ecommerce_combo_product/images/icons/CombinationProduct';

	function CalculatedPrice() {return $this->getCalculatedPrice();}
	function getCalculatedPrice() {
		$price = parent::getCalculatedPrice();
		$components = $this->Components();
		$reduction = 0;
		if($components && $components->count()){
			foreach($components as $component) {
				$reduction += $component->CalculatedPrice();
			}
		}
		return $price - $reduction;
		//work out difference
	}

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Content.Components", $this->getComponentsFormField());
		return $fields;
	}


	/**
	 *@return TreeMultiselectField
	 **/
	protected function getComponentsFormField() {
		$field = new TreeMultiselectField($name = "Components", $title = "Product Components", $sourceObject = "SiteTree", $keyField = "ID", $labelField = "MenuTitle");
		//See issue: 139
		return $field;
	}

	/**
	 * Conditions for whether a product can be purchased.
	 *
	 * If it has the checkbox for 'Allow this product to be purchased',
	 * as well as having a price, it can be purchased. Otherwise a user
	 * can't buy it.
	 *
	 * Other conditions may be added by decorating with the canPurcahse function
	 *
	 * @return boolean
	 */
	function canPurchase($member = null) {
		if($components = $this->Components()) {
			if($components->count())
				foreach($components as $product) {
					if(!$product->canPurchase($member)) {
						return false;
					}
				}
				return true;
			}
		}
		return false;
	}

}

class CombinationProduct_Controller extends Product_Controller {





}

class CombinationProduct_OrderItem extends OrderItem {


	function onAfterDelete(){
		parent::onAfterDelete();
		//remove all the combination products
		//we need to remove them because otherwise removing the
		//combination product does not add anything special.
	}
}
