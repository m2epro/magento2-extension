<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonListingEdit');
        $this->_controller = 'adminhtml_amazon_listing';
        $this->_mode = 'edit';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        if ($this->getRequest()->getParam('back') !== null) {
            $url = $this->dataHelper->getBackUrl(
                '*/amazon_listing/index'
            );
            $this->addButton(
                'back',
                [
                    'label' => __('Back'),
                    'onclick' => 'AmazonListingSettingsObj.backClick(\'' . $url . '\')',
                    'class' => 'back',
                ]
            );
        }

        $this->addButton(
            'auto_action',
            [
                'label' => __('Auto Add/Remove Rules'),
                'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
                'class' => 'action-primary',
            ]
        );

        $backUrl = $this->dataHelper->getBackUrlParam('list');

        $url = $this->getUrl(
            '*/amazon_listing/save',
            [
                'id' => $this->getListing()->getId(),
                'back' => $backUrl,
            ]
        );
        $saveButtonsProps = [
            'save' => [
                'label' => __('Save And Back'),
                'onclick' => 'AmazonListingSettingsObj.saveClick(\'' . $url . '\')',
                'class' => 'save primary',
            ],
        ];

        $url = $this->getUrl(
            '*/amazon_listing/save',
            [
                'id' => $this->getListing()->getId(),
            ]
        );

        $editBackUrl = $this->getUrl(
            '*/amazon_listing/edit',
            [
                'id' => $this->getListing()->getId(),
                'back' => $backUrl,
            ]
        );

        $saveButtons = [
            'id' => 'save_and_continue',
            'label' => __('Save And Continue Edit'),
            'class' => 'add',
            'button_class' => '',
            'onclick' =>
                "AmazonListingSettingsObj.saveAndEditClick('$url', '$editBackUrl')",
            'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
            'options' => $saveButtonsProps,
        ];

        $this->addButton('save_buttons', $saveButtons);
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions(
                'Amazon_Listing_AutoAction',
                ['listing_id' => $this->getListing()->getId()]
            )
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/amazon_listing_autoAction/getProductTypesList',
                [
                    'marketplace_id' => $this->getListing()->getMarketplaceId(),
                    'is_new_asin_accepted' => 1,
                ]
            ),
            'amazon_listing_autoAction/getProductTypesList'
        );

        $this->jsTranslator->addTranslations(
            [
                'Remove Category' => __('Remove Category'),
                'Add New Rule' => __('Add New Rule'),
                'Add/Edit Categories Rule' => __('Add/Edit Categories Rule'),
                'Auto Add/Remove Rules' => __('Auto Add/Remove Rules'),
                'Based on Magento Categories' => __('Based on Magento Categories'),
                'You must select at least 1 Category.' => __('You must select at least 1 Category.'),
                'Rule with the same Title already exists.' => __('Rule with the same Title already exists.'),
            ]
        );

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Amazon/Listing/AutoAction'
    ], function(){
        window.ListingAutoActionObj = new AmazonListingAutoAction();
    });
JS
        );

        return parent::_prepareLayout();
    }

    //########################################

    public function getFormHtml()
    {
        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            [
                'data' => ['listing' => $this->listing],
            ]
        );

        $sellingForm = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Selling\Form::class
        );

        return $viewHeaderBlock->toHtml() . $sellingForm->toHtml() . parent::getFormHtml();
    }

    //########################################

    protected function getListing()
    {
        if ($this->listing === null && $this->getRequest()->getParam('id')) {
            $this->listing = $this->amazonFactory->getCachedObjectLoaded(
                'Listing',
                $this->getRequest()->getParam('id')
            );
        }

        return $this->listing;
    }

    //########################################
}
