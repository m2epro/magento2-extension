<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\SendInvoice;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Orders\SendInvoice\ItemsRequester
 */
abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    /** @var string */
    protected $rawPdfDoc;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account = null,
        array $params = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;

        $this->rawPdfDoc = $params['order']['document_pdf'];
        unset($params['order']['document_pdf']);

        parent::__construct(
            $helperFactory,
            $modelFactory,
            $account,
            $params
        );
    }

    //########################################

    public function getCommand()
    {
        return ['orders', 'send', 'invoice'];
    }

    //########################################

    public function process()
    {
        parent::process();

        if ($this->getProcessingRunner()->getProcessingObject() == null) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Order\Action\Processing $processingAction */
        $processingAction = $this->getProcessingRunner()->getProcessingAction();

        if (!empty($this->processingServerHash)) {
            /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
            $requestPendingSingle = $this->activeRecordFactory->getObject('Request_Pending_Single');
            $requestPendingSingle->setData(
                [
                    'component'       => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                    'server_hash'     => $this->processingServerHash,
                    'expiration_date' => $this->getHelper('Data')->getDate(
                        $this->getHelper('Data')->getCurrentGmtDate(true)
                        + \Ess\M2ePro\Model\Amazon\Order\Action\Processor::PENDING_REQUEST_MAX_LIFE_TIME
                    )
                ]
            );
            $requestPendingSingle->save();

            $this->activeRecordFactory->getObject('Amazon_Order_Action_Processing')->getResource()->markAsInProgress(
                [$processingAction->getId()],
                $requestPendingSingle
            );
        }
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Connector_Orders_ProcessingRunner';
    }

    protected function getProcessingParams()
    {
        return array_merge(
            parent::getProcessingParams(),
            [
                'request_data' => $this->getRequestData(),
                'order_id'     => $this->params['order']['order_id'],
                'change_id'    => $this->params['order']['change_id'],
                'action_type'  => \Ess\M2ePro\Model\Amazon\Order\Action\Processing::ACTION_TYPE_SEND_INVOICE,
                'lock_name'    => 'send_invoice_order',
                'start_date'   => $this->getHelper('Data')->getCurrentGmtDate(),
            ]
        );
    }

    protected function buildRequestInstance()
    {
        $request = parent::buildRequestInstance();
        $request->setRawData($this->rawPdfDoc);

        return $request;
    }

    //########################################

    public function getRequestData()
    {
        $requestData = [
            'order_id' => $this->params['order']['amazon_order_id'],
            'document_number' => $this->params['order']['document_number'],
            'document_type' => $this->params['order']['document_type']
        ];

        if (isset($this->params['order']['document_shipping_id'])) {
            $requestData['document_shipping_id'] = $this->params['order']['document_shipping_id'];
        }

        if (isset($this->params['order']['document_transaction_id'])) {
            $requestData['document_transaction_id'] = $this->params['order']['document_transaction_id'];
        }

        if (isset($this->params['order']['document_total_amount'])) {
            $requestData['document_total_amount'] = $this->params['order']['document_total_amount'];
        }

        if (isset($this->params['order']['document_total_vat_amount'])) {
            $requestData['document_total_vat_amount'] = $this->params['order']['document_total_vat_amount'];
        }

        return $requestData;
    }

    //########################################
}
