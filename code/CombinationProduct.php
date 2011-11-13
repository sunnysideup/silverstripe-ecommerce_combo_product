<?php
/**
 * A combination product combines several products into one new product.
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

	protected static $list_separator = ";"; //SUM or COUNT
		static function set_list_separator($s){self::$list_separator = $s;}
		static function get_list_separator(){return self::$list_separator;}

	function CalculatedPrice() {return $this->getCalculatedPrice();}
	function getCalculatedPrice() {
		$price = $this->Price;
		$this->extend('updateCalculatedPrice',$price);
		return $price;
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

class Product_Controller extends Page_Controller {

	static $allowed_actions = array();

	function init() {
		parent::init();
		Requirements::themedCSS('Products');
	}


	function AddProductForm(){
		if($this->canPurchase()) {
			$farray = array();
			$requiredFields = array();
			$fields = new FieldSet($farray);
			$fields->push(new NumericField('Quantity','Quantity',1)); //TODO: perhaps use a dropdown instead (elimiates need to use keyboard)
			$actions = new FieldSet(
				new FormAction('addproductfromform', _t("ProductWithVariationDecorator.ADDLINK","Add this item to cart"))
			);
			$requiredfields[] = 'Quantity';
			$validator = new RequiredFields($requiredfields);
			$form = new Form($this,'AddProductForm',$fields,$actions,$validator);
			return $form;
		}
		else {
			return "Product not for sale";
		}
	}

	function addproductfromform($data,$form){
		if(!$this->IsInCart()) {
			$quantity = round($data['Quantity'], $this->QuantityDecimals());
			if(!$quantity) {
				$quantity = 1;
			}
			$product = DataObject::get_by_id("Product", $this->ID);
			if($product) {
				ShoppingCart::singleton()->addBuyable($product,$quantity);
			}
			if($this->IsInCart()) {
				$msg = _t("Product.SUCCESSFULLYADDED","Added to cart.");
				$status = "good";
			}
			else {
				$msg = _t("Product.NOTADDEDTOCART","Not added to cart.");
				$status = "bad";
			}
			if(Director::is_ajax()){
				return ShoppingCart::singleton()->setMessageAndReturn($msg, $status);
			}
			else {
				$form->sessionMessage($msg,$status);
				Director::redirectBack();
			}
		}
		else {
			return new EcomQuantityField($this);
		}
	}



}

class Product_Image extends Image {

	public static $db = array();

	public static $has_one = array();

	public static $has_many = array();

	public static $many_many = array();

	public static $belongs_many_many = array();

	//default image sizes
	protected static $thumbnail_width = 140;
	protected static $thumbnail_height = 100;

	protected static $content_image_width = 200;

	protected static $large_image_width = 600;

	static function set_thumbnail_size($width = 140, $height = 100){
		self::$thumbnail_width = $width;
		self::$thumbnail_height = $height;
	}

	static function set_content_image_width($width = 200){
		self::$content_image_width = $width;
	}

	static function set_large_image_width($width = 600){
		self::$large_image_width = $width;
	}

	/**
	 *@return GD
	 **/
	function generateThumbnail($gd) {
		$gd->setQuality(80);
		return $gd->paddedResize(self::$thumbnail_width,self::$thumbnail_height);
	}

	/**
	 *@return GD
	 **/
	function generateContentImage($gd) {
		$gd->setQuality(90);
		return $gd->resizeByWidth(self::$content_image_width);
	}

	/**
	 *@return GD
	 **/
	function generateLargeImage($gd) {
		$gd->setQuality(90);
		return $gd->resizeByWidth(self::$large_image_width);
	}



}
class Product_OrderItem extends OrderItem {

	function canCreate($member = null) {
		return true;
	}
	/**
	 * Overloaded Product accessor method.
	 *
	 * Overloaded from the default has_one accessor to
	 * retrieve a product by it's version, this is extremely
	 * useful because we can set in stone the version of
	 * a product at the time when the user adds the item to
	 * their cart, so if the CMS admin changes the price, it
	 * remains the same for this order.
	 *
	 * @param boolean $current If set to TRUE, returns the latest published version of the Product,
	 * 								If set to FALSE, returns the set version number of the Product
	 * 						 		(instead of the latest published version)
	 * @return Product object
	 */
	public function Product($current = false) {
		return $this->Buyable($current);
	}


	/**
	 *@return Boolean
	 **/
	function hasSameContent($orderItem) {
		$parentIsTheSame = parent::hasSameContent($orderItem);
		return $parentIsTheSame && $orderItem instanceOf Product_OrderItem;
	}

	/**
	 *@return Float
	 **/
	function UnitPrice($recalculate = false) {return $this->getUnitPrice($recalculate);}
	function getUnitPrice($recalculate = false) {
		$unitprice = 0;
		if($this->priceHasBeenFixed() && !$recalculate) {
			return parent::getUnitPrice($recalculate);
		}
		elseif($product = $this->Product()){
			$unitprice = $product->getCalculatedPrice();
			$this->extend('updateUnitPrice',$unitprice);
		}
		return $unitprice;
	}


	/**
	 *@return String
	 **/
	function TableTitle() {return $this->getTableTitle();}
	function getTableTitle() {
		$tabletitle = _t("Product.UNKNOWN", "Unknown Product");
		if($product = $this->Product()) {
			$tabletitle = $product->Title;
			$this->extend('updateTableTitle',$tabletitle);
		}
		return $tabletitle;
	}

	/**
	 *@return String
	 **/
	function TableSubTitle() {return $this->getTableSubTitle();}
	function getTableSubTitle() {
		$tablesubtitle = '';
		if($product = $this->Product()) {
			$tablesubtitle = $product->Quantifier;
			$this->extend('updateTableSubTitle',$tablesubtitle);
		}
		return $tablesubtitle;
	}

	public function debug() {
		$title = $this->TableTitle();
		$productID = $this->BuyableID;
		$productVersion = $this->Version;
		$html = parent::debug() .<<<HTML
			<h3>Product_OrderItem class details</h3>
			<p>
				<b>Title : </b>$title<br/>
				<b>Product ID : </b>$productID<br/>
				<b>Product Version : </b>$productVersion
			</p>
HTML;
		$this->extend('updateDebug',$html);
		return $html;
	}


}
