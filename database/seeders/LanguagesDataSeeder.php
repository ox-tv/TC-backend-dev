<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LanguagesDataSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * php artisan db:seed --class=LanguagesDataSeeder
     * @return void
     */
    public function run()
    {
        $languages = [
            0 => [
                'languageCode' => 'ab',
                'displayName' => 'Abkhazian',
            ],
            1 => [
                'languageCode' => 'aa',
                'displayName' => 'Afar',
            ],
            2 => [
                'languageCode' => 'af',
                'displayName' => 'Afrikaans',
            ],
            3 => [
                'languageCode' => 'akk',
                'displayName' => 'Akkadian',
            ],
            4 => [
                'languageCode' => 'sq',
                'displayName' => 'Albanian',
            ],
            5 => [
                'languageCode' => 'ase',
                'displayName' => 'American Sign Language',
            ],
            6 => [
                'languageCode' => 'am',
                'displayName' => 'Amharic',
            ],
            7 => [
                'languageCode' => 'ar',
                'displayName' => 'Arabic',
            ],
            8 => [
                'languageCode' => 'arc',
                'displayName' => 'Aramaic',
            ],
            9 => [
                'languageCode' => 'hy',
                'displayName' => 'Armenian',
            ],
            10 => [
                'languageCode' => 'as',
                'displayName' => 'Assamese',
            ],
            11 => [
                'languageCode' => 'ay',
                'displayName' => 'Aymara',
            ],
            12 => [
                'languageCode' => 'az',
                'displayName' => 'Azerbaijani',
            ],
            13 => [
                'languageCode' => 'bn',
                'displayName' => 'Bangla',
            ],
            14 => [
                'languageCode' => 'ba',
                'displayName' => 'Bashkir',
            ],
            15 => [
                'languageCode' => 'eu',
                'displayName' => 'Basque',
            ],
            16 => [
                'languageCode' => 'be',
                'displayName' => 'Belarusian',
            ],
            17 => [
                'languageCode' => 'bh',
                'displayName' => 'Bhojpuri',
            ],
            18 => [
                'languageCode' => 'bi',
                'displayName' => 'Bislama',
            ],
            19 => [
                'languageCode' => 'brx',
                'displayName' => 'Bodo',
            ],
            20 => [
                'languageCode' => 'bs',
                'displayName' => 'Bosnian',
            ],
            21 => [
                'languageCode' => 'br',
                'displayName' => 'Breton',
            ],
            22 => [
                'languageCode' => 'bg',
                'displayName' => 'Bulgarian',
            ],
            23 => [
                'languageCode' => 'my',
                'displayName' => 'Burmese',
            ],
            24 => [
                'languageCode' => 'yue',
                'displayName' => 'Cantonese',
            ],
            25 => [
                'languageCode' => 'yue-HK',
                'displayName' => 'Cantonese (Hong Kong)',
            ],
            26 => [
                'languageCode' => 'ca',
                'displayName' => 'Catalan',
            ],
            27 => [
                'languageCode' => 'chr',
                'displayName' => 'Cherokee',
            ],
            28 => [
                'languageCode' => 'zh',
                'displayName' => 'Chinese',
            ],
            29 => [
                'languageCode' => 'zh-CN',
                'displayName' => 'Chinese (China)',
            ],
            30 => [
                'languageCode' => 'zh-HK',
                'displayName' => 'Chinese (Hong Kong)',
            ],
            31 => [
                'languageCode' => 'zh-Hans',
                'displayName' => 'Chinese (Simplified)',
            ],
            32 => [
                'languageCode' => 'zh-SG',
                'displayName' => 'Chinese (Singapore)',
            ],
            33 => [
                'languageCode' => 'zh-TW',
                'displayName' => 'Chinese (Taiwan)',
            ],
            34 => [
                'languageCode' => 'zh-Hant',
                'displayName' => 'Chinese (Traditional)',
            ],
            35 => [
                'languageCode' => 'cho',
                'displayName' => 'Choctaw',
            ],
            36 => [
                'languageCode' => 'cop',
                'displayName' => 'Coptic',
            ],
            37 => [
                'languageCode' => 'co',
                'displayName' => 'Corsican',
            ],
            38 => [
                'languageCode' => 'cr',
                'displayName' => 'Cree',
            ],
            39 => [
                'languageCode' => 'hr',
                'displayName' => 'Croatian',
            ],
            40 => [
                'languageCode' => 'cs',
                'displayName' => 'Czech',
            ],
            41 => [
                'languageCode' => 'da',
                'displayName' => 'Danish',
            ],
            42 => [
                'languageCode' => 'doi',
                'displayName' => 'Dogri',
            ],
            43 => [
                'languageCode' => 'nl',
                'displayName' => 'Dutch',
            ],
            44 => [
                'languageCode' => 'nl-BE',
                'displayName' => 'Dutch (Belgium)',
            ],
            45 => [
                'languageCode' => 'nl-NL',
                'displayName' => 'Dutch (Netherlands)',
            ],
            46 => [
                'languageCode' => 'dz',
                'displayName' => 'Dzongkha',
            ],
            47 => [
                'languageCode' => 'en',
                'displayName' => 'English',
            ],
            48 => [
                'languageCode' => 'en-CA',
                'displayName' => 'English (Canada)',
            ],
            49 => [
                'languageCode' => 'en-IN',
                'displayName' => 'English (India)',
            ],
            50 => [
                'languageCode' => 'en-IE',
                'displayName' => 'English (Ireland)',
            ],
            51 => [
                'languageCode' => 'en-GB',
                'displayName' => 'English (United Kingdom)',
            ],
            52 => [
                'languageCode' => 'en-US',
                'displayName' => 'English (United States)',
            ],
            53 => [
                'languageCode' => 'eo',
                'displayName' => 'Esperanto',
            ],
            54 => [
                'languageCode' => 'et',
                'displayName' => 'Estonian',
            ],
            55 => [
                'languageCode' => 'fo',
                'displayName' => 'Faroese',
            ],
            56 => [
                'languageCode' => 'fj',
                'displayName' => 'Fijian',
            ],
            57 => [
                'languageCode' => 'fil',
                'displayName' => 'Filipino',
            ],
            58 => [
                'languageCode' => 'fi',
                'displayName' => 'Finnish',
            ],
            59 => [
                'languageCode' => 'fr',
                'displayName' => 'French',
            ],
            60 => [
                'languageCode' => 'fr-BE',
                'displayName' => 'French (Belgium)',
            ],
            61 => [
                'languageCode' => 'fr-CA',
                'displayName' => 'French (Canada)',
            ],
            62 => [
                'languageCode' => 'fr-FR',
                'displayName' => 'French (France)',
            ],
            63 => [
                'languageCode' => 'fr-CH',
                'displayName' => 'French (Switzerland)',
            ],
            64 => [
                'languageCode' => 'ff',
                'displayName' => 'Fulah',
            ],
            65 => [
                'languageCode' => 'gl',
                'displayName' => 'Galician',
            ],
            66 => [
                'languageCode' => 'ka',
                'displayName' => 'Georgian',
            ],
            67 => [
                'languageCode' => 'de',
                'displayName' => 'German',
            ],
            68 => [
                'languageCode' => 'de-AT',
                'displayName' => 'German (Austria)',
            ],
            69 => [
                'languageCode' => 'de-DE',
                'displayName' => 'German (Germany)',
            ],
            70 => [
                'languageCode' => 'de-CH',
                'displayName' => 'German (Switzerland)',
            ],
            71 => [
                'languageCode' => 'el',
                'displayName' => 'Greek',
            ],
            72 => [
                'languageCode' => 'gn',
                'displayName' => 'Guarani',
            ],
            73 => [
                'languageCode' => 'gu',
                'displayName' => 'Gujarati',
            ],
            74 => [
                'languageCode' => 'ht',
                'displayName' => 'Haitian Creole',
            ],
            75 => [
                'languageCode' => 'hak',
                'displayName' => 'Hakka Chinese',
            ],
            76 => [
                'languageCode' => 'hak-TW',
                'displayName' => 'Hakka Chinese (Taiwan)',
            ],
            77 => [
                'languageCode' => 'ha',
                'displayName' => 'Hausa',
            ],
            78 => [
                'languageCode' => 'haw',
                'displayName' => 'Hawaiian',
            ],
            79 => [
                'languageCode' => 'iw',
                'displayName' => 'Hebrew',
            ],
            80 => [
                'languageCode' => 'hi',
                'displayName' => 'Hindi',
            ],
            81 => [
                'languageCode' => 'hi-Latn',
                'displayName' => 'Hindi (Latin)',
            ],
            82 => [
                'languageCode' => 'ho',
                'displayName' => 'Hiri Motu',
            ],
            83 => [
                'languageCode' => 'hu',
                'displayName' => 'Hungarian',
            ],
            84 => [
                'languageCode' => 'is',
                'displayName' => 'Icelandic',
            ],
            85 => [
                'languageCode' => 'ig',
                'displayName' => 'Igbo',
            ],
            86 => [
                'languageCode' => 'id',
                'displayName' => 'Indonesian',
            ],
            87 => [
                'languageCode' => 'ia',
                'displayName' => 'Interlingua',
            ],
            88 => [
                'languageCode' => 'ie',
                'displayName' => 'Interlingue',
            ],
            89 => [
                'languageCode' => 'iu',
                'displayName' => 'Inuktitut',
            ],
            90 => [
                'languageCode' => 'ik',
                'displayName' => 'Inupiaq',
            ],
            91 => [
                'languageCode' => 'ga',
                'displayName' => 'Irish',
            ],
            92 => [
                'languageCode' => 'it',
                'displayName' => 'Italian',
            ],
            93 => [
                'languageCode' => 'ja',
                'displayName' => 'Japanese',
            ],
            94 => [
                'languageCode' => 'jv',
                'displayName' => 'Javanese',
            ],
            95 => [
                'languageCode' => 'kl',
                'displayName' => 'Kalaallisut',
            ],
            96 => [
                'languageCode' => 'kn',
                'displayName' => 'Kannada',
            ],
            97 => [
                'languageCode' => 'ks',
                'displayName' => 'Kashmiri',
            ],
            98 => [
                'languageCode' => 'kk',
                'displayName' => 'Kazakh',
            ],
            99 => [
                'languageCode' => 'km',
                'displayName' => 'Khmer',
            ],
            100 => [
                'languageCode' => 'rw',
                'displayName' => 'Kinyarwanda',
            ],
            101 => [
                'languageCode' => 'tlh',
                'displayName' => 'Klingon',
            ],
            102 => [
                'languageCode' => 'kok',
                'displayName' => 'Konkani',
            ],
            103 => [
                'languageCode' => 'ko',
                'displayName' => 'Korean',
            ],
            104 => [
                'languageCode' => 'ku',
                'displayName' => 'Kurdish',
            ],
            105 => [
                'languageCode' => 'ky',
                'displayName' => 'Kyrgyz',
            ],
            106 => [
                'languageCode' => 'lad',
                'displayName' => 'Ladino',
            ],
            107 => [
                'languageCode' => 'lo',
                'displayName' => 'Lao',
            ],
            108 => [
                'languageCode' => 'la',
                'displayName' => 'Latin',
            ],
            109 => [
                'languageCode' => 'lv',
                'displayName' => 'Latvian',
            ],
            110 => [
                'languageCode' => 'ln',
                'displayName' => 'Lingala',
            ],
            111 => [
                'languageCode' => 'lt',
                'displayName' => 'Lithuanian',
            ],
            112 => [
                'languageCode' => 'lb',
                'displayName' => 'Luxembourgish',
            ],
            113 => [
                'languageCode' => 'mk',
                'displayName' => 'Macedonian',
            ],
            114 => [
                'languageCode' => 'mai',
                'displayName' => 'Maithili',
            ],
            115 => [
                'languageCode' => 'mg',
                'displayName' => 'Malagasy',
            ],
            116 => [
                'languageCode' => 'ms',
                'displayName' => 'Malay',
            ],
            117 => [
                'languageCode' => 'ml',
                'displayName' => 'Malayalam',
            ],
            118 => [
                'languageCode' => 'mt',
                'displayName' => 'Maltese',
            ],
            119 => [
                'languageCode' => 'mni',
                'displayName' => 'Manipuri',
            ],
            120 => [
                'languageCode' => 'mi',
                'displayName' => 'Maori',
            ],
            121 => [
                'languageCode' => 'mr',
                'displayName' => 'Marathi',
            ],
            122 => [
                'languageCode' => 'mas',
                'displayName' => 'Masai',
            ],
            123 => [
                'languageCode' => 'nan',
                'displayName' => 'Min Nan Chinese',
            ],
            124 => [
                'languageCode' => 'nan-TW',
                'displayName' => 'Min Nan Chinese (Taiwan)',
            ],
            125 => [
                'languageCode' => 'lus',
                'displayName' => 'Mizo',
            ],
            126 => [
                'languageCode' => 'mn',
                'displayName' => 'Mongolian',
            ],
            127 => [
                'languageCode' => 'mn-Mong',
                'displayName' => 'Mongolian (Mongolian)',
            ],
            128 => [
                'languageCode' => 'na',
                'displayName' => 'Nauru',
            ],
            129 => [
                'languageCode' => 'nv',
                'displayName' => 'Navajo',
            ],
            130 => [
                'languageCode' => 'ne',
                'displayName' => 'Nepali',
            ],
            131 => [
                'languageCode' => 'no',
                'displayName' => 'Norwegian',
            ],
            132 => [
                'languageCode' => 'oc',
                'displayName' => 'Occitan',
            ],
            133 => [
                'languageCode' => 'or',
                'displayName' => 'Odia',
            ],
            134 => [
                'languageCode' => 'om',
                'displayName' => 'Oromo',
            ],
            135 => [
                'languageCode' => 'pap',
                'displayName' => 'Papiamento',
            ],
            136 => [
                'languageCode' => 'ps',
                'displayName' => 'Pashto',
            ],
            137 => [
                'languageCode' => 'fa',
                'displayName' => 'Persian',
            ],
            138 => [
                'languageCode' => 'fa-AF',
                'displayName' => 'Persian (Afghanistan)',
            ],
            139 => [
                'languageCode' => 'fa-IR',
                'displayName' => 'Persian (Iran)',
            ],
            140 => [
                'languageCode' => 'pl',
                'displayName' => 'Polish',
            ],
            141 => [
                'languageCode' => 'pt',
                'displayName' => 'Portuguese',
            ],
            142 => [
                'languageCode' => 'pt-BR',
                'displayName' => 'Portuguese (Brazil)',
            ],
            143 => [
                'languageCode' => 'pt-PT',
                'displayName' => 'Portuguese (Portugal)',
            ],
            144 => [
                'languageCode' => 'pa',
                'displayName' => 'Punjabi',
            ],
            145 => [
                'languageCode' => 'qu',
                'displayName' => 'Quechua',
            ],
            146 => [
                'languageCode' => 'ro',
                'displayName' => 'Romanian',
            ],
            147 => [
                'languageCode' => 'mo',
                'displayName' => 'Romanian (Moldova)',
            ],
            148 => [
                'languageCode' => 'rm',
                'displayName' => 'Romansh',
            ],
            149 => [
                'languageCode' => 'rn',
                'displayName' => 'Rundi',
            ],
            150 => [
                'languageCode' => 'ru',
                'displayName' => 'Russian',
            ],
            151 => [
                'languageCode' => 'ru-Latn',
                'displayName' => 'Russian (Latin)',
            ],
            152 => [
                'languageCode' => 'sm',
                'displayName' => 'Samoan',
            ],
            153 => [
                'languageCode' => 'sg',
                'displayName' => 'Sango',
            ],
            154 => [
                'languageCode' => 'sa',
                'displayName' => 'Sanskrit',
            ],
            155 => [
                'languageCode' => 'sat',
                'displayName' => 'Santali',
            ],
            156 => [
                'languageCode' => 'sc',
                'displayName' => 'Sardinian',
            ],
            157 => [
                'languageCode' => 'gd',
                'displayName' => 'Scottish Gaelic',
            ],
            158 => [
                'languageCode' => 'sr',
                'displayName' => 'Serbian',
            ],
            159 => [
                'languageCode' => 'sr-Cyrl',
                'displayName' => 'Serbian (Cyrillic)',
            ],
            160 => [
                'languageCode' => 'sr-Latn',
                'displayName' => 'Serbian (Latin)',
            ],
            161 => [
                'languageCode' => 'sh',
                'displayName' => 'Serbo-Croatian',
            ],
            162 => [
                'languageCode' => 'sdp',
                'displayName' => 'Sherdukpen',
            ],
            163 => [
                'languageCode' => 'sn',
                'displayName' => 'Shona',
            ],
            164 => [
                'languageCode' => 'scn',
                'displayName' => 'Sicilian',
            ],
            165 => [
                'languageCode' => 'sd',
                'displayName' => 'Sindhi',
            ],
            166 => [
                'languageCode' => 'si',
                'displayName' => 'Sinhala',
            ],
            167 => [
                'languageCode' => 'sk',
                'displayName' => 'Slovak',
            ],
            168 => [
                'languageCode' => 'sl',
                'displayName' => 'Slovenian',
            ],
            169 => [
                'languageCode' => 'so',
                'displayName' => 'Somali',
            ],
            170 => [
                'languageCode' => 'st',
                'displayName' => 'Southern Sotho',
            ],
            171 => [
                'languageCode' => 'es',
                'displayName' => 'Spanish',
            ],
            172 => [
                'languageCode' => 'es-419',
                'displayName' => 'Spanish (Latin America)',
            ],
            173 => [
                'languageCode' => 'es-MX',
                'displayName' => 'Spanish (Mexico)',
            ],
            174 => [
                'languageCode' => 'es-ES',
                'displayName' => 'Spanish (Spain)',
            ],
            175 => [
                'languageCode' => 'es-US',
                'displayName' => 'Spanish (United States)',
            ],
            176 => [
                'languageCode' => 'su',
                'displayName' => 'Sundanese',
            ],
            177 => [
                'languageCode' => 'sw',
                'displayName' => 'Swahili',
            ],
            178 => [
                'languageCode' => 'ss',
                'displayName' => 'Swati',
            ],
            179 => [
                'languageCode' => 'sv',
                'displayName' => 'Swedish',
            ],
            180 => [
                'languageCode' => 'tl',
                'displayName' => 'Tagalog',
            ],
            181 => [
                'languageCode' => 'tg',
                'displayName' => 'Tajik',
            ],
            182 => [
                'languageCode' => 'ta',
                'displayName' => 'Tamil',
            ],
            183 => [
                'languageCode' => 'tt',
                'displayName' => 'Tatar',
            ],
            184 => [
                'languageCode' => 'te',
                'displayName' => 'Telugu',
            ],
            185 => [
                'languageCode' => 'th',
                'displayName' => 'Thai',
            ],
            186 => [
                'languageCode' => 'bo',
                'displayName' => 'Tibetan',
            ],
            187 => [
                'languageCode' => 'ti',
                'displayName' => 'Tigrinya',
            ],
            188 => [
                'languageCode' => 'tpi',
                'displayName' => 'Tok Pisin',
            ],
            189 => [
                'languageCode' => 'to',
                'displayName' => 'Tongan',
            ],
            190 => [
                'languageCode' => 'ts',
                'displayName' => 'Tsonga',
            ],
            191 => [
                'languageCode' => 'tn',
                'displayName' => 'Tswana',
            ],
            192 => [
                'languageCode' => 'tr',
                'displayName' => 'Turkish',
            ],
            193 => [
                'languageCode' => 'tk',
                'displayName' => 'Turkmen',
            ],
            194 => [
                'languageCode' => 'tw',
                'displayName' => 'Twi',
            ],
            195 => [
                'languageCode' => 'uk',
                'displayName' => 'Ukrainian',
            ],
            196 => [
                'languageCode' => 'ur',
                'displayName' => 'Urdu',
            ],
            197 => [
                'languageCode' => 'ug',
                'displayName' => 'Uyghur',
            ],
            198 => [
                'languageCode' => 'uz',
                'displayName' => 'Uzbek',
            ],
            199 => [
                'languageCode' => 've',
                'displayName' => 'Venda',
            ],
            200 => [
                'languageCode' => 'vi',
                'displayName' => 'Vietnamese',
            ],
            201 => [
                'languageCode' => 'vo',
                'displayName' => 'Volapük',
            ],
            202 => [
                'languageCode' => 'vro',
                'displayName' => 'Võro',
            ],
            203 => [
                'languageCode' => 'cy',
                'displayName' => 'Welsh',
            ],
            204 => [
                'languageCode' => 'fy',
                'displayName' => 'Western Frisian',
            ],
            205 => [
                'languageCode' => 'wo',
                'displayName' => 'Wolof',
            ],
            206 => [
                'languageCode' => 'xh',
                'displayName' => 'Xhosa',
            ],
            207 => [
                'languageCode' => 'yi',
                'displayName' => 'Yiddish',
            ],
            208 => [
                'languageCode' => 'yo',
                'displayName' => 'Yoruba',
            ],
            209 => [
                'languageCode' => 'zu',
                'displayName' => 'Zulu',
            ],
        ];

        foreach ($languages as $language){
            Language::firstOrCreate([
                'code' => $language['languageCode']
                ],[
                    'code' => $language['languageCode'],
                    'display_name' => $language['displayName'],
                ]
            );
        }

    }
}
