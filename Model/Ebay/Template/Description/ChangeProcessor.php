<?php

namespace Ess\M2ePro\Model\Ebay\Template\Description;

class ChangeProcessor extends \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract
{
    public const INSTRUCTION_INITIATOR = 'template_description_change_processor';

    protected function getInstructionInitiator(): string
    {
        return self::INSTRUCTION_INITIATOR;
    }

    // ---------------------------------------

    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status): array
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Description\Diff $diff */

        $data = [];

        if ($diff->isTitleDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_TITLE_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isSubtitleDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_SUBTITLE_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isDescriptionDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_DESCRIPTION_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isImagesDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isVariationImagesDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_VARIATION_IMAGES_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isVideoDifferent() && $status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
            $data[] = [
                'type' => self::INSTRUCTION_TYPE_VIDEO_DATA_CHANGED,
                'priority' => 30,
            ];
        }

        if ($diff->isOtherDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_OTHER_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        return $data;
    }
}
