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
class CombinationProductModifier extends OrderModifier
{

    // ######################################## *** other (non) static variables (e.g. private static $special_name_for_something, protected $order)

    private static $savings = 0;


    /**
     * standard modifier method
     * @param Boolean $force - should the update run no matter what
     */
    public function runUpdate($force = false)
    {
        $this->addProductsPerCombo();
        parent::runUpdate($force);
    }


// ######################################## *** form functions (e. g. showform and getform)

    /**
     * standard OrderModifier Method
     * Should we show a form in the checkout page for this modifier?
     */
    public function ShowForm()
    {
        return false;
    }

// ######################################## *** template functions (e.g. ShowInTable, TableTitle, etc...) ... USES DB VALUES

    /**
     * standard OrderModifer Method
     * Tells us if the modifier should take up a row in the table on the checkout page.
     * @return Boolean
     */
    public function ShowInTable()
    {
        return $this->loadCombinationProducts()->count() ? true: false;
    }

    /**
     * standard OrderModifer Method
     * Tells us if the modifier can be removed (hidden / turned off) from the order.
     * @return Boolean
     */
    public function CanBeRemoved()
    {
        return false;
    }

// ######################################## ***  inner calculations.... USES CALCULATED VALUES

    /**
     * loads the items in the static variable $order_items
     * and saves the items for future use.
     * @return Null | DataObjectSet
     */
    protected function loadIncludedProductItems()
    {
        return IncludedProduct_OrderItem::get()->filter(array("OrderID" => $this->Order()->ID));
    }

    /**
     * loads the items in the static variable $order_items
     * and saves the items for future use.
     * @return Null | DataObjectSet
     */
    protected function loadCombinationProducts()
    {
        return CombinationProduct_OrderItem::get()->filter(array("OrderID" => $this->Order()->ID));
    }

    /**
     * checks for Combination Products and makes sure that enough of the component products are added.
     *
     */
    protected function addProductsPerCombo()
    {
        $reload = false;
        $shoppingCart = ShoppingCart::singleton();
        $combinationProductOrderItems = $this->loadCombinationProducts();
        if ($combinationProductOrderItems->count()) {
            foreach ($combinationProductOrderItems as $combinationProductOrderItem) {
                $combinationProduct = $combinationProductOrderItem->Buyable();
                $comboProductQTY = $combinationProductOrderItem->Quantity;
                $includedProducts = $combinationProduct->IncludedProducts();
                if ($includedProducts) {
                    foreach ($includedProducts as $includedProduct) {
                        $includedProductQTY = $this->countOfProductInOrder($includedProduct);
                        $difference = $comboProductQTY - $includedProductQTY;
                        if ($difference) {
                            $reload = true;
                            if ($comboProductQTY) {
                                //in case it has not been added
                                if (!$includedProduct->IsInCart()) {
                                    $includedProduct->setAlternativeClassNameForOrderItem("IncludedProduct_OrderItem");
                                    $item = $shoppingCart->addBuyable($includedProduct);
                                    if ($item) {
                                        $item->ParentOrderItemID = $combinationProductOrderItem->ID;
                                        $item->write();
                                    }
                                }
                                $shoppingCart->setQuantity($includedProduct, $comboProductQTY);
                            } else {
                                $shoppingCart->deleteBuyable($includedProduct);
                            }
                        }
                    }
                }
            }
        }
        if ($reload) {
            CartResponse::set_force_reload();
        }
    }

    /**
     * Tells us the number of times a product has been added to the order (quantity)
     * @param Product $product - the product we are checking.
     * @return Integer
     */
    protected function countOfProductInOrder($product)
    {
        if ($items = $this->loadIncludedProductItems()) {
            foreach ($items as $item) {
                $buyable = $item->Buyable();
                if ($buyable) {
                    if ($product->ClassName == $buyable->ClassName && $product->ID == $buyable->ID) {
                        return $item->Quantity;
                    }
                }
            }
        }
    }

// ######################################## *** calculate database fields: protected function Live[field name]  ... USES CALCULATED VALUES

    public function LiveName()
    {
        return _t("CombinationProductModifier.SAVINGS", "Savings");
    }

    public function LiveTableValue()
    {
        self::$savings = 0;
        if ($this->ShowInTable()) {
            $combinationProductOrderItems =  CombinationProduct_OrderItem::get()->filter(array("OrderID" => $this->Order()->ID));
            if ($combinationProductOrderItems->count()) {
                foreach ($combinationProductOrderItems as $combinationProductOrderItem) {
                    $buyable = $combinationProductOrderItem->Buyable();
                    self::$savings -= ($buyable->getPrice() - $buyable->NewPrice) * $combinationProductOrderItem->Quantity;
                }
            }
        }
        return self::$savings;
    }

// ######################################## *** Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)


// ######################################## *** standard database related functions (e.g. onBeforeWrite, onAfterWrite, etc...)

// ######################################## *** AJAX related functions

// ######################################## *** debug functions
}
