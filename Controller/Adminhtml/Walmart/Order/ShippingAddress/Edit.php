<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Order\ShippingAddress;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

class Edit extends Order
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->globalData = $globalData;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->walmartFactory->getObjectLoaded('Order', (int)$id);
        $this->globalData->setValue('order', $order);

        $form = $this->getLayout()
                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Order\Edit\ShippingAddress\Form::class);

        $this->setAjaxContent($form->toHtml());
        return $this->getResult();
    }
}
