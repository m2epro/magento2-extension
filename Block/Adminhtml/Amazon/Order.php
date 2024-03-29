<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class Order extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory */
    private $accountCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountCollection */
    private $accountCollection;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->accountCollectionFactory = $accountCollectionFactory;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_amazon_order';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->addOrderSettingButton();

        $this->addButton(
            'upload_by_user',
            [
                'label' => __('Order Reimport'),
                'onclick' => 'UploadByUserObj.openPopup()',
                'class' => 'action-primary',
            ]
        );
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => __(
                <<<HTML
                <p>In this section, you can find the list of the Orders imported from Amazon.</p><br>

                <p>An Amazon Order, for which Magento Order is created, contains a value in
                <strong>Magento Order </strong>column of the grid. You can find the corresponding Magento Order
                in Sales > Orders section of your Magento.</p><br>
                <p>To manage the imported Amazon Orders, you can use Mass Action options available in the
                Actions bulk: Reserve QTY, Cancel QTY Reserve, Mark Order(s) as Shipped
                and Resend Shipping Information.</p><br>
                <p>Also, you can view the detailed Order information by clicking on the appropriate
                row of the grid.</p>
                <p><strong>Note:</strong> Automatic creation of Magento Orders, Invoices, and Shipments is
                performed in accordance with the Order settings specified in <br>
                <strong>Account Settings (Amazon Integration > Configuration > Accounts)</strong>.</p>
HTML
            ),
        ]);

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\Item\Edit::class)->toHtml() .
            parent::getGridHtml();
    }

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Controller\Adminhtml\Order\EditItem::class)
        );

        $this->js->addRequireJs(
            ['upload' => 'M2ePro/Order/UploadByUser'],
            <<<JS
UploadByUserObj = new UploadByUser('amazon', 'orderUploadByUserPopupGrid');
JS
        );

        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Order_UploadByUser')
        );

        $this->jsTranslator->addTranslations(
            [
                'Order Reimport' => __('Order Reimport'),
                'Order importing in progress.' => __('Order importing in progress.'),
                'Order importing is canceled.' => __('Order importing is canceled.'),
            ]
        );

        return parent::_beforeToHtml();
    }

    private function addOrderSettingButton(): void
    {
        $accountId = $this->getAmazonAccountId();
        $url = $accountId ? $this->getSettingButtonUrl($accountId) : '';
        $classAttribute = $accountId ? 'action-primary' : 'drop_down edit_default_settings_drop_down primary';
        $className = !$accountId ? \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class : null;

        $this->addButton(
            'order_settings',
            [
                'label' => __('Order Settings'),
                'onclick' => $url,
                'class' => $classAttribute,
                'class_name' => $className,
                'options' => $this->getAccountSettingsDropDownItems($accountId)
            ]
        );
    }

    private function getAmazonAccountId(): int
    {
        return $this->getAccountIdFromRequest() ?: $this->getAccountIdFromCollection();
    }

    private function getAccountIdFromRequest(): int
    {
        return (int)$this->getRequest()->getParam('amazonAccount');
    }

    private function getAccountIdFromCollection(): int
    {
        $accountCollection = $this->getAccountCollection();

        return $accountCollection->getSize() < 2 ? (int)$accountCollection->getFirstItem()->getId() : 0;
    }

    private function getAccountCollection(): \Ess\M2ePro\Model\ResourceModel\Account\Collection
    {
        if (!$this->accountCollection) {
            $this->accountCollection = $this->accountCollectionFactory->create();
            $this->accountCollection->addFieldToFilter(
                'component_mode',
                \Ess\M2ePro\Helper\View\Amazon::NICK
            );
        }

        return $this->accountCollection;
    }

    private function getSettingButtonUrl(int $accountId): string
    {
        $url = $this->getUrl('*/amazon_account/edit', ['id' => $accountId, 'tab' => 'orders']);

        return sprintf("window.open('%s', '_blank')", $url);
    }

    private function getAccountSettingsDropDownItems(int $accountId): array
    {
        $dropDownItems = [];

        if (!$accountId) {
            foreach ($this->getAccountCollection() as $accountItem) {
                $accountTitle = $this->filterManager->truncate(
                    $accountItem->getTitle(),
                    ['length' => 15]
                );

                $dropDownItems[] = [
                    'label' => __($accountTitle),
                    'onclick' => $this->getSettingButtonUrl((int)$accountItem->getId())
                ];
            }
        }

        return $dropDownItems;
    }
}
