<?php

/**
 * @author     Aurélien Millet
 * @link       https://github.com/aurmil/
 */

class Aurmil_CustomizePriceFilter_Model_System_Config_Backend_Priceranges
extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $pattern = '#^(\d+)?\-\d+;(\d+\-\d+;)*\d+\-(\d+)?$#';
        $value = $this->getValue();

        if (('' !== $value) && !preg_match($pattern, $value)) {
            $message = "Provided Layered Navigation Price Ranges are incorrect.";
            $message = Mage::helper('aurmil_customizepricefilter')->__($message);
            Mage::throwException($message);
        }

        return parent::save();
    }
}
