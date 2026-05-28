<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

class MultiLocationInventory extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public const FORM_KEY_MULTI_LOCATION_INVENTORY = 'multi_location_inventory';

    private \Ess\M2ePro\Model\Account $account;
    private \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository;
    private \Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory\ReceiveAmazonLocationsList $receiveAmazonLocationsList;

    public function __construct(
        \Ess\M2ePro\Model\Account $account,
        \Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory\ReceiveAmazonLocationsList $receiveAmazonLocationsList,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->account = $account;
        $this->receiveAmazonLocationsList = $receiveAmazonLocationsList;
        $this->sourceRepository = $objectManager->get(\Magento\InventoryApi\Api\SourceRepositoryInterface::class);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $this->addHelpBlock($form);

        $fieldset = $form->addFieldset(
            'location_mapping_table',
            [
                'legend' => __('Amazon Location and Magento Inventory Source Mapping'),
                'collapsable' => false,
            ]
        );

        $tableRender = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\MultiLocationInventory\MappingFieldRender::class
            );

        $fieldset->addField(
            'mapping',
            'note',
            [
                'select_form_key' => self::FORM_KEY_MULTI_LOCATION_INVENTORY,
                'magento_sources' => $this->getMagentoSourcesList(),
                'amazon_locations' => $this->receiveAmazonLocationsList->execute($this->account),
                'multi_location_inventory_mapping' => $this->account->getChildObject()->getMultiLocationInventoryMapping(),
            ]
        )->setRenderer($tableRender);

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    private function addHelpBlock($form): void
    {
        $helpText = __(
            'Define how Amazon inventory locations are linked to your Magento MSI sources.<br>' .
            'Each Amazon location can be mapped to a Magento source to control where inventory quantities ' .
            'are taken from during synchronization.' .
            '<ul><li>Select Amazon location for each Magento Inventory Source</li>' .
            '<li>Leave "None" if the Inventory Source should not be used</li></ul>'
        );

        $form->addField(
            'multi_location_inventory_help',
            self::HELP_BLOCK,
            ['content' => $helpText]
        );
    }

    /**
     * @return \Magento\Inventory\Model\Source[]
     */
    private function getMagentoSourcesList(): array
    {
        $sourceSearchResult = $this->sourceRepository->getList();

        return $sourceSearchResult->getItems();
    }
}
