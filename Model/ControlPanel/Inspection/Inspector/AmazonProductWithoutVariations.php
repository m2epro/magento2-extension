<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Component\Amazon;
use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Helper\Data as HelperData;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Data\Form\FormKey;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory as ParentFactory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class AmazonProductWithoutVariations implements InspectorInterface, FixerInterface
{
    /** @var HelperData */
    private $helperData;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var FormKey */
    private $formKey;

    /** @var ParentFactory */
    private $parentFactory;

    /** @var ActiveRecordFactory */
    private $activeRecordFactory;

    /** @var IssueFactory */
    private $issueFactory;

    //########################################

    public function __construct(
        HelperData $helperData,
        UrlInterface $urlBuilder,
        FormKey $formKey,
        ParentFactory $parentFactory,
        ActiveRecordFactory $activeRecordFactory,
        IssueFactory $issueFactory
    ) {
        $this->helperData = $helperData;
        $this->urlBuilder = $urlBuilder;
        $this->formKey = $formKey;
        $this->parentFactory = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->issueFactory = $issueFactory;
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
            $issues[] = $this->issueFactory->create(
                'Has products without variation',
                $this->renderMetadata($brokenItems)
            );
        }

        return $issues;
    }

    private function renderMetadata($data)
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

        $repairInfo = $this->helperData->jsonEncode($data['ids']);
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
