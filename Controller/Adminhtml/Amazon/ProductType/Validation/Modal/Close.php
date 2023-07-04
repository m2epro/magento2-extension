<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType\Validation\Modal;

class Close extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType\Validation\Modal\ListingProductStorage */
    private $storage;

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType\Validation\Modal\ListingProductStorage $storage,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->storage = $storage;
    }

    public function execute()
    {
        $this->storage->reset();

        return $this->getResult();
    }
}
