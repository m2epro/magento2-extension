<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Template\SellingFormat;

class PriceChange extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'template/selling_format/price_change.phtml';

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    public $magentoAttributeHelper;
    /** @var array */
    private $allAttributes;
    /** @var array */
    private $attributesByInputTypes;

    /**
     * @param \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->allAttributes = $magentoAttributeHelper->getAll();
        $this->attributesByInputTypes = [
            'text_price' => $magentoAttributeHelper->filterByInputTypes($this->allAttributes, ['text', 'price']),
        ];
    }

    /**
     * @param string $priceModifierString
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getPriceModifierAttributes(string $priceModifierString): array
    {
        $priceModifier = \Ess\M2ePro\Helper\Json::decode($priceModifierString);
        if (!is_array($priceModifier) || empty($priceModifier)) {
            return [];
        }

        $result = [];
        foreach ($priceModifier as $modification) {
            if (
                $modification['mode'] == \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_ATTRIBUTE
                && $modification['attribute_code']
            ) {
                $result[] = $modification['attribute_code'];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllAttributes(): array
    {
        return $this->allAttributes;
    }

    /**
     * @return array
     */
    public function getAttributesByInputTypes(): array
    {
        return $this->attributesByInputTypes;
    }

    /**
     * @return string|null
     */
    public function getPriceType(): ?string
    {
        return $this->getData('price_type');
    }

    /**
     * @return string
     */
    public function getPriceModifier(): string
    {
        return (string)$this->getData('price_modifier');
    }
}
