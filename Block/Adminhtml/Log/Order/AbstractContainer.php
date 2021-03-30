<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Order;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Order\AbstractContainer
 */
abstract class AbstractContainer extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    //#######################################

    abstract protected function getComponentMode();

    //#######################################

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_' . $this->getComponentMode() . '_log_order';

        $this->setId(ucfirst($this->getComponentMode()) . 'OrderLog');

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
    }

    //########################################

    protected function _toHtml()
    {
        $filtersHtml = $this->getFiltersHtml();

        if (empty($filtersHtml)) {
            return parent::_toHtml();
        }

        $filtersHtml = <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$filtersHtml}
    </div>
</div>
HTML;

        return $filtersHtml . parent::_toHtml();
    }

    protected function getFiltersHtml()
    {
        $accountSwitcherBlock = $this->createAccountSwitcherBlock();
        $marketplaceSwitcherBlock = $this->createMarketplaceSwitcherBlock();
        $uniqueMessageFilterBlock = $this->createUniqueMessageFilterBlock();

        $orderId = $this->getRequest()->getParam('id', false);

        if ($orderId) {

            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $orderId);

            $accountTitle = $this->filterManager->truncate(
                $order->getAccount()->getTitle(),
                ['length' => 15]
            );

            return
                $this->getStaticFilterHtml(
                    $accountSwitcherBlock->getLabel(),
                    $accountTitle
                )
                . $this->getStaticFilterHtml(
                    $marketplaceSwitcherBlock->getLabel(),
                    $order->getMarketplace()->getTitle()
                );
        }

        if ($marketplaceSwitcherBlock->isEmpty() && $accountSwitcherBlock->isEmpty()) {
            return $uniqueMessageFilterBlock->toHtml();
        }

        return $accountSwitcherBlock->toHtml()
            . $marketplaceSwitcherBlock->toHtml()
            . $uniqueMessageFilterBlock->toHtml();
    }

    protected function getStaticFilterHtml($label, $value)
    {
        return <<<HTML
<p class="static-switcher">
    <span>{$label}:</span>
    <span>{$value}</span>
</p>
HTML;
    }

    protected function createAccountSwitcherBlock()
    {
        return $this->createBlock('Account\Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createMarketplaceSwitcherBlock()
    {
        return $this->createBlock('Marketplace\Switcher')->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createUniqueMessageFilterBlock()
    {
        return $this->createBlock('Log\UniqueMessageFilter')->setData([
            'route' => "*/{$this->getComponentMode()}_log_order/",
            'title' => $this->__('Only messages with a unique Order ID')
        ]);
    }

    //########################################
}
