<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Promotion;

class OpenGridPromotion extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $accountId = (int)$this->getRequest()->getParam('account_id');
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Promotion\Grid $grid */
        $grid = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Promotion\Grid::class,
            '',
            [
                'data' => [
                    'accountId' => $accountId,
                    'marketplaceId' => $marketplaceId
                ]
            ]
        );

        $this->setAjaxContent($grid->toHtml());

        return $this->getResult();
    }
}
