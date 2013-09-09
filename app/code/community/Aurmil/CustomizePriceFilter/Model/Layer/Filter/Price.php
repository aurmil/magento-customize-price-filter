<?php

if ((!extension_loaded('gmp') || !function_exists('gmp_gcd')) && !function_exists('gcd'))
{
    function gcd($a, $b)
    {
        $a = abs((int)$a);
        $b = abs((int)$b);

        if ((0 == $a) || (0 == $b))
        {
            $gcd = 1;
        }
        elseif ($a == $b)
        {
            $gcd = $a;
        }
        else
        {
            if ($a < $b)
            {
                $rest = $a;
                $a = $b;
                $b = $rest;
            }

            do
            {
                $rest = $a % $b;
                $a = $b;
                $b = $rest;
            } while (0 !== $rest);

            $gcd = $a;
        }

        return $gcd;
    }
}

class Aurmil_CustomizePriceFilter_Model_Layer_Filter_Price
extends Mage_Catalog_Model_Layer_Filter_Price
{
    protected function _getItemsData()
    {
        $priceRanges = Mage::getStoreConfig('catalog/layered_navigation/price_ranges');

        if (('' != $priceRanges) && (self::RANGE_CALCULATION_MANUAL == Mage::getStoreConfig(self::XML_PATH_RANGE_CALCULATION)))
        {
            $data = array();

            if ($this->getInterval())
            {
                return $data;
            }

            $priceRanges = explode(';', $priceRanges);

            foreach ($priceRanges as $priceRange)
            {
                $range = explode('-', $priceRange);
                $min = (int)$range[0];
                $max = (int)$range[1];

                // 1. from 0 to x
                if (0 == $min)
                {
                    $counts = $this->getRangeItemCounts($max);

                    $count = 0;
                    if (array_key_exists(1, $counts))
                    {
                        $count = $counts[1];
                    }
                }
                // 2. from x to infinite
                elseif (0 == $max)
                {
                    $counts = $this->getRangeItemCounts($min);

                    if (array_key_exists(1, $counts))
                    {
                        unset($counts[1]);
                    }

                    $count = array_sum($counts);
                }
                // 3. from x to y
                else
                {
                    $range = array($min, $max);
                    $min = min($range);
                    $max = max($range);

                    if (extension_loaded('gmp') && function_exists('gmp_gcd'))
                    {
                        $gcd = gmp_strval(gmp_gcd($min, $max));
                    }
                    elseif (function_exists('gcd'))
                    {
                        $gcd = gcd($min, $max);
                    }
                    else
                    {
                        throw new Exception('GCD function is missing!');
                    }
                    
                    $counts = $this->getRangeItemCounts($gcd);

                    $count = 0;
                    for ($i = (($min / $gcd) + 1); ($i * $gcd) <= $max; $i++)
                    {
                        if (array_key_exists($i, $counts))
                        {
                            $count += $counts[$i];
                        }
                    }
                }

                if (0 < $count)
                {
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
        $store      = Mage::app()->getStore();
        $formattedFromPrice  = $store->formatPrice($fromPrice);
        if ($fromPrice === '') {
            return Mage::helper('aurmil_customizepricefilter')->__('Under %s', $store->formatPrice($toPrice));
        } elseif ($toPrice === '') {
            return Mage::helper('catalog')->__('%s and above', $formattedFromPrice);
        } elseif ($fromPrice == $toPrice && Mage::app()->getStore()->getConfig(self::XML_PATH_ONE_PRICE_INTERVAL)) {
            return $formattedFromPrice;
        } else {
            if (($fromPrice != $toPrice) && Mage::getStoreConfigFlag('catalog/layered_navigation/price_subtraction')) {
                $toPrice -= .01;
            }
            return Mage::helper('catalog')->__('%s - %s', $formattedFromPrice, $store->formatPrice($toPrice));
        }
    }
}
