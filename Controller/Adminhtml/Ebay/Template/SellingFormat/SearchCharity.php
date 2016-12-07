<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;
use \Magento\Backend\App\Action;

class SearchCharity extends Template
{
    //########################################

    public function execute()
    {
        $query = $this->getRequest()->getPost('query');
        $destination = $this->getRequest()->getPost('destination');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        $params = array(
            $destination    => $query,
            'maxRecord'     => 10,
        );

        try {

            $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('marketplace', 'get', 'charity',
                $params, NULL,
                $marketplaceId
            );

            $dispatcherObject->process($connectorObj);
            $responseData = $connectorObj->getResponseData();

        } catch (\Exception $e) {
            $this->setJsonContent([
                'result' => 'error',
                'html' => $this->getLayout()->createBlock('Magento\Framework\View\Element\Messages')
                    ->addError($this->__('Error search charity'))->toHtml()
            ]);

            return $this->getResult();
        }

        $charities = empty($responseData['Charities']) ? [] : $responseData['Charities'];
        $totalCount = empty($responseData['total_count']) ? 0 : $responseData['total_count'];

        $grid = $this->createBlock(
            'Ebay\Template\SellingFormat\Edit\Form\Charity\Search\Grid', '', [
                'data' => [
                    'charities' => $charities
                ]
            ]
        );

        $response = array(
            'result' => 'success',
            'html' => $grid->toHtml()
        );

        if ((int)$totalCount > 10) {
            $response['count'] = (int)$responseData['total_count'];
        }

        $this->setJsonContent($response);

        return $this->getResult();
    }

    //########################################
}