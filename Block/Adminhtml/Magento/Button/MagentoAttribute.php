<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Button;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Button\MagentoAttribute
 */
class MagentoAttribute extends \Ess\M2ePro\Block\Adminhtml\Magento\Button
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($helperFactory, $context, $data);
        $this->dataHelper = $dataHelper;
    }

    protected function _prepareAttributes($title, $classes, $disabled)
    {
        $destinationId = $this->getDestinationId();
        $onClickCallback = $this->getOnClickCallback() ? $this->getOnClickCallback() : false;

        $onclick = "AttributeObj.appendToText('selectAttr_{$destinationId}', '{$destinationId}');";

        if ($onClickCallback) {
            $onclick .= "{$onClickCallback}";
        }

        $attributes = [
            'id'       => $this->getId(),
            'name'     => $this->getElementName(),
            'title'    => $title,
            'type'     => $this->getType(),
            'class'    => join(' ', $classes) . ' magento-attribute-btn',
            'onclick'  => $onclick,
            'style'    => $this->getStyle(),
            'value'    => $this->getValue(),
            'disabled' => $disabled,
        ];

        if ($this->getDataAttribute()) {
            foreach ($this->getDataAttribute() as $key => $attr) {
                $attributes['data-' . $key] = is_scalar($attr)
                    ? $attr : $this->dataHelper->jsonEncode($attr);
            }
        }

        return $attributes;
    }

    //########################################
}
