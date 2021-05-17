<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Breadcrumb
 */
class Breadcrumb extends \Ess\M2ePro\Block\Adminhtml\Widget\Breadcrumb
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonOrderMerchantFulfillmentBreadcrumb');

        $this->setSteps(
            [
                [
                    'id'          => 1,
                    'title'       => $this->__('Step 1'),
                    'description' => $this->__('Configure Options')
                ],
                [
                    'id'          => 2,
                    'title'       => $this->__('Step 2'),
                    'description' => $this->__('Choose Service')
                ],
                [
                    'id'          => 3,
                    'title'       => $this->__('Step 3'),
                    'description' => $this->__('Congratulations')
                ]
            ]
        );
    }

    //########################################
}
