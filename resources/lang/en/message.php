<?php

return [
    'login_successful'        => 'Login successful.',
    'logout_successful'       => 'Logout successful.',
    'auth_failed'             => 'These credentials do not match our records.',
    'register_successful'     => 'Register successful.',
    'user_not_found'          => 'User not found.',
    'old_password_incorrect'  => 'The current password is incorrect.',
    'token_incorrect'         => 'The token is incorrect.',
    'updated_successful'      => 'Updated successful.',
    'country_or_city_start_search'  => 'Start entering the name of the city or country. At least 2 letters.',
    'country_or_city_not_found'     => 'No results found. Check that the input is correct.',
    'country_not_found'       => 'Country not found.',
    'city_not_found'          => 'City not found.',
    'category_not_found'      => 'Category not found.',
    'not_filled_profile'      => 'In profile not filled name or surname or birthday.',
    'order_not_found'         => 'Order not found.',
    'order_exists'            => 'Order exists.',
    'route_not_found'         => 'Route not found.',
    'route_exists'            => 'Route exists.',
    'rate_not_found'          => 'Rate not found.',
    'who_start_incorrect'     => 'The field who_start incorrect.',
    'one_rate_per_order'      => 'Can be only one basic rate per order.',
    'three_rate_per_route'    => 'Сan be max three basic rate per route.',
    'differs_from_basic_rate' => 'The parameter differs from the basic rate.',
    'not_owner_basic_rate'    => 'You not owner basic rate.',
    'not_last_rate'           => 'Not last rate.',
    'rate_not_accepted'       => 'Rate not accepted.',
    'rate_accepted'           => 'Rate accepted.',
    'unique_review'           => 'You have already reviewed this job',
    'review_not_allowed'      => 'You can not leave review for this job',
    'review_not_ready'        => 'You can not leave review for unfinished job',
    'type_freelancer'         => 'Freelancer',
    'type_creator'            => 'Creator',
    'not_have_permission'     => 'You don\'t have permission.',
    'already_have_complaint'  => 'There is already a complaint from you.',
    'chat_not_found'          => 'Chat not found.',
    'exists_active_statement' => 'Exists active statement.',
    'statement_max_limit'     => 'Was created the maximum number of prolongations.',
    'deadline_not_arrived'    => 'The deadline has not arrived.',
    'statement_not_found'     => 'Statement not found.',
    'job_not_found'           => 'Job not found.',
    'image_not_found'         => 'Image not found.',

    'verification_code' => [
        'incorrect'         => 'Verification code is incorrect',
        'send_error'        => 'Error sending verification code',
        'send_by_email'     => 'Verification code sent to address',
        'change_successful' => 'Data updated successfully',
    ],

    # Messages for mailing letters
    'email' => [
        'social_registration' => 'Registration through the social network',
        'send_success'        => 'The message has been sent, you will be contacted shortly.',
        'send_error'          => 'There was an error sending your message.',
    ],

    # User attributes
    'user' => [
        'genders' => [
            'male'    => 'Male',
            'female'  => 'Female',
            'unknown' => 'Unknown',
        ],
        'validations' => [
            'valid'    => 'Verified',
            'no_valid' => 'Not verified',
        ],
        'statuses' => [
            'active'     => 'Active',
            'not_active' => 'Not active',
            'banned'     => 'Banned',
            'removed'    => 'Removed',
        ],
    ],

    # Order attributes
    'order' => [
        'statuses' => [
            'active'     => 'Active',
            'in_work'    => 'In work',
            'closed'     => 'Closed',
            'banned'     => 'Banned',
            'successful' => 'Successful',
        ],
    ],

    # Route attributes
    'route' => [
        'statuses' => [
            'active'     => 'Active',
            'in_work'    => 'In work',
            'closed'     => 'Closed',
            'banned'     => 'Banned',
            'successful' => 'Successful',
        ],
    ],

    # Rate attributes
    'rate' => [
        'statuses' => [
            'active'   => 'Active',
            'canceled' => 'Canceled',
            'rejected' => 'Rejected',
            'accepted' => 'Accepted',
            'buyed'    => 'Buyed',
            'approved' => 'Approved',
            'dispute'  => 'Dispute',
            'done'     => 'Done',
            'banned'   => 'Banned',
        ],
    ],

];
