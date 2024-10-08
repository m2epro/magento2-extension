<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Marketplace\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Form extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->walmartHelper = $walmartHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->css->addFile('marketplace/form.css');
    }

    protected function _prepareForm()
    {
        $this->prepareData();

        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        foreach ($this->groups as $group) {
            $fieldset = $form->addFieldset(
                'marketplaces_group_' . $group['id'],
                ['legend' => __($group['title'])]
            );

            foreach ($group['marketplaces'] as $groupMarketplace) {
                /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
                $marketplace = $groupMarketplace['instance'];
                $afterElementHtml = '
                <div id="run_single_button_' . $marketplace->getId() . '" class="control-value"';
                $marketplace->getStatus() == \Ess\M2ePro\Model\Marketplace::STATUS_DISABLE &&
                $afterElementHtml .= ' style="display: none;"';
                $afterElementHtml .= '">';

                $onClick = $marketplace->getChildObject()
                                       ->isSupportedProductType()
                    ? 'WalmartMarketplaceWithProductTypeSyncObj.runSingleSynchronization(this)'
                    : 'MarketplaceObj.runSingleSynchronization(this)';
                $afterElementHtml .= $this->getLayout()
                                          ->createBlock(\Magento\Backend\Block\Widget\Button::class)
                                          ->setData([
                                              'label' => __('Update Now'),
                                              'onclick' => $onClick,
                                              'class' => 'run_single_button primary',
                                          ])->toHtml();

                $afterElementHtml .= <<<HTML
                </div>
                <div id="synch_info_container" class="control-value">
                    <div id="synch_info_wait_{$marketplace->getId()}"
                        class="value" style="display: none; color: gray;">&nbsp; {$this->__('Waiting')}</div>

                    <div id="synch_info_process_{$marketplace->getId()}"
                        class="value" style="display: none; color: blue;">&nbsp; {$this->__('Processing')}</div>

                    <div id="synch_info_complete_{$marketplace->getId()}"
                        class="value" style="display: none; color: green;">{$this->__('Completed')}</div>

                    <div id="synch_info_error_{$marketplace->getId()}"
                        class="value" style="display: none; color: red;">{$this->__('Error')}</div>

                    <div id="synch_info_skip_{$marketplace->getId()}"
                        class="value" style="display: none; color: gray;">{$this->__('Skipped')}</div>

                    <div id="marketplace_title_{$marketplace->getId()}"
                        class="value" style="display: none;">{$marketplace->getTitle()}</div>
                </div>
                <div id="changed_{$marketplace->getId()}" class="changed control-value"
                    style="display: none;">
                </div>
HTML;

                $selectData = [
                    'label' => __($marketplace->getData('title')),
                    'style' => 'display: inline-block;',
                    'after_element_html' => $afterElementHtml,
                ];

                if ($groupMarketplace['params']['locked']) {
                    $selectData['disabled'] = 'disabled';
                    $selectData['values'] = [
                        \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE => __('Enabled') . ' - ' .
                            __('Used in Account(s)'),
                    ];
                    $selectData['value'] = \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE;
                } else {
                    $selectData['values'] = [
                        \Ess\M2ePro\Model\Marketplace::STATUS_DISABLE => __('Disabled'),
                        \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE => __('Enabled'),
                    ];
                    $selectData['value'] = $marketplace->getStatus();
                }

                $selectData['name'] = 'status_' . $marketplace->getId();
                $selectData['class'] = 'marketplace_status_select';
                $selectData['note'] = $marketplace->getUrl();

                $fieldset->addField(
                    'status_' . $marketplace->getId(),
                    self::SELECT,
                    $selectData
                )->addCustomAttribute('marketplace_id', $marketplace->getId())
                         ->addCustomAttribute('component_name', \Ess\M2ePro\Helper\Component\Walmart::NICK)
                         ->addCustomAttribute('component_title', $this->walmartHelper->getTitle())
                         ->addCustomAttribute('onchange', 'MarketplaceObj.changeStatus(this);');
            }
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    protected function prepareData()
    {
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Marketplace[] $tempMarketplaces */
        $tempMarketplaces = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Walmart::NICK, 'Marketplace')
                                                ->getCollection()
                                                ->setOrder('group_title', 'ASC')
                                                ->setOrder('sorder', 'ASC')
                                                ->setOrder('title', 'ASC')
                                                ->getItems();

        $storedStatuses = [];
        $groups = [];
        $idGroup = 1;

        $groupOrder = [
            'america' => 'America',
        ];

        foreach ($groupOrder as $key => $groupOrderTitle) {
            $groups[$key] = [
                'id' => $idGroup++,
                'title' => $groupOrderTitle,
                'marketplaces' => [],
            ];

            /** @var \Ess\M2ePro\Model\Marketplace $tempMarketplace */
            foreach ($tempMarketplaces as $tempMarketplace) {
                if ($groupOrderTitle != $tempMarketplace->getGroupTitle()) {
                    continue;
                }

                $isLocked = (bool)$this->parentFactory
                    ->getObject(\Ess\M2ePro\Helper\Component\Walmart::NICK, 'Account')->getCollection()
                    ->addFieldToFilter('marketplace_id', $tempMarketplace->getId())
                    ->getSize();

                $storedStatuses[] = [
                    'marketplace_id' => $tempMarketplace->getId(),
                    'status' => $tempMarketplace->getStatus(),
                    'is_need_sync_after_save' => !$tempMarketplace->getChildObject()
                                                                  ->isSupportedProductType(),
                ];

                $marketplace = [
                    'instance' => $tempMarketplace,
                    'params' => ['locked' => $isLocked],
                ];

                $groups[$key]['marketplaces'][] = $marketplace;
            }
        }

        $this->groups = $groups;
        $this->storedStatuses = $storedStatuses;
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl('m2epro/walmart_marketplace/save'),
            'logViewUrl' => $this->getUrl(
                '*/walmart_synchronization_log/index',
                ['back' => $this->dataHelper->makeBackUrlParam('*/walmart_synchronization/index')]
            ),
            'runSynchNow' => $this->getUrl('*/walmart_marketplace/runSynchNow'),
            'walmart_marketplace_withProductType/runSynchNow' => $this->getUrl(
                '*/walmart_marketplace_withProductType/runSynchNow'
            ),
            'walmart_marketplace_withProductType/synchGetExecutingInfo' => $this->getUrl(
                '*/walmart_marketplace_withProductType/synchGetExecutingInfo'
            ),
        ]);

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart\Marketplace'));

        $syncLogUrl = $this->getUrl('*/walmart_synchronization_log/index');
        $this->jsTranslator->addTranslations([
            'marketplace_sync_success_message' => (string)__('Marketplace synchronization was completed.'),
            'marketplace_sync_error_message' => (string)__(
                'Marketplace synchronization was completed with errors.'
                . ' <a target="_blank" href="%url">View Log</a> for the details.',
                ['url' => $syncLogUrl]
            ),
            'marketplace_sync_warning_message' => (string)__(
                'Marketplace synchronization was completed with warnings.'
                . ' <a target="_blank" href="%url">View Log</a> for the details.',
                ['url' => $syncLogUrl]
            ),
        ]);

        $storedStatuses = \Ess\M2ePro\Helper\Json::encode($this->storedStatuses);
        $this->js->addOnReadyJs(
            <<<JS
            require([
                'M2ePro/Walmart/Marketplace',
                'M2ePro/SynchProgress',
                'M2ePro/Walmart/Marketplace/WithProductType/Sync',
                'M2ePro/Walmart/Marketplace/WithProductType/SyncProgress',
                'M2ePro/Plugin/ProgressBar',
                'M2ePro/Plugin/AreaWrapper'
            ], function() {
                window.MarketplaceProgressObj = new SynchProgress(
                    new ProgressBar('marketplaces_progress_bar'),
                    new AreaWrapper('marketplaces_content_container')
                );
                window.MarketplaceObj = new WalmartMarketplace(MarketplaceProgressObj, $storedStatuses);

                const walmartMarketplaceWithProductTypeSyncProgress = new WalmartMarketplaceWithProductTypeSyncProgress(
                    new ProgressBar('marketplaces_progress_bar'),
                    new AreaWrapper('marketplaces_content_container')
                );
                window.WalmartMarketplaceWithProductTypeSyncObj = new WalmartMarketplaceWithProductTypeSync(
                    walmartMarketplaceWithProductTypeSyncProgress,
                    $storedStatuses
                );
            });
JS
        );

        return parent::_toHtml();
    }
}
