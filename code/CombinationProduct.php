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
 * (b) all products need to return true for canPurchase. This means that if the product is available as part of a combo, it should also be available by itself.
 *
 * We use the sort order for the order attribute to group it...
 *
 * @package: ecommerce
 * @sub-package: products
 *
 **/


class CombinationProduct extends Product {

	public static $db = array(
		'NewPrice' => 'Currency'
	);

	public static $many_many = array(
		'IncludedProducts' => 'Product'
	);

	public static $searchable_fields = array(
		'ID',
		'Title',
		'InternalItemID',
		'Price',
		'ListOfProducts'
	);

	public static $casting = array(
		"OriginalPrice" => "Currency"
	);

	public static $singular_name = "Combination Product";
		function i18n_singular_name() { return _t("CombinationProduct.COMBINATIONPRODUCT", "Combination Product");}

	public static $plural_name = "Combination Products";
		function i18n_plural_name() { return _t("CombinationProduct.COMBINATIONPRODUCTS", "Combination Products");}

	public static $default_parent = 'ProductGroup';

	public static $default_sort = '"Title" ASC';

	public static $icon = 'ecommerce_combo_product/images/icons/CombinationProduct';

	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Content.Components", $this->getIncludedProductsFormField());
		$fields->replaceField("Price", new ReadOnlyField("Price", "Full Price"));
		$fields->addFieldToTab("Root.Content.Details", new NumericField("NewPrice", "New Price"), "Price");
		$fields->addFieldToTab("Root.Content.Details", new ReadOnlyField("Savings", "Savings", $this->getPrice() - $this->NewPrice), "Price");
		return $fields;
	}


	/**
	 *@return TreeMultiselectField
	 **/
	protected function getIncludedProductsFormField() {
		$field = new TreeMultiselectField(
			$name = "IncludedProducts",
			$title = "Included Products",
			$sourceObject = "SiteTree",
			$keyField = "ID",
			$labelField = "MenuTitle"
		);
		$filter = create_function('$obj', 'return ( ( $obj InstanceOf Product || $obj InstanceOf ProductGroup) && ($obj->ID != '.$this->ID.'));');
		$field->setFilterFunction($filter);
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
		if($includedProducts = $this->IncludedProducts()) {
			if($includedProducts->count()) {
				foreach($includedProducts as $includedProduct) {
					if(!$includedProduct->canPurchase($member)) {
						return false;
					}
				}
				return parent::canPurchase($member);
			}
		}
		return false;
	}

	public function classNameForOrderItem() {
		return "CombinationProduct_OrderItem";
	}


	/**
	 *
	 *
	 *
	 */
	function getPrice(){
		if($includedProducts = $this->IncludedProducts()) {
			$originalPrice = 0;
			if($includedProducts && $includedProducts->count()){
				foreach($includedProducts as $includedProduct) {
					$originalPrice += $includedProduct->CalculatedPrice();
				}
			}
		}
		return $originalPrice;
	}

	function getCalculatedPrice(){
		return $this->getField("NewPrice");
	}

	/**
	 * remove any non-products from the list.
	 *
	 */
	function onBeforeWrite(){
		parent::onBeforeWrite();
		$includedProducts = $this->IncludedProducts();
		if($includedProducts) {
			foreach($includedProducts as $includedProduct) {
				if(!$includedProduct instanceOf Product) {
					$includedProducts->remove($includedProduct);
				}
			}
		}
		$this->Price = $this->NewPrice;
	}

}

class CombinationProduct_Controller extends Product_Controller {


	function init(){
		parent::init();
		Requirements::themedCSS("CombinationProduct");
	}



}


class CombinationProduct_OrderItem extends Product_OrderItem {


	//add a deletion system

	function onBeforeDelete(){
		parent::onBeforeDelete();
		$includedProductsOrderItems = DataObject::get("IncludedProduct_OrderItem", "\"ParentOrderItemID\" = ".$this->ID." AND OrderID = ".$this->Order()->ID);
		if($includedProductsOrderItems){
			foreach($includedProductsOrderItems as $includedProductsOrderItem) {
				$includedProductsOrderItem->delete();
				$includedProductsOrderItem->destroy();
			}
		}
	}

	function TableSubTitle(){
		$buyable = $this->Buyable();
		$includedProducts = $buyable->IncludedProducts();
		$titleArray = array();
		if($includedProducts){
			foreach($includedProducts as $includedProduct) {
				$titleArray[] = $includedProduct->MenuTitle;
			}
		}
		if(count($titleArray)) {
			return _t("CombinationProduct.INCLUDES", "Includes").": ".implode(", ", $titleArray).".";
		}
	}

	function onBeforeWrite() {
		Requirements::themedCSS("CombinationProductModifier");
		parent::onBeforeWrite();
		$this->Sort = $this->Buyable()->ID;
	}
}

class IncludedProduct_OrderItem extends Product_OrderItem {

	static $has_one = array(
		"ParentOrderItem" => "CombinationProduct_OrderItem"
	);

	function LiveCalculatedTotal(){
		return 0;
	}

	function Total(){
		return 0;
	}

	function getTotal(){
		return 0;
	}

	function TableSubTitle(){
		Requirements::themedCSS("CombinationProductModifier");
		return _t("CombinationProduct.PARTOF", "Part of").": ".$this->ParentOrderItem()->TableTitle().".";
	}

	function onBeforeWrite() {
		parent::onBeforeWrite();
		if($parentOrderItem = $this->ParentOrderItem()) {
			if($buyable = $parentOrderItem->Buyable()) {
				$this->Sort = $buyable->ID + 1;
			}
		}

	}

	function RemoveLink() {
		return "";
	}


	function RemoveAllLink(){
		return "";
	}

	function QuantityField(){
		return new ReadonlyField("Quantity", "", $this->Quantity);
	}


	function onBeforeDelete(){
		parent::onBeforeDelete();
		CartResponse::set_force_reload();
	}



}

