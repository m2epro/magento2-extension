<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

class ListingProductStructure extends AbstractInspection implements InspectorInterface, FixerInterface
{
    /** @var array */
    protected $brokenData = [];

    //########################################

    public function getTitle()
    {
        return 'Listing product structure';
    }

    public function getGroup()
    {
        return Manager::GROUP_PRODUCTS;
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_FAST;
    }

    //########################################

    public function process()
    {
        $issues = [];

        $this->getBrokenOption();
        $this->getBrokenListing();
        $this->getBrokenVariation();
        $this->getBrokenListingProduct();

        if (!empty($this->brokenData)) {
            $issues[] = $this->resultFactory->createError(
                $this,
                'Has broken listing or listing product',
                $this->renderMetadata($this->brokenData)
            );
        }

        return $issues;
    }

    //########################################

    protected function getBrokenOption()
    {
        $listingProductVariationTable = $this->activeRecordFactory->getObject('Listing_Product_Variation')
            ->getResource()->getMainTable();

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')->getCollection();
        $collection->getSelect()->joinLeft(
            ['mlpv' => $listingProductVariationTable],
            'main_table.listing_product_variation_id=mlpv.id',
            []
        );
        $collection->addFieldToFilter('mlpv.id', ['null' => true]);
        $collection->getSelect()->group('main_table.id');

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'main_table.id', 'main_table.component_mode'
        ]);

        if ($total = $collection->getSize()) {
            $this->brokenData['broken_option'] = [
                'table' => $collection->getMainTable(),
                'total' => $total,
                'ids' => $collection->getAllIds()
            ];
        }
    }

    protected function getBrokenVariation()
    {
        $listingProductTable = $this->activeRecordFactory->getObject('Listing\Product')
            ->getResource()->getMainTable();

        $listingProductVariationOptionTable = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')
            ->getResource()->getMainTable();

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Variation')->getCollection();
        $collection->getSelect()->joinLeft(
            ['mlp' => $listingProductTable],
            'main_table.listing_product_id=mlp.id',
            []
        );
        $collection->getSelect()->joinLeft(
            ['mlpvo' => $listingProductVariationOptionTable],
            'main_table.id=mlpvo.listing_product_variation_id',
            []
        );

        $collection->getSelect()->where('mlp.id IS NULL OR mlpvo.id IS NULL');
        $collection->getSelect()->group('main_table.id');

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'main_table.id', 'main_table.component_mode'
        ]);

        if ($total = $collection->getSize()) {
            $this->brokenData['broken_variation'] = [
                'table' => $collection->getMainTable(),
                'total' => $total,
                'ids' => $collection->getAllIds()
            ];
        }
    }

    protected function getBrokenListingProduct()
    {
        $listingTable = $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable();

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->joinLeft(
            ['ml' => $listingTable],
            'main_table.listing_id=ml.id',
            []
        );
        $collection->addFieldToFilter('ml.id', ['null' => true]);
        $collection->getSelect()->group('main_table.id');

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'main_table.id', 'main_table.component_mode'
        ]);

        if ($total = $collection->getSize()) {
            $this->brokenData['broken_product'] = [
                'table' => $collection->getMainTable(),
                'total' => $total,
                'ids' => $collection->getAllIds()
            ];
        }
    }

    protected function getBrokenListing()
    {
        $accountTable = $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable();

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $collection->getSelect()->joinLeft(
            ['ma' => $accountTable],
            'main_table.account_id=ma.id',
            []
        );
        $collection->addFieldToFilter('ma.id', ['null' => true]);
        $collection->getSelect()->group('main_table.id');

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'main_table.id', 'main_table.component_mode'
        ]);

        if ($total = $collection->getSize()) {
            $this->brokenData['broken_listing'] = [
                'table' => $collection->getMainTable(),
                'total' => $total,
                'ids' => $collection->getAllIds()
            ];
        }
    }

    //########################################

    protected function renderMetadata($data)
    {
        $formKey = $this->formKey->getFormKey();
        $currentUrl = $this->urlBuilder
            ->getUrl('m2epro/controlPanel_tools_m2ePro/general', ['action' => 'repairListingProductStructure']);

        $html = <<<HTML
    <form method="POST" action="{$currentUrl}">
    <input type="hidden" name="form_key" value="{$formKey}">
<table>
    <tr>
        <th style="width: 150px"></th>
        <th style="width: 300px"></th>
    </tr>
HTML;

        foreach ($data as $key => $item) {
            $repairInfo =  $this->helperFactory->getObject('Data')->jsonEncode($item);
            $description = str_replace('_', ' ', $key);
            $input = "<input type='checkbox' name='repair_info[]' value='" . $repairInfo . "'>";
            $html .= <<<HTML
<tr>
    <td>{$description} ({$item['total']})</td>
    <td>{$input}</td>
</tr>
HTML;
        }

        $html .= '</table>
<button type="button" onclick="ControlPanelInspectionObj.removeRow(this)">Delete broken items</button>
</form>';

        return $html;
    }

    public function fix($data)
    {
        $connection = $this->resourceConnection->getConnection();

        foreach ($data as $table => $ids) {
            $connection->delete(
                $table,
                '`id` IN (' . implode(',', $ids) . ')'
            );
        }
    }

    //########################################
}
