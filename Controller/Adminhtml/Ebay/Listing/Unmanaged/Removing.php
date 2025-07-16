<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Unmanaged;

class Removing extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    private \Ess\M2ePro\Model\Ebay\Listing\Other\Remover $productRemover;
    private \Ess\M2ePro\Helper\Module\Exception $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Other\Remover $productRemover,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->productRemover = $productRemover;
        $this->exceptionHelper = $exceptionHelper;
    }

    public function execute()
    {
        $productIds = $this->getRequest()->getParam('product_ids');

        if (!$productIds) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        $productArray = array_map('intval', explode(',', $productIds));

        if (empty($productArray)) {
            $this->setAjaxContent('0', false);

            return $this->getResult();
        }

        try {
            $this->productRemover->remove($productArray);
        } catch (\Throwable $exception) {
            $this->exceptionHelper->process($exception);
            $this->setAjaxContent('removing_error', false);
            return $this->getResult();
        }

        $this->setAjaxContent('1', false);

        return $this->getResult();
    }
}
