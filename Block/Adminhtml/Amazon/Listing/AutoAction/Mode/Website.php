<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode;

class Website extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Website
{
    public $showCreateNewAsin = 0;

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField('auto_mode_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    '<p>These Rules of automatic product adding and removal come into action when a
                    Magento Product is added to the Website with regard to the Store View selected for the
                    M2E Pro Listing. In other words, after a Magento Product is added to the selected Website,
                    it can be automatically added to M2E Pro Listing if the settings are enabled.</p><br>
                    <p>Accordingly, if a Magento Product present in the M2E Pro Listing is removed
                    from the Website, the Item will be removed from the Listing and its
                    sale will be stopped on Channel.</p><br>
                    <p>More detailed information you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/lAYtAQ')
                )
            ]
        );

        $form->addField('auto_mode', 'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE
            ]
        );

        $fieldSet = $form->addFieldset('auto_website_fieldset_container', []);

        $fieldSet->addField('auto_website_adding_mode',
            self::SELECT,
            [
                'name' => 'auto_website_adding_mode',
                'label' => $this->__('Product Added to Website'),
                'title' => $this->__('Product Added to Website'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE, 'label' => $this->__('No Action')],
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD, 'label' => $this->__('Add to the Listing')],
                ],
                'value' => $this->formData['auto_website_adding_mode'],
                'tooltip' => $this->__('Action which will be applied automatically.'),
                'style' => 'width: 350px'
            ]
        );

        $fieldSet->addField('auto_website_adding_add_not_visible',
            self::SELECT,
            [
                'name' => 'auto_website_adding_add_not_visible',
                'label' => $this->__('Add not Visible Individually Products'),
                'title' => $this->__('Add not Visible Individually Products'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO, 'label' => $this->__('No')],
                    [
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
                        'label' => $this->__('Yes')
                    ],
                ],
                'value' => $this->formData['auto_website_adding_add_not_visible'],
                'field_extra_attributes' => 'id="auto_website_adding_add_not_visible_field"',
                'tooltip' => $this->__(
                    'Set to <strong>Yes</strong> if you want the Magento Products with
                    Visibility \'Not visible Individually\' to be added to the Listing
                    Automatically.<br/>
                    If set to <strong>No</strong>, only Variation (i.e.
                    Parent) Magento Products will be added to the Listing Automatically,
                    excluding Child Products.'
                )
            ]
        );

        $fieldSet->addField('auto_action_create_asin',
            self::SELECT,
            [
                'name' => 'auto_action_create_asin',
                'label' => $this->__('Create New ASIN / ISBN if not found'),
                'title' => $this->__('Create New ASIN / ISBN if not found'),
                'values' => [
                    [
                        'value' => \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_NO,
                        'label' => $this->__('No')
                    ],
                    [
                        'value' => \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_YES,
                        'label' => $this->__('Yes')
                    ],
                ],
                'value' => (int)!empty($this->formData['auto_website_adding_description_template_id']),
                'field_extra_attributes' => 'id="auto_action_amazon_add_and_create_asin"',
                'tooltip' => $this->__(
                    'Should M2E Pro try to create new ASIN/ISBN in case Search
                    Settings are not set or contain the incorrect values?'
                )
            ]
        );

        $collection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Template\Description'
        )->getCollection();
        $collection->addFieldToFilter('marketplace_id', $this->getListing()->getMarketplaceId());

        $descriptionTemplates = $collection->getData();

        if (count($descriptionTemplates) > 0) {
            $this->showCreateNewAsin = 1;
        }

        usort($descriptionTemplates, function($a, $b) {
            return $a["is_new_asin_accepted"] < $b["is_new_asin_accepted"];
        });

        $options = [['label' => '','value' => '', 'attrs' => ['class' => 'empty']]];
        foreach($descriptionTemplates as $template) {
            $tmp = [
                'label' => $this->escapeHtml($template['title']),
                'value' => $template['id']
            ];

            if (!$template['is_new_asin_accepted']) {
                $tmp['attrs'] = ['disabled' => 'disabled'];
            }

            $options[] = $tmp;
        }

        $url = $this->getUrl('*/amazon_template_description/new', array(
            'is_new_asin_accepted'  => 1,
            'marketplace_id'        => $this->getListing()->getMarketplaceId(),
            'close_on_save' => true
        ));

        $fieldSet->addField('adding_description_template_id',
            self::SELECT,
            [
                'name' => 'adding_description_template_id',
                'label' => $this->__('Description Policy'),
                'title' => $this->__('Description Policy'),
                'values' => $options,
                'value' => $this->formData['auto_website_adding_description_template_id'],
                'field_extra_attributes' => 'id="auto_action_amazon_add_and_assign_description_template"',
                'required' => true,
                'after_element_html' => $this->getTooltipHtml($this->__(
                    'Creation of new ASIN/ISBN will be performed based on specified Description Policy.
                    Only the Description Policies set for new ASIN/ISBN creation are available for choosing.
                    <br/><br/><b>Note:</b> If chosen Description Policy doesn’t meet all the
                    Conditions for new ASIN/ISBN creation, the Products will still be added to M2E Pro Listings
                    but will not be Listed on Amazon.'
                                    )) . '<a href="javascript: void(0);"
                                            style="vertical-align: inherit; margin-left: 65px;"
                                            onclick="ListingAutoActionObj.addNewTemplate(\''.$url.'\',
                                            ListingAutoActionObj.reloadDescriptionTemplates);">'.$this->__('Add New').'
                                         </a>'
            ]
        );

        $fieldSet->addField('auto_website_deleting_mode',
            self::SELECT,
            [
                'name' => 'auto_website_deleting_mode',
                'label' => $this->__('Product Deleted from Website'),
                'title' => $this->__('Product Deleted from Website'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
                        'label' => $this->__('No Action')],
                    ['value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP,
                        'label' => $this->__('Stop on Channel')],
                    ['value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
                        'label' => $this->__('Stop on Channel and Delete from Listing')],
                ],
                'value' => $this->formData['auto_website_deleting_mode'],
                'style' => 'width: 350px'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Listing')
        );

        $this->js->add(<<<JS

        ListingAutoActionObj.showCreateNewAsin = {$this->showCreateNewAsin};

        $('auto_action_create_asin')
            .observe('change', ListingAutoActionObj.createAsinChange)
            .simulate('change');

        $('adding_description_template_id').observe('change', function(el) {
            var options = $(el.target).select('.empty');
            options.length > 0 && options[0].hide();
        });
JS
        );

        return parent::_afterToHtml($html);
    }

    //########################################
}
