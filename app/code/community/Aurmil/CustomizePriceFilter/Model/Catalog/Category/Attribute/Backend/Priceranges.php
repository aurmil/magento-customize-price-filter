<?php
/**
 * @author Aurélien Millet
 * @link https://github.com/aurmil/magento-customize-price-filter
 * @license https://github.com/aurmil/magento-customize-price-filter/blob/master/LICENSE.md
 */

class Aurmil_CustomizePriceFilter_Model_Catalog_Category_Attribute_Backend_Priceranges
extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract
{
    /**
     * Validate object
     *
     * @param  Varien_Object      $object
     * @throws Mage_Eav_Exception
     * @return boolean
     */
    public function validate($object)
    {
        $pattern = '#^(\d+)?\-\d+;(\d+\-\d+;)*\d+\-(\d+)?$#';
        $value = $object->getFilterPriceRanges();

        if (('' != $value) && !preg_match($pattern, $value)) {
            $message = "Provided Layered Navigation Price Ranges are incorrect.";
            $message = Mage::helper('aurmil_customizepricefilter')->__($message);
            Mage::throwException($message);
        }

        return parent::validate($object);
    }
}
