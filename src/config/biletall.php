<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Environment & credentials
    |--------------------------------------------------------------------------
    |
    | When sandbox is enabled the test WSDL is used, otherwise the live one.
    |
    */

    'sandbox' => (bool) env('BILETALL_SANDBOX', false),

    'username' => env('BILETALL_WS_USERNAME'),

    'password' => env('BILETALL_WS_PASSWORD'),

    'wsdl' => [
        'test' => env('BILETALL_TEST_WSDL', 'http://62.248.56.228/WSTEST/Service.asmx?wsdl'),
        'live' => env('BILETALL_LIVE_WSDL', 'https://ws.biletall.com/Service.asmx?wsdl'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SOAP client
    |--------------------------------------------------------------------------
    |
    | Options for the "biletall" service registered in siberfx/laravel-soap.
    | Any key supported by Soap::add() can be used here.
    |
    */

    'soap' => [
        'soap_version' => SOAP_1_2,
        'trace' => (bool) env('BILETALL_TRACE', false),
        'cache' => (int) env('BILETALL_WSDL_CACHE', WSDL_CACHE_BOTH),
        'connection_timeout' => (int) env('BILETALL_CONNECTION_TIMEOUT', 30),
        'verify_ssl' => (bool) env('BILETALL_VERIFY_SSL', true),
        'encoding' => 'UTF-8',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Publish routes/siberfx/biletall.php to override the package routes.
    |
    */

    'routes' => [
        'enabled' => (bool) env('BILETALL_ROUTES', true),
        'prefix' => 'bus',
        'middleware' => [],
    ],

    // Seconds to cache the KaraNoktaGetirKomut location list.
    'locations_cache_ttl' => (int) env('BILETALL_LOCATIONS_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Default feature flags
    |--------------------------------------------------------------------------
    |
    | A real OTipOzellik value: every character is the true (1) / false (0)
    | state of the feature whose "tip" equals its position, e.g. position 3
    | is WC. When enabled it is used instead of the value BiletAll returns,
    | which is useful against the test service. null = only in "local".
    |
    */

    'default_feature_flags' => env('BILETALL_DEFAULT_FEATURE_FLAGS', '11111111001000100000000000000000000000000000000000'),

    'use_default_feature_flags' => env('BILETALL_USE_DEFAULT_FEATURE_FLAGS'),

    // Public path (resolved with asset()) holding the feature images below.
    'feature_image_path' => 'images/bus-features',

    /*
    |--------------------------------------------------------------------------
    | Bus features
    |--------------------------------------------------------------------------
    |
    | OTipOzellik is shipped as a string of 0/1 flags; the position of each
    | "1" is the "tip" of an available feature.
    |
    */

    'features' => [
        [
            'tip' => 0,
            'tip_aciklama' => 'İnternet',
            'tip_detay' => 'Sağladığımız internet bağlantısını bilgisayarınızda kullanabilirsiniz.',
            'tip_image' => 'Internet.gif'
        ],
        [
            'tip' => 1,
            'tip_aciklama' => 'Rahat Koltuk',
            'tip_detay' => 'Bu araçta geniş ve rahat koltuk bulunmaktadır.',
            'tip_image' => 'Rahat_Koltuk.gif'
        ],
        [
            'tip' => 2,
            'tip_aciklama' => 'Koltuk Ekranı (Uydu Yayını)',
            'tip_detay' => 'Koltuk ekranları üzerinden birçok interaktif medya ve uydu yayını izlenebilmektedir.',
            'tip_image' => 'Koltuk_Ekrani.gif'
        ],
        [
            'tip' => 3,
            'tip_aciklama' => 'WC',
            'tip_detay' => 'Araçta Tuvalet Bulunmaktadır.',
            'tip_image' => 'WC.gif'
        ],
        [
            'tip' => 4,
            'tip_aciklama' => 'TV (Genel)',
            'tip_detay' => 'Araçta uygun saatlerde Genel TV ve Video yayını yapılmaktadır.',
            'tip_image' => 'TV_Genel.Gif'
        ],
        [
            'tip' => 5,
            'tip_aciklama' => 'Digiturk',
            'tip_detay' => 'Digiturk yayını bulunmaktadır.',
            'tip_image' => 'Digiturk.gif'
        ],
        [
            'tip' => 6,
            'tip_aciklama' => 'Kulaklık',
            'tip_detay' => 'Seyahat sırasında Kulaklık verilebilmektedir.',
            'tip_image' => 'Kulaklik.gif'
        ],
        [
            'tip' => 7,
            'tip_aciklama' => 'Müzik Yayını (Genel)',
            'tip_detay' => 'Araçta uygun saatlerde Genel Radyo ve Müzik yayını yapılmaktadır.',
            'tip_image' => 'Muzik_Genel.gif'
        ],
        [
            'tip' => 8,
            'tip_aciklama' => 'Müzik Yayını (Koltuk)',
            'tip_detay' => 'Koltuk üzerindeki kulaklık çıkışlarından çok kanallı müzik yayını yapılmaktadır.',
            'tip_image' => 'Muzik_Koltuk.gif'
        ],
        [
            'tip' => 9,
            'tip_aciklama' => 'Cep Telefonu (Serbest)',
            'tip_detay' => 'Seyahat sırasında cep telefonunuzu kullanabilirsiniz.',
            'tip_image' => 'Cep.gif'
        ],
        [
            'tip' => 10,
            'tip_aciklama' => '220 Volt Priz',
            'tip_detay' => 'Seyahat Sırasında 220 volt ile çalışan cihazlarınızı kullanabileceğiniz priz bulunmaktadır.',
            'tip_image' => 'priz.gif'
        ],
        [
            'tip' => 11,
            'tip_aciklama' => 'Koltuk Ekranı (MIT)',
            'tip_detay' => 'Koltuk ekranları üzerinden Multimedya,Internet ve Tv yayını kullanılabilemektedir.',
            'tip_image' => 'Koltuk_MIT.gif'
        ],
        [
            'tip' => 12,
            'tip_aciklama' => 'Namaz Vakitlerinde Durur',
            'tip_detay' => 'Namaz Vakitlerinde Durur',
            'tip_image' => 'Cami.gif'
        ],
        [
            'tip' => 13,
            'tip_aciklama' => 'Ligtv',
            'tip_detay' => 'Ligtv yayını bulunmaktadır.',
            'tip_image' => 'ligtv.gif'
        ],
        [
            'tip' => 14,
            'tip_aciklama' => 'Koltuk Ekranı (10 inç)',
            'tip_detay' => 'Bu araçta 10 inç Koltuk Ekranı bulunmaktadır.',
            'tip_image' => 'Koltuk_Ekrani10.gif'
        ],
        [
            'tip' => 15,
            'tip_aciklama' => 'Okuma Lambası',
            'tip_detay' => 'Bu araçta Koltuk Arkası Okuma Lambası bulunmaktadır.',
            'tip_image' => 'OkumaLambasi.gif'
        ],
        [
            'tip' => 16,
            'tip_aciklama' => 'Radyo (Kişisel)',
            'tip_detay' => 'Bu araçta Kişisel Radyo bulunmaktadır.',
            'tip_image' => 'KisiselRadyo.gif'
        ],
        [
            'tip' => 17,
            'tip_aciklama' => 'Koltuk Ekranı (13 inç)',
            'tip_detay' => 'Bu araçta 13 inç Koltuk Ekranı bulunmaktadır.',
            'tip_image' => 'Koltuk_Ekrani13.gif'
        ],
        [
            'tip' => 18,
            'tip_aciklama' => 'USB Giriş',
            'tip_detay' => 'Bu araçta müzik, video ve şarj için kullanabileceğiniz USB girişi bulunmaktadır.',
            'tip_image' => 'usb.gif'
        ],
        [
            'tip' => 19,
            'tip_aciklama' => 'Kahvaltı',
            'tip_detay' => 'Bu araçta kahvaltı verilmektedir.',
            'tip_image' => 'Kahvalti.gif'
        ],
        [
            'tip' => 20,
            'tip_aciklama' => 'Sıcak Yemek',
            'tip_detay' => 'Bu araçta sıcak yemek verilmektedir.',
            'tip_image' => 'sicakyemek.gif'
        ],
        [
            'tip' => 21,
            'tip_aciklama' => 'Masajlı Koltuk',
            'tip_detay' => 'Bu araçta masajlı koltuk bulunmaktadır.',
            'tip_image' => 'masajlikoltuk.gif'
        ],
        [
            'tip' => 22,
            'tip_aciklama' => 'Antiviral Koruma',
            'tip_detay' => 'Bu araçta aktif filtre antiviral koruma bulunmaktadır.',
            'tip_image' => 'antiviralkoruma.gif'
        ]
    ]
];
