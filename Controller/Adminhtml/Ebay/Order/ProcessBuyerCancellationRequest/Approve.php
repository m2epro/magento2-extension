<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order\ProcessBuyerCancellationRequest;

use Ess\M2ePro\Model\Ebay\Order\Cancellation\ByBuyer\MassManager as MassCancellationManager;

class Approve extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Order
{
    /** @var MassCancellationManager */
    private $massCancellationManager;

    public function __construct(
        MassCancellationManager $massCancellationManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->massCancellationManager = $massCancellationManager;
    }

    public function execute()
    {
        $ordersIds = $this->getRequestIds();
        if (empty($ordersIds)) {
            return $this->_redirect($this->redirect->getRefererUrl());
        }

        $result = $this->massCancellationManager
            ->approveCancellationRequests(
                $ordersIds,
                \Ess\M2ePro\Helper\Data::INITIATOR_USER
            );

        if ($success = $result->getSuccess()) {
            $this->messageManager->addSuccessMessage(
                __(
                    "Acceptance of the order cancellation request(s) for %count order(s) is being processed.",
                    [
                        'count' => $success,
                    ]
                )
            );
        }

        if ($errors = $result->getErrors()) {
            $this->messageManager->addErrorMessage(
                __(
                    "Cancellation request(s) for %count order(s) were not accepted.",
                    [
                        'count' => $errors,
                    ]
                )
            );
        }

        if ($notAllowed = $result->getNotAllowed()) {
            $this->messageManager->addWarningMessage(
                __(
                    "Cancellation request(s) for %count order(s) is not available to be accepted.",
                    [
                        'count' => $notAllowed,
                    ]
                )
            );
        }

        if (count($ordersIds) === 1) {
            return $this->_redirect('*/ebay_order/view', ['id' => $ordersIds[0]]);
        }

        return $this->_redirect($this->redirect->getRefererUrl());
    }
}
