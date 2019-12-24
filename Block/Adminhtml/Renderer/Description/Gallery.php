<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Renderer\Description;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Renderer\Description\Gallery
 */
class Gallery extends \Ess\M2ePro\Block\Adminhtml\Renderer\Description
{
    private $galleryId;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('rendererDescriptionGallery');
        // ---------------------------------------

        $this->setTemplate('Ess_M2ePro::renderer/description/gallery.phtml');
    }

    //########################################

    public function getGalleryId()
    {
        if ($this->galleryId === null) {
            $this->galleryId = substr(sha1(
                'gallery-'
                . $this->getData('index_number')
                . $this->getHelper('Data')->jsonEncode($this->getGalleryImages())
            ), 20);
        }
        return $this->galleryId;
    }

    //########################################

    public function isModeDefault()
    {
        return $this->getData('linked_mode') == \Ess\M2ePro\Helper\Module\Renderer\Description::IMAGES_MODE_DEFAULT;
    }

    public function isModeGallery()
    {
        return $this->getData('linked_mode') == \Ess\M2ePro\Helper\Module\Renderer\Description::IMAGES_MODE_GALLERY;
    }

    public function isLayoutColumnMode()
    {
        return $this->getData('layout') == \Ess\M2ePro\Helper\Module\Renderer\Description::LAYOUT_MODE_COLUMN;
    }

    //########################################

    public function getGalleryImages()
    {
        return $this->getData('images') ? $this->getData('images') : [];
    }

    //########################################
}
