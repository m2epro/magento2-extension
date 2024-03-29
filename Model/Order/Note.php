<?php

namespace Ess\M2ePro\Model\Order;

class Note extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Order */
    private $order = null;

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Order\Note::class);
    }

    public function getNote()
    {
        return $this->getData('note');
    }

    public function setNote(string $note)
    {
        $this->setData('note', $note);
    }

    public function getOrderId()
    {
        return $this->getData('order_id');
    }

    public function setOrderId(int $orderId)
    {
        $this->setData('order_id', $orderId);
    }

    public function afterDelete()
    {
        $component = $this->getOrder()->getComponentMode();

        if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $component = $this->getHelper('Component\Ebay')->getTitle();
        } else {
            $component = ucfirst($component);
        }

        $comment = $this->getHelper('Module\Translation')->__(
            'Custom Note for the corresponding %component% order was deleted.',
            $component
        );

        $this->updateMagentoOrderComments($comment);

        return parent::afterDelete();
    }

    public function afterSave()
    {
        $component = $this->getOrder()->getComponentMode();

        if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $component = $this->getHelper('Component\Ebay')->getTitle();
        } else {
            $component = ucfirst($component);
        }

        $comment = $this->getHelper('Module\Translation')->__(
            'Custom Note was added to the corresponding %component% order: %text%.',
            $component,
            $this->getData('note')
        );

        if ($this->getOrigData('id') !== null) {
            $comment = $this->getHelper('Module\Translation')->__(
                'Custom Note for the corresponding %component% order was updated: %text%.',
                $component,
                $this->getData('note')
            );
        }

        $this->updateMagentoOrderComments($comment);

        return parent::afterSave();
    }

    protected function updateMagentoOrderComments($comment)
    {
        $magentoOrderModel = $this->getOrder()->getMagentoOrder();

        if ($magentoOrderModel !== null) {
            /** @var \Ess\M2ePro\Model\Magento\Order\Updater $orderUpdater */
            $orderUpdater = $this->modelFactory->getObject('Magento_Order_Updater');

            $orderUpdater->setMagentoOrder($magentoOrderModel);
            $orderUpdater->updateComments($comment);
            $orderUpdater->finishUpdate();
        }
    }

    public function getOrder()
    {
        if ($this->order === null) {
            $this->order = $this->activeRecordFactory->getObjectLoaded('Order', $this->getOrderId());
        }

        return $this->order;
    }
}
