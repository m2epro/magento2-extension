<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

class Tabs extends AbstractTabs
{
    protected function _construct()
    {
        parent::_construct();

        $this->setId('ebayAccountEditTabs');
        $this->setDestElementId('edit_form');
    }

    protected function _beforeToHtml()
    {
        $this->addTab(
            'general',
            [
                'label' => __('General'),
                'title' => __('General'),
                'content' => $this->createBlock(
                    'Ebay\Account\Edit\Tabs\General'
                )->toHtml()
            ]
        );

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        if ($account && $account->getId()) {

            $this->addTab('listingOther', array(
                'label' => $this->__('3rd Party Listings'),
                'title' => $this->__('3rd Party Listings'),
                'content' => $this->createBlock(
                    'Ebay\Account\Edit\Tabs\ListingOther'
                )->toHtml()
            ));

            $this->addTab('store', array(
                'label'   => $this->__('eBay Store'),
                'title'   => $this->__('eBay Store'),
                'content' => $this->createBlock(
                    'Ebay\Account\Edit\Tabs\Store'
                )->toHtml()
            ));

            $this->addTab('order', array(
                'label'   => $this->__('Orders'),
                'title'   => $this->__('Orders'),
                'content' => $this->createBlock(
                    'Ebay\Account\Edit\Tabs\Order'
                )->toHtml()
            ));

            $this->addTab('feedback', array(
                'label' => $this->__('Feedback'),
                'title' => $this->__('Feedback'),
                'content' => $this->createBlock(
                    'Ebay\Account\Edit\Tabs\Feedback'
                )->toHtml()
            ));

            if ($this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {

                $this->addTab('my_stores', array(
                    'label' => $this->__('In-Store Pickup'),
                    'title' => $this->__('In-Store Pickup'),
                    'content' => $this->createBlock(
                        'Ebay\Account\Edit\Tabs\PickupStore'
                    )->toHtml()
                ));
            }
        }

        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Account'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Account\Feedback'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Account\Feedback\Template', [
            'account_id' => $this->getRequest()->getParam('id')
        ]));
        $this->jsUrl->add(
            $this->getUrl('*/ebay_account/beforeGetToken',array('_current' => true)), 'ebay_account/beforeGetToken'
        );
        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/ebay_account/save', array('_current' => true, 'id' => $this->getRequest()->getParam('id'))
            ),
            'deleteAction' => $this->getUrl('*/ebay_account/delete', array('id' => $this->getRequest()->getParam('id')))
        ]);

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay'));
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Account'));

        $this->jsTranslator->addTranslations([
            'The specified Title is already used for other Account. Account Title must be unique.' => $this->__(
                'The specified Title is already used for other Account. Account Title must be unique.'
            ),
            'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
            . 'This will cause inappropriate work of all Accounts\' copies.' => $this->__(
                'Be attentive! By Deleting Account you delete all information on it from M2E Pro Server. '
                . 'This will cause inappropriate work of all Accounts\' copies.'
            ),
            'No Customer entry is found for specified ID.' => $this->__('No Customer entry is found for specified ID.'),
            'If Yes is chosen, you must select at least one Attribute for Product Mapping.' => $this->__(
                'If Yes is chosen, you must select at least one Attribute for Product Mapping.'
            ),
            'You should create at least one Response Template.' => $this->__(
                'You should create at least one Response Template.'
            ),
            'Prefix length should not be greater than 5 characters.' => $this->__(
                'Prefix length should not be greater than 5 characters.'
            ),
            'You must get token.' => $this->__('You must get token.')
        ]);

        return parent::_beforeToHtml();
    }
}