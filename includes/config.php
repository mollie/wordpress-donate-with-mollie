<?php

// General
define('DMM_PLUGIN_ROLE', 'administrator');

// Database
define('DMM_TABLE_DONATIONS', $wpdb->prefix . 'donate_mollie');
define('DMM_TABLE_DONORS', $wpdb->prefix . 'donate_mollie_donors');
define('DMM_TABLE_SUBSCRIPTIONS', $wpdb->prefix . 'donate_mollie_subscriptions');

// Language
define('DMM_TXT_DOMAIN', 'doneren-met-mollie');

// Pages
define('DMM_PAGE_DONATION', 'doneren-met-mollie-donatie');
define('DMM_PAGE_DONATIONS', 'doneren-met-mollie');
define('DMM_PAGE_DONORS', 'doneren-met-mollie-donateurs');
define('DMM_PAGE_SUBSCRIPTIONS', 'doneren-met-mollie-subscriptions');
define('DMM_PAGE_SETTINGS', 'doneren-met-mollie-instellingen');

// Default values
define('DMM_NAME_LABEL', __('Name', DMM_TXT_DOMAIN));
define('DMM_EMAIL_LABEL', __('E-mail', DMM_TXT_DOMAIN));
define('DMM_AMOUNT_LABEL', __('Amount', DMM_TXT_DOMAIN));
define('DMM_DONATE_BTN', __('Donate', DMM_TXT_DOMAIN));
define('DMM_SUCCESS_MSG', __('Thank you for your donation!', DMM_TXT_DOMAIN));
define('DMM_FAILURE_MSG', __('The payment was not successful, please try again.', DMM_TXT_DOMAIN));
define('DMM_SUCCESS_CLS', '');
define('DMM_FAILURE_CLS', '');
define('DMM_PAYMENT_DESCRIPTION', __('Donation', DMM_TXT_DOMAIN));