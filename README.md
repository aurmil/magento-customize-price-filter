# Magento - Customize price filter extension

## Overview

Magento is able to display price ranges in the layered navigation. It offers 3 ways to calculate price step. But none of them allows to specify exactly the price ranges you want to see.

Another point is that Magento subtracts 0.01 to the highest value of each price range when displaying them. I.e. if range is "100-200", Magento will display "100.00 - 199.99".

This extension allows you to set the exact price ranges you need (globally, per store and/or per category), to disable subtraction of 0.01 and to display "Up to [price]" instead of "0.00 - [price]".

## Compatibility

Tested on Magento CE 1.6 - 1.9

## Notes

* Free and open source
* Fully configurable
* Bundled with English and French translations

## Installation

No Magento files will be modified but following classes will be extended and some of their methods overridden:

* Mage\_Catalog\_Model\_Layer\_Filter\_Price
* Mage\_Catalog\_Model\_Resource\_Layer\_Filter\_Price

### With modman

* ```$ modman clone https://github.com/aurmil/magento-customize-price-filter.git```

### Manually

* Download the latest version of this module [here](https://github.com/aurmil/magento-customize-price-filter/archive/master.zip)
* Unzip it
* Move the "app" folder into the root directory of your Magento application, it will be merged with the existing "app" folder

### With composer

* Adapt the following "composer.json" file into yours:

```
{
    "require": {
        "aurmil/magento-customize-price-filter": "dev-master"
    },
    "repositories": [
        {
            "type": "composer",
            "url": "http://packages.firegento.com"
        },
        {
            "type": "vcs",
            "url": "git://github.com/aurmil/magento-customize-price-filter"
        }
    ],
    "extra": {
        "magento-root-dir": "./"
    }
}
```

* Install or update your composer project dependencies

## Usage

In __System > Configuration > Catalog > Catalog > Layered Navigation__, this extension adds three new options:

### Enable/disable 0.01 subtraction from the highest value of each price range

![](https://1.bp.blogspot.com/-tEmrYPB_3hA/VwNsGHzNJyI/AAAAAAAAXeQ/YZqMFrvxVOE5uQcOjX7gsxtUg6NnVnjoA/s1600/price_filter_substract.PNG)

Allows to display, for example, "100.00 - 200.00" instead of "100.00 - 199.99".

This option is available regardless of the value set for __Price Navigation Step Calculation__ and has absolutely no effect on Magento CE < 1.7 as these versions do not substract 0.01.

* Select "Yes" (default value) to stay with the Magento default behavior
* Select "No" to disable subtraction

When disabling subtraction, the price filter becomes inclusive. This means that if the upper value of a range is X, products with min price equals exactly X will be listed (and counted in) for this filter.

### Enable/disable using text in the first range label

![](https://2.bp.blogspot.com/-Oj7D-dTRaw0/VwNsHCCOc8I/AAAAAAAAXeU/5GmEU49BdbkvoNmDH4osknQzEPisAOaTw/s1600/price_filter_use_label.PNG)

Allows to display, for example, "Up to 99.99" instead of "0.00 - 99.99".

This option is available regardless of the value set for __Price Navigation Step Calculation__.

* Select "Yes" to display text
* Select "No" (default value) to stay with the Magento default behavior

The text/translation can be modified, if needed, in __app/locale/xx_XX/Aurmil_CustomizePriceFilter.csv__ files.

### Use custom price ranges

![](http://4.bp.blogspot.com/-ubCE1QQ-XSs/UHkh7AbIvBI/AAAAAAAALMg/dACSlC0T6Xw/s1600/price-ranges.png)

__Note about the screenshot:__ you can see a semicolon at the end of the field. This is just because the value continues on the right, this is not the last character of the price range.

This option is only available if you choose __Manual__ for __Price Navigation Step Calculation__.

Leaving this field empty means stay with the Magento basic behavior for manual calculation.

You have to stick to this format:

* __;__ separates prices ranges
* __\-__ separates min and max values of a given range
* values must be integers
* min value of the first range and max value of the last range are optional

In __Catalog > Manage Categories__, this extension adds a new category attribute: __Price Ranges__ in the __Display Settings__ tab panel.

![](http://1.bp.blogspot.com/-tpY23PoFlSs/VEe393Ml79I/AAAAAAAARyo/mWC7SL9yc6o/s1600/display-settings.png)

This attribute allows you to override the price ranges configuration option for each catalog category. It will be considered when browsing the corresponding category frontend page.

Leaving this field empty means using the price ranges configuration option.

## Uninstall

If you disable the module or completely remove the files, you will get an error as the catalog category attribute is still in DB and its backend model can not be found anymore.

So remove "filter_price_ranges" attribute from "eav_attribute" table and "aurmil_customizepricefilter_setup" entry from "core_resource" table then clear caches, rebuild indexes and voilà.

## License

The MIT License (MIT). Please see [License File](https://github.com/aurmil/magento-customize-price-filter/blob/master/LICENSE.md) for more information.
