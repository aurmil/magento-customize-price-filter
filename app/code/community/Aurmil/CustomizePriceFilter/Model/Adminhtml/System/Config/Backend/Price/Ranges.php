<?php

class Aurmil_CustomizePriceFilter_Model_Adminhtml_System_Config_Backend_Price_Ranges
extends Mage_Core_Model_Config_Data
{
    public function save()
    {
        if (!preg_match('#^(\d+)?\-\d+;(\d+\-\d+;)*\d+\-(\d+)?$#', $this->getValue()))
        {
            Mage::throwException(Mage::helper('aurmil_customizepricefilter')->__("Provided Layered Navigation Price Ranges are incorrect."));
        }

        return parent::save();
    }
}
