<?php

/**
 * @author     Aurélien Millet
 * @link       https://github.com/aurmil/
 */

class Aurmil_CustomizePriceFilter_Model_Catalog_Resource_Layer_Filter_Price
extends Mage_Catalog_Model_Resource_Layer_Filter_Price
{
    public function applyFilterToCollection($filter, $from, $to)
    {
        if (method_exists($filter, 'usePriceRanges') && $filter->usePriceRanges()) {
            if (('' === $from) && ('' === $to)) {
                return $this;
            }

            $collection = $filter->getLayer()->getProductCollection();
            $collection->addPriceData($filter->getCustomerGroupId(), $filter->getWebsiteId());

            $select     = $collection->getSelect();
            $response   = $this->_dispatchPreparePriceEvent($filter, $select);

            $table      = $this->_getIndexTableAlias();
            $additional = join('', $response->getAdditionalCalculations());
            $rate       = $filter->getCurrencyRate();
            $priceExpr  = new Zend_Db_Expr("(({$table}.min_price {$additional}) * {$rate})");

            if ('' !== $to) {
                $to = (int) $to;
                if ($from == $to) {
                    $to += 1;
                }
            }

            if ('' !== $from) {
                $select->where($priceExpr . ' >= ?', $from);
            }
            if ('' !== $to) {
                if (Mage::getStoreConfigFlag('catalog/layered_navigation/price_subtraction')) {
                    $select->where($priceExpr . ' < ?', $to);
                } else {
                    $select->where($priceExpr . ' <= ?', $to);
                }
            }
        } else {
            $range = $from;
            $index = $to;

            $collection = $filter->getLayer()->getProductCollection();
            $collection->addPriceData($filter->getCustomerGroupId(), $filter->getWebsiteId());

            $select     = $collection->getSelect();
            $response   = $this->_dispatchPreparePriceEvent($filter, $select);

            $table      = $this->_getIndexTableAlias();
            $additional = join('', $response->getAdditionalCalculations());
            $rate       = $filter->getCurrencyRate();
            $priceExpr  = new Zend_Db_Expr("(({$table}.min_price {$additional}) * {$rate})");

            $select->where($priceExpr . ' >= ?', ($range * ($index - 1)));

            if (Mage::getStoreConfigFlag('catalog/layered_navigation/price_subtraction')) {
                $select->where($priceExpr . ' < ?', ($range * $index));
            } else {
                $select->where($priceExpr . ' <= ?', ($range * $index));
            }
        }

        return $this;
    }

    public function applyPriceRange($filter)
    {
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
            $to = (float) $to;
            if ($from == $to) {
                $to += self::MIN_POSSIBLE_PRICE;
            }
        }

        if ($from !== '') {
            $select->where($priceExpr . ' >= ' . $this->_getComparingValue($from, $filter));
        }
        if ($to !== '') {
            if (Mage::getStoreConfigFlag('catalog/layered_navigation/price_subtraction')) {
                $select->where($priceExpr . ' < ' . $this->_getComparingValue($to, $filter));
            } else {
                $select->where($priceExpr . ' <= ' . $to);
            }
        }

        return $this;
    }
}
