<?php
/**
 * @author Aurélien Millet
 * @link https://github.com/aurmil/magento-customize-price-filter
 * @license https://github.com/aurmil/magento-customize-price-filter/blob/master/LICENSE.md
 */

if ((!extension_loaded('gmp') || !function_exists('gmp_gcd'))
    && !function_exists('gcd')
) {
    function gcd($a, $b)
    {
        $a = abs((int) $a);
        $b = abs((int) $b);

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
    protected function _getItemsData()
    {
        // if custom price ranges not used => parent
        if (!Mage::helper('aurmil_customizepricefilter')->usePriceRanges()) {
            return parent::_getItemsData();
        }

        // an interval was applied in filters, we don't need the items list
        if ($this->getInterval()) {
            return array();
        }

        $ranges = Mage::helper('aurmil_customizepricefilter')->getPriceRanges();
        $ranges = explode(';', $ranges);
        $rangesCount = count($ranges);
        $data   = array();

        if ($rangesCount > 0) {
            $useGMP = (extension_loaded('gmp') && function_exists('gmp_gcd'));
            // check on parent class as self class defines the method _renderRangeLabel
            $renderRangeLabel = method_exists(new Mage_Catalog_Model_Layer_Filter_Price, '_renderRangeLabel');

            foreach ($ranges as $k => $range) {
                $prices = array_map('intval', explode('-', $range));
                $fromPrice = $prices[0];
                $toPrice = $prices[1];
                $count = 0;

                // needed by Aurmil_CustomizePriceFilter_Model_Catalog_Resource_Layer_Filter_Price::getCount()
                $this->setData('currentRangePrices', $prices);

                if (0 === $fromPrice && 0 < $toPrice && 0 === $k) { // from 0 to X, first range
                    $dbRanges = $this->getRangeItemCounts($toPrice);

                    if (array_key_exists(1, $dbRanges)) {
                        $count = $dbRanges[1];
                    }
                } elseif (0 < $fromPrice && 0 === $toPrice && ($rangesCount - 1) === $k) { // from X to infinite, last range
                    $dbRanges = $this->getRangeItemCounts($fromPrice);

                    if (array_key_exists(1, $dbRanges)) {
                        unset($dbRanges[1]);
                    }

                    $count = array_sum($dbRanges);
                } elseif (0 < $fromPrice && $fromPrice === $toPrice) { // X = Y, X != 0
                    $dbRanges = $this->getRangeItemCounts($fromPrice + 0.01);

                    if (array_key_exists(1, $dbRanges)) {
                        $count = $dbRanges[1];

                        $dbRanges = $this->getRangeItemCounts($fromPrice - 0.01);

                        if (array_key_exists(1, $dbRanges)) {
                            $count -= $dbRanges[1];
                        }
                    }
                } elseif (0 < $fromPrice && 0 < $toPrice && $fromPrice < $toPrice) { // from X to Y, X < Y, X != 0, Y != 0
                    if ($useGMP) {
                        $gcd = gmp_intval(gmp_gcd($fromPrice, $toPrice));
                    } else {
                        $gcd = gcd($fromPrice, $toPrice);
                    }

                    $dbRanges = $this->getRangeItemCounts($gcd);

                    for ($i = ((int) ($fromPrice / $gcd) + 1); ($i * $gcd) <= $toPrice; $i++) {
                        if (array_key_exists($i, $dbRanges)) {
                            $count += $dbRanges[$i];
                        }
                    }
                } else {
                    Mage::log('Customize Price Filter module: price range "'.$range.'" is invalid', Zend_Log::WARN);
                }

                if (0 < $count) {
                    // we want strings, not integers as we previously had
                    $prices = explode('-', $range);

                    if ($renderRangeLabel) { // Magento CE >= 1.7
                        $label = $this->_renderRangeLabel($prices[0], $prices[1]);
                    } else { // Magento CE < 1.7
                        $label = $this->_renderItemLabel($prices[0], $prices[1]);
                    }

                    $data[] = array(
                        'label' => $label,
                        'value' => $range,
                        'count' => $count,
                    );
                }
            }
        }

        return $data;
    }

    public function getRangeItemCounts($range)
    {
        // if custom price ranges not used or Magento CE < 1.7 => parent
        if (!Mage::helper('aurmil_customizepricefilter')->usePriceRanges()
            || !method_exists($this, 'getMaxIntervalsNumber')
        ) {
            return parent::getRangeItemCounts($range);
        }

        // for Magento CE >= 1.7, parent's code
        // but without checking max number of intervals
        $rangeKey = 'range_item_counts_' . $range;
        $items = $this->getData($rangeKey);
        if (is_null($items)) {
            $items = $this->_getResource()->getCount($this, $range);
            $this->setData($rangeKey, $items);
        }
        return $items;
    }

    /**
     * target is only Magento CE < 1.7
     * to manage "fromPrice-toPrice" format instead of "index,range"
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        // if custom price ranges not used or Magento CE >= 1.7 => parent
        // test should be improved as getMaxIntervalsNumber method is not involved in the code below
        if (!Mage::helper('aurmil_customizepricefilter')->usePriceRanges()
            || method_exists($this, 'getMaxIntervalsNumber')
        ) {
            return parent::apply($request, $filterBlock);
        }

        // from Magento CE 1.6
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter) {
            return $this;
        }
        $filter = explode('-', $filter); // modification: , => -
        if (count($filter) != 2) {
            return $this;
        }

        // from Magento CE 1.9
        foreach ($filter as $v) {
            if (($v !== '' && $v !== '0' && (float)$v <= 0) || is_infinite((float)$v)) {
                return $this;
            }
        }
        list($from, $to) = $filter;
        $this->setInterval(array($from, $to));

        // from Magento CE 1.6/1.9
        $this->_applyToCollection($from, $to);
        $this->getLayer()->getState()->addFilter($this->_createItem(
            $this->_renderItemLabel(empty($from) ? 0 : $from, $to),
            $filter
        ));

        // from Magento CE 1.6
        $this->_items = array();
        return $this;
    }

    /**
     * for Magento CE >= 1.7
     */
    protected function _renderRangeLabel($fromPrice, $toPrice)
    {
        $store      = Mage::app()->getStore();
        $formattedFromPrice  = $store->formatPrice($fromPrice);
        $helper = Mage::helper('aurmil_customizepricefilter');
        $usePriceRanges = $helper->usePriceRanges();

        $formattedToPrice = '';
        if ($toPrice) {
            $tmpToPrice = $toPrice;
            if ($helper->usePriceSubstract()) {
                $tmpToPrice -= .01;
            }
            $formattedToPrice = $store->formatPrice($tmpToPrice);
        }

        if (!$fromPrice && $toPrice && $helper->useFirstRangeText()) {
            $label = $helper->__('Up to %s', $formattedToPrice);
        } elseif ($fromPrice && !$toPrice) {
            $label = Mage::helper('catalog')->__('%s and above', $formattedFromPrice);
        } elseif ($fromPrice == $toPrice && Mage::app()->getStore()->getConfig(self::XML_PATH_ONE_PRICE_INTERVAL)) {
            $label = $formattedFromPrice;
        } else {
            $label = Mage::helper('catalog')->__('%s - %s', $formattedFromPrice, $formattedToPrice);
        }

        return $label;
    }

    /**
     * for Magento CE < 1.7
     * changed parameters names
     */
    protected function _renderItemLabel($fromPrice, $toPrice)
    {
        $store      = Mage::app()->getStore();
        $helper = Mage::helper('aurmil_customizepricefilter');
        $usePriceRanges = $helper->usePriceRanges();

        if (!$usePriceRanges) {
            $range = $fromPrice;
            $value = $toPrice;

            $fromPrice  = ($value-1)*$range;
            $toPrice    = $value*$range;
        }

        $formattedFromPrice  = $store->formatPrice($fromPrice);
        $formattedToPrice    = $store->formatPrice($toPrice);

        if (!$fromPrice && $toPrice && $helper->useFirstRangeText()) {
            $label = $helper->__('Up to %s', $formattedToPrice);
        } elseif ($fromPrice && !$toPrice && $usePriceRanges) {
            // this translation does not exist in Magento CE < 1.7, so this module manages it on its own
            $label = $helper->__('%s and above', $formattedFromPrice);
        } /*elseif ($usePriceRanges
            && $fromPrice == $toPrice
        ) {
            $label = $formattedFromPrice;
        }*/ else {
            $label = $helper->__('%s - %s', $formattedFromPrice, $formattedToPrice);
        }

        return $label;
    }
}
