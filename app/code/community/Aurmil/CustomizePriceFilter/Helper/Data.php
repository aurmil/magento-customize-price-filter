<?php
/**
 * @author Aurélien Millet
 * @link https://github.com/aurmil/magento-customize-price-filter
 * @license https://github.com/aurmil/magento-customize-price-filter/blob/master/LICENSE.md
 */

class Aurmil_CustomizePriceFilter_Helper_Data
extends Mage_Core_Helper_Abstract
{
    const XML_PATH_PRICE_RANGES = 'catalog/layered_navigation/price_ranges';
    const XML_PATH_PRICE_SUBTRACTION = 'catalog/layered_navigation/price_subtraction';
    const XML_PATH_USE_FIRST_RANGE_TEXT = 'catalog/layered_navigation/use_first_range_text';

    /**
     * @var string
     */
    protected $_priceRanges;

    /**
     * @var boolean
     */
    protected $_usePriceRanges;

    /**
     * @return string
     */
    public function getPriceRanges()
    {
        if (is_null($this->_priceRanges)) {
            $ranges = '';

            $currentCategory = Mage::registry('current_category_filter');
            if (!$currentCategory) {
                $currentCategory = Mage::getSingleton('catalog/layer')->getCurrentCategory();
            }

            if ($currentCategory) {
                $ranges = $currentCategory->getFilterPriceRanges();
            }

            if (!$ranges) {
                $ranges = Mage::getStoreConfig(self::XML_PATH_PRICE_RANGES);
            }

            $this->_priceRanges = $ranges;
        }

        return $this->_priceRanges;
    }

    /**
     * @return boolean
     */
    public function usePriceRanges()
    {
        if (is_null($this->_usePriceRanges)) {
            $calculationMode = Mage::getStoreConfig(Mage_Catalog_Model_Layer_Filter_Price::XML_PATH_RANGE_CALCULATION);
            $manualMode = Mage_Catalog_Model_Layer_Filter_Price::RANGE_CALCULATION_MANUAL;
            $priceRanges = $this->getPriceRanges();

            $this->_usePriceRanges = (($calculationMode == $manualMode) && $priceRanges);
        }

        return $this->_usePriceRanges;
    }

    /**
     * @return boolean
     */
    public function usePriceSubstract()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_PRICE_SUBTRACTION);
    }

    /**
     * @return boolean
     */
    public function useFirstRangeText()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_USE_FIRST_RANGE_TEXT);
    }
}
