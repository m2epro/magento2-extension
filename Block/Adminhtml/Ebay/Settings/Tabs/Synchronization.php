<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs;

use Magento\Framework\Message\MessageInterface;

class Synchronization extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    //########################################

    protected function _prepareForm()
    {
        $synchronizationConfig = $this->modelFactory->getObject('Config\Manager\Synchronization');

        // ---------------------------------------
        $listingsMode = $synchronizationConfig->getGroupValue('/ebay/templates/', 'mode');
        $ordersMode = $synchronizationConfig->getGroupValue('/ebay/orders/', 'mode');
        $otherListingsMode = $synchronizationConfig->getGroupValue('/ebay/other_listings/', 'mode');
        // ---------------------------------------

        // ---------------------------------------

        $this->reviseAllStartDate = $synchronizationConfig->getGroupValue(
            '/ebay/templates/synchronization/revise/total/', 'start_date'
        );
        $this->reviseAllStartDate && $this->reviseAllStartDate = $this->templateContext->_localeDate
            ->formatDate($this->reviseAllStartDate, \IntlDateFormatter::MEDIUM);

        $this->reviseAllEndDate = $synchronizationConfig->getGroupValue(
            '/ebay/templates/synchronization/revise/total/', 'end_date'
        );
        $this->reviseAllEndDate && $this->reviseAllEndDate = $this->templateContext->_localeDate
            ->formatDate($this->reviseAllEndDate, \IntlDateFormatter::MEDIUM);
        // ---------------------------------------

        // ---------------------------------------
        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;
        $data = array(
            'class'   => 'ok_button',
            'label'   => $this->__('Confirm'),
            'onclick' => "ReviseAllConfirmPopup.closeModal(); SynchronizationObj.runReviseAll('{$component}');",
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('revise_all_confirm_popup_ok_button', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $this->inspectorMode = (int)$synchronizationConfig->getGroupValue(
            '/global/magento_products/inspector/','mode'
        );
        // ---------------------------------------

        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $fieldset = $form->addFieldset('ebay_synchronization_templates',
            [
                'legend' => $this->__('M2E Pro Listings Synchronization'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField('templates_mode',
            self::SELECT,
            [
                'name'        => 'templates_mode',
                'label'       => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => $listingsMode,
                'tooltip' => $this->__(
                    '<p>This synchronization includes import of changes made on eBay channel as well as the ability
                     to enable/disable the data synchronization managed by the Synchronization Policy Rules.</p><br>
                     <p>However, it does not exclude the ability to manually manage
                     Items in Listings using the available List, Revise, Relist or Stop Action options.</p>'
                )
            ]
        );

        if ($this->isShowReviseAll()) {
            $fieldset->addField('block_notice_ebay_synchronization_revise_all',
                self::MESSAGES,
                [
                    'messages' => [
                        [
                            'type' => MessageInterface::TYPE_NOTICE,
                            'content' => $this->__(
                                    'If your eBay Listings for some reason were asynchronized with the Products in
                                 Magento, <a href="javascript:" onclick="%script_code%">turn on</a> the Revise All
                                 Action to catch data up.
                                 <br/>Revise is performed by the Inventory Synchronization, 100 Items per a cycle.
                                 <br/><br/>',
                                    'SynchronizationObj.showReviseAllConfirmPopup(\''.
                                    \Ess\M2ePro\Helper\Component\Ebay::NICK.'\');') .
                                '<span id="ebay_revise_all_start" style="display: none">

                                        <span style="color: blue">
                                            '. $this->__('In Progress, start date - ') .'
                                        </span>

                                        <span id="ebay_revise_all_start_date" style="color: blue">
                                            '. $this->reviseAllStartDate .'
                                        </span>

                                    </span>

                                    <span id="ebay_revise_all_end" style="display: none">

                                        <span style="color: green">
                                            '. $this->__('Finished, end date - ') .'
                                        </span>

                                        <span id="ebay_revise_all_end_date" style="color: green">
                                            '. $this->reviseAllEndDate .'
                                        </span>
                                    </span>'
                        ]
                    ]
                ]
            );
        }

        $fieldset = $form->addFieldset('ebay_synchronization_orders',
            [
                'legend' => $this->__('Orders Synchronization'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField('templates_orders_mode',
            self::SELECT,
            [
                'name'        => 'templates_orders_mode',
                'label'       => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => $ordersMode,
                'disabled' => true,
                'tooltip' => $this->__(
                    '<p>This Synchronization cannot be disabled as it is a critically important
                    condition for the proper work of the automatic synchronization rules according
                    to which data update between Magento and eBay is performed.</p>
                    <p>However, there is an ability to enable/disable Magento Order creation for each Account in
                    <strong>eBay Integration > Configuration > Accounts</strong> section.</p>'
                )
            ]
        );

        $fieldset = $form->addFieldset('ebay_synchronization_other_listings',
            [
                'legend' => $this->__('3rd Party Synchronization '),
                'collapsable' => false,
            ]
        );

        $fieldset->addField('templates_other_listings_mode',
            self::SELECT,
            [
                'name'        => 'templates_other_listings_mode',
                'label'       => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => $otherListingsMode,
                'disabled' => true,
                'tooltip' => $this->__(
                    '<p>This Synchronization performs import and regular updates of the imported
                    3rd Party Listings. It can be enabled/disabled for each Account separately in
                    <strong>eBay Integration > Configuration > Accounts</strong> section.</p>'
                )
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        parent::_prepareForm();
    }

    protected function _toHtml()
    {
        $reviseAllInProcessingState = $this->modelFactory->getObject('Config\Manager\Synchronization')->getGroupValue(
            '/ebay/templates/synchronization/revise/total/', 'last_listing_product_id'
        );

        $js = "require([
                'M2ePro/Plugin/ProgressBar',
                'M2ePro/Plugin/AreaWrapper',
                'M2ePro/SynchProgress',
                'M2ePro/Synchronization'
            ], function() {

            window.SynchProgressBarObj = new ProgressBar('synchronization_progress_bar');
            window.SynchWrapperObj = new AreaWrapper('synchronization_content_container');

            window.SynchronizationProgressObj = new SynchProgress(SynchProgressBarObj, SynchWrapperObj );
            window.SynchronizationObj = new Synchronization(SynchronizationProgressObj);";

        if ($this->isShowReviseAll()) {
            $js .=
                'SynchronizationObj.initReviseAllInfo(' .
                $this->getHelper('Data')->jsonEncode(!is_null($reviseAllInProcessingState)) . ',\'' .
                $this->reviseAllStartDate . '\',\'' .
                $this->reviseAllEndDate . '\',\'' .
                \Ess\M2ePro\Helper\Component\Ebay::NICK .'\'
                );';
        }

        $js .= '})';

        $this->js->addOnReadyJs($js);

        $this->jsTranslator->addTranslations(
            [
                'Synchronization Settings have been saved.' => 'Synchronization Settings have been saved.',
                'Running All Enabled Tasks' => 'Running All Enabled Tasks',
                'Another Synchronization Is Already Running.' => 'Another Synchronization Is Already Running.',
                'Getting information. Please wait ...' => 'Getting information. Please wait ...',
                'Preparing to start. Please wait ...' => 'Preparing to start. Please wait ...',
                'Synchronization has successfully ended.' => 'Synchronization has successfully ended.',
                'Synchronization ended with warnings. <a target="_blank" href="%url%">View Log</a> for details.' =>
                    'Synchronization ended with warnings. <a target="_blank" href="%url%">View Log</a> for details.',
                'Synchronization ended with errors. <a target="_blank" href="%url%">View Log</a> for details.' =>
                    'Synchronization ended with errors. <a target="_blank" href="%url%">View Log</a> for details.',
                'Revise All' => 'Revise All'
            ]
        );

        $this->jsUrl->addUrls([
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_SYNCHRONIZATION => $this->getUrl(
                '*/ebay_synchronization/save'
            ),
            'synch_formSubmit' => $this->getUrl('*/ebay_synchronization/save'),
            'logViewUrl' => $this->getUrl('*/ebay_synchronization_log/index',array('back'=>$this->getHelper('Data')
                ->makeBackUrlParam('*/ebay_synchronization/index'))),

            'runReviseAll'        => $this->getUrl('*/ebay_synchronization/runReviseAll'),
            'runAllEnabledNow'    => $this->getUrl('*/ebay_synchronization/runAllEnabledNow'),

            'synchCheckProcessingNow' => $this->getUrl('*/ebay_synchronization/synchCheckProcessingNow')
        ]);

        return '<div id="synchronization_progress_bar"></div>
            <div id="synchronization_content_container">'.parent::_toHtml().'
            </div>
            <div id="ebay_revise_all_confirm_popup" style="display: none;">
                <div style="margin: 17px 0">'.
        $this->__(
            'Click \'Confirm\' and Revise will be performed by the Inventory Synchronization, 100
                         Items per a cycle.'
        ) .'
            </div>

            <div style="padding-bottom: 20px; text-align: right">
                <a onclick="ReviseAllConfirmPopup.closeModal();">'. $this->__('Cancel') .'</a>
                &nbsp;&nbsp;&nbsp;&nbsp;'.
                $this->getChildHtml('revise_all_confirm_popup_ok_button') .'
            </div>

            </div>';
    }

    protected function isShowReviseAll()
    {
        return (bool)$this->getHelper('Module')->getConfig()->getGroupValue(
            '/view/synchronization/revise_total/','show'
        );
    }

    //########################################

    protected function getGlobalNotice()
    {
        return '';
    }

    //########################################
}