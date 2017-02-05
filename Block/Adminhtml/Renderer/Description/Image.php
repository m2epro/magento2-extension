<?php

namespace Ess\M2ePro\Block\Adminhtml\Renderer\Description;

class Image extends \Ess\M2ePro\Block\Adminhtml\Renderer\Description
{
    private $imageId;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('rendererDescriptionImage');
        // ---------------------------------------

        $this->setTemplate('renderer/description/image.phtml');
    }

    //########################################

    public function getImageId()
    {
        if (is_null($this->imageId)) {
            $this->imageId = substr(sha1(
                'image-'
                . $this->getData('index_number')
                . $this->getHelper('Data')->jsonEncode($this->getData('src'))
            ), 20);
        }
        return $this->imageId;
    }

    //########################################

    public function isLinkMode()
    {
        return $this->getData('linked_mode') == \Ess\M2ePro\Helper\Module\Renderer\Description::IMAGES_MODE_NEW_WINDOW;
    }

    //########################################
}