<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Complete world currency dictionary dataset.
     * Single source of truth containing code, name, country, symbol, aliases, and label.
     */
    public static function dataset(): array
    {
        return [
            'AED' => ['code' => 'AED', 'name' => 'UAE Dirham', 'country' => 'United Arab Emirates', 'symbol' => 'AED', 'aliases' => ['uae dirham', 'dirham'], 'label' => 'UAE Dirham - AED'],
            'AFN' => ['code' => 'AFN', 'name' => 'Afghan Afghani', 'country' => 'Afghanistan', 'symbol' => '؋', 'aliases' => ['afghan afghani', 'afghani'], 'label' => 'Afghan Afghani - AFN'],
            'ALL' => ['code' => 'ALL', 'name' => 'Albanian Lek', 'country' => 'Albania', 'symbol' => 'L', 'aliases' => ['albanian lek', 'lek'], 'label' => 'Albanian Lek - ALL'],
            'AMD' => ['code' => 'AMD', 'name' => 'Armenian Dram', 'country' => 'Armenia', 'symbol' => '֏', 'aliases' => ['armenian dram', 'dram'], 'label' => 'Armenian Dram - AMD'],
            'ANG' => ['code' => 'ANG', 'name' => 'Netherlands Antillian Guilder', 'country' => 'Netherlands Antilles', 'symbol' => 'ƒ', 'aliases' => ['guilder'], 'label' => 'Netherlands Antillian Guilder - ANG'],
            'AOA' => ['code' => 'AOA', 'name' => 'Angolan Kwanza', 'country' => 'Angola', 'symbol' => 'Kz', 'aliases' => ['angolan kwanza', 'kwanza'], 'label' => 'Angolan Kwanza - AOA'],
            'ARS' => ['code' => 'ARS', 'name' => 'Argentine Peso', 'country' => 'Argentina', 'symbol' => '$', 'aliases' => ['argentine peso'], 'label' => 'Argentine Peso - ARS'],
            'AUD' => ['code' => 'AUD', 'name' => 'Australian Dollar', 'country' => 'Australia', 'symbol' => 'A$', 'aliases' => ['australian dollar'], 'label' => 'Australian Dollar - AUD'],
            'AWG' => ['code' => 'AWG', 'name' => 'Aruban Florin', 'country' => 'Aruba', 'symbol' => 'ƒ', 'aliases' => ['aruban florin', 'florin'], 'label' => 'Aruban Florin - AWG'],
            'AZN' => ['code' => 'AZN', 'name' => 'Azerbaijani Manat', 'country' => 'Azerbaijan', 'symbol' => '₼', 'aliases' => ['azerbaijani manat', 'manat'], 'label' => 'Azerbaijani Manat - AZN'],
            'BAM' => ['code' => 'BAM', 'name' => 'Bosnia and Herzegovina Mark', 'country' => 'Bosnia and Herzegovina', 'symbol' => 'KM', 'aliases' => ['convertible mark'], 'label' => 'Bosnia and Herzegovina Mark - BAM'],
            'BBD' => ['code' => 'BBD', 'name' => 'Barbados Dollar', 'country' => 'Barbados', 'symbol' => 'Bds$', 'aliases' => ['barbados dollar'], 'label' => 'Barbados Dollar - BBD'],
            'BDT' => ['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'country' => 'Bangladesh', 'symbol' => '৳', 'aliases' => ['bangladeshi taka', 'taka'], 'label' => 'Bangladeshi Taka - BDT'],
            'BGN' => ['code' => 'BGN', 'name' => 'Bulgarian Lev', 'country' => 'Bulgaria', 'symbol' => 'лв', 'aliases' => ['bulgarian lev', 'lev'], 'label' => 'Bulgarian Lev - BGN'],
            'BHD' => ['code' => 'BHD', 'name' => 'Bahraini Dinar', 'country' => 'Bahrain', 'symbol' => 'BD', 'aliases' => ['bahraini dinar'], 'label' => 'Bahraini Dinar - BHD'],
            'BIF' => ['code' => 'BIF', 'name' => 'Burundian Franc', 'country' => 'Burundi', 'symbol' => 'FBu', 'aliases' => ['burundian franc'], 'label' => 'Burundian Franc - BIF'],
            'BMD' => ['code' => 'BMD', 'name' => 'Bermudian Dollar', 'country' => 'Bermuda', 'symbol' => '$', 'aliases' => ['bermudian dollar'], 'label' => 'Bermudian Dollar - BMD'],
            'BND' => ['code' => 'BND', 'name' => 'Brunei Dollar', 'country' => 'Brunei', 'symbol' => 'B$', 'aliases' => ['brunei dollar'], 'label' => 'Brunei Dollar - BND'],
            'BOB' => ['code' => 'BOB', 'name' => 'Bolivian Boliviano', 'country' => 'Bolivia', 'symbol' => 'Bs.', 'aliases' => ['boliviano'], 'label' => 'Bolivian Boliviano - BOB'],
            'BRL' => ['code' => 'BRL', 'name' => 'Brazilian Real', 'country' => 'Brazil', 'symbol' => 'R$', 'aliases' => ['brazilian real', 'real'], 'label' => 'Brazilian Real - BRL'],
            'BSD' => ['code' => 'BSD', 'name' => 'Bahamian Dollar', 'country' => 'Bahamas', 'symbol' => 'B$', 'aliases' => ['bahamian dollar'], 'label' => 'Bahamian Dollar - BSD'],
            'BTN' => ['code' => 'BTN', 'name' => 'Bhutanese Ngultrum', 'country' => 'Bhutan', 'symbol' => 'Nu.', 'aliases' => ['ngultrum'], 'label' => 'Bhutanese Ngultrum - BTN'],
            'BWP' => ['code' => 'BWP', 'name' => 'Botswana Pula', 'country' => 'Botswana', 'symbol' => 'P', 'aliases' => ['botswana pula', 'pula'], 'label' => 'Botswana Pula - BWP'],
            'BYN' => ['code' => 'BYN', 'name' => 'Belarusian Ruble', 'country' => 'Belarus', 'symbol' => 'Br', 'aliases' => ['belarusian ruble'], 'label' => 'Belarusian Ruble - BYN'],
            'BZD' => ['code' => 'BZD', 'name' => 'Belize Dollar', 'country' => 'Belize', 'symbol' => 'BZ$', 'aliases' => ['belize dollar'], 'label' => 'Belize Dollar - BZD'],
            'CAD' => ['code' => 'CAD', 'name' => 'Canadian Dollar', 'country' => 'Canada', 'symbol' => 'C$', 'aliases' => ['canadian dollar'], 'label' => 'Canadian Dollar - CAD'],
            'CDF' => ['code' => 'CDF', 'name' => 'Congolese Franc', 'country' => 'Democratic Republic of the Congo', 'symbol' => 'FC', 'aliases' => ['congolese franc'], 'label' => 'Congolese Franc - CDF'],
            'CHF' => ['code' => 'CHF', 'name' => 'Swiss Franc', 'country' => 'Switzerland', 'symbol' => 'CHF', 'aliases' => ['swiss franc', 'franc'], 'label' => 'Swiss Franc - CHF'],
            'CLF' => ['code' => 'CLF', 'name' => 'Chilean Unidad de Fomento', 'country' => 'Chile', 'symbol' => 'UF', 'aliases' => ['unidad de fomento'], 'label' => 'Chilean Unidad de Fomento - CLF'],
            'CLP' => ['code' => 'CLP', 'name' => 'Chilean Peso', 'country' => 'Chile', 'symbol' => '$', 'aliases' => ['chilean peso'], 'label' => 'Chilean Peso - CLP'],
            'CNH' => ['code' => 'CNH', 'name' => 'Offshore Chinese Renminbi', 'country' => 'China', 'symbol' => '¥', 'aliases' => ['offshore yuan', 'cnh'], 'label' => 'Offshore Chinese Renminbi - CNH'],
            'CNY' => ['code' => 'CNY', 'name' => 'Chinese Renminbi', 'country' => 'China', 'symbol' => '¥', 'aliases' => ['china yuan', 'chinese yuan', 'yuan', 'rmb', 'renminbi'], 'label' => 'Chinese Renminbi - CNY'],
            'COP' => ['code' => 'COP', 'name' => 'Colombian Peso', 'country' => 'Colombia', 'symbol' => '$', 'aliases' => ['colombian peso'], 'label' => 'Colombian Peso - COP'],
            'CRC' => ['code' => 'CRC', 'name' => 'Costa Rican Colon', 'country' => 'Costa Rica', 'symbol' => '₡', 'aliases' => ['costa rican colon', 'colon'], 'label' => 'Costa Rican Colon - CRC'],
            'CUP' => ['code' => 'CUP', 'name' => 'Cuban Peso', 'country' => 'Cuba', 'symbol' => '$MN', 'aliases' => ['cuban peso'], 'label' => 'Cuban Peso - CUP'],
            'CVE' => ['code' => 'CVE', 'name' => 'Cape Verdean Escudo', 'country' => 'Cape Verde', 'symbol' => '$', 'aliases' => ['escudo'], 'label' => 'Cape Verdean Escudo - CVE'],
            'CZK' => ['code' => 'CZK', 'name' => 'Czech Koruna', 'country' => 'Czech Republic', 'symbol' => 'Kč', 'aliases' => ['czech koruna', 'koruna'], 'label' => 'Czech Koruna - CZK'],
            'DJF' => ['code' => 'DJF', 'name' => 'Djiboutian Franc', 'country' => 'Djibouti', 'symbol' => 'Fdj', 'aliases' => ['djiboutian franc'], 'label' => 'Djiboutian Franc - DJF'],
            'DKK' => ['code' => 'DKK', 'name' => 'Danish Krone', 'country' => 'Denmark', 'symbol' => 'kr.', 'aliases' => ['danish krone'], 'label' => 'Danish Krone - DKK'],
            'DOP' => ['code' => 'DOP', 'name' => 'Dominican Peso', 'country' => 'Dominican Republic', 'symbol' => 'RD$', 'aliases' => ['dominican peso'], 'label' => 'Dominican Peso - DOP'],
            'DZD' => ['code' => 'DZD', 'name' => 'Algerian Dinar', 'country' => 'Algeria', 'symbol' => 'DA', 'aliases' => ['algerian dinar'], 'label' => 'Algerian Dinar - DZD'],
            'EGP' => ['code' => 'EGP', 'name' => 'Egyptian Pound', 'country' => 'Egypt', 'symbol' => 'E£', 'aliases' => ['egyptian pound'], 'label' => 'Egyptian Pound - EGP'],
            'ERN' => ['code' => 'ERN', 'name' => 'Eritrean Nakfa', 'country' => 'Eritrea', 'symbol' => 'Nfk', 'aliases' => ['eritrean nakfa'], 'label' => 'Eritrean Nakfa - ERN'],
            'ETB' => ['code' => 'ETB', 'name' => 'Ethiopian Birr', 'country' => 'Ethiopia', 'symbol' => 'Br', 'aliases' => ['ethiopian birr', 'birr'], 'label' => 'Ethiopian Birr - ETB'],
            'EUR' => ['code' => 'EUR', 'name' => 'Euro', 'country' => 'European Union', 'symbol' => '€', 'aliases' => ['euro'], 'label' => 'Euro - EUR'],
            'FJD' => ['code' => 'FJD', 'name' => 'Fiji Dollar', 'country' => 'Fiji', 'symbol' => 'FJ$', 'aliases' => ['fiji dollar'], 'label' => 'Fiji Dollar - FJD'],
            'FKP' => ['code' => 'FKP', 'name' => 'Falkland Islands Pound', 'country' => 'Falkland Islands', 'symbol' => '£', 'aliases' => ['falkland pound'], 'label' => 'Falkland Islands Pound - FKP'],
            'FOK' => ['code' => 'FOK', 'name' => 'Faroese Króna', 'country' => 'Faroe Islands', 'symbol' => 'kr', 'aliases' => ['faroese krona'], 'label' => 'Faroese Króna - FOK'],
            'GBP' => ['code' => 'GBP', 'name' => 'Pound Sterling', 'country' => 'United Kingdom', 'symbol' => '£', 'aliases' => ['british pound', 'pound', 'pound sterling'], 'label' => 'Pound Sterling - GBP'],
            'GEL' => ['code' => 'GEL', 'name' => 'Georgian Lari', 'country' => 'Georgia', 'symbol' => '₾', 'aliases' => ['georgian lari', 'lari'], 'label' => 'Georgian Lari - GEL'],
            'GGP' => ['code' => 'GGP', 'name' => 'Guernsey Pound', 'country' => 'Guernsey', 'symbol' => '£', 'aliases' => ['guernsey pound'], 'label' => 'Guernsey Pound - GGP'],
            'GHS' => ['code' => 'GHS', 'name' => 'Ghanaian Cedi', 'country' => 'Ghana', 'symbol' => 'GH₵', 'aliases' => ['ghanaian cedi', 'cedi'], 'label' => 'Ghanaian Cedi - GHS'],
            'GIP' => ['code' => 'GIP', 'name' => 'Gibraltar Pound', 'country' => 'Gibraltar', 'symbol' => '£', 'aliases' => ['gibraltar pound'], 'label' => 'Gibraltar Pound - GIP'],
            'GMD' => ['code' => 'GMD', 'name' => 'Gambian Dalasi', 'country' => 'The Gambia', 'symbol' => 'D', 'aliases' => ['dalasi'], 'label' => 'Gambian Dalasi - GMD'],
            'GNF' => ['code' => 'GNF', 'name' => 'Guinean Franc', 'country' => 'Guinea', 'symbol' => 'FG', 'aliases' => ['guinean franc'], 'label' => 'Guinean Franc - GNF'],
            'GTQ' => ['code' => 'GTQ', 'name' => 'Guatemalan Quetzal', 'country' => 'Guatemala', 'symbol' => 'Q', 'aliases' => ['quetzal'], 'label' => 'Guatemalan Quetzal - GTQ'],
            'GYD' => ['code' => 'GYD', 'name' => 'Guyanese Dollar', 'country' => 'Guyana', 'symbol' => 'G$', 'aliases' => ['guyanese dollar'], 'label' => 'Guyanese Dollar - GYD'],
            'HKD' => ['code' => 'HKD', 'name' => 'Hong Kong Dollar', 'country' => 'Hong Kong', 'symbol' => 'HK$', 'aliases' => ['hong kong dollar'], 'label' => 'Hong Kong Dollar - HKD'],
            'HNL' => ['code' => 'HNL', 'name' => 'Honduran Lempira', 'country' => 'Honduras', 'symbol' => 'L', 'aliases' => ['lempira'], 'label' => 'Honduran Lempira - HNL'],
            'HRK' => ['code' => 'HRK', 'name' => 'Croatian Kuna', 'country' => 'Croatia', 'symbol' => 'kn', 'aliases' => ['croatian kuna', 'kuna'], 'label' => 'Croatian Kuna - HRK'],
            'HTG' => ['code' => 'HTG', 'name' => 'Haitian Gourde', 'country' => 'Haiti', 'symbol' => 'G', 'aliases' => ['gourde'], 'label' => 'Haitian Gourde - HTG'],
            'HUF' => ['code' => 'HUF', 'name' => 'Hungarian Forint', 'country' => 'Hungary', 'symbol' => 'Ft', 'aliases' => ['hungarian forint', 'forint'], 'label' => 'Hungarian Forint - HUF'],
            'IDR' => ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'country' => 'Indonesia', 'symbol' => 'Rp', 'aliases' => ['indonesian rupiah', 'rupiah'], 'label' => 'Indonesian Rupiah - IDR'],
            'ILS' => ['code' => 'ILS', 'name' => 'Israeli New Shekel', 'country' => 'Israel', 'symbol' => '₪', 'aliases' => ['shekel'], 'label' => 'Israeli New Shekel - ILS'],
            'IMP' => ['code' => 'IMP', 'name' => 'Manx Pound', 'country' => 'Isle of Man', 'symbol' => '£', 'aliases' => ['manx pound'], 'label' => 'Manx Pound - IMP'],
            'INR' => ['code' => 'INR', 'name' => 'Indian Rupee', 'country' => 'India', 'symbol' => '₹', 'aliases' => ['indian rupee', 'rupee'], 'label' => 'Indian Rupee - INR'],
            'IQD' => ['code' => 'IQD', 'name' => 'Iraqi Dinar', 'country' => 'Iraq', 'symbol' => 'IQD', 'aliases' => ['iraqi dinar'], 'label' => 'Iraqi Dinar - IQD'],
            'ISK' => ['code' => 'ISK', 'name' => 'Icelandic Króna', 'country' => 'Iceland', 'symbol' => 'kr', 'aliases' => ['icelandic krona'], 'label' => 'Icelandic Króna - ISK'],
            'JEP' => ['code' => 'JEP', 'name' => 'Jersey Pound', 'country' => 'Jersey', 'symbol' => '£', 'aliases' => ['jersey pound'], 'label' => 'Jersey Pound - JEP'],
            'JMD' => ['code' => 'JMD', 'name' => 'Jamaican Dollar', 'country' => 'Jamaica', 'symbol' => 'J$', 'aliases' => ['jamaican dollar'], 'label' => 'Jamaican Dollar - JMD'],
            'JOD' => ['code' => 'JOD', 'name' => 'Jordanian Dinar', 'country' => 'Jordan', 'symbol' => 'JD', 'aliases' => ['jordanian dinar'], 'label' => 'Jordanian Dinar - JOD'],
            'JPY' => ['code' => 'JPY', 'name' => 'Japanese Yen', 'country' => 'Japan', 'symbol' => '¥', 'aliases' => ['japanese yen', 'yen'], 'label' => 'Japanese Yen - JPY'],
            'KES' => ['code' => 'KES', 'name' => 'Kenyan Shilling', 'country' => 'Kenya', 'symbol' => 'KSh', 'aliases' => ['kenyan shilling'], 'label' => 'Kenyan Shilling - KES'],
            'KGS' => ['code' => 'KGS', 'name' => 'Kyrgyzstani Som', 'country' => 'Kyrgyzstan', 'symbol' => 'с', 'aliases' => ['kyrgyzstani som', 'som'], 'label' => 'Kyrgyzstani Som - KGS'],
            'KHR' => ['code' => 'KHR', 'name' => 'Cambodian Riel', 'country' => 'Cambodia', 'symbol' => '៛', 'aliases' => ['cambodian riel', 'riel'], 'label' => 'Cambodian Riel - KHR'],
            'KID' => ['code' => 'KID', 'name' => 'Kiribati Dollar', 'country' => 'Kiribati', 'symbol' => '$', 'aliases' => ['kiribati dollar'], 'label' => 'Kiribati Dollar - KID'],
            'KMF' => ['code' => 'KMF', 'name' => 'Comorian Franc', 'country' => 'Comoros', 'symbol' => 'CF', 'aliases' => ['comorian franc'], 'label' => 'Comorian Franc - KMF'],
            'KRW' => ['code' => 'KRW', 'name' => 'South Korean Won', 'country' => 'South Korea', 'symbol' => '₩', 'aliases' => ['south korean won', 'korean won', 'won'], 'label' => 'South Korean Won - KRW'],
            'KWD' => ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'country' => 'Kuwait', 'symbol' => 'KD', 'aliases' => ['kuwaiti dinar'], 'label' => 'Kuwaiti Dinar - KWD'],
            'KYD' => ['code' => 'KYD', 'name' => 'Cayman Islands Dollar', 'country' => 'Cayman Islands', 'symbol' => 'CI$', 'aliases' => ['cayman dollar'], 'label' => 'Cayman Islands Dollar - KYD'],
            'KZT' => ['code' => 'KZT', 'name' => 'Kazakhstani Tenge', 'country' => 'Kazakhstan', 'symbol' => '₸', 'aliases' => ['tenge'], 'label' => 'Kazakhstani Tenge - KZT'],
            'LAK' => ['code' => 'LAK', 'name' => 'Lao Kip', 'country' => 'Laos', 'symbol' => '₭', 'aliases' => ['lao kip', 'kip'], 'label' => 'Lao Kip - LAK'],
            'LBP' => ['code' => 'LBP', 'name' => 'Lebanese Pound', 'country' => 'Lebanon', 'symbol' => 'L£', 'aliases' => ['lebensese pound'], 'label' => 'Lebanese Pound - LBP'],
            'LKR' => ['code' => 'LKR', 'name' => 'Sri Lanka Rupee', 'country' => 'Sri Lanka', 'symbol' => 'Rs', 'aliases' => ['sri lankan rupee'], 'label' => 'Sri Lanka Rupee - LKR'],
            'LRD' => ['code' => 'LRD', 'name' => 'Liberian Dollar', 'country' => 'Liberia', 'symbol' => 'L$', 'aliases' => ['liberian dollar'], 'label' => 'Liberian Dollar - LRD'],
            'LSL' => ['code' => 'LSL', 'name' => 'Lesotho Loti', 'country' => 'Lesotho', 'symbol' => 'L', 'aliases' => ['lesotho loti', 'loti'], 'label' => 'Lesotho Loti - LSL'],
            'LYD' => ['code' => 'LYD', 'name' => 'Libyan Dinar', 'country' => 'Libya', 'symbol' => 'LD', 'aliases' => ['libyan dinar'], 'label' => 'Libyan Dinar - LYD'],
            'MAD' => ['code' => 'MAD', 'name' => 'Moroccan Dirham', 'country' => 'Morocco', 'symbol' => 'DH', 'aliases' => ['moroccan dirham'], 'label' => 'Moroccan Dirham - MAD'],
            'MDL' => ['code' => 'MDL', 'name' => 'Moldovan Leu', 'country' => 'Moldova', 'symbol' => 'L', 'aliases' => ['moldovan leu'], 'label' => 'Moldovan Leu - MDL'],
            'MGA' => ['code' => 'MGA', 'name' => 'Malagasy Ariary', 'country' => 'Madagascar', 'symbol' => 'Ar', 'aliases' => ['ariary'], 'label' => 'Malagasy Ariary - MGA'],
            'MKD' => ['code' => 'MKD', 'name' => 'Macedonian Denar', 'country' => 'North Macedonia', 'symbol' => 'ден', 'aliases' => ['denar'], 'label' => 'Macedonian Denar - MKD'],
            'MMK' => ['code' => 'MMK', 'name' => 'Burmese Kyat', 'country' => 'Myanmar', 'symbol' => 'K', 'aliases' => ['kyat'], 'label' => 'Burmese Kyat - MMK'],
            'MNT' => ['code' => 'MNT', 'name' => 'Mongolian Tögrög', 'country' => 'Mongolia', 'symbol' => '₮', 'aliases' => ['tugrik', 'togrog'], 'label' => 'Mongolian Tögrög - MNT'],
            'MOP' => ['code' => 'MOP', 'name' => 'Macanese Pataca', 'country' => 'Macau', 'symbol' => 'MOP$', 'aliases' => ['pataca'], 'label' => 'Macanese Pataca - MOP'],
            'MRU' => ['code' => 'MRU', 'name' => 'Mauritanian Ouguiya', 'country' => 'Mauritania', 'symbol' => 'UM', 'aliases' => ['ouguiya'], 'label' => 'Mauritanian Ouguiya - MRU'],
            'MUR' => ['code' => 'MUR', 'name' => 'Mauritian Rupee', 'country' => 'Mauritius', 'symbol' => 'Rs', 'aliases' => ['mauritian rupee'], 'label' => 'Mauritian Rupee - MUR'],
            'MVR' => ['code' => 'MVR', 'name' => 'Maldivian Rufiyaa', 'country' => 'Maldives', 'symbol' => 'Rf', 'aliases' => ['rufiyaa'], 'label' => 'Maldivian Rufiyaa - MVR'],
            'MWK' => ['code' => 'MWK', 'name' => 'Malawian Kwacha', 'country' => 'Malawi', 'symbol' => 'MK', 'aliases' => ['kwacha'], 'label' => 'Malawian Kwacha - MWK'],
            'MXN' => ['code' => 'MXN', 'name' => 'Mexican Peso', 'country' => 'Mexico', 'symbol' => '$', 'aliases' => ['mexican peso'], 'label' => 'Mexican Peso - MXN'],
            'MYR' => ['code' => 'MYR', 'name' => 'Malaysian Ringgit', 'country' => 'Malaysia', 'symbol' => 'RM', 'aliases' => ['malaysian ringgit', 'ringgit'], 'label' => 'Malaysian Ringgit - MYR'],
            'MZN' => ['code' => 'MZN', 'name' => 'Mozambican Metical', 'country' => 'Mozambique', 'symbol' => 'MT', 'aliases' => ['metical'], 'label' => 'Mozambican Metical - MZN'],
            'NAD' => ['code' => 'NAD', 'name' => 'Namibian Dollar', 'country' => 'Namibia', 'symbol' => 'N$', 'aliases' => ['namibian dollar'], 'label' => 'Namibian Dollar - NAD'],
            'NGN' => ['code' => 'NGN', 'name' => 'Nigerian Naira', 'country' => 'Nigeria', 'symbol' => '₦', 'aliases' => ['naira'], 'label' => 'Nigerian Naira - NGN'],
            'NIO' => ['code' => 'NIO', 'name' => 'Nicaraguan Córdoba', 'country' => 'Nicaragua', 'symbol' => 'C$', 'aliases' => ['cordoba'], 'label' => 'Nicaraguan Córdoba - NIO'],
            'NOK' => ['code' => 'NOK', 'name' => 'Norwegian Krone', 'country' => 'Norway', 'symbol' => 'kr', 'aliases' => ['norwegian krone'], 'label' => 'Norwegian Krone - NOK'],
            'NPR' => ['code' => 'NPR', 'name' => 'Nepalese Rupee', 'country' => 'Nepal', 'symbol' => 'Rs.', 'aliases' => ['nepalese rupee'], 'label' => 'Nepalese Rupee - NPR'],
            'NZD' => ['code' => 'NZD', 'name' => 'New Zealand Dollar', 'country' => 'New Zealand', 'symbol' => 'NZ$', 'aliases' => ['new zealand dollar'], 'label' => 'New Zealand Dollar - NZD'],
            'OMR' => ['code' => 'OMR', 'name' => 'Omani Rial', 'country' => 'Oman', 'symbol' => 'RO', 'aliases' => ['omani rial'], 'label' => 'Omani Rial - OMR'],
            'PAB' => ['code' => 'PAB', 'name' => 'Panamanian Balboa', 'country' => 'Panama', 'symbol' => 'B/.', 'aliases' => ['balboa'], 'label' => 'Panamanian Balboa - PAB'],
            'PEN' => ['code' => 'PEN', 'name' => 'Peruvian Sol', 'country' => 'Peru', 'symbol' => 'S/.', 'aliases' => ['peruvian sol', 'sol'], 'label' => 'Peruvian Sol - PEN'],
            'PGK' => ['code' => 'PGK', 'name' => 'Papua New Guinean Kina', 'country' => 'Papua New Guinea', 'symbol' => 'K', 'aliases' => ['kina'], 'label' => 'Papua New Guinean Kina - PGK'],
            'PHP' => ['code' => 'PHP', 'name' => 'Philippine Peso', 'country' => 'Philippines', 'symbol' => '₱', 'aliases' => ['philippine peso'], 'label' => 'Philippine Peso - PHP'],
            'PKR' => ['code' => 'PKR', 'name' => 'Pakistani Rupee', 'country' => 'Pakistan', 'symbol' => 'Rs', 'aliases' => ['pakistani rupee'], 'label' => 'Pakistani Rupee - PKR'],
            'PLN' => ['code' => 'PLN', 'name' => 'Polish Złoty', 'country' => 'Poland', 'symbol' => 'zł', 'aliases' => ['polish zloty', 'zloty'], 'label' => 'Polish Złoty - PLN'],
            'PYG' => ['code' => 'PYG', 'name' => 'Paraguayan Guaraní', 'country' => 'Paraguay', 'symbol' => '₲', 'aliases' => ['guarani'], 'label' => 'Paraguayan Guaraní - PYG'],
            'QAR' => ['code' => 'QAR', 'name' => 'Qatari Riyal', 'country' => 'Qatar', 'symbol' => 'QR', 'aliases' => ['qatari riyal'], 'label' => 'Qatari Riyal - QAR'],
            'RON' => ['code' => 'RON', 'name' => 'Romanian Leu', 'country' => 'Romania', 'symbol' => 'lei', 'aliases' => ['romanian leu', 'leu'], 'label' => 'Romanian Leu - RON'],
            'RSD' => ['code' => 'RSD', 'name' => 'Serbian Dinar', 'country' => 'Serbia', 'symbol' => 'din.', 'aliases' => ['serbian dinar'], 'label' => 'Serbian Dinar - RSD'],
            'RUB' => ['code' => 'RUB', 'name' => 'Russian Ruble', 'country' => 'Russia', 'symbol' => '₽', 'aliases' => ['russian ruble', 'ruble'], 'label' => 'Russian Ruble - RUB'],
            'RWF' => ['code' => 'RWF', 'name' => 'Rwandan Franc', 'country' => 'Rwanda', 'symbol' => 'FRw', 'aliases' => ['rwandan franc'], 'label' => 'Rwandan Franc - RWF'],
            'SAR' => ['code' => 'SAR', 'name' => 'Saudi Riyal', 'country' => 'Saudi Arabia', 'symbol' => 'SR', 'aliases' => ['saudi riyal', 'riyal'], 'label' => 'Saudi Riyal - SAR'],
            'SBD' => ['code' => 'SBD', 'name' => 'Solomon Islands Dollar', 'country' => 'Solomon Islands', 'symbol' => 'SI$', 'aliases' => ['solomon islands dollar'], 'label' => 'Solomon Islands Dollar - SBD'],
            'SCR' => ['code' => 'SCR', 'name' => 'Seychellois Rupee', 'country' => 'Seychelles', 'symbol' => 'SR', 'aliases' => ['seychellois rupee'], 'label' => 'Seychellois Rupee - SCR'],
            'SDG' => ['code' => 'SDG', 'name' => 'Sudanese Pound', 'country' => 'Sudan', 'symbol' => 'SDG', 'aliases' => ['sudanese pound'], 'label' => 'Sudanese Pound - SDG'],
            'SEK' => ['code' => 'SEK', 'name' => 'Swedish Krona', 'country' => 'Sweden', 'symbol' => 'kr', 'aliases' => ['swedish krona'], 'label' => 'Swedish Krona - SEK'],
            'SGD' => ['code' => 'SGD', 'name' => 'Singapore Dollar', 'country' => 'Singapore', 'symbol' => 'S$', 'aliases' => ['singapore dollar', 'singapore dolar', 'singapore'], 'label' => 'Singapore Dollar - SGD'],
            'SHP' => ['code' => 'SHP', 'name' => 'Saint Helena Pound', 'country' => 'Saint Helena', 'symbol' => '£', 'aliases' => ['saint helena pound'], 'label' => 'Saint Helena Pound - SHP'],
            'SLE' => ['code' => 'SLE', 'name' => 'Sierra Leonean Leone', 'country' => 'Sierra Leone', 'symbol' => 'Le', 'aliases' => ['leone'], 'label' => 'Sierra Leonean Leone - SLE'],
            'SOS' => ['code' => 'SOS', 'name' => 'Somali Shilling', 'country' => 'Somalia', 'symbol' => 'Sh.So.', 'aliases' => ['somali shilling'], 'label' => 'Somali Shilling - SOS'],
            'SRD' => ['code' => 'SRD', 'name' => 'Surinamese Dollar', 'country' => 'Suriname', 'symbol' => 'Sr$', 'aliases' => ['surinamese dollar'], 'label' => 'Surinamese Dollar - SRD'],
            'SSP' => ['code' => 'SSP', 'name' => 'South Sudanese Pound', 'country' => 'South Sudan', 'symbol' => 'SS£', 'aliases' => ['south sudanese pound'], 'label' => 'South Sudanese Pound - SSP'],
            'STN' => ['code' => 'STN', 'name' => 'São Tomé and Príncipe Dobra', 'country' => 'São Tomé and Príncipe', 'symbol' => 'Db', 'aliases' => ['dobra'], 'label' => 'São Tomé and Príncipe Dobra - STN'],
            'SYP' => ['code' => 'SYP', 'name' => 'Syrian Pound', 'country' => 'Syria', 'symbol' => 'LS', 'aliases' => ['syrian pound'], 'label' => 'Syrian Pound - SYP'],
            'SZL' => ['code' => 'SZL', 'name' => 'Eswatini Lilangeni', 'country' => 'Eswatini', 'symbol' => 'E', 'aliases' => ['lilangeni'], 'label' => 'Eswatini Lilangeni - SZL'],
            'THB' => ['code' => 'THB', 'name' => 'Thai Baht', 'country' => 'Thailand', 'symbol' => '฿', 'aliases' => ['thai baht', 'baht'], 'label' => 'Thai Baht - THB'],
            'TJS' => ['code' => 'TJS', 'name' => 'Tajikistani Somoni', 'country' => 'Tajikistan', 'symbol' => 'SM', 'aliases' => ['somoni'], 'label' => 'Tajikistani Somoni - TJS'],
            'TMT' => ['code' => 'TMT', 'name' => 'Turkmenistan Manat', 'country' => 'Turkmenistan', 'symbol' => 'T', 'aliases' => ['turkmenistan manat'], 'label' => 'Turkmenistan Manat - TMT'],
            'TND' => ['code' => 'TND', 'name' => 'Tunisian Dinar', 'country' => 'Tunisia', 'symbol' => 'DT', 'aliases' => ['tunisian dinar'], 'label' => 'Tunisian Dinar - TND'],
            'TOP' => ['code' => 'TOP', 'name' => 'Tongan Paʻanga', 'country' => 'Tonga', 'symbol' => 'T$', 'aliases' => ['paanga'], 'label' => 'Tongan Paʻanga - TOP'],
            'TRY' => ['code' => 'TRY', 'name' => 'Turkish Lira', 'country' => 'Turkey', 'symbol' => '₺', 'aliases' => ['turkish lira', 'lira'], 'label' => 'Turkish Lira - TRY'],
            'TTD' => ['code' => 'TTD', 'name' => 'Trinidad and Tobago Dollar', 'country' => 'Trinidad and Tobago', 'symbol' => 'TT$', 'aliases' => ['trinidad dollar'], 'label' => 'Trinidad and Tobago Dollar - TTD'],
            'TVD' => ['code' => 'TVD', 'name' => 'Tuvaluan Dollar', 'country' => 'Tuvalu', 'symbol' => '$', 'aliases' => ['tuvaluan dollar'], 'label' => 'Tuvaluan Dollar - TVD'],
            'TWD' => ['code' => 'TWD', 'name' => 'New Taiwan Dollar', 'country' => 'Taiwan', 'symbol' => 'NT$', 'aliases' => ['taiwan dollar', 'new taiwan dollar'], 'label' => 'New Taiwan Dollar - TWD'],
            'TZS' => ['code' => 'TZS', 'name' => 'Tanzanian Shilling', 'country' => 'Tanzania', 'symbol' => 'TSh', 'aliases' => ['tanzanian shilling'], 'label' => 'Tanzanian Shilling - TZS'],
            'UAH' => ['code' => 'UAH', 'name' => 'Ukrainian Hryvnia', 'country' => 'Ukraine', 'symbol' => '₴', 'aliases' => ['hryvnia'], 'label' => 'Ukrainian Hryvnia - UAH'],
            'UGX' => ['code' => 'UGX', 'name' => 'Ugandan Shilling', 'country' => 'Uganda', 'symbol' => 'USh', 'aliases' => ['ugandan shilling'], 'label' => 'Ugandan Shilling - UGX'],
            'USD' => ['code' => 'USD', 'name' => 'United States Dollar', 'country' => 'United States', 'symbol' => '$', 'aliases' => ['us dollar', 'american dollar', 'dollar'], 'label' => 'United States Dollar - USD'],
            'UYU' => ['code' => 'UYU', 'name' => 'Uruguayan Peso', 'country' => 'Uruguay', 'symbol' => '$U', 'aliases' => ['uruguayan peso'], 'label' => 'Uruguayan Peso - UYU'],
            'UZS' => ['code' => 'UZS', 'name' => 'Uzbekistani So\'m', 'country' => 'Uzbekistan', 'symbol' => 'so\'m', 'aliases' => ['uzbekistani som'], 'label' => 'Uzbekistani So\'m - UZS'],
            'VES' => ['code' => 'VES', 'name' => 'Venezuelan Bolívar Soberano', 'country' => 'Venezuela', 'symbol' => 'Bs.S', 'aliases' => ['bolivar'], 'label' => 'Venezuelan Bolívar Soberano - VES'],
            'VND' => ['code' => 'VND', 'name' => 'Vietnamese Đồng', 'country' => 'Vietnam', 'symbol' => '₫', 'aliases' => ['vietnamese dong', 'dong'], 'label' => 'Vietnamese Đồng - VND'],
            'VUV' => ['code' => 'VUV', 'name' => 'Vanuatu Vatu', 'country' => 'Vanuatu', 'symbol' => 'VT', 'aliases' => ['vatu'], 'label' => 'Vanuatu Vatu - VUV'],
            'WST' => ['code' => 'WST', 'name' => 'Samoan Tālā', 'country' => 'Samoa', 'symbol' => 'WS$', 'aliases' => ['tala'], 'label' => 'Samoan Tālā - WST'],
            'XAF' => ['code' => 'XAF', 'name' => 'Central African CFA Franc', 'country' => 'CEMAC', 'symbol' => 'FCFA', 'aliases' => ['cfa franc'], 'label' => 'Central African CFA Franc - XAF'],
            'XCD' => ['code' => 'XCD', 'name' => 'East Caribbean Dollar', 'country' => 'Organisation of Eastern Caribbean States', 'symbol' => 'EC$', 'aliases' => ['east caribbean dollar'], 'label' => 'East Caribbean Dollar - XCD'],
            'XDR' => ['code' => 'XDR', 'name' => 'Special Drawing Rights', 'country' => 'International Monetary Fund', 'symbol' => 'SDR', 'aliases' => ['special drawing rights'], 'label' => 'Special Drawing Rights - XDR'],
            'XOF' => ['code' => 'XOF', 'name' => 'West African CFA franc', 'country' => 'CFA', 'symbol' => 'CFA', 'aliases' => ['west african cfa franc'], 'label' => 'West African CFA franc - XOF'],
            'XPF' => ['code' => 'XPF', 'name' => 'CFP Franc', 'country' => 'Collectivités d\'Outre-Mer', 'symbol' => '₣', 'aliases' => ['cfp franc'], 'label' => 'CFP Franc - XPF'],
            'YER' => ['code' => 'YER', 'name' => 'Yemeni Rial', 'country' => 'Yemen', 'symbol' => 'YR', 'aliases' => ['yemeni rial'], 'label' => 'Yemeni Rial - YER'],
            'ZAR' => ['code' => 'ZAR', 'name' => 'South African Rand', 'country' => 'South Africa', 'symbol' => 'R', 'aliases' => ['rand', 'south african rand'], 'label' => 'South African Rand - ZAR'],
            'ZMW' => ['code' => 'ZMW', 'name' => 'Zambian Kwacha', 'country' => 'Zambia', 'symbol' => 'ZK', 'aliases' => ['zambian kwacha'], 'label' => 'Zambian Kwacha - ZMW'],
            'ZWL' => ['code' => 'ZWL', 'name' => 'Zimbabwean Dollar', 'country' => 'Zimbabwe', 'symbol' => 'Z$', 'aliases' => ['zimbabwean dollar'], 'label' => 'Zimbabwean Dollar - ZWL'],
        ];
    }

    /**
     * Get all currencies dictionary array indexed by currency code.
     */
    public static function all(): array
    {
        return static::dataset();
    }

    /**
     * Find a currency entry by Code, Name, Alias, or Country search text.
     */
    public static function find(?string $search): ?array
    {
        if (empty($search)) {
            return null;
        }

        $all = static::dataset();
        $input = trim($search);
        $upperInput = strtoupper($input);
        $lowerInput = strtolower($input);

        // 1. Match by 3-letter currency code (e.g., "USD", "SGD", "CNY")
        if (isset($all[$upperInput])) {
            return $all[$upperInput];
        }

        // 2. Search by Aliases array inside dataset
        foreach ($all as $item) {
            if (!empty($item['aliases'])) {
                foreach ($item['aliases'] as $alias) {
                    if (strtolower($alias) === $lowerInput) {
                        return $item;
                    }
                }
            }
        }

        // 3. Extract 3-letter code from string (e.g., "Singapore Dollar (SGD)")
        if (preg_match('/\b([A-Za-z]{3})\b/', $input, $matches)) {
            $extractedCode = strtoupper($matches[1]);
            if (isset($all[$extractedCode])) {
                return $all[$extractedCode];
            }
        }

        // 4. Search by Name or Country (exact match or substring)
        foreach ($all as $item) {
            if (strtolower($item['name']) === $lowerInput || strtolower($item['country']) === $lowerInput) {
                return $item;
            }
        }

        foreach ($all as $item) {
            if (str_contains(strtolower($item['name']), $lowerInput)) {
                return $item;
            }
        }

        // 5. Partial alias match (contains alias string)
        foreach ($all as $item) {
            if (!empty($item['aliases'])) {
                foreach ($item['aliases'] as $alias) {
                    if (str_contains($lowerInput, strtolower($alias)) || str_contains(strtolower($alias), $lowerInput)) {
                        return $item;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Format currency string to "Name - CODE" (e.g., "Chinese Renminbi - CNY", "Singapore Dollar - SGD")
     */
    public static function formatLabel(?string $input, string $default = 'Chinese Renminbi - CNY'): string
    {
        if (empty($input)) {
            return $default;
        }

        $found = static::find($input);
        if ($found) {
            return $found['label'];
        }

        if (preg_match('/\b([A-Za-z]{3})\b/', (string)$input, $matches)) {
            $code = strtoupper($matches[1]);
            $cleanName = trim(preg_replace('/\s*[\(\-]\s*[A-Za-z]{3}\s*[\)]?\s*/', '', (string)$input));
            return (!empty($cleanName) && strtolower($cleanName) !== strtolower($code))
                ? "{$cleanName} - {$code}"
                : "{$code} - {$code}";
        }

        return (string)$input;
    }

    /**
     * Get 3-letter currency ISO code from input.
     */
    public static function getCode(?string $input, string $default = 'CNY'): string
    {
        $found = static::find($input);
        if ($found) {
            return $found['code'];
        }

        if (preg_match('/\b([A-Za-z]{3})\b/', (string)$input, $matches)) {
            return strtoupper($matches[1]);
        }

        return strlen((string)$input) === 3 ? strtoupper((string)$input) : $default;
    }

    /**
     * Get official authentic Currency Symbol directly from dataset entry.
     */
    public static function getSymbol(?string $input, string $default = '¥'): string
    {
        $found = static::find($input);
        if ($found && !empty($found['symbol'])) {
            return $found['symbol'];
        }

        $code = static::getCode($input);
        return (!empty($code) && strlen($code) === 3) ? $code : $default;
    }
}
