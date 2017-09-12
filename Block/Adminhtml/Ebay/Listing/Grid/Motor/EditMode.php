<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Motor;

class EditMode extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $listingId;

    //########################################

    public function setListingId($id)
    {
        $this->listingId = $id;
        return $this;
    }

    public function getListing()
    {
        if (empty($this->listingId)) {
            throw new \Exception('Listing ID is not set.');
        }

        return $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK, 'Listing', $this->listingId
        );
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_mode_form',
                'action' => 'javascript:void(0)',
                'method' => 'post'
            ]]
        );

        $form->addField(
            'id',
            'hidden',
            [
                'name'  => 'id',
                'value' => $this->getListing()->getId()
            ]
        );

        $form->addField('edit_mode_help',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
You are able to select one of the available modes:<br>
<ul>
    <li>
        <strong>ePIDS</strong> - is based on the Product Reference IDs of compatible vehicles for this eBay Site.
    </li>
    <li>
        <strong>kTypes</strong> - is based on the common kType data, provided by eBay for all Sites.
    </li>
</ul>
HTML
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'edit_mode_fieldset',
            []
        );

        $fieldset->addField('parts_compatibility_mode',
            self::SELECT,
            [
                'name' => 'parts_compatibility_mode',
                'label' => $this->__('Mode'),
                'value' => $this->getListing()->getChildObject()->isPartsCompatibilityModeEpids()
                                ? \Ess\M2ePro\Model\Ebay\Listing::PARTS_COMPATIBILITY_MODE_EPIDS
                                : \Ess\M2ePro\Model\Ebay\Listing::PARTS_COMPATIBILITY_MODE_KTYPES,
                'values' => [
                    \Ess\M2ePro\Model\Ebay\Listing::PARTS_COMPATIBILITY_MODE_EPIDS => $this->__('ePIDs'),
                    \Ess\M2ePro\Model\Ebay\Listing::PARTS_COMPATIBILITY_MODE_KTYPES => $this->__('kTypes'),
                ],
                'required' => true
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}