<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class View extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Order
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->globalData = $globalData;
    }

    public function execute()
    {
        $id    = $this->getRequest()->getParam('id');
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);

        $this->globalData->setValue('order', $order);

        $this->addContent(
            $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Order\View::class
            )
        );

        $this->init();
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('View Order Details'));

        return $this->getResult();
    }
}
