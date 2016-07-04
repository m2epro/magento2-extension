<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

class MerchantFulfillment extends \Ess\M2ePro\Helper\AbstractHelper
{
    const STATUS_PURCHASED       = 'Purchased';
    const STATUS_REFUND_PENDING  = 'RefundPending';
    const STATUS_REFUND_REJECTED = 'RefundRejected';
    const STATUS_REFUND_APPLIED  = 'RefundApplied';

    const DIMENSION_SOURCE_NONE       = 0;
    const DIMENSION_SOURCE_CUSTOM     = 1;
    const DIMENSION_SOURCE_PREDEFINED = 2;

    const DIMENSION_MEASURE_INCHES      = 'inches';
    const DIMENSION_MEASURE_CENTIMETERS = 'centimeters';

    const WEIGHT_MEASURE_OUNCES = 'ounces';
    const WEIGHT_MEASURE_GRAMS  = 'grams';

    const DELIVERY_EXPERIENCE_NO_TRACKING           = 0;
    const DELIVERY_EXPERIENCE_WITHOUT_SIGNATURE     = 1;
    const DELIVERY_EXPERIENCE_WITH_SIGNATURE        = 2;
    const DELIVERY_EXPERIENCE_WITH_ADULT_SIGNATURE  = 3;

    const CARRIER_WILL_PICK_UP_NO  = 0;
    const CARRIER_WILL_PICK_UP_YES = 1;

    //########################################

    public function getPredefinedPackageDimensions()
    {
        return array(
            'FedEx' => array(
                'FedEx_Box_10kg' => 'FedEx Box 10 kg (15.81 x 12.94 x 10.19 in)',
                'FedEx_Box_25kg' => 'FedEx Box 25 kg (54.80 x 42.10 x 33.50 in)',
                'FedEx_Box_Extra_Large_1' => 'FedEx Box Extra Large 1 (11.88 x 11.00 x 10.75 in)',
                'FedEx_Box_Extra_Large_2' => 'FedEx Box Extra Large 2 (15.75 x 14.13 x 6.00 in)',
                'FedEx_Box_Large_1' => 'FedEx Box Large 1 (17.50 x 12.38 x 3.00 in)',
                'FedEx_Box_Large_2' => 'FedEx Box Large 2 (11.25 x 8.75 x 7.75 in)',
                'FedEx_Box_Medium_1' => 'FedEx Box Medium 1 (13.25 x 11.50 x 2.38 in)',
                'FedEx_Box_Medium_2' => 'FedEx Box Medium 2 (11.25 x 8.75 x 4.38 in)',
                'FedEx_Box_Small_1' => 'FedEx Box Small 1 (12.38 x 10.88 x 1.50 in)',
                'FedEx_Box_Small_2' => 'FedEx Box Small 2 (11.25 x 8.75 x 4.38 in)',
                'FedEx_Envelope' => 'FedEx Envelope (12.50 x 9.50 x 0.80 in)',
                'FedEx_Padded_Pak' => 'FedEx Padded Pak (11.75 x 14.75 x 2.00 in)',
                'FedEx_Pak_1' => 'FedEx Pak 1 (15.50 x 12.00 x 0.80 in)',
                'FedEx_Pak_2' => 'FedEx Pak 2 (12.75 x 10.25 x 0.80 in)',
                'FedEx_Tube' => 'FedEx Tube (38.00 x 6.00 x 6.00 in)',
                'FedEx_XL_Pak' => 'FedEx XL Pak (17.50 x 20.75 x 2.00 in)'
            ),
            'UPS' => array(
                'UPS_Box_10kg' => 'UPS Box 10 kg (41.00 x 33.50 x 26.50 cm)',
                'UPS_Box_25kg' => 'UPS Box 25 kg (48.40 x 43.30 x 35.00 cm)',
                'UPS_Express_Box' => 'UPS Express Box (46.00 x 31.50 x 9.50 cm)',
                'UPS_Express_Box_Large' => 'UPS Express Box Large (18.00 x 13.00 x 3.00 in)',
                'UPS_Express_Box_Medium' => 'UPS Express Box Medium (15.00 x 11.00 x 3.00 in)',
                'UPS_Express_Box_Small' => 'UPS Express Box Small (13.00 x 11.00 x 2.00 in)',
                'UPS_Express_Envelope' => 'UPS Express Envelope (12.50 x 9.50 x 2.00 in)',
                'UPS_Express_Hard_Pak' => 'UPS Express Hard Pak (14.75 x 11.50 x 2.00 in)',
                'UPS_Express_Legal_Envelope' => 'UPS Express Legal Envelope (15.00 x 9.50 x 2.00 in)',
                'UPS_Express_Pak' => 'UPS Express Pak (16.00 x 12.75 x 2.00 in)',
                'UPS_Express_Tube' => 'UPS Express Tube (97.00 x 19.00 x 16.50 cm)',
                'UPS_Laboratory_Pak' => 'UPS Laboratory Pak (17.25 x 12.75 x 2.00 in)',
                'UPS_Pad_Pak' => 'UPS Pad Pak (14.75 x 11.00 x 2.00 in)',
                'UPS_Pallet' => 'UPS Pallet (120.00 x 80.00 x 200.00 cm)'
            ),
            'USPS' => array(
                'USPS_Card' => 'USPS Card (6.00 x 4.25 x 0.01 in)',
                'USPS_Flat' => 'USPS Flat (15.00 x 12.00 x 0.75 in)',
                'USPS_FlatRateCardboardEnvelope' => 'USPS Flat Rate Cardboard Envelope (12.50 x 9.50 x 4.00 in)',
                'USPS_FlatRateEnvelope' => 'USPS Flat Rate Envelope (12.50 x 9.50 x 4.00 in)',
                'USPS_FlatRateGiftCardEnvelope' => 'USPS Flat Rate Gift Card Envelope (10.00 x 7.00 x 4.00 in)',
                'USPS_FlatRateLegalEnvelope' => 'USPS Flat Rate Legal Envelope (15.00 x 9.50 x 4.00 in)',
                'USPS_FlatRatePaddedEnvelope' => 'USPS Flat Rate Padded Envelope (12.50 x 9.50 x 4.00 in)',
                'USPS_FlatRateWindowEnvelope' => 'USPS Flat Rate Window Envelope (10.00 x 5.00 x 4.00 in)',
                'USPS_LargeFlatRateBoardGameBox' => 'USPS Large Flat Rate Board Game Box (24.06 x 11.88 x 3.13 in)',
                'USPS_LargeFlatRateBox' => 'USPS Large Flat Rate Box (12.25 x 12.25 x 6.00 in)',
                'USPS_Letter' => 'USPS Letter (11.50 x 6.13 x 0.25 in)',
                'USPS_MediumFlatRateBox1' => 'USPS Medium Flat Rate Box 1 (11.25 x 8.75 x 6.00 in)',
                'USPS_MediumFlatRateBox2' => 'USPS Medium Flat Rate Box 2 (14.00 x 12.00 x 3.50 in)',
                'USPS_RegionalRateBoxA1' => 'USPS Regional Rate Box A1 (10.13 x 7.13 x 5.00 in)',
                'USPS_RegionalRateBoxA2' => 'USPS Regional Rate Box A2 (13.06 x 11.06 x 2.50 in)',
                'USPS_RegionalRateBoxB1' => 'USPS Regional Rate Box B1 (16.25 x 14.50 x 3.00 in)',
                'USPS_RegionalRateBoxB2' => 'USPS Regional Rate Box B2 (12.25 x 10.50 x 5.50 in)',
                'USPS_RegionalRateBoxC' => 'USPS Regional Rate Box C (15.00 x 12.00 x 12.00 in)',
                'USPS_SmallFlatRateBox' => 'USPS Small Flat Rate Box (8.69 x 5.44 x 1.75 in)',
                'USPS_SmallFlatRateEnvelope' => 'USPS Small Flat Rate Envelope (10.00 x 6.00 x 4.00 in)'
            )
        );
    }

    //########################################
}