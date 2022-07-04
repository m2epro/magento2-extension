<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Helper\Component\Ebay;
use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\Listing\Product;
use Ess\M2ePro\Helper\Data as HelperData;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Data\Form\FormKey;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory as ParentFactory;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\ControlPanel\Inspection\Issue\Factory as IssueFactory;

class EbayItemIdStructure implements InspectorInterface, FixerInterface
{
    /** @var array */
    private $brokenData = [];

    /** @var HelperData  */
    private $helperData;

    /** @var UrlInterface */
    private $urlBuilder;

    /** @var FormKey */
    private $formKey;

    /** @var ParentFactory */
    private $parentFactory;

    /** @var ActiveRecordFactory */
    private $activeRecordFactory;

    /** @var IssueFactory  */
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
        $this->helperData          = $helperData;
        $this->urlBuilder          = $urlBuilder;
        $this->formKey             = $formKey;
        $this->parentFactory       = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->issueFactory        = $issueFactory;
    }

    //########################################

    public function process()
    {
        $issues = [];

        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Product')->getCollection();
        $collection->getSelect()->joinLeft(
            ['ei' => $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable()],
            '`second_table`.`ebay_item_id` = `ei`.`id`',
            ['item_id' => 'item_id']
        );
        $collection->addFieldToFilter(
            'status',
            [
                'nin' => [
                    Product::STATUS_NOT_LISTED,
                    Product::STATUS_UNKNOWN
                ]
            ]
        );

        $collection->addFieldToFilter('item_id', ['null' => true]);

        if ($total = $collection->getSize()) {
            $this->brokenData = [
                'total' => $total,
                'ids'   => $collection->getAllIds()
            ];
        }

        if (!empty($this->brokenData)) {
            $issues[] = $this->issueFactory->create(
                'Ebay item id N\A',
                $this->renderMetadata($this->brokenData)
            );
        }

        return $issues;
    }

    private function renderMetadata($data)
    {
        $formKey = $this->formKey->getFormKey();
        $currentUrl = $this->urlBuilder
            ->getUrl('m2epro/controlPanel_tools_m2ePro/general', ['action' => 'repairEbayItemIdStructure']);

        $html = <<<HTML
    <form method="POST" action="{$currentUrl}">
    <input type="hidden" name="form_key" value="{$formKey}">
<table>
    <tr>
        <th style="width: 150px"></th>
        <th style="width: 300px"></th>
    </tr>
HTML;
        $repairInfo = $this->helperData->jsonEncode($data['ids']);
        $input = "<input type='checkbox' style='display: none;' checked='checked'
            name='repair_info' value='" . $repairInfo . "'>";
        $html .= <<<HTML
<tr>
    <td>Total broken items ({$data['total']})</td>
    <td>{$input}</td>
</tr>
HTML;
        $html .= '</table>
<button type="button" onclick="ControlPanelInspectionObj.removeRow(this)">Delete broken items</button>
</form>';

        return $html;
    }

    public function fix($ids)
    {
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $this->parentFactory->getObject(Ebay::NICK, 'Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);

        while ($item = $collection->fetchItem()) {
            $item->setData('status', Product::STATUS_NOT_LISTED)->save();
        }
    }

    //########################################
}
