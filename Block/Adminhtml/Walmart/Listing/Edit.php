<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartListingEdit');
        $this->_controller = 'adminhtml_walmart_listing';
        $this->_mode = 'edit';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        if ($this->getRequest()->getParam('back') !== null) {
            $url = $this->getHelper('Data')->getBackUrl(
                '*/walmart_listing/index'
            );
            $this->addButton(
                'back',
                [
                    'label'   => $this->__('Back'),
                    'onclick' => 'WalmartListingSettingsObj.backClick(\'' . $url . '\')',
                    'class'   => 'back'
                ]
            );
        }

        $this->addButton(
            'auto_action',
            [
                'label'   => $this->__('Auto Add/Remove Rules'),
                'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
                'class'   => 'action-primary'
            ]
        );

        $backUrl = $this->getHelper('Data')->getBackUrlParam('list');

        $url = $this->getUrl(
            '*/walmart_listing/save',
            [
                'id'   => $this->getListing()->getId(),
                'back' => $backUrl
            ]
        );
        $saveButtonsProps = [
            'save' => [
                'label'   => $this->__('Save And Back'),
                'onclick' => 'WalmartListingSettingsObj.saveClick(\'' . $url . '\')',
                'class'   => 'save primary'
            ]
        ];

        $editBackUrl = $this->getHelper('Data')->makeBackUrlParam(
            $this->getUrl(
                '*/walmart_listing/edit',
                [
                    'id'   => $this->listing['id'],
                    'back' => $backUrl
                ]
            )
        );
        $url = $this->getUrl(
            '*/walmart_listing/save',
            [
                'id'   => $this->listing['id'],
                'back' => $editBackUrl
            ]
        );
        $saveButtons = [
            'id'           => 'save_and_continue',
            'label'        => $this->__('Save And Continue Edit'),
            'class'        => 'add',
            'button_class' => '',
            'onclick'      => 'WalmartListingSettingsObj.saveAndEditClick(\'' . $url . '\', 1)',
            'class_name'   => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
            'options'      => $saveButtonsProps
        ];

        $this->addButton('save_buttons', $saveButtons);
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions(
                'Walmart_Listing_AutoAction',
                ['listing_id' => $this->getListing()->getId()]
            )
        );

        $path = 'walmart_listing_autoAction/getCategoryTemplatesList';
        $this->jsUrl->add(
            $this->getUrl(
                '*/' . $path,
                [
                    'marketplace_id' => $this->getListing()->getMarketplaceId()
                ]
            ),
            $path
        );

        $this->jsTranslator->addTranslations(
            [
                'Remove Category'                          => $this->__('Remove Category'),
                'Add New Rule'                             => $this->__('Add New Rule'),
                'Add/Edit Categories Rule'                 => $this->__('Add/Edit Categories Rule'),
                'Auto Add/Remove Rules'                    => $this->__('Auto Add/Remove Rules'),
                'Based on Magento Categories'              => $this->__('Based on Magento Categories'),
                'You must select at least 1 Category.'     => $this->__('You must select at least 1 Category.'),
                'Rule with the same Title already exists.' => $this->__('Rule with the same Title already exists.')
            ]
        );

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Walmart/Listing/AutoAction'
    ], function(){
        window.ListingAutoActionObj = new WalmartListingAutoAction();
    });
JS
        );

        return parent::_prepareLayout();
    }

    //########################################

    public function getFormHtml()
    {
        $viewHeaderBlock = $this->createBlock(
            'Listing_View_Header',
            '',
            [
                'data' => ['listing' => $this->getListing()]
            ]
        );

        return $viewHeaderBlock->toHtml() . parent::getFormHtml();
    }

    //########################################

    protected function getListing()
    {
        if ($this->listing === null && $this->getRequest()->getParam('id')) {
            $this->listing = $this->walmartFactory->getCachedObjectLoaded(
                'Listing',
                $this->getRequest()->getParam('id')
            );
        }

        return $this->listing;
    }

    //########################################
}
