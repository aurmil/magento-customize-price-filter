<?php
/**
 * @author Aurélien Millet
 * @link https://github.com/aurmil/magento-customize-price-filter
 * @license https://github.com/aurmil/magento-customize-price-filter/blob/master/LICENSE.md
 */

class Aurmil_CustomizePriceFilter_Model_Catalog_Resource_Layer_Filter_Price
extends Mage_Catalog_Model_Resource_Layer_Filter_Price
{
    /**
     * for Magento CE >= 1.7
     */
    public function applyPriceRange($filter)
    {
        // parent's code

        $interval = $filter->getInterval();
        if (!$interval) {
            return $this;
        }

        list($from, $to) = $interval;
        if ($from === '' && $to === '') {
            return $this;
        }

        $select = $filter->getLayer()->getProductCollection()->getSelect();
        $priceExpr = $this->_getPriceExpression($filter, $select, false);

        if ($to !== '') {
            $to = (float)$to;
            if ($from == $to) {
                $to += self::MIN_POSSIBLE_PRICE;
            }
        }

        if ($from !== '') {
            $select->where($priceExpr . ' >= ' . $this->_getComparingValue($from, $filter));
        }
        if ($to !== '') {
            // only modification compared to parent's code
            $operator = ' <= ';
            $decrease = false;
            if (Mage::helper('aurmil_customizepricefilter')->usePriceSubstract()) {
                $operator = ' < ';
                $decrease = true;
            }
            $select->where($priceExpr . $operator . $this->_getComparingValue($to, $filter, $decrease));
        }

        return $this;
    }

    /**
     * for Magento CE < 1.7
     * changed parameters names
     */
    public function applyFilterToCollection($filter, $from, $to)
    {
        // we don't care of price substraction here (< 1.7) so
        // if custom price ranges not used => parent
        if (!Mage::helper('aurmil_customizepricefilter')->usePriceRanges()) {
            return parent::applyFilterToCollection($filter, $from, $to);
        }

        // from Magento CE 1.9
        if ($from === '' && $to === '') {
            return $this;
        }

        // parent's code
        $collection = $filter->getLayer()->getProductCollection();
        $collection->addPriceData($filter->getCustomerGroupId(), $filter->getWebsiteId());
        $select     = $collection->getSelect();
        $response   = $this->_dispatchPreparePriceEvent($filter, $select);
        $table      = $this->_getIndexTableAlias();
        $additional = join('', $response->getAdditionalCalculations());
        $rate       = $filter->getCurrencyRate();
        $priceExpr  = new Zend_Db_Expr("(({$table}.min_price {$additional}) * {$rate})");

        // modification compared to parent's code
        if ($from !== '') {
            $select->where($priceExpr . ' >= ?', $from);
        }
        if ($to !== '') {
            $select->where($priceExpr . ' < ?', $to);
        }

        return $this;
    }

    public function getCount($filter, $range)
    {
        $counts = parent::getCount($filter, $range);

        // if price substract not used and Magento CE >= 1.7
        // check on parent class as self class defines the method applyPriceRange
        if (!Mage::helper('aurmil_customizepricefilter')->usePriceSubstract()
            && method_exists(new Mage_Catalog_Model_Resource_Layer_Filter_Price, 'applyPriceRange')
        ) {
            // products with price = range upper boundary
            // won't be counted in the range items count
            // so we have to count them in additionally

            // we need to know the range upper price
            if ($filter->hasData('currentRangePrices')
                && $filter->getData('currentRangePrices')
            ) {
                $prices = $filter->getData('currentRangePrices');

                // if prices are equals, count is already correct, 
                // even if price is a range upper boundary
                if ($prices[0] !== $prices[1]) {
                    // parent's code
                    $select = $this->_getSelect($filter);
                    $priceExpression = $this->_getFullPriceExpression($filter, $select);
                    $range = floatval($range);
                    if ($range == 0) {
                        $range = 1;
                    }
                    $countExpr = new Zend_Db_Expr('COUNT(*)');
                    $rangeExpr = new Zend_Db_Expr("FLOOR(({$priceExpression}) / {$range})"); // modification
                    $rangeOrderExpr = new Zend_Db_Expr("FLOOR(({$priceExpression}) / {$range}) + 1 ASC");
                    $select->columns(array(
                        'range' => $rangeExpr,
                        'count' => $countExpr
                    ));

                    // modifications
                    $select->group($rangeExpr);
                    $select->where("MOD({$priceExpression}, {$prices[1]}) = 0");
                    $counts2 = $this->_getReadAdapter()->fetchPairs($select);

                    if (count($counts2)) {
                        foreach ($counts2 as $i => $c) {
                            if (!isset($counts[$i])) {
                                $counts[$i] = 0;
                            }

                            $counts[$i] += $c;
                        }

                        // in case we added new key(s), sort array on keys to order them correctly
                        ksort($counts);
                    }
                }
            }
        }

        return $counts;
    }
}
