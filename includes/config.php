<?php

// General
define('DMM_PLUGIN_ROLE', 'administrator');
define('DMM_WEBHOOK', '/dmm-webhook/');

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