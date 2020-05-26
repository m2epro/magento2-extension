<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Order\Service\Quote\Submit;

/**
 * Class \Ess\M2ePro\Observer\Order\Service\Quote\Submit\Before
 */
class Before extends \Ess\M2ePro\Observer\AbstractModel
{
    //########################################

    public function process()
    {
        /** @var \Magento\Sales\Model\Order $magentoOrder */
        /** @var \Magento\Quote\Model\Quote $quote */

        $magentoOrder = $this->getEvent()->getOrder();
        $quote        = $this->getEvent()->getQuote();

        if ($quote->getIsM2eProQuote()) {
            $magentoOrder->setCanSendNewEmailFlag($quote->getIsNeedToSendEmail());
        }
    }

    //########################################
}
