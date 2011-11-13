<?php


/**
 * developed by www.sunnysideup.co.nz
 * author: Nicolaas - modules [at] sunnysideup.co.nz
**/


//copy the lines between the START AND END line to your /mysite/_config.php file and choose the right settings
//===================---------------- START ecommerce_tax MODULE ----------------===================
// *** GST TAX MODIFIER
//MUST SET
//Order::add_modifier("GSTTaxModifier");
//HIGHLY RECOMMENDED
//StoreAdmin::add_managed_model("GSTTaxModifierOptions");
//MAY SET
//GSTTaxModifier::set_default_country_code("NZ");
//GSTTaxModifier::set_fixed_country_code("NZ");
//GSTTaxModifier::set_exclusive_explanation(" (to be added to prices above)");
//GSTTaxModifier::set_inclusive_explanation(" (included in prices above)");
//GSTTaxModifier::set_based_on_country_note(" - based on a sale to: ");
//GSTTaxModifier::set_no_tax_description("tax-exempt");
//GSTTaxModifier::set_refund_title("Tax Exemption");
//GSTTaxModifier::set_order_item_function_for_tax_exclusive_portion("PortionWithoutTax");
//===================---------------- END ecommerce_tax MODULE ----------------===================
