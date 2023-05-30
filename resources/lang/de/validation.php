<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'=>'Das :attribute muss akzeptiert werden.',
    'active_url'=>'Das :attribute ist keine gültige URL.',
    'after'=>'Das :attribute muss ein Datum nach :date sein.',
    'after_or_equal'=>'Das :attribute muss ein Datum nach oder gleich :date sein.',
    'alpha'=>'Das :attribute darf nur Buchstaben enthalten.',
    'alpha_dash'=>'Das :attribute darf nur Buchstaben, Zahlen, Striche und Unterstriche enthalten.',
    'alpha_num'=>'Das :attribute darf nur Buchstaben und Zahlen enthalten.',
    'array'=>'Das :attribute muss ein Array sein.',
    'before'=>'Das :attribute muss ein Datum vor :date sein.',
    'before_or_equal'=>'Das :attribute muss ein Datum vor oder gleich :date sein.',

    'between' => [
        'numeric'=>'Das :attribute muss zwischen :min und :max liegen.',
        'file'=>'Das :attribute muss zwischen :min und :max Kilobyte liegen.',
        'string'=>'Das :attribute muss zwischen :min und :max Zeichen liegen.',
        'array'=>'Das :attribute muss zwischen :min und :max Elemente haben.',
    ], 
    'boolean'=>'Das Feld :attribute muss wahr oder falsch sein.',
    'confirmed'=>'Die Bestätigung :attribute stimmt nicht überein.',
    'date'=>':attribute ist kein gültiges Datum.',
    'date_equals'=>'Das :attribute muss ein Datum gleich :date sein.',
    'date_format'=>'Das :attribute entspricht nicht dem format :format.',
    'different'=>'Das :attribute und :other müssen unterschiedlich sein.',
    'digits'=>'Das :attribute muss aus den Ziffern :digits bestehen.',
    'digits_between'=>'Das :attribute muss zwischen :min und :max Ziffern liegen.',
    'dimensions'=>'Das :attribute hat ungültige Bildabmessungen.',
    'distinct'=>'Das Feld :attribute hat einen doppelten Wert.',
    'email'=>'Das :attribute muss eine gültige E-Mail-Adresse sein.',
    'ends_with'=>'Das :attribute muss mit einem der folgenden Werte enden: :values',
    'exists'=>'Das ausgewählte :attribute ist ungültig.',
    'file'=>'Das :attribute muss eine Datei sein.',
    'filled'=>'Das Feld :attribute muss einen Wert haben.',

    'gt' => [
        'numeric'=>'Das :attribute muss größer als der Wert :value sein.',
        'file'=>'Das :attribute muss größer als der Wert :value kilobytes sein.',
        'string'=>'Das :attribute muss größer als :value Zeichen sein.',
        'array'=>'Das :attribute muss mehr als :value Element haben.',
    ],
    'gte' => [
        'numeric'=>'Das :attribute muss größer oder gleich als :value sein.',
        'file'=>'Das :attribute muss größer oder gleich als :value kilobytes sein.',
        'string'=>'Das :attribute muss größer oder gleich als :value Zeichen sein.',
        'array'=>'Das :attribute muss :value items oder mehr haben.',
    ],
    'image'=>'Das :attribute muss ein Bild sein.',
    'in'=>'Das :attribute ist ungültig.',
    'in_array'=>'Das Feld :attribute existiert nicht in :other.',
    'integer'=>'Das :attribute muss eine ganze Zahl sein.',
    'ip'=>'Das :attribute muss eine gültige IP-Adresse sein.',
    'ipv4'=>'Das :attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6'=>'Das :attribute muss eine gültige IPv6-Adresse sein.',
    'json'=>'Das :attribute muss ein gültiger JSON-String sein.',
    'lt' => [
        'numeric'=>'Das :attribute muss kleiner als :value sein.',
        'file'=>'Das :attribute muss kleiner als der Wert :value kilobytes sein.',
        'string'=>'Das :attribute muss kleiner als :value Zeichen haben.',
        'array'=>'Das :attribute muss kleiner als :value Elemente sein.',
    ],
    'lte' => [
        'numeric'=>'Das :attribute muss kleiner oder gleich als :value sein.',
        'file'=>'Das :attribute muss kleiner oder gleich als :value kilobytes sein.',
        'string'=>'Das :attribute muss kleiner oder gleich als :value Zeichen haben.',
        'array'=>'Das :attribute darf nicht mehr als :value Elemente haben.',
    ],
    'max' => [
        'numeric'=>'Das :attribute darf nicht größer als :max sein.',
        'file'=>'Das :attribute darf nicht größer als :max Kilobytes sein.',
        'string'=>'Das :attribute darf nicht größer als :max Zeichen haben.',
        'array'=>'Das :attribute darf nicht mehr als :max Elemente enthalten.',
    ],
   'mimes'=>'Das :attribute muss eine Datei vom Typ: :values sein.',
   'mimetypes'=>'Das :attribute muss eine Datei vom Typ: :values sein.',
    'min' => [
        'numeric'=>'Das :attribute muss mindestens :min. sein.',
        'file'=>'Das :attribute muss mindestens :min Kilobytes betragen.',
        'string'=>'Das :attribute muss aus mindestens :min Zeichen bestehen.',
        'array'=>'Das :attribute muss mindestens :min Elemente enthalten.',
    ],
    'not_in'=>'Das ausgewählte :attribute ist ungültig.',
    'not_regex'=>'Das Format des :attribute ist ungültig.',
    'numeric'=>'Das :attribute muss eine Zahl sein.',
    'present'=>'Das Feld :attribute muss vorhanden sein.',
    'regex'=>'Das Format des :attribute ist ungültig.',
    'required'=>'Das Feld :attribute ist erforderlich.',
    'required_if'=>'Das Feld :attribute wird benötigt, wenn :other der Wert :value ist.',
    'required_unless'=>'Das Feld :attribute ist erforderlich, es sei denn, :other steht in :values.',
    'required_with'=>'Das Feld :attribute ist erforderlich, wenn :values vorhanden ist.',
    'required_with_all'=>'Das Feld :attribute ist erforderlich, wenn :values vorhanden sind.',
    'required_without'=>'Das Feld :attribute ist erforderlich, wenn :values nicht vorhanden ist.',
    'required_without_all'=>'Das Feld :attribute wird benötigt, wenn keiner der :values vorhanden ist.',
    'same'=>'Das :attribute und :other müssen übereinstimmen.',
    'size' => [
        'numeric'=>'Das :attribute muss :size sein.',
        'file'=>'Das :attribute muss :size kilobytes sein.',
        'string'=>'Das :attribute muss :size Zeichen sein.',
        'array'=>'Das :attribute muss :size Elemente enthalten.',
    ],
    'starts_with'=>'Das :attribute muss mit einem der folgenden Werte beginnen: :Werte',
    'string'=>'Das :attribute muss eine Zeichenkette sein.',
    'timezone'=>'Das :attribute muss eine gültige Zone sein.',
    'unique'=>'Das :attribute wurde bereits vergeben.',
    'uploaded'=>'Das :attribute konnte nicht hochgeladen werden.',
    'url'=>'Das Format des :attribute ist ungültig.',
    'uuid'=>'Das :attribute muss eine gültige UUID sein.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

    /**Other validation */
    'dob_invalid_formate' => 'Der Dob passt nicht zum Format.',
    'action_invalid' => 'Die ausgewählte Aktion ist ungültig.',

    'start_date_date_format' => 'Das Startdatum entspricht nicht dem Format Y-m-d.',
    'end_date_date_format' => 'Das Enddatum entspricht nicht dem Format Y-m-d.',
    'end_date_after_or_equal' => 'Das Enddatum muss ein Datum nach oder gleich dem Startdatum sein.',
    'start_time_date_format' => 'Die Startzeit entspricht nicht dem Format H:i:s.',
    'end_time_date_format' => 'Die Endzeit entspricht nicht dem Format H:i:s.',
    'end_time_after' => 'Die Endzeit muss ein Datum nach der Startzeit sein.',
    'action_in' => 'Die ausgewählte Aktion ist ungültig.',
    'id_required_if' => 'Das ID-Feld ist erforderlich, wenn die Aktion bearbeitet wird.',
    'id_exists' => 'Die ausgewählte ID ist ungültig.'
    /**End */
];
