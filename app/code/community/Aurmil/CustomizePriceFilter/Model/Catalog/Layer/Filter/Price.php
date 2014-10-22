<?php

/**
 * @author     Aurélien Millet
 * @link       https://github.com/aurmil/
 */

if ((!extension_loaded('gmp') || !function_exists('gmp_gcd'))
    && !function_exists('gcd')
) {
    function gcd($a, $b)
    {
        $a = abs((int)$a);
        $b = abs((int)$b);

        if ((0 === $a) || (0 === $b)) {         // 0 and x => x
            $gcd = max($a, $b);
        } elseif ((1 === $a) || (1 === $b)) {   // 1 and x => 1
            $gcd = 1;
        } elseif ($a === $b) {                  // x and x => x
            $gcd = $a;
        } else {                                // x and y
            if ($a < $b) {
                $rest = $a;
                $a = $b;
                $b = $rest;
            }

            do {
                $rest = $a % $b;
                $a = $b;
                $b = $rest;
            } while (0 !== $rest);

            $gcd = $a;
        }

        return $gcd;
    }
}

class Aurmil_CustomizePriceFilter_Model_Catalog_Layer_Filter_Price
extends Mage_Catalog_Model_Layer_Filter_Price
{
    const XML_PATH_PRICE_RANGES = 'catalog/layered_navigation/price_ranges';

    protected function _getPriceRanges()
    {
        $key = 'price_ranges';
        $ranges = $this->getData($key);
        if (is_null($ranges)) {
            if (Mage::registry('current_category')) {
                $ranges = Mage::registry('current_category')->getFilterPriceRanges();
            }

            if (!$ranges) {
                $ranges = Mage::getStoreConfig(self::XML_PATH_PRICE_RANGES);
            }

            $this->setData($key, $ranges);
        }

        return $ranges;
    }

    public function usePriceRanges()
    {
        $priceRanges = $this->_getPriceRanges();
        $calculationMode = Mage::getStoreConfig(self::XML_PATH_RANGE_CALCULATION);
        $manualMode = self::RANGE_CALCULATION_MANUAL;

        return (('' != $priceRanges) && ($manualMode == $calculationMode));
    }

    protected function _getItemsData()
    {
        if ($this->usePriceRanges()) {
            $data = array();

            if ($this->getInterval()) {
                return $data;
            }

            $priceRanges = $this->_getPriceRanges();
            $priceRanges = explode(';', $priceRanges);

            foreach ($priceRanges as $priceRange) {
                $range = explode('-', $priceRange);
                $min = (int)$range[0];
                $max = (int)$range[1];

                if (0 === $min) {               // from 0 to x
                    $counts = $this->getRangeItemCounts($max);

                    $count = 0;
                    if (array_key_exists(1, $counts)) {
                        $count = $counts[1];
                    }
                } elseif (0 === $max) {         // from x to infinite
                    $counts = $this->getRangeItemCounts($min);

                    if (array_key_exists(1, $counts)) {
                        unset($counts[1]);
                    }

                    $count = array_sum($counts);
                } else {                        // from x to y
                    $range = array($min, $max);
                    $min = min($range);
                    $max = max($range);

                    if (extension_loaded('gmp') && function_exists('gmp_gcd')) {
                        $gcd = gmp_intval(gmp_gcd($min, $max));
                    } else {
                        $gcd = gcd($min, $max);
                    }

                    $counts = $this->getRangeItemCounts($gcd);

                    $count = 0;
                    for ($i = ((int)($min / $gcd) + 1); ($i * $gcd) <= $max; $i++) {
                        if (array_key_exists($i, $counts)) {
                            $count += $counts[$i];
                        }
                    }
                }

                if (0 < $count) {
                    $range = explode('-', $priceRange);

                    $data[] = array(
                        'label' => $this->_renderRangeLabel($range[0], $range[1]),
                        'value' => $priceRange,
                        'count' => $count
                    );
                }
            }

            return $data;
        }

        return parent::_getItemsData();
    }

    protected function _renderRangeLabel($fromPrice, $toPrice)
    {
        $store = Mage::app()->getStore();
        $formattedFromPrice = $store->formatPrice($fromPrice);

        if (($fromPrice != $toPrice)
            && Mage::getStoreConfigFlag('catalog/layered_navigation/price_subtraction')
        ) {
            $toPrice -= .01;
        }
        $formattedToPrice = $store->formatPrice($toPrice);

        if (0 === (int)$fromPrice) {
            $helper = Mage::helper('aurmil_customizepricefilter');
            $label = $helper->__('Under %s', $formattedToPrice);
            return $label;
        } elseif (0 === (int)$toPrice) {
            // this translation is missing in Magento < 1.7, so this module manages it on its own
            $helper = Mage::helper('aurmil_customizepricefilter');
            $label = $helper->__('%s and above', $formattedFromPrice);
            return $label;
        } elseif (($fromPrice == $toPrice)
            && $store->getConfig(self::XML_PATH_ONE_PRICE_INTERVAL)
        ) {
            return $formattedFromPrice;
        } else {
            $helper = Mage::helper('catalog');
            $label = $helper->__('%s - %s', $formattedFromPrice, $formattedToPrice);
            return $label;
        }
    }

    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $version = Mage::getVersionInfo();

        if (!$this->usePriceRanges() || (int)$version['minor'] >= 7) {
            return parent::apply($request, $filterBlock);
        } else {
            /**
             * Filter must be string: $fromPrice-$toPrice
             */
            $filter = $request->getParam($this->getRequestVar());
            if (!$filter) {
                return $this;
            }

            $filter = explode('-', $filter);
            if (count($filter) != 2) {
                return $this;
            }

            foreach ($filter as $v) {
                if (($v !== '' && $v !== '0' && (int)$v <= 0)
                    || is_infinite((int)$v)
                ) {
                    return $this;
                }
            }

            list($from, $to) = $filter;

            $this->setInterval(array($from, $to));

            $this->_applyToCollection($from, $to);
            $this->getLayer()->getState()->addFilter($this->_createItem(
                $this->_renderRangeLabel(empty($from) ? 0 : $from, $to),
                $filter
            ));

            $this->_items = array();

            return $this;
        }
    }

    public function getRangeItemCounts($range)
    {
        $rangeKey = 'range_item_counts_' . $range;
        $items = $this->getData($rangeKey);
        if (is_null($items)) {
            $items = $this->_getResource()->getCount($this, $range);

            if (defined('self::XML_PATH_RANGE_MAX_INTERVALS') && !$this->usePriceRanges()) {
                // checking max number of intervals
                $i = 0;
                $lastIndex = null;
                $maxIntervalsNumber = $this->getMaxIntervalsNumber();
                $calculation = Mage::app()->getStore()->getConfig(self::XML_PATH_RANGE_CALCULATION);
                foreach ($items as $k => $v) {
                    ++$i;
                    if ($calculation == self::RANGE_CALCULATION_MANUAL && $i > 1 && $i > $maxIntervalsNumber) {
                        $items[$lastIndex] += $v;
                        unset($items[$k]);
                    } else {
                        $lastIndex = $k;
                    }
                }
            }

            $this->setData($rangeKey, $items);
        }

        return $items;
    }
}
