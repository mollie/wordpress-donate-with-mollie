<?php

class Dmm_Admin {

    private $wpdb;

    /**
     * Dmm_Admin constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        add_action('admin_menu', array($this, 'dmm_admin_menu'));
        add_action('admin_init', array($this, 'dmm_register_settings'));
    }

    /**
     * Admin menu
     *
     * @since 1.0.0
     */
    public function dmm_admin_menu() {
        add_menu_page(
            __('Donate with Mollie', DMM_TXT_DOMAIN),
            __('Donations', DMM_TXT_DOMAIN),
            DMM_PLUGIN_ROLE,
            DMM_PAGE_DONATIONS,
            array(
                $this,
                'dmm_page_donations'
            ),
            'dashicons-money'
        );

        if (get_option('dmm_recurring'))
        {
            add_submenu_page(
                DMM_PAGE_DONATIONS,
                __('Subscriptions', DMM_TXT_DOMAIN) . ' | ' . __('Donate with Mollie', DMM_TXT_DOMAIN),
                __('Subscriptions', DMM_TXT_DOMAIN),
                DMM_PLUGIN_ROLE,
                DMM_PAGE_SUBSCRIPTIONS,
                array(
                    $this,
                    'dmm_page_subscriptions'
                )
            );
            add_submenu_page(
                DMM_PAGE_DONATIONS,
                __('Donors', DMM_TXT_DOMAIN) . ' | ' . __('Donate with Mollie', DMM_TXT_DOMAIN),
                __('Donors', DMM_TXT_DOMAIN),
                DMM_PLUGIN_ROLE,
                DMM_PAGE_DONORS,
                array(
                    $this,
                    'dmm_page_donors'
                )
            );
        }

        add_submenu_page(
            DMM_PAGE_DONATIONS,
            __('Settings', DMM_TXT_DOMAIN) . ' | ' . __('Donate with Mollie', DMM_TXT_DOMAIN),
            __('Settings', DMM_TXT_DOMAIN),
            DMM_PLUGIN_ROLE,
            DMM_PAGE_SETTINGS,
            array(
                $this,
                'dmm_page_settings'
            )
        );

        // Hidden
        add_submenu_page(
            null,
            __('Donation', DMM_TXT_DOMAIN),
            __('Donation', DMM_TXT_DOMAIN),
            DMM_PLUGIN_ROLE,
            DMM_PAGE_DONATION,
            array(
                $this,
                'dmm_page_donation'
            )
        );
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    public function dmm_register_settings() {
        register_setting('dmm-settings-mollie', 'dmm_mollie_apikey');

        register_setting('dmm-settings-recurring', 'dmm_recurring');
        register_setting('dmm-settings-recurring', 'dmm_recurring_interval');
        register_setting('dmm-settings-recurring', 'dmm_name_foundation');

        register_setting('dmm-settings-general', 'dmm_amount');
        register_setting('dmm-settings-general', 'dmm_free_input');
        register_setting('dmm-settings-general', 'dmm_default_amount');
        register_setting('dmm-settings-general', 'dmm_minimum_amount');
        register_setting('dmm-settings-general', 'dmm_payment_description');
        register_setting('dmm-settings-general', 'dmm_methods_display');
        register_setting('dmm-settings-general', 'dmm_redirect_success');
        register_setting('dmm-settings-general', 'dmm_redirect_failure');
        register_setting('dmm-settings-general', 'dmm_projects');

        register_setting('dmm-settings-form', 'dmm_form_fields');
        register_setting('dmm-settings-form', 'dmm_success_cls');
        register_setting('dmm-settings-form', 'dmm_failure_cls');
        register_setting('dmm-settings-form', 'dmm_form_cls');
        register_setting('dmm-settings-form', 'dmm_fields_cls');
        register_setting('dmm-settings-form', 'dmm_button_cls');

        if (get_option('dmm_recurring')){
            $fields = get_option('dmm_form_fields');
            $fields['Name'] = array('active' => 'On', 'required' => 'On');
            $fields['Email address'] = array('active' => 'On', 'required' => 'On');
            update_option('dmm_form_fields', $fields);
        }
    }

    /**
     * Donations table
     *
     * @return string
     * @since 1.0.0
     */
    public function dmm_page_donations() {

        try {
            $mollie = new Mollie_API_Client;
            if (get_option('dmm_mollie_apikey'))
                $mollie->setApiKey(get_option('dmm_mollie_apikey'));
            else
            {
                echo '<div class="error notice"><p>' . esc_html__('No API-key set', DMM_TXT_DOMAIN) . '</p></div>';
                return;
            }


            if (isset($_GET['action']) && $_GET['action'] == 'refund' && isset($_GET['payment']) && check_admin_referer('refund-donation_' . $_GET['payment']))
            {
                $payment = $mollie->payments->get($_GET['payment']);
                if ($payment->canBeRefunded())
                {
                    $refund = $mollie->payments->refund($payment);
                    wp_redirect('?page=' . $_REQUEST['page'] . '&msg=refund-ok');
                }
                else
                    wp_redirect('?page=' . $_REQUEST['page'] . '&msg=refund-nok');
            }

        } catch (Mollie_API_Exception $e) {
            $dmm_msg =  "<div class=\"error notice\"><p>API call failed: " . htmlspecialchars($e->getMessage()) . "</p></div>";
        }


        if (isset($_GET['msg']))
        {
            switch ($_GET['msg'])
            {
                case 'refund-ok':
                    $dmm_msg = '<div class="updated notice"><p>' . esc_html__('The donation is successful refunded to the donator', DMM_TXT_DOMAIN) . '</p></div>';
                    break;
                case 'refund-nok':
                    $dmm_msg = '<div class="error notice"><p>' . esc_html__('The donation can not be refunded', DMM_TXT_DOMAIN) . '</p></div>';
                    break;
                case 'truncate-ok':
                    $dmm_msg = '<div class="updated notice"><p>' . esc_html__('The donations have been successfully removed from the database', DMM_TXT_DOMAIN) . '</p></div>';
                    break;
            }
        }

        $dmmTable = new Dmm_List_Table();
        $dmmTable->prepare_items();
        ?>
        <div class="wrap">
            <h2><?php esc_html_e('Donations', DMM_TXT_DOMAIN) ?></h2>

            <?php
            echo isset($dmm_msg) ? $dmm_msg : '';

            $dmmTable->display();
            ?>
        </div>
    <?php
    }

    public function dmm_page_donation()
    {
        $donation = $this->wpdb->get_row("SELECT * FROM " . DMM_TABLE_DONATIONS . " WHERE id = '" . esc_sql($_REQUEST['id']) . "'");
        ?>
        <div class="wrap">
            <h2><?php esc_html_e('Donation', DMM_TXT_DOMAIN) ?></h2>

            <table class="widefat fixed striped">
                <thead>
                <tr valign="top">
                    <th id="empty" class="manage-column column-empty" style="width:5px;">&nbsp;</th>
                    <th id="a" class="manage-column column-a" style="width: 200px;">&nbsp;</th>
                    <th id="b" class="manage-column column-b">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Name', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->dm_name);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Email address', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->dm_email);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Company name', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->dm_company);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Phone number', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->dm_phone);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Address', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->dm_address);?><br><?php echo esc_html($donation->dm_zipcode . ' ' . $donation->dm_city);?><br><?php echo esc_html($donation->dm_country);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Project', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->dm_project);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Message', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo nl2br(esc_html($donation->dm_message));?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Amount', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b">&euro; <?php echo number_format($donation->dm_amount, 2, ',', '');?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Payment method', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->payment_method);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Payment status', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->dm_status);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Donation ID', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->donation_id);?></td>
                    </tr>
                    <tr>
                        <th class="column-empty"></th>
                        <th class="column-a" scope="row"><strong><?php esc_html_e('Payment ID', DMM_TXT_DOMAIN);?></strong></th>
                        <td class="column-b"><?php echo esc_html($donation->payment_id);?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function dmm_page_donors()
    {
        $dmmTable = new Dmm_Donors_Table();
        $dmmTable->prepare_items();
        ?>
        <div class="wrap">
            <h2><?php esc_html_e('Donors', DMM_TXT_DOMAIN) ?></h2>

            <?php
            echo isset($dmm_msg) ? $dmm_msg : '';

            $dmmTable->display();
            ?>
        </div>
        <?php
    }

    public function dmm_page_subscriptions()
    {
        try {
            $mollie = new Mollie_API_Client;
            if (get_option('dmm_mollie_apikey'))
                $mollie->setApiKey(get_option('dmm_mollie_apikey'));
            else
            {
                echo '<div class="error notice"><p>' . esc_html__('No API-key set', DMM_TXT_DOMAIN) . '</p></div>';
                return;
            }


            if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['subscription']) && check_admin_referer('cancel-subscription_' . $_GET['subscription']))
            {
                $customer = $this->wpdb->get_row("SELECT * FROM " . DMM_TABLE_DONORS . " WHERE id = '" . esc_sql($_GET['customer']) . "'");
                $subscription = $mollie->customers_subscriptions->withParentId($customer->customer_id)->get($_GET['subscription']);
                $cancelledSub = $mollie->customers_subscriptions->withParentId($customer->customer_id)->cancel($subscription);

                if ($cancelledSub->status == 'cancelled')
                {
                    $this->wpdb->query($this->wpdb->prepare("UPDATE " . DMM_TABLE_SUBSCRIPTIONS . " SET sub_status = %s WHERE subscription_id = %s",
                        $cancelledSub->status,
                        $_GET['subscription']
                    ));
                    wp_redirect('?page=' . $_REQUEST['page'] . '&msg=cancel-ok');
                }
                else
                    wp_redirect('?page=' . $_REQUEST['page'] . '&msg=cancel-nok&status=' . $cancelledSub->status);
            }

        } catch (Mollie_API_Exception $e) {
            $dmm_msg =  "<div class=\"error notice\"><p>API call failed: " . htmlspecialchars($e->getMessage()) . "</p></div>";
        }


        if (isset($_GET['msg']))
        {
            switch ($_GET['msg'])
            {
                case 'cancel-ok':
                    $dmm_msg = '<div class="updated notice"><p>' . esc_html__('The subscription is successful cancelled', DMM_TXT_DOMAIN) . '</p></div>';
                    break;
                case 'cancel-nok':
                    $dmm_msg = '<div class="error notice"><p>' . esc_html__('The subscription is not cancelled', DMM_TXT_DOMAIN) . '</p></div>';
                    break;
            }
        }

        $dmmTable = new Dmm_Subscriptions_Table();
        $dmmTable->prepare_items();
        ?>
        <div class="wrap">
            <h2><?php esc_html_e('Subscriptions', DMM_TXT_DOMAIN) ?></h2>

            <?php
            echo isset($dmm_msg) ? $dmm_msg : '';

            $dmmTable->display();
            ?>
        </div>
        <?php
    }

    public function dmm_page_settings()
    {
        if (!isset($_GET['tab']))
            $tab = 'general';
        else
            $tab = $_GET['tab'];
        ?>
        <div class="wrap">
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo DMM_PAGE_SETTINGS ?>" class="nav-tab<?php echo $tab == 'general' ? ' nav-tab-active' : '';?>"><?php esc_html_e('General', DMM_TXT_DOMAIN);?></a>
                <a href="?page=<?php echo DMM_PAGE_SETTINGS ?>&tab=form" class="nav-tab<?php echo $tab == 'form' ? ' nav-tab-active' : '';?>"><?php esc_html_e('Form', DMM_TXT_DOMAIN);?></a>
                <a href="?page=<?php echo DMM_PAGE_SETTINGS ?>&tab=mollie" class="nav-tab<?php echo $tab == 'mollie' ? ' nav-tab-active' : '';?>"><?php esc_html_e('Mollie settings', DMM_TXT_DOMAIN);?></a>
                <a href="?page=<?php echo DMM_PAGE_SETTINGS ?>&tab=recurring" class="nav-tab<?php echo $tab == 'recurring' ? ' nav-tab-active' : '';?>"><?php esc_html_e('Recurring payments', DMM_TXT_DOMAIN);?></a>
            </h2>
            <?php
            settings_errors();

            switch ($tab)
            {
                case 'recurring':
                    $this->dmm_tab_settings_recurring();
                    break;
                case 'mollie':
                    $this->dmm_tab_settings_mollie();
                    break;
                case 'form':
                    $this->dmm_tab_settings_form();
                    break;
                default:
                    $this->dmm_tab_settings_general();
            }
            ?>
        </div>
        <?php
    }

    private function dmm_tab_settings_general()
    {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dmm-settings-general');?>

            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Amounts', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <input type="text" size="50" name="dmm_amount" value="<?php echo esc_attr(get_option('dmm_amount'));?>"><br>
                            <small><?php printf(esc_html__('Separate amounts with /. Example: "%s"', DMM_TXT_DOMAIN), '5,00/10,00/25,00/50,00');?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Free input', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <input type="checkbox" name="dmm_free_input" <?php echo (get_option('dmm_free_input', 0) ? 'checked' : '');?>>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Default amount', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <input type="text" size="50" name="dmm_default_amount" value="<?php echo esc_attr(get_option('dmm_default_amount'));?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Minimum amount', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <input type="text" size="50" name="dmm_minimum_amount" value="<?php echo esc_attr(get_option('dmm_minimum_amount'));?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Payment description', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <input type="text" size="50" name="dmm_payment_description" value="<?php echo esc_attr(get_option('dmm_payment_description', DMM_PAYMENT_DESCRIPTION));?>"><br>
                            <small><?php printf(esc_html__('You can use: %s', DMM_TXT_DOMAIN), '{id} {name} {project} {amount} {company}');?></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Payment methods', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <select name="dmm_methods_display">
                                <option value="list"><?php esc_html_e('Icons & text', DMM_TXT_DOMAIN);?></option>
                                <option value="list_no_icons" <?php echo (get_option('dmm_methods_display') == 'list_no_icons' ? 'selected' : '');?>><?php esc_html_e('Only text', DMM_TXT_DOMAIN);?></option>
                                <option value="list_icons" <?php echo (get_option('dmm_methods_display') == 'list_icons' ? 'selected' : '');?>><?php esc_html_e('Only icons', DMM_TXT_DOMAIN);?></option>
                                <option value="dropdown" <?php echo (get_option('dmm_methods_display') == 'dropdown' ? 'selected' : '');?>><?php esc_html_e('Dropdown', DMM_TXT_DOMAIN);?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><?php esc_html_e('Page after successful donation', DMM_TXT_DOMAIN);?></th>
                        <td class="forminp"><?php $dmm_redirect_success = $this->get_page_id_by_slug(get_option('dmm_redirect_success'));wp_dropdown_pages(array('value_field' => 'post_name', 'selected' => $dmm_redirect_success, 'name' => 'dmm_redirect_success', 'show_option_no_change' => '-- ' . __('Default') . ' --'));?></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc"><?php esc_html_e('Page after failed donation', DMM_TXT_DOMAIN);?></th>
                        <td class="forminp"><?php $dmm_redirect_failure = $this->get_page_id_by_slug(get_option('dmm_redirect_failure'));wp_dropdown_pages(array('value_field' => 'post_name', 'selected' => $dmm_redirect_failure, 'name' => 'dmm_redirect_failure', 'show_option_no_change' => '-- ' . __('Default') . ' --'));?></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Projects', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <textarea rows="10" name="dmm_projects" style="width: 370px;"><?php echo esc_attr(get_option('dmm_projects'));?></textarea><br>
                            <small><?php esc_html_e('Each project on a new line', DMM_TXT_DOMAIN);?></small>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button();?>
        </form>
        <?php
    }

    private function dmm_tab_settings_form()
    {
        $dmm_form_fields = get_option('dmm_form_fields');
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dmm-settings-form');?>

            <h3><?php esc_html_e('Fields', DMM_TXT_DOMAIN);?></h3>
            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <td class="forminp">
                        <table class="widefat fixed striped">
                            <thead>
                                <tr valign="top">
                                    <th id="empty" class="manage-column column-empty" style="width:5px;">&nbsp;</th>
                                    <th id="field" class="manage-column column-field"><?php esc_html_e('Field', DMM_TXT_DOMAIN);?></th>
                                    <th id="active" class="manage-column column-active"><?php esc_html_e('Active', DMM_TXT_DOMAIN);?></th>
                                    <th id="required" class="manage-column column-required"><?php esc_html_e('Required', DMM_TXT_DOMAIN);?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th class="column-empty"></th>
                                    <th class="column-field" scope="row"><?php esc_html_e('Name', DMM_TXT_DOMAIN);?></th>
                                    <td class="column-active"><input type="checkbox" name="dmm_form_fields[Name][active]" <?php echo (isset($dmm_form_fields['Name']['active']) ? 'checked' : '');?>></td>
                                    <td class="column-required"><input type="checkbox" name="dmm_form_fields[Name][required]" <?php echo (isset($dmm_form_fields['Name']['required']) ? 'checked' : '');?>></td>
                                </tr>
                                <tr>
                                    <th class="column-empty"></th>
                                    <th class="column-field" scope="row"><?php esc_html_e('Email address', DMM_TXT_DOMAIN);?></th>
                                    <td class="column-active"><input type="checkbox" name="dmm_form_fields[Email address][active]" <?php echo (isset($dmm_form_fields['Email address']['active']) ? 'checked' : '');?>></td>
                                    <td class="column-required"><input type="checkbox" name="dmm_form_fields[Email address][required]" <?php echo (isset($dmm_form_fields['Email address']['required']) ? 'checked' : '');?>></td>
                                </tr>
                                <tr>
                                    <th class="column-empty"></th>
                                    <th class="column-field" scope="row"><?php esc_html_e('Phone number', DMM_TXT_DOMAIN);?></th>
                                    <td class="column-active"><input type="checkbox" name="dmm_form_fields[Phone number][active]" <?php echo (isset($dmm_form_fields['Phone number']['active']) ? 'checked' : '');?>></td>
                                    <td class="column-required"><input type="checkbox" name="dmm_form_fields[Phone number][required]" <?php echo (isset($dmm_form_fields['Phone number']['required']) ? 'checked' : '');?>></td>
                                </tr>
                                <tr>
                                    <th class="column-empty"></th>
                                    <th class="column-field" scope="row"><?php esc_html_e('Address', DMM_TXT_DOMAIN);?></th>
                                    <td class="column-active"><input type="checkbox" name="dmm_form_fields[Address][active]" <?php echo (isset($dmm_form_fields['Address']['active']) ? 'checked' : '');?>></td>
                                    <td class="column-required"><input type="checkbox" name="dmm_form_fields[Address][required]" <?php echo (isset($dmm_form_fields['Address']['required']) ? 'checked' : '');?>></td>
                                </tr>
                                <tr>
                                    <th class="column-empty"></th>
                                    <th class="column-field" scope="row"><?php esc_html_e('Company name', DMM_TXT_DOMAIN);?></th>
                                    <td class="column-active"><input type="checkbox" name="dmm_form_fields[Company name][active]" <?php echo (isset($dmm_form_fields['Company name']['active']) ? 'checked' : '');?>></td>
                                    <td class="column-required"><input type="checkbox" name="dmm_form_fields[Company name][required]" <?php echo (isset($dmm_form_fields['Company name']['required']) ? 'checked' : '');?>></td>
                                </tr>
                                <tr>
                                    <th class="column-empty"></th>
                                    <th class="column-field" scope="row"><?php esc_html_e('Message', DMM_TXT_DOMAIN);?></th>
                                    <td class="column-active"><input type="checkbox" name="dmm_form_fields[Message][active]" <?php echo (isset($dmm_form_fields['Message']['active']) ? 'checked' : '');?>></td>
                                    <td class="column-required"><input type="checkbox" name="dmm_form_fields[Message][required]" <?php echo (isset($dmm_form_fields['Message']['required']) ? 'checked' : '');?>></td>
                                </tr>
                                <tr>
                                    <th class="column-empty"></th>
                                    <th class="column-field" scope="row"><?php esc_html_e('Project', DMM_TXT_DOMAIN);?></th>
                                    <td class="column-active"><input type="checkbox" name="dmm_form_fields[Project][active]" <?php echo (isset($dmm_form_fields['Project']['active']) ? 'checked' : '');?>></td>
                                    <td class="column-required"><input type="checkbox" name="dmm_form_fields[Project][required]" <?php echo (isset($dmm_form_fields['Project']['required']) ? 'checked' : '');?>></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>

            <h3><?php esc_html_e('Classes', DMM_TXT_DOMAIN);?></h3>
            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label><?php esc_html_e('Form class', DMM_TXT_DOMAIN);?></label>
                    </th>
                    <td class="forminp">
                        <input type="text" size="50" name="dmm_form_cls" value="<?php echo esc_attr(get_option('dmm_form_cls'));?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label><?php esc_html_e('Form fields class', DMM_TXT_DOMAIN);?></label>
                    </th>
                    <td class="forminp">
                        <input type="text" size="50" name="dmm_fields_cls" value="<?php echo esc_attr(get_option('dmm_fields_cls'));?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label><?php esc_html_e('Form button class', DMM_TXT_DOMAIN);?></label>
                    </th>
                    <td class="forminp">
                        <input type="text" size="50" name="dmm_button_cls" value="<?php echo esc_attr(get_option('dmm_button_cls'));?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label><?php esc_html_e('Message success class', DMM_TXT_DOMAIN);?></label>
                    </th>
                    <td class="forminp">
                        <input type="text" size="50" name="dmm_success_cls" value="<?php echo esc_attr(get_option('dmm_success_cls'));?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label><?php esc_html_e('Message failure class', DMM_TXT_DOMAIN);?></label>
                    </th>
                    <td class="forminp">
                        <input type="text" size="50" name="dmm_failure_cls" value="<?php echo esc_attr(get_option('dmm_failure_cls'));?>">
                    </td>
                </tr>
                </tbody>
            </table>

            <?php submit_button();?>
        </form>
        <?php
    }

    private function dmm_tab_settings_recurring()
    {
        $recurring = false;
        try {
            $mollie = new Mollie_API_Client;
            if (get_option('dmm_mollie_apikey'))
                $mollie->setApiKey(get_option('dmm_mollie_apikey'));
            else
            {
                echo '<div class="error notice"><p>' . esc_html__('No API-key set', DMM_TXT_DOMAIN) . '</p></div>';
                return;
            }

            foreach ($mollie->methods->all() as $method)
                if ($method->id == 'directdebit' || $method->id == 'creditcard')
                    $recurring = true;

        } catch (Mollie_API_Exception $e) {
            echo "<div class=\"error notice\"><p>API call failed: " . htmlspecialchars($e->getMessage()) . "</p></div>";
        }
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dmm-settings-recurring');?>

            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label><?php esc_html_e('Activate recurring payments', DMM_TXT_DOMAIN);?></label>
                    </th>
                    <td class="forminp">
                        <input type="checkbox" name="dmm_recurring" <?php echo get_option('dmm_recurring') ? 'checked' : '';?> value="1" <?php echo $recurring ? '' : 'disabled';?>><br>
                        <small><?php esc_html_e('Creditcard or SEPA Direct Debit is necessary', DMM_TXT_DOMAIN);?></small>
                    </td>
                </tr>


                <?php if (get_option('dmm_recurring')) {
                    $intervals = get_option('dmm_recurring_interval');
                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Name of the foundation', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <input type="text" size="50" name="dmm_name_foundation" value="<?php echo esc_attr(get_option('dmm_name_foundation'));?>">
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label><?php esc_html_e('Possible intervals', DMM_TXT_DOMAIN);?></label>
                        </th>
                        <td class="forminp">
                            <label><input type="checkbox" name="dmm_recurring_interval[month]" <?php echo isset($intervals['month']) ? 'checked' : '';?> value="1"> <?php esc_html_e('Monthly', DMM_TXT_DOMAIN);?></label><br>
                            <label><input type="checkbox" name="dmm_recurring_interval[quarter]" <?php echo isset($intervals['quarter']) ? 'checked' : '';?> value="1"> <?php esc_html_e('Each quarter', DMM_TXT_DOMAIN);?></label><br>
                                <label><input type="checkbox" name="dmm_recurring_interval[year]" <?php echo isset($intervals['year']) ? 'checked' : '';?> value="1"> <?php esc_html_e('Annually', DMM_TXT_DOMAIN);?></label>
                        </td>
                    </tr>
                <?php } ?>

                </tbody>
            </table>

            <?php submit_button();?>
        </form>
        <?php
    }

    private function dmm_tab_settings_mollie()
    {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('dmm-settings-mollie');?>

            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <label><?php esc_html_e('API-key', DMM_TXT_DOMAIN);?></label>
                    </th>
                    <td class="forminp">
                        <input type="text" size="50" name="dmm_mollie_apikey" value="<?php echo esc_attr(get_option('dmm_mollie_apikey'));?>"><br>
                        <small><?php esc_html_e('Starts with live_ or test_', DMM_TXT_DOMAIN);?></small>
                    </td>
                </tr>
                </tbody>
            </table>

            <?php submit_button();?>
        </form>
        <?php
    }

    public function get_page_id_by_slug($slug)
    {
        $id = $this->wpdb->get_var("SELECT id FROM " . $this->wpdb->posts . " WHERE post_name = '" . esc_sql($slug) . "' AND post_type = 'page'");
        return $id;
    }
}