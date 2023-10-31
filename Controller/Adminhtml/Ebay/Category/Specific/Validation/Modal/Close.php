<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Specific\Validation\Modal;

class Close extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Specific\Validation\Modal\ListingProductStorage */
    private $storage;

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Specific\Validation\Modal\ListingProductStorage $storage,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->storage = $storage;
    }

    public function execute()
    {
        $this->storage->reset();

        return $this->getResult();
    }
}
