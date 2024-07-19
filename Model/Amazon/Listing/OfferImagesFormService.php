<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing;

class OfferImagesFormService
{
    public function convertToString(array $offerImages): string
    {
        return (string)json_encode($offerImages, JSON_THROW_ON_ERROR);
    }

    public function convertToArray(string $offerImages): array
    {
        return (array)json_decode($offerImages, true);
    }

    public function prepareOfferImagesData(array $offerImages): array
    {
        $result = [];
        foreach ($offerImages as $offerImageKey => $items) {
            foreach ($items as $offerImage) {
                $mode = (int)($offerImage['mode'] ?? 0);
                $attributeCode = $offerImage['attribute_code'] ?? '';
                if (
                    $mode === 0
                    || $attributeCode === ''
                ) {
                    continue;
                }

                $data = [
                    'mode' => $mode,
                    'attribute_code' => $attributeCode,
                ];

                $imagesLimit = (int)($offerImage['images_limit'] ?? 0);
                if ($imagesLimit !== 0) {
                    $data['images_limit'] = $imagesLimit;
                }
                $result[$offerImageKey][] = $data;
            }
        }

        return $result;
    }
}
