<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template\Shipping;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class GetRateTableData extends Template
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($templateManager, $ebayFactory, $context);

        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $accountId     = $this->getRequest()->getParam('account_id', false);
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', false);
        $type          = $this->getRequest()->getParam('type', false);

        if (!$accountId || !$marketplaceId || !$type) {
            return $this->getResponse()->setBody(
                $this->dataHelper->jsonEncode(
                    [
                        'error' => $this->__('Wrong parameters.')
                    ]
                )
            );
        }

        $account = $this->ebayFactory->getObjectLoaded('Account', $accountId);
        /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
        $ebayAccount = $account->getChildObject();

        if (!$ebayAccount->getSellApiTokenSession()) {
            return $this->getResponse()->setBody(
                $this->dataHelper->jsonEncode(
                    [
                        'sell_api_disabled' => true,
                        'error' => $this->__('Sell Api token is missing.')
                    ]
                )
            );
        }

        try {
            $ebayAccount->updateRateTables();
        } catch (\Exception $exception) {
            return $this->getResponse()->setBody(
                $this->dataHelper->jsonEncode(
                    [
                        'error' => $exception->getMessage()
                    ]
                )
            );
        }

        $rateTables = $ebayAccount->getRateTables();

        $marketplace = $this->ebayFactory->getObjectLoaded('Marketplace', $marketplaceId);
        $countryCode = $marketplace->getChildObject()->getOriginCountry();
        $type = $type == 'local' ? 'domestic' : 'international';

        $rateTablesData = [];
        foreach ($rateTables as $rateTable) {
            if (empty($rateTable['countryCode']) ||
                strtolower($rateTable['countryCode']) != $countryCode ||
                strtolower($rateTable['locality']) != $type) {
                continue;
            }

            if (empty($rateTable['rateTableId'])) {
                continue;
            }

            $rateTablesData[$rateTable['rateTableId']] = isset($rateTable['name']) ? $rateTable['name'] :
                $rateTable['rateTableId'];
        }

        $this->setJsonContent(['data' => $rateTablesData]);

        return $this->getResult();
    }
}
