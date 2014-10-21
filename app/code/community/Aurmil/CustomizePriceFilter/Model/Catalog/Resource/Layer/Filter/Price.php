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
        if (!method_exists($filter, 'usePriceRanges') || !$filter->usePriceRanges()) {
            return parent::applyFilterToCollection($filter, $from, $to);
        }

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
            $to = (int)$to;
            if ($from == $to) {
                $to += 1;
            }
        }

        if ('' !== $from) {
            $select->where($priceExpr . ' >= ?', $from);
        }
        if ('' !== $to) {
            $select->where($priceExpr . ' < ?', $to);
        }

        return $this;
    }
}
