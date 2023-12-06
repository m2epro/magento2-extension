<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order\ProcessBuyerReturnRequest;

use Ess\M2ePro\Model\Ebay\Order\ReturnRequest\Decide\MassManager as MassReturnManager;

class Approve extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Order
{
    /** @var MassReturnManager */
    private $massReturnManager;

    public function __construct(
        MassReturnManager $massReturnManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->massReturnManager = $massReturnManager;
    }

    public function execute()
    {
        $ordersIds = $this->getRequestIds();
        if (empty($ordersIds)) {
            return $this->_redirect($this->redirect->getRefererUrl());
        }

        $result = $this->massReturnManager
            ->approveReturnRequests(
                $ordersIds,
                \Ess\M2ePro\Helper\Data::INITIATOR_USER
            );

        if ($success = $result->getSuccess()) {
            $this->messageManager->addSuccessMessage(
                (string)__(
                    'Acceptance of the order Return request(s) for %count order(s) is being processed.',
                    [
                        'count' => $success,
                    ]
                )
            );
        }

        if ($errors = $result->getErrors()) {
            $this->messageManager->addErrorMessage(
                (string)__(
                    'Return request(s) for %count order(s) were not accepted.',
                    [
                        'count' => $errors,
                    ]
                )
            );
        }

        if ($notAllowed = $result->getNotAllowed()) {
            $this->messageManager->addWarningMessage(
                (string)__(
                    'Return request(s) for %count order(s) is not available to be accepted.',
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
