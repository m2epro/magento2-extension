<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Observer\Shipment\View;

class Before extends \Ess\M2ePro\Model\Observer\AbstractModel
{
    protected $directory;
    protected $shipmentRepository;
    protected $assetRepo;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->directory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->shipmentRepository = $shipmentRepository;
        $this->assetRepo = $assetRepo;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function execute(\Magento\Framework\Event\Observer $eventObserver)
    {
        // event dispatched for ALL rendered magento blocks, so we need to skip unnecessary blocks ASAP
        if (!($eventObserver->getEvent()->getBlock() instanceof \Magento\Shipping\Block\Adminhtml\Create) &&
            !($eventObserver->getEvent()->getBlock() instanceof \Magento\Shipping\Block\Adminhtml\View)
        ) {
            return;
        }

        parent::execute($eventObserver);
    }

    //########################################

    public function process()
    {
        $block = $this->getEvent()->getBlock();

        if ($block instanceof \Magento\Shipping\Block\Adminhtml\Create) {
            $this->processNewShipment($block);
        }

        if ($block instanceof \Magento\Shipping\Block\Adminhtml\View) {
            $this->processExistShipment($block);
        }
    }

    //########################################

    private function processNewShipment(\Magento\Shipping\Block\Adminhtml\Create $block)
    {
        $orderId = $block->getRequest()->getParam('order_id');
        if (empty($orderId)) {
            return;
        }

        try {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->amazonFactory->getObjectLoaded(
                'Order', (int)$orderId, 'magento_order_id'
            );
        } catch (\Exception $exception) {
            return;
        }

        if (is_null($order) || !$order->getId()) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
        $amazonOrder = $order->getChildObject();

        if (!$amazonOrder->isEligibleForMerchantFulfillment() || $amazonOrder->isMerchantFulfillmentApplied()) {
            return;
        }

        return;
        //TODO unsupported feature
//        $themeFileName = 'prototype/windows/themes/magento.css';
//        $themeLibFileName = 'lib/'.$themeFileName;
//        $themeFileFound = false;
//        $skinBaseDir = $this->assetRepo->getSkinBaseDir(
//            array(
//                '_package' => Mage\Core\Model\Design\Package::DEFAULT_PACKAGE,
//                '_theme' => Mage\Core\Model\Design\Package::DEFAULT_THEME,
//            )
//        );
//
//        if (!$themeFileFound && is_file($skinBaseDir .'/'.$themeLibFileName)) {
//            $themeFileFound = true;
//            $block->getLayout()->getBlock('head')->addCss($themeLibFileName);
//        }
//
//        if (!$themeFileFound && is_file($this->directory->getAbsolutePath().'/js/'.$themeFileName)) {
//            $themeFileFound = true;
//            $block->getLayout()->getBlock('head')->addItem('js_css', $themeFileName);
//        }
//
//        if (!$themeFileFound) {
//            $block->getLayout()->getBlock('head')->addCss($themeLibFileName);
//            $block->getLayout()->getBlock('head')->addItem('js_css', $themeFileName);
//        }
//
//        $block->getLayout()->getBlock('head')
//            ->addJs('prototype/window.js')
//            ->addJs('M2ePro/General/CommonHandler.js')
//            ->addJs('M2ePro/General/PhpHandler.js')
//            ->addJs('M2ePro/General/TranslatorHandler.js')
//            ->addJs('M2ePro/General/UrlHandler.js')
//            ->addJs('M2ePro/Common/Amazon/Order/MerchantFulfillment/MagentoHandler.js')
//            ->addItem('js_css', 'prototype/windows/themes/default.css');
    }

    private function processExistShipment(\Magento\Shipping\Block\Adminhtml\View $block)
    {
        $shipmentId = $block->getRequest()->getParam('shipment_id');
        if (empty($shipmentId)) {
            return;
        }

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $this->shipmentRepository->get((int)$shipmentId);

        try {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->amazonFactory->getObjectLoaded(
                'Order', (int)$shipment->getOrderId(), 'magento_order_id'
            );
        } catch (\Exception $exception) {
            return;
        }

        if (is_null($order) || !$order->getId()) {
            return;
        }

        if (!$order->isMagentoShipmentCreatedByOrder($shipment)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
        $amazonOrder = $order->getChildObject();

        if (!$amazonOrder->isMerchantFulfillmentApplied() || !$amazonOrder->getData('merchant_fulfillment_label')) {
            return;
        }

        return;

        //TODO unsupported feature
//        $getLabelUrl = $block->getUrl(
//            'M2ePro/common_amazon_order_merchantFulfillment/getLabel',
//            array('order_id' => $order->getId())
//        );
//
//        $block->updateButton('print', 'onclick', 'window.open(\''.$getLabelUrl.'\')');
    }

    //########################################
}