<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\MultiLocationInventory;

class MappingFieldRender extends \Magento\Backend\Block\Template implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    protected $_template = 'Ess_M2ePro::amazon/account/multi_location_inventory/mapping_field.phtml';

    /** @var \Magento\Inventory\Model\Source[] */
    private array $magentoSources;
    /** @var \Ess\M2ePro\Model\Amazon\Connector\InventoryLocation\Get\Locations\Location[] */
    private array $amazonLocations;
    private string $selectFormKey;
    private \Ess\M2ePro\Model\Amazon\Account\MultiLocationInventoryMapping $multiLocationInventoryMapping;

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element): string
    {
        $this->init($element);

        return $this->toHtml();
    }

    public function getMagentoSources(): array
    {
        return $this->magentoSources;
    }

    public function getAmazonLocationSelect(\Magento\Inventory\Model\Source $magentoSource): string
    {
        $magentoSourceCode = $magentoSource->getSourceCode();

        $existedMapping = $this->multiLocationInventoryMapping
            ->findByMagentoSourceCode($magentoSourceCode);

        $select = sprintf(
            '<select class="amazon_location_select" data-for="%1$s" name="%2$s[%1$s][id]" style="width: 100%%">%3$s</select>',
            $magentoSourceCode,
            $this->selectFormKey,
            $this->getAmazonOptionsHtml($existedMapping)
        );

        $titleInput = sprintf(
            '<input id="%1$s_title" type="hidden" name="%2$s[%1$s][title]" value=""/>',
            $magentoSourceCode,
            $this->selectFormKey
        );

        return $select . $titleInput;
    }

    private function init(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->selectFormKey = $element->getData('select_form_key');
        $this->magentoSources = $element->getData('magento_sources');
        $this->amazonLocations = $element->getData('amazon_locations');
        $this->multiLocationInventoryMapping = $element->getData('multi_location_inventory_mapping');
    }

    private function getAmazonOptionsHtml(
        ?\Ess\M2ePro\Model\Amazon\Account\MultiLocationInventoryMapping\Item $existedMapping
    ): string {
        $options = [
            sprintf('<option value="">%s</option>', __('None')),
        ];

        foreach ($this->amazonLocations as $amazonLocation) {
            $selected = $existedMapping !== null
                && $existedMapping->amazonLocationCode === $amazonLocation->supplySourceId;

            $options[] = sprintf(
                '<option value="%s"%s>%s</option>',
                $amazonLocation->supplySourceId,
                $selected ? ' selected' : '',
                $amazonLocation->alias
            );
        }

        return implode('', $options);
    }
}
