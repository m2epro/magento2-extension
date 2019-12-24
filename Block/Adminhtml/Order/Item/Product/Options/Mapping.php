<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Item\Product\Options;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\Item\Product\Options\Mapping
 */
class Mapping extends AbstractContainer
{
    protected $_template = 'order/item/product/options/mapping.phtml';

    /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
    private $magentoProduct = null;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Item
     */
    public function getOrderItem()
    {
        return $this->getHelper('Data\GlobalData')->getValue('order_item');
    }

    public function getProductTypeHeader()
    {
        $title = $this->__('Custom Options');

        if ($this->magentoProduct->isBundleType()) {
            $title = $this->__('Bundle Items');
        } elseif ($this->magentoProduct->isGroupedType() ||
                  $this->magentoProduct->isConfigurableType()) {
            $title = $this->__('Associated Products');
        }

        return $title;
    }

    public function isMagentoOptionSelected(array $magentoOption, array $magentoOptionValue)
    {
        if ($this->magentoProduct->isGroupedType()) {
            $associatedProducts = $this->getOrderItem()->getAssociatedProducts();

            if (count($associatedProducts) == 1
                && count(array_diff($associatedProducts, $magentoOptionValue['product_ids'])) == 0
            ) {
                return true;
            }

            return false;
        }

        $associatedOptions = $this->getOrderItem()->getAssociatedOptions();

        if (isset($associatedOptions[(int)$magentoOption['option_id']])
            && $associatedOptions[(int)$magentoOption['option_id']] == $magentoOptionValue['value_id']
        ) {
            return true;
        }

        return false;
    }

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $channelOptions = [];

        foreach ($this->getOrderItem()->getChildObject()->getVariationChannelOptions() as $attribute => $value) {
            $channelOptions[] = ['label' => $attribute, 'value' => $value];
        }

        $this->setData('channel_options', $channelOptions);
        // ---------------------------------------

        // ---------------------------------------
        $this->magentoProduct = $this->getOrderItem()->getMagentoProduct();

        $magentoOptions = [];
        $magentoVariations = $this->magentoProduct->getVariationInstance()->getVariationsTypeRaw();

        if ($this->magentoProduct->isGroupedType()) {
            $magentoOptionLabel = $this->__(
                \Ess\M2ePro\Model\Magento\Product\Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL
            );

            $magentoOption = [
                'option_id' => 0,
                'label' => $magentoOptionLabel,
                'values' => []
            ];

            foreach ($magentoVariations as $key => $magentoVariation) {
                $magentoOption['values'][] = [
                    'value_id' => $key,
                    'label' => $magentoVariation->getName(),
                    'product_ids' => [$magentoVariation->getId()]
                ];
            }

            $magentoOptions[] = $magentoOption;
        } else {
            foreach ($magentoVariations as $magentoVariation) {
                $magentoOptionLabel = array_shift($magentoVariation['labels']);
                if (!$magentoOptionLabel) {
                    $magentoOptionLabel = $this->__('N/A');
                }

                $magentoOption = [
                    'option_id' => $magentoVariation['option_id'],
                    'label' => $magentoOptionLabel,
                    'values' => []
                ];

                foreach ($magentoVariation['values'] as $magentoOptionValue) {
                    $magentoValueLabel = array_shift($magentoOptionValue['labels']);
                    if (!$magentoValueLabel) {
                        $magentoValueLabel = $this->__('N/A');
                    }

                    $magentoOption['values'][] = [
                        'value_id' => $magentoOptionValue['value_id'],
                        'label' => $magentoValueLabel,
                        'product_ids' => $magentoOptionValue['product_ids']
                    ];
                }

                $magentoOptions[] = $magentoOption;
            }
        }

        $this->setData('magento_options', $magentoOptions);
        // ---------------------------------------

        $this->setChild(
            'product_mapping_options_help_block',
            $this->createBlock('HelpBlock')->setData([
                'content' => $this->__(
                    'As M2E Pro was not able to find appropriate Option in Magento Product you are supposed
                    find and Map it manualy.
                    <br/>If you want to use the same Settings for the similar subsequent Orders,
                    select appropriate check-box at the bottom.
                    <br/><br/><b>Note:</b> Magento Order can be only created when all Products of Order are
                    found in Magento Catalog.'
                )
            ])
        );

        $this->setChild(
            'product_mapping_options_out_of_stock_message',
            $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class)
                ->addWarning($this->__('Selected Product Option is Out of Stock.'))
        );

        parent::_beforeToHtml();
    }

    //########################################
}
