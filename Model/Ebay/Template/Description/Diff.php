<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Template\Description;

class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    public function isDifferent(): bool
    {
        return $this->isTitleDifferent()
            || $this->isSubtitleDifferent()
            || $this->isDescriptionDifferent()
            || $this->isImagesDifferent()
            || $this->isVariationImagesDifferent()
            || $this->isVideoDifferent()
            || $this->isComplianceDocumentsDifferent()
            || $this->isOtherDifferent()
            || $this->isProductIdentifierDifferent();
    }

    public function isTitleDifferent(): bool
    {
        $keys = [
            'title_mode',
            'title_template',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isSubtitleDifferent(): bool
    {
        $keys = [
            'subtitle_mode',
            'subtitle_template',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isDescriptionDifferent(): bool
    {
        $keys = [
            'description_mode',
            'description_template',
            'product_details',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isImagesDifferent(): bool
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
            'watermark_settings',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isVariationImagesDifferent(): bool
    {
        $keys = [
            'variation_images_mode',
            'variation_images_attribute',
            'variation_images_limit',
            'variation_configurable_images',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isVideoDifferent(): bool
    {
        $keys = [
            'video_mode',
            'video_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isOtherDifferent(): bool
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

    public function isProductIdentifierDifferent(): bool
    {
        $keys = [
            'product_details',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isComplianceDocumentsDifferent(): bool
    {
        $keys = [
            'compliance_documents',
        ];

        return $this->isSettingsDifferent($keys);
    }
}
