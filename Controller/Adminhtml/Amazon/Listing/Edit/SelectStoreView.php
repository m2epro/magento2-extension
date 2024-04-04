<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Edit;

class SelectStoreView extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->amazonFactory = $amazonFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $params['id']);

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
