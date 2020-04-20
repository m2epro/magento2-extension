<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Revise;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return $this->getResponseObject()->getSuccessfulMessage();
    }

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        if ($this->isSuccess) {
            return;
        }

        $additionalData = $this->listingProduct->getAdditionalData();
        $additionalData['need_full_synchronization_template_recheck'] = true;
        $this->listingProduct->setSettings('additional_data', $additionalData)->save();
    }

    //########################################
}
