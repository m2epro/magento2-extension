<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\ListAction;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\ListAction\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Amazon\Connector\Product\ProcessingRunner
{
    // ########################################

    protected function eventBefore()
    {
        $params = $this->getParams();

        $accountId = (int)$params['account_id'];
        $sku       = (string)$params['request_data']['sku'];

        $processingActionListSku = $this->activeRecordFactory->getObject('Amazon_Processing_Action_ListAction_Sku');
        $processingActionListSku->setData([
            'account_id' => $accountId,
            'sku'        => $sku,
        ]);
        $processingActionListSku->save();

        parent::eventBefore();
    }

    protected function eventAfter()
    {
        $params = $this->getParams();

        $accountId = (int)$params['account_id'];
        $sku       = (string)$params['request_data']['sku'];

        $processingActionListSkuCollection = $this->activeRecordFactory
                                                  ->getObject('Amazon_Processing_Action_ListAction_Sku')
                                                  ->getCollection();
        $processingActionListSkuCollection->addFieldToFilter('account_id', $accountId);
        $processingActionListSkuCollection->addFieldToFilter('sku', $sku);

        /** @var \Ess\M2ePro\Model\Amazon\Processing\Action\ListAction\Sku $processingActionListSku */
        $processingActionListSku = $processingActionListSkuCollection->getFirstItem();

        if ($processingActionListSku->getId()) {
            $processingActionListSku->delete();
        }

        parent::eventAfter();
    }

    // ########################################
}
