<?php
return [
    'front_base_url' => env('ADMINPANEL_URL', 'https://e3-qkmountain.qkinnovations.com/element3-crm'),
    'api_access_key' => 'AAAARlq9gKE:APA91bFrzf9NeNmdo3gP1QlxrymGSDsXWtPkkxZyZtMUe-WI2RUV0-x8PYSyHFjXAkc8Qp8k_rMy2XP0viX6hZtRcy9jXHR5upRJSm779hcevDXSCqHzUo3hohzrmQS1BLFDDesLUG2e',
    'MAIL_HOST'=>env('MAIL_HOST'),
    'STUDENT_LIMIT'=>env('STUDENT_LIMIT', 20),
    'openfireSecret'=>env('OPENFIRESECRET', 'topSecret'),
    'openfireHost'=>env('OPENFIREHOST', '18.194.43.57'),
    'openfirePort'=>env('OPENFIREPORT', '9090'),
    'openfireInternalIp'=>env('OPENFIREIP', 'ip-172-31-44-110.eu-central-1.compute.internal'),
    'conference_ip'=>env('OPENFIREiP', 'conference.ip-172-31-44-110.eu-central-1.compute.internal'),
    'book2ski_email'=>'Book2Ski',
    'chat_admin_email'=>'chatAdmin',
    'element3_admin'=>'Element3 Admin',
    'instructor_daily_office_hour'=>6,

    'concardis' => [
        'PSPID'              => env('CONCARDIS_PSPID', '40F08695'),
        'LANGUAGE'           => env('CONCARDIS_LANGUAGE', 'en_US'),
        'CURRENCY'           => env('CONCARDIS_CURRENCY', 'EUR'),
        'SHA_IN_PASSPHRASE'  => env('CONCARDIS_SHA_IN_PASSPHRASE', 'test-sha-in-pass-phrase'),
        'SHA_OUT_PASSPHRASE' => env('CONCARDIS_SHA_OUT_PASSPHRASE', 'test-sha-out-pass-phrase'),
        'ACTION'             => env('CONCARDIS_ACTION', 'https://secure.payengine.de/ncol/test/orderstandard.asp')
    ],
    'crm_error_page' => env('ADMINPANEL_URL', 'https://e3-qkmountain.qkinnovations.com/element3-crm').env('ERROR_PAGE', '/error-404'),
    'crm_payment_success_page' => env('ADMINPANEL_URL', 'https://e3-qkmountain.qkinnovations.com/element3-crm').env('PAYMENT_SUCCESS_PAGE', '/payment-success'),
    'crm_payment_fail_page' => env('ADMINPANEL_URL', 'https://e3-qkmountain.qkinnovations.com/element3-crm').env('PAYMENT_FAIL_PAGE', '/payment-fail'),
    'crm_payment_page' => env('ADMINPANEL_URL', 'https://e3-qkmountain.qkinnovations.com/element3-crm').env('PAYMENT_PAGE', '/payment'),
    'file_access_seconds' => env('file_access_seconds',60),
    'ELDA_S3_PATH' => 'elda/'
];
