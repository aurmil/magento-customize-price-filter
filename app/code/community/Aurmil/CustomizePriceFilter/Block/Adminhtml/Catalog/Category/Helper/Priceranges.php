<?php
/**
 * @author Aurélien Millet
 * @link https://github.com/aurmil/magento-customize-price-filter
 * @license https://github.com/aurmil/magento-customize-price-filter/blob/master/LICENSE.md
 */

/**
 * @see Mage_Adminhtml_Block_Catalog_Category_Helper_Pricestep
 */
class Aurmil_CustomizePriceFilter_Block_Adminhtml_Catalog_Category_Helper_Priceranges
extends Varien_Data_Form_Element_Text
{
    /**
     * Returns js code that is used instead of default toggle code for "Use default config" checkbox
     *
     * @return string
     */
    public function getToggleCode()
    {
        $htmlId = 'use_config_' . $this->getHtmlId();
        return "toggleValueElements(this, this.parentNode.parentNode);"
            . "if (!this.checked) toggleValueElements($('$htmlId'), $('$htmlId').parentNode);";
    }

    /**
     * Retrieve Element HTML fragment
     *
     * @return string
     */
    public function getElementHtml()
    {
        $elementDisabled = $this->getDisabled() == 'disabled';
        $disabled = false;

        if (!$this->getValue() || $elementDisabled) {
            $this->setData('disabled', 'disabled');
            $disabled = true;
        }

        parent::addClass('validate-no-html-tags'); // only modification compared to parent's code
        $html = parent::getElementHtml();
        $htmlId = 'use_config_' . $this->getHtmlId();
        $html .= '<br/><input id="'.$htmlId.'" name="use_config[]" value="' . $this->getId() . '"';
        $html .= ($disabled ? ' checked="checked"' : '');

        if ($this->getReadonly() || $elementDisabled) {
            $html .= ' disabled="disabled"';
        }

        $html .= ' onclick="toggleValueElements(this, this.parentNode);" class="checkbox" type="checkbox" />';

        $html .= ' <label for="' . $htmlId . '" class="normal">'
            . Mage::helper('adminhtml')->__('Use Config Settings') .'</label>';
        $html .= '<script type="text/javascript">' . 'toggleValueElements($(\'' . $htmlId . '\'), $(\'' . $htmlId
            . '\').parentNode);' . '</script>';

        return $html;
    }
}
