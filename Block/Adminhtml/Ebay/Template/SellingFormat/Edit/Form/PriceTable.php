<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PriceTable extends AbstractBlock
{
    protected $_template = 'ebay/template/selling_format/price_table.phtml';

    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $currency;
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    public $magentoAttributeHelper;

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $currency,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->currency = $currency;
        $this->helperData = $helperData;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
    }

    public function getCurrencySymbol($currency)
    {
        return $this->currency->getCurrency($currency)->getSymbol();
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
            ->setData([
                'label'   => $this->__('Add Price Change'),
                'onclick' => 'EbayTemplateSellingFormatObj.addFixedPriceChangeRow();',
                'class' => 'action primary'
            ]);
        $this->setChild('add_fixed_price_change_button', $buttonBlock);
    }

    /**
     * @param string $fixedPriceModifierString
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getFixedPriceModifierAttributes($fixedPriceModifierString)
    {
        $fixedPriceModifier = $this->helperData->jsonDecode($fixedPriceModifierString);
        if (!is_array($fixedPriceModifier) || empty($fixedPriceModifier)) {
            return [];
        }

        $result = [];
        foreach ($fixedPriceModifier as $modification) {
            if ($modification['mode'] == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_ATTRIBUTE
                && $modification['attribute_code']
            ) {
                $result[] = $modification['attribute_code'];
            }
        }

        return $result;
    }
}
