<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Edit;

class SelectStoreView extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->walmartFactory = $walmartFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $params['id']);

        $this->setAjaxContent(
            $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Listing\Edit\EditStoreView::class,
                '',
                ['listing' => $listing]
            )
        );

        return $this->getResult();
    }
}
