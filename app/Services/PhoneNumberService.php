<?php

namespace App\Services;

class PhoneNumberService
{
    /**
     * Validate and normalize a phone number
     *
     * @param string $phoneNumber
     * @return array
     */
    public static function validateAndNormalize(string $phoneNumber): array
    {
        // Remove all non-digit characters except + and spaces
        $cleaned = preg_replace('/[^\d\s\+]/', '', $phoneNumber);
        
        // Remove extra spaces
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));
        
        // Handle Tanzanian numbers
        if (self::isTanzanianNumber($cleaned)) {
            return self::normalizeTanzanianNumber($cleaned);
        }
        
        // Handle international numbers
        if (self::isInternationalNumber($cleaned)) {
            return self::normalizeInternationalNumber($cleaned);
        }
        
        return [
            'is_valid' => false,
            'normalized' => null,
            'error' => 'Invalid phone number format',
            'country' => null,
            'type' => null
        ];
    }
    
    /**
     * Check if the number is a Tanzanian number
     */
    private static function isTanzanianNumber(string $number): bool
    {
        // Remove +255 if present
        $number = preg_replace('/^\+255/', '', $number);
        // Also handle 255 prefix (common when Excel strips the +)
        $number = preg_replace('/^255/', '', $number);
        // Remove leading 0 if present
        $number = preg_replace('/^0/', '', $number);
        // Tanzanian mobile numbers start with 6, 7, or 8
        // Format: 6XX XXX XXX, 7XX XXX XXX, 8XX XXX XXX
        return preg_match('/^(6|7|8)\d{8}$/', $number);
    }
    
    /**
     * Normalize Tanzanian number
     */
    private static function normalizeTanzanianNumber(string $number): array
    {
        // Remove +255 if present
        $number = preg_replace('/^\+255/', '', $number);
        // Also handle 255 prefix (common when Excel strips the +)
        $number = preg_replace('/^255/', '', $number);
        // Remove leading 0 if present
        $number = preg_replace('/^0/', '', $number);
        // Remove spaces
        $number = preg_replace('/\s/', '', $number);
        // Validate length and format
        if (!preg_match('/^(6|7|8)\d{8}$/', $number)) {
            return [
                'is_valid' => false,
                'normalized' => null,
                'error' => 'Invalid Tanzanian mobile number format',
                'country' => 'Tanzania',
                'type' => 'mobile'
            ];
        }
        // Determine provider
        $provider = self::getTanzanianProvider($number);
        return [
            'is_valid' => true,
            'normalized' => '+255' . $number,
            'error' => null,
            'country' => 'Tanzania',
            'type' => 'mobile',
            'provider' => $provider
        ];
    }
    
    /**
     * Get Tanzanian mobile provider
     */
    private static function getTanzanianProvider(string $number): string
    {
        $prefix = substr($number, 0, 3);
        
        $providers = [
            '61' => 'Airtel',
            '62' => 'Airtel',
            '63' => 'Airtel',
            '64' => 'Airtel',
            '65' => 'Airtel',
            '66' => 'Airtel',
            '67' => 'Airtel',
            '68' => 'Airtel',
            '69' => 'Airtel',
            '71' => 'Tigo',
            '72' => 'Tigo',
            '73' => 'Tigo',
            '74' => 'Tigo',
            '75' => 'Tigo',
            '76' => 'Tigo',
            '77' => 'Tigo',
            '78' => 'Tigo',
            '79' => 'Tigo',
            '81' => 'Vodacom',
            '82' => 'Vodacom',
            '83' => 'Vodacom',
            '84' => 'Vodacom',
            '85' => 'Vodacom',
            '86' => 'Vodacom',
            '87' => 'Vodacom',
            '88' => 'Vodacom',
            '89' => 'Vodacom',
        ];
        
        return $providers[$prefix] ?? 'Unknown';
    }
    
    /**
     * Check if the number is an international number
     */
    private static function isInternationalNumber(string $number): bool
    {
        // International numbers start with + and have country code
        if (!str_starts_with($number, '+')) {
            return false;
        }
        
        // Remove + and check if it's a valid international format
        $withoutPlus = substr($number, 1);
        
        // Check for common country codes (1-3 digits)
        $countryCodes = [
            '1',    // US/Canada
            '44',   // UK
            '33',   // France
            '49',   // Germany
            '39',   // Italy
            '34',   // Spain
            '31',   // Netherlands
            '32',   // Belgium
            '46',   // Sweden
            '47',   // Norway
            '45',   // Denmark
            '358',  // Finland
            '48',   // Poland
            '420',  // Czech Republic
            '36',   // Hungary
            '43',   // Austria
            '41',   // Switzerland
            '351',  // Portugal
            '30',   // Greece
            '90',   // Turkey
            '971',  // UAE
            '966',  // Saudi Arabia
            '91',   // India
            '86',   // China
            '81',   // Japan
            '82',   // South Korea
            '65',   // Singapore
            '60',   // Malaysia
            '66',   // Thailand
            '84',   // Vietnam
            '62',   // Indonesia
            '63',   // Philippines
            '880',  // Bangladesh
            '977',  // Nepal
            '94',   // Sri Lanka
            '95',   // Myanmar
            '856',  // Laos
            '855',  // Cambodia
            '233',  // Ghana
            '234',  // Nigeria
            '254',  // Kenya
            '256',  // Uganda
            '250',  // Rwanda
            '257',  // Burundi
            '260',  // Zambia
            '263',  // Zimbabwe
            '267',  // Botswana
            '268',  // Eswatini
            '266',  // Lesotho
            '27',   // South Africa
            '251',  // Ethiopia
            '252',  // Somalia
            '253',  // Djibouti
            '249',  // Sudan
            '20',   // Egypt
            '212',  // Morocco
            '213',  // Algeria
            '216',  // Tunisia
            '218',  // Libya
            '222',  // Mauritania
            '221',  // Senegal
            '220',  // Gambia
            '224',  // Guinea
            '225',  // Ivory Coast
            '226',  // Burkina Faso
            '227',  // Niger
            '228',  // Togo
            '229',  // Benin
            '230',  // Mauritius
            '231',  // Liberia
            '232',  // Sierra Leone
            '235',  // Chad
            '236',  // Central African Republic
            '237',  // Cameroon
            '238',  // Cape Verde
            '239',  // Sao Tome and Principe
            '240',  // Equatorial Guinea
            '241',  // Gabon
            '242',  // Republic of the Congo
            '243',  // Democratic Republic of the Congo
            '244',  // Angola
            '245',  // Guinea-Bissau
            '246',  // British Indian Ocean Territory
            '247',  // Ascension Island
            '248',  // Seychelles
            '249',  // Sudan
            '290',  // Saint Helena
            '291',  // Eritrea
            '297',  // Aruba
            '298',  // Faroe Islands
            '299',  // Greenland
            '350',  // Gibraltar
            '351',  // Portugal
            '352',  // Luxembourg
            '353',  // Ireland
            '354',  // Iceland
            '355',  // Albania
            '356',  // Malta
            '357',  // Cyprus
            '358',  // Finland
            '359',  // Bulgaria
            '370',  // Lithuania
            '371',  // Latvia
            '372',  // Estonia
            '373',  // Moldova
            '374',  // Armenia
            '375',  // Belarus
            '376',  // Andorra
            '377',  // Monaco
            '378',  // San Marino
            '379',  // Vatican City
            '380',  // Ukraine
            '381',  // Serbia
            '382',  // Montenegro
            '383',  // Kosovo
            '385',  // Croatia
            '386',  // Slovenia
            '387',  // Bosnia and Herzegovina
            '389',  // North Macedonia
            '420',  // Czech Republic
            '421',  // Slovakia
            '423',  // Liechtenstein
            '500',  // Falkland Islands
            '501',  // Belize
            '502',  // Guatemala
            '503',  // El Salvador
            '504',  // Honduras
            '505',  // Nicaragua
            '506',  // Costa Rica
            '507',  // Panama
            '508',  // Saint Pierre and Miquelon
            '509',  // Haiti
            '590',  // Guadeloupe
            '591',  // Bolivia
            '592',  // Guyana
            '593',  // Ecuador
            '594',  // French Guiana
            '595',  // Paraguay
            '596',  // Martinique
            '597',  // Suriname
            '598',  // Uruguay
            '599',  // Netherlands Antilles
            '670',  // East Timor
            '672',  // Australian External Territories
            '673',  // Brunei
            '674',  // Nauru
            '675',  // Papua New Guinea
            '676',  // Tonga
            '677',  // Solomon Islands
            '678',  // Vanuatu
            '679',  // Fiji
            '680',  // Palau
            '681',  // Wallis and Futuna
            '682',  // Cook Islands
            '683',  // Niue
            '685',  // Samoa
            '686',  // Kiribati
            '687',  // New Caledonia
            '688',  // Tuvalu
            '689',  // French Polynesia
            '690',  // Tokelau
            '691',  // Micronesia
            '692',  // Marshall Islands
            '850',  // North Korea
            '852',  // Hong Kong
            '853',  // Macau
            '855',  // Cambodia
            '856',  // Laos
            '880',  // Bangladesh
            '886',  // Taiwan
            '960',  // Maldives
            '961',  // Lebanon
            '962',  // Jordan
            '963',  // Syria
            '964',  // Iraq
            '965',  // Kuwait
            '966',  // Saudi Arabia
            '967',  // Yemen
            '968',  // Oman
            '970',  // Palestine
            '971',  // UAE
            '972',  // Israel
            '973',  // Bahrain
            '974',  // Qatar
            '975',  // Bhutan
            '976',  // Mongolia
            '977',  // Nepal
            '992',  // Tajikistan
            '993',  // Turkmenistan
            '994',  // Azerbaijan
            '995',  // Georgia
            '996',  // Kyrgyzstan
            '998',  // Uzbekistan
        ];
        
        foreach ($countryCodes as $code) {
            if (str_starts_with($withoutPlus, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Normalize international number
     */
    private static function normalizeInternationalNumber(string $number): array
    {
        // Remove spaces
        $number = preg_replace('/\s/', '', $number);
        
        // Basic validation for international format
        if (!preg_match('/^\+\d{7,15}$/', $number)) {
            return [
                'is_valid' => false,
                'normalized' => null,
                'error' => 'Invalid international phone number format',
                'country' => 'International',
                'type' => 'unknown'
            ];
        }
        
        return [
            'is_valid' => true,
            'normalized' => $number,
            'error' => null,
            'country' => 'International',
            'type' => 'mobile'
        ];
    }
    
    /**
     * Format phone number for display
     */
    public static function formatForDisplay(string $phoneNumber): string
    {
        $validation = self::validateAndNormalize($phoneNumber);
        
        if (!$validation['is_valid']) {
            return $phoneNumber; // Return original if invalid
        }
        
        $normalized = $validation['normalized'];
        
        // Format Tanzanian numbers
        if ($validation['country'] === 'Tanzania') {
            $number = substr($normalized, 4); // Remove +255
            return '+255 ' . substr($number, 0, 3) . ' ' . substr($number, 3, 3) . ' ' . substr($number, 6);
        }
        
        // For international numbers, just return normalized
        return $normalized;
    }
    
    /**
     * Get validation rules for Laravel validation
     */
    public static function getValidationRules(): array
    {
        return [
            'phone_number' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    $validation = self::validateAndNormalize($value);
                    if (!$validation['is_valid']) {
                        $fail($validation['error']);
                    }
                }
            ]
        ];
    }
} 