<?php

class Aurmil_CustomizePriceFilter_Model_System_Config_Backend_Price_Ranges
extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        $pattern = '#^(\d+)?\-\d+;(\d+\-\d+;)*\d+\-(\d+)?$#';
    
        if (!preg_match($pattern, $this->getValue())) {
            $message = "Provided Layered Navigation Price Ranges are incorrect.";
            $message = Mage::helper('aurmil_customizepricefilter')->__($message);
            Mage::throwException($message);
        }

        return parent::save();
    }
}
