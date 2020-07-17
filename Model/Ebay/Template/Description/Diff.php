<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Description;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Description\Diff
 */
class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    //########################################

    public function isDifferent()
    {
        return $this->isTitleDifferent() ||
               $this->isSubtitleDifferent() ||
               $this->isDescriptionDifferent() ||
               $this->isImagesDifferent() ||
               $this->isVariationImagesDifferent() ||
               $this->isOtherDifferent();
    }

    //########################################

    public function isTitleDifferent()
    {
        $keys = [
            'title_mode',
            'title_template',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isSubtitleDifferent()
    {
        $keys = [
            'subtitle_mode',
            'subtitle_template',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isDescriptionDifferent()
    {
        $keys = [
            'description_mode',
            'description_template',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isImagesDifferent()
    {
        $keys = [
            'gallery_type',
            'image_main_mode',
            'image_main_attribute',
            'gallery_images_mode',
            'gallery_images_attribute',
            'gallery_images_limit',
            'default_image_url',
            'use_supersize_images',

            'watermark_mode',
            'watermark_image',
            'watermark_settings'
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isVariationImagesDifferent()
    {
        $keys = [
            'variation_images_mode',
            'variation_images_attribute',
            'variation_images_limit',
            'variation_configurable_images',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isOtherDifferent()
    {
        $keys = [
            'condition_mode',
            'condition_value',
            'condition_attribute',
            'condition_note_mode',
            'condition_note_template',
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
