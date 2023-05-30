<?php
return [

    //This array contains fileds of the second line and can be used to generates second line in function
    'elda_generate_registration_second_line' => [
        [
            'name' => 'identification_part', 'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'project_DM', 'length' => 2,  'type' => 'a',  'default_value' => 'TM',
        ],

        [
            'name' => 'inventory_designation/processing',  'length' => 2, 'type' => 'a', 'default_value' => 'VR',
        ],

        [
            'name' => 'data_carrier_number',  'length' => 6, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'creation_date',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'creation_time',  'length' => 6, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'manufacturer_name',  'length' => 45, 'type' => 'an', 'default_value' => 'qkinnovation',
        ],

        [
            'name' => 'manufacturer_international_vehicle_registration_number',  'length' => 3, 'type' => 'an', 'default_value' => 'D',
        ],

        [
            'name' => 'manufacturer_zip_code',  'length' => 7, 'type' => 'an', 'default_value' => '80801',
        ],

        [
            'name' => 'manufacturer_place',  'length' => 20, 'type' => 'an', 'default_value' => 'Mwnchen',
        ],

        [
            'name' => 'manufacturer_street',  'length' => 30, 'type' => 'an', 'default_value' => 'Franz Joseph Strasse 14',
        ],

        [
            'name' => 'version_number_of_the_record_structures',  'length' => 2, 'type' => 'n', 'default_value' => '01',
        ],

        [
            'name' => 'manufacturer_telephone_number',  'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'software_identification_number',  'length' => 70, 'type' => 'an', 'default_value' => 'ELDA Client Software V5.2.0.4845',
        ],

        [
            'name' => 'version_number_of_message_file',  'length' => 5, 'type' => 'an', 'default_value' => '3.0',
        ],
    ],


    //This array contains fileds of the second line and can be used to generates Third line in function

    'elda_generate_registration_third_line' => [
        [
            'name' => 'identification_part', 'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'reference_value', 'length' => 40,  'type' => 'an',  'default_value' => '',
        ],

        [
            'name' => 'reference_value_of_the_original_report',  'length' => 40, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'contribution_account_number',  'length' => 10, 'type' => 'an', 'default_value' => '4307356',
        ],

        [
            'name' => 'employer_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'employer_phone_number',  'length' => 50, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Employer_email_address',  'length' => 60, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'first_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'second_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Insurance_number',  'length' => 10, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Date_of_birth',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Reference_value_of_the_VSNR_requirement',  'length' => 40, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'family_name',  'length' => 70, 'type' => 'an', 'default_value' => 'Mustermann',
        ],

        [
            'name' => 'first_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'On/off_date_and_change_date',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Change_date_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Correct_registration/cancellation_date',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Employment_area',  'length' => 2, 'type' => 'an', 'default_value' => '01',
        ],

        [
            'name' => 'Insignificance',  'length' => 1, 'type' => 'an', 'default_value' => 'N',
        ],

        [
            'name' => 'Free_service_contract',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'End_of_employment',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Reason_for deregistration_code',  'length' => 2, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Reason_for_deregistration_text',  'length' => 20, 'type' => 'an', 'default_value' => '',
        ],


        [
            'name' => 'Termination_compensation',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Termination_compensation_up_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Holiday_compensation_from',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Holiday_compensation_up_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Company_pension_contribution-AB ',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Company_pension_END',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Company_pension',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],
    ],


    //This array contains fileds of the second line and can be used to generates fourth line in function

    'elda_generate_registration_fourth_line' => [
        [
            'name' => 'identification_part', 'length' => 20, 'type' => 'an', 'default_value' => '990000003ED915749918',
        ],

        [
            'name' => 'Number_of_sets', 'length' => 6,  'type' => 'n',  'default_value' => '000003',
        ],

        [
            'name' => 'ELDA_serial_number',  'length' => 6, 'type' => 'n', 'default_value' => '000000',
        ],

        [
            'name' => 'Manufacturer_email_address',  'length' => 60, 'type' => 'an', 'default_value' => 'info@qkinnovations.com',
        ],

    ],



    //This array contains fileds of the second line and can be used to generates second line for get insurance nuber
    'elda_request_an_insurancno_second' => [
        [
            'name' => 'identification_part', 'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'project_DM', 'length' => 2,  'type' => 'a',  'default_value' => 'TM',
        ],

        [
            'name' => 'inventory_designation/processing',  'length' => 2, 'type' => 'a', 'default_value' => 'VS',
        ],

        [
            'name' => 'data_carrier_number',  'length' => 6, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'creation_date',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'creation_time',  'length' => 6, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'manufacturer_name',  'length' => 45, 'type' => 'an', 'default_value' => 'qkinnovation',
        ],

        [
            'name' => 'manufacturer_international_vehicle_registration_number',  'length' => 3, 'type' => 'an', 'default_value' => 'D',
        ],

        [
            'name' => 'manufacturer_zip_code',  'length' => 7, 'type' => 'an', 'default_value' => '80801',
        ],

        [
            'name' => 'manufacturer_place',  'length' => 20, 'type' => 'an', 'default_value' => 'Mwnchen',
        ],

        [
            'name' => 'manufacturer_street',  'length' => 30, 'type' => 'an', 'default_value' => 'Franz Joseph Strasse 14',
        ],

        [
            'name' => 'version_number_of_the_record_structures',  'length' => 2, 'type' => 'n', 'default_value' => '01',
        ],

        [
            'name' => 'manufacturer_telephone_number',  'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'software_identification_number',  'length' => 70, 'type' => 'an', 'default_value' => 'ELDA Client Software V5.2.0.4845',
        ],

        [
            'name' => 'version_number_of_message_file',  'length' => 5, 'type' => 'an', 'default_value' => '3.0',
        ],
    ],


    //This array contains fileds of the second line and can be used to generates fourth line in function

    'elda_request_an_insurancno_third' => [
        [
            'name' => 'identification_part', 'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'reference_value', 'length' => 40,  'type' => 'an',  'default_value' => '',
        ],

        [
            'name' => 'contribution_account_number',  'length' => 10, 'type' => 'an', 'default_value' => '4307356',
        ],

        [
            'name' => 'employer_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'employer_phone_number',  'length' => 50, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Employer_email_address',  'length' => 60, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'first_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'second_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Date_of_birth',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],



        [
            'name' => 'family_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'former_family_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'first_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'academic_degree',  'length' => 30, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'academic_degree',  'length' => 30, 'type' => 'an', 'default_value' => '',
        ],
        [
            'name' => 'gender',  'length' => 1, 'type' => 'n', 'default_value' => '',
        ],
        [
            'name' => 'nationality',  'length' => 3, 'type' => 'an', 'default_value' => '',
        ],
        [
            'name' => 'place_of_residence,international_vehicle_registration_number',  'length' => 3, 'type' => 'an', 'default_value' => 'A',
        ],
        [
            'name' => 'city_zip_code',  'length' => 9, 'type' => 'an', 'default_value' => '',
        ],
        [
            'name' => 'place_of_residence_placename',  'length' => 50, 'type' => 'an', 'default_value' => '',
        ],
        [
            'name' => 'place_of_residence_streetname',  'length' => 50, 'type' => 'an', 'default_value' => '',
        ],
        [
            'name' => 'place_of_residence,house_number',  'length' => 10, 'type' => 'an', 'default_value' => '55',
        ],
        [
            'name' => 'Residence,floor/door,rest',  'length' => 10, 'type' => 'an', 'default_value' => '',
        ],

    ],


    'deregistration_elda_third_line' => [
        [
            'name' => 'identification_part', 'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'reference_value', 'length' => 40,  'type' => 'an',  'default_value' => '',
        ],

        [
            'name' => 'reference_value_of_the_original_report',  'length' => 40, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'contribution_account_number',  'length' => 10, 'type' => 'an', 'default_value' => '4307356',
        ],

        [
            'name' => 'employer_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'employer_phone_number',  'length' => 50, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Employer_email_address',  'length' => 60, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'first_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'second_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Insurance_number',  'length' => 10, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Date_of_birth',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Reference_value_of_the_VSNR_requirement',  'length' => 40, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'family_name',  'length' => 70, 'type' => 'an', 'default_value' => 'Mustermann',
        ],

        [
            'name' => 'first_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'On/off_date_and_change_date',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Change_date_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Correct_registration/cancellation_date',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Employment_area',  'length' => 2, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Insignificance',  'length' => 1, 'type' => 'an', 'default_value' => 'N',
        ],

        [
            'name' => 'Free_service_contract',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'End_of_employment',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Reason_for deregistration_code',  'length' => 2, 'type' => 'an', 'default_value' => '01',
        ],

        [
            'name' => 'Reason_for_deregistration_text',  'length' => 20, 'type' => 'an', 'default_value' => '',
        ],


        [
            'name' => 'Termination_compensation',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Termination_compensation_up_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Holiday_compensation_from',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Holiday_compensation_up_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Company_pension_contribution-AB ',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Company_pension_END',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Company_pension',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],
    ],


    'cancelregistration_elda_third_line' => [
        [
            'name' => 'identification_part', 'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'reference_value', 'length' => 40,  'type' => 'an',  'default_value' => '',
        ],

        [
            'name' => 'reference_value_of_the_original_report',  'length' => 40, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'contribution_account_number',  'length' => 10, 'type' => 'an', 'default_value' => '4307356',
        ],

        [
            'name' => 'employer_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'employer_phone_number',  'length' => 50, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Employer_email_address',  'length' => 60, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'first_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'second_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Insurance_number',  'length' => 10, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Date_of_birth',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Reference_value_of_the_VSNR_requirement',  'length' => 40, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'family_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'first_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'On/off_date_and_change_date',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Change_date_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Correct_registration/cancellation_date',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Employment_area',  'length' => 2, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Insignificance',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Free_service_contract',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'End_of_employment',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Reason_for deregistration_code',  'length' => 2, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Reason_for_deregistration_text',  'length' => 20, 'type' => 'an', 'default_value' => '',
        ],


        [
            'name' => 'Termination_compensation',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Termination_compensation_up_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Holiday_compensation_from',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Holiday_compensation_up_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Company_pension_contribution-AB ',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Company_pension_END',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Company_pension',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],
    ],


    'cancellation_elda_third_line' => [
        [
            'name' => 'identification_part', 'length' => 20, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'reference_value', 'length' => 40,  'type' => 'an',  'default_value' => '',
        ],

        [
            'name' => 'reference_value_of_the_original_report',  'length' => 40, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'contribution_account_number',  'length' => 10, 'type' => 'an', 'default_value' => '4307356',
        ],

        [
            'name' => 'employer_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'employer_phone_number',  'length' => 50, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Employer_email_address',  'length' => 60, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'first_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'second_free_information_field_for_the_employer',  'length' => 12, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Insurance_number',  'length' => 10, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Date_of_birth',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Reference_value_of_the_VSNR_requirement',  'length' => 40, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'family_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'first_name',  'length' => 70, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'On/off_date_and_change_date',  'length' => 8, 'type' => 'n', 'default_value' => '',
        ],

        [
            'name' => 'Change_date_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Correct_registration/cancellation_date',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Employment_area',  'length' => 2, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Insignificance',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Free_service_contract',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'End_of_employment',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Reason_for deregistration_code',  'length' => 2, 'type' => 'an', 'default_value' => '',
        ],

        [
            'name' => 'Reason_for_deregistration_text',  'length' => 20, 'type' => 'an', 'default_value' => '',
        ],


        [
            'name' => 'Termination_compensation',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Termination_compensation_up_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Holiday_compensation_from',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Holiday_compensation_up_to',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Company_pension_contribution-AB ',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],
        [
            'name' => 'Company_pension_END',  'length' => 8, 'type' => 'n', 'default_value' => '00000000',
        ],

        [
            'name' => 'Company_pension',  'length' => 1, 'type' => 'an', 'default_value' => '',
        ],
    ],

];
