<?php

/**
 * @author Nicolaas [at] sunnysideup.co.nz
 * @package: ecommerce
 * @sub-package: ecommerce_delivery
 * @description: This modifier check for Combination Products
 * If it find a combination product it:
 *
 * a. adds all the relevent combination products
 * b. checks for quantities
 *
 **/
class CombinationProductModifier extends OrderModifier {

// ######################################## *** other (non) static variables (e.g. protected static $special_name_for_something, protected $order)

	protected static $order_items = false;

	/**
	 * standard modifier method
	 * @param Boolean $force - should the update run no matter what
	 */
	public function runUpdate($force = false) {
		$this->addAndRemoveProducts();
		parent::runUpdate($force);
	}


// ######################################## *** form functions (e. g. showform and getform)

	/**
	 * standard OrderModifier Method
	 * Should we show a form in the checkout page for this modifier?
	 */
	public function showForm() {
		return false;
	}

// ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES

	/**
	 * standard OrderModifer Method
	 * Tells us if the modifier should take up a row in the table on the checkout page.
	 * @return Boolean
	 */
	public function ShowInTable() {
		return false;
	}

	/**
	 * standard OrderModifer Method
	 * Tells us if the modifier can be removed (hidden / turned off) from the order.
	 * @return Boolean
	 */
	public function CanBeRemoved() {
		return false;
	}

// ######################################## ***  inner calculations.... USES CALCULATED VALUES

	/**
	 * loads the items in the static variable $order_items
	 * and saves the items for future use.
	 *
	 */
	protected function loadItems(){
		if(self::$order_items === false) {
			self::$order_items = null;
			$order = $this->Order();
			if($order) {
				self::$order_items = $this->Order()->Items();
			}
		}
		return self::$order_items;
	}

	/**
	 * checks for Combination Products and makes sure that enough of the component products are added.
	 *
	 */
	protected function addProductsPerCombo(){
		if($items = $this->loadItems()) {
			foreach($items as $item) {
				if($item instanceOf CombinationProduct_OrderItem) {
					$comboProduct = $item->Buyable();
					$comboQuantity = $item->Quantity;
					$childProducts = $comboProduct->Components();
					if($childProducts){
						foreach($childProducts as $childProduct) {
							$childQuantity = $this->countOfProductInOrder($childProduct);
							$difference = $comboQuantity - $childQuantity;
							if($difference > 0) {
								$shoppingCart = ShoppingCart::singleton();
								$shoppingCart->addBuyable($childProduct, $difference);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Tells us the number of times a product has been added to the order (quantity)
	 * @param Product $product - the product we are checking.
	 * @return Integer
	 */
	protected function countOfProductInOrder($product) {
		if($items = $this->loadItems()) {
			foreach($items as $item) {
				$buyable = $item->Buyable();
				if($buyable) {
					if($product->ClassName == $buyable->ClassName && $product->ID == $buyable->ID) {
						return $item->Quantity;
					}
				}
			}
		}
	}

// ######################################## *** calculate database fields: protected function Live[field name]  ... USES CALCULATED VALUES

// ######################################## *** Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)



// ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

// ######################################## *** AJAX related functions
	/**
	* some modifiers can be hidden after an ajax update (e.g. if someone enters a discount coupon and it does not exist).
	* There might be instances where ShowInTable (the starting point) is TRUE and HideInAjaxUpdate return false.
	*@return Boolean
	**/
	public function HideInAjaxUpdate() {
		return true;
	}
// ######################################## *** debug functions

}
