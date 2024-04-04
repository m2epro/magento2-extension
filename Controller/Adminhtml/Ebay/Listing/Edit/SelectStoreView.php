<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit;

class SelectStoreView extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->ebayFactory = $ebayFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $params['id']);

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
