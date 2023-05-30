<?php
return [
    'confirm_booking' => [
        'description' => 'Deine Buchungsnummer #:booking_number ist für den Kurs :course_name bestätigt.',
        'description_instructor' => 'Ihnen wurde eine neue Buchung zugewiesen #:booking_number im Kurs :course_name.',
    ],


    'multiple_booking'  =>  [
        'description' =>  'Neue Buchung wurde von Book2ski hinzugefügt. #:booking_number ist für den Kurs :course_name bestätigt.',
    ],

    'update_booking'  =>  [
        'description' => 'Ihre Buchung: #:booking_number wurde aktualisiert auf :update_at.',
    ],

    'booking_alert'  =>  [
        'general_alert' => 'Ihre Buchung #:booking_number Kursalarm für :course_name beim :fleg',
        'five_pm' => 'Kursbenachrichtigung: Ihre Buchung #:booking_number zum :course_name startet gleich!',
        'two_hour_ago' => 'Ihre Buchung #:booking_number Kursalarm für :course_name beim :fleg',
    ],

    'invoice_course_end_paid'  =>  [
        'description' => 'Rechnung für Ihre Buchung #:booking_number Kurs :course_name Bezahlt.',
    ],

    'invoice_course_end'  =>  [
        'description' => 'Der Rechnungsstatus für Ihre Buchung #:booking_number vom Kurs :course_name ist: unbezahlt.',
    ],

    'course_review'  =>  [
        'description' => 'Kursbewertung zur Buchung #:booking_number.',
    ],

    'customer_course_review'  =>  [
        'description' => 'Kursbewertung zur Buchung #:booking_number Kurs :course_name.',
    ],

    'password_reset'  =>  [
        'description' => 'Der Link zum Zurücksetzen des Passworts wurde an :update_at gesendet.',
    ],

    'assign_training_material'  =>  [
        'description' => 'Neues Unterrichtsmaterial zugewiesen von :instructor_name für :update_at.',
    ],
    'customer_payment_success' => ':customer_name - Ihre Zahlung wurde erfolgreich abgeschlossen :payment_at.',
    'consolidated_booking_invoice' => ':name Ihre konsolidierte Rechnung ist hier! :created_at.',
    'cancellation_booking_invoice' => ':name Ihre Stornierungsrechnung finden Sie hier! :created_at.',
    'admin_alert' => 'Element3 CRM hat einen neuen Benutzer hinzugefügt :created_at.',
    'draft_booking' => [
        'description' => 'Ihre Buchung #:booking_number ist ein Entwurf für :course_name.',
    ],
    'quote_offer_invoice' => 'Angebotsrechnung',
    'booking_invoice' => 'Ihre Buchung #:booking_number Rechnung :invoice_number',
    'season_ticket_invoice' => 'Dauerkartenrechnung',
    'two_week_invoice_reminder' => 'Erinnerung für Ihre Buchung #:booking_number Rechnung :invoice_number'
];
