<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class SaveRecentProductDataNick extends Description
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductData */
    protected $productData;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\ProductData $productData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->productData = $productData;
    }

    public function execute()
    {
        $marketplaceId   = $this->getRequest()->getPost('marketplace_id');
        $productDataNick = $this->getRequest()->getPost('product_data_nick');

        if (!$marketplaceId || !$productDataNick) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        $this->productData->addRecent($marketplaceId, $productDataNick);
        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }
}
