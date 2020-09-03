<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\FixerInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;

class EbayItemIdStructure extends AbstractInspection implements InspectorInterface, FixerInterface
{
    /** @var array */
    protected $brokenData = [];

    //########################################

    public function getTitle()
    {
        return 'Ebay item id N\A';
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

        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Order\Item')->getCollection();
        $collection->getSelect()->joinLeft(
            ['mo' => $this->activeRecordFactory->getObject('Order')->getResource()->getMainTable()],
            'main_table.order_id=mo.id',
            []
        );
        $collection->addFieldToFilter('mo.id', ['null' => true]);

        if ($total = $collection->getSize()) {
            $brokenOrdersItem = [
                'total' => $total,
                'ids' => $collection->getAllIds()
            ];
        }

        if (!empty($brokenOrdersItem)) {
            $issues[] = $this->resultFactory->createError(
                $this,
                'Has broken order item',
                $this->renderMetadata($this->brokenData)
            );
        }

        return $issues;
    }

    protected function renderMetadata($data)
    {
        $formKey = $this->formKey->getFormKey();
        $currentUrl = $this->urlBuilder
            ->getUrl('m2epro/controlPanel_tools_m2ePro/general', ['action' => 'repairOrderItemStructure']);

        $html = <<<HTML
    <form method="POST" action="{$currentUrl}">
    <input type="hidden" name="form_key" value="{$formKey}">
<table>
    <tr>
        <th style="width: 150px"></th>
        <th style="width: 300px"></th>
    </tr>
HTML;
        $repairInfo = $this->helperFactory->getObject('Data')->jsonEncode($data['ids']);
        $input = "<input type='checkbox' style='display: none;' checked='checked'
            name='repair_info' value='" . $repairInfo . "'>";
        $html .= <<<HTML
<tr>
    <td>Total broken items ({$data['total']})</td>
    <td>{$input}</td>
</tr>
HTML;
        $html .= '</table>
<input style="margin-top: 10px" type="submit" value="Delete broken items">
</form>';

        return $html;
    }

    public function fix($ids)
    {
        /** @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $collection = $this->activeRecordFactory->getObject('Order\Item')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $ids]);

        while ($item = $collection->fetchItem()) {
            $item->delete();
        }
    }

    //########################################
}
