<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

class AmazonProductWithoutVariations extends AbstractInspection implements InspectorInterface, FixerInterface
{
    //########################################

    public function getTitle()
    {
        return 'Amazon products without variations';
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
        $brokenItems = [];

        $listingProductVariationTable = $this->activeRecordFactory->getObject('Listing_Product_Variation')
            ->getResource()
            ->getMainTable();

        $collection = $this->parentFactory->getObject(Amazon::NICK, 'Listing\Product')->getCollection();
        $collection->getSelect()->joinLeft(
            ['mlpv' => $listingProductVariationTable],
            '`second_table`.`listing_product_id` = `mlpv`.`listing_product_id`',
            []
        );
        $collection->addFieldToFilter('is_variation_product', 1);
        $collection->addFieldToFilter('is_variation_product_matched', 1);
        $collection->addFieldToFilter('mlpv.id', ['null' => true]);

        if (!empty($brokenItems)) {
            $issues[] = $this->resultFactory->createError(
                $this,
                'Has products without variation',
                $this->renderMetadata($brokenItems)
            );
        }

        return $issues;
    }

    protected function renderMetadata($data)
    {
        $formKey = $this->formKey->getFormKey();
        $currentUrl = $this->urlBuilder
            ->getUrl(
                'm2epro/controlPanel_tools_m2ePro/general',
                ['action' => 'repairAmazonProductWithoutVariations']
            );

        $html = <<<HTML
    <form method="POST" action="{$currentUrl}">
    <input type="hidden" name="form_key" value="{$formKey}">
<table>
    <tr>
        <th style="width: 300px"></th>
        <th style="width: 300px"></th>
    </tr>
HTML;

        $repairInfo = $this->helperFactory->getObject('Data')->jsonEncode($data['ids']);
        $input = "<input type='checkbox' style='display: none;' checked='checked' 
        name='repair_info' value='" . $repairInfo . "'>";
        $html .= <<<HTML
<tr>
    <td> Total amazon product without variation: {$data['total']}</td>
    <td>{$input}</td>
</tr>
HTML;
        $html .= '</table>
<button type="button" onclick="ControlPanelInspectionObj.removeRow(this)">Repair</button>
</form>';

        return $html;
    }

    public function fix($ids)
    {
        $collection = $this->parentFactory->getObject(Amazon::NICK, 'Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);

        while ($item = $collection->fetchItem()) {
            $item->getChildObject()->setData('is_variation_product_matched', 0)->save();
        }
    }

    //########################################
}
