<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;
use \Magento\Backend\App\Action;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\SellingFormat\SearchCharity
 */
class SearchCharity extends Template
{
    //########################################

    public function execute()
    {
        $query = $this->getRequest()->getPost('query');
        $destination = $this->getRequest()->getPost('destination');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        $params = [
            $destination    => $query,
            'maxRecord'     => 10,
        ];

        try {
            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'marketplace',
                'get',
                'charity',
                $params,
                null,
                $marketplaceId
            );

            $dispatcherObject->process($connectorObj);
            $responseData = $connectorObj->getResponseData();
        } catch (\Exception $e) {
            $this->setJsonContent([
                'result' => 'error',
                'html' => $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class)
                    ->addError($this->__('Error search charity'))->toHtml()
            ]);

            return $this->getResult();
        }

        $charities = empty($responseData['Charities']) ? [] : $responseData['Charities'];
        $totalCount = empty($responseData['total_count']) ? 0 : $responseData['total_count'];

        $grid = $this->createBlock(
            'Ebay_Template_SellingFormat_Edit_Form_Charity_Search_Grid',
            '',
            [
                'data' => [
                    'charities' => $charities
                ]
            ]
        );

        $response = [
            'result' => 'success',
            'html' => $grid->toHtml()
        ];

        if ((int)$totalCount > 10) {
            $response['count'] = (int)$responseData['total_count'];
        }

        $this->setJsonContent($response);

        return $this->getResult();
    }

    //########################################
}
