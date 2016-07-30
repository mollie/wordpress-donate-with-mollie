<?php
class Dmm_Webhook {

    private $wpdb;

    /**
     * Hook WordPress
     * @since 2.1.4
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        add_filter('query_vars', array($this, 'add_query_vars'), 0);
        add_action('parse_request', array($this, 'sniff_requests'), 0);
        add_action('init', array($this, 'add_endpoint'), 0);
    }

    /**
     * Add public query vars
     * @param array $vars List of current public query vars
     * @since 2.1.4
     * @return array $vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = '__dmmapi';
        $vars[] = 'sub';
        $vars[] = 'first';
        $vars[] = 'secret';
        return $vars;
    }

    /**
     * Add API Endpoint
     * @since 2.1.4
     * @return void
     */
    public function add_endpoint()
    {
        add_rewrite_rule('^dmm-webhook/first/([0-9]+)/secret/([a-zA-Z0-9]+)/?', 'index.php?__dmmapi=1&first=$matches[1]&secret=$matches[2]', 'top');
        add_rewrite_rule('^dmm-webhook/sub/([0-9]+)/?', 'index.php?__dmmapi=1&sub=$matches[1]', 'top');
        add_rewrite_rule('^dmm-webhook/?','index.php?__dmmapi=1','top');
        flush_rewrite_rules();
    }

    /**
     * Sniff Requests
     * @param $query
     * @since 2.1.4
     * @return die if API request
     */
    public function sniff_requests($query)
    {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($query->query_vars['__dmmapi']))
        {
            echo $this->handle_request($query);
            exit;
        }
    }

    /**
     * Handle Webhook Request
     * @param $query
     * @since 2.1.4
     * @return string
     */
    protected function handle_request($query)
    {
        $dmm_webhook = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on'?'https://':'http://') . $_SERVER['HTTP_HOST'] . DMM_WEBHOOK;

        try {
            // Connect with Mollie
            $mollie = new Mollie_API_Client;
            if (get_option('dmm_mollie_apikey'))
                $mollie->setApiKey(get_option('dmm_mollie_apikey'));
            else
            {
                status_header(404);
                return 'No API-key set';
            }

            if (!$query->query_vars['sub'])
            {
                // First payment of recurring donation or one-time donation
                $payment_id = $_POST['id'];
                if (!$payment_id)
                {
                    status_header(404);
                    return 'No payment id';
                }

                $donation = $this->wpdb->get_row("SELECT * FROM " . DMM_TABLE_DONATIONS . " WHERE payment_id = '" . esc_sql($payment_id) . "'");

                if (!$donation->id)
                {
                    status_header(404);
                    return 'Donation not found';
                }

                $payment = $mollie->payments->get($payment_id);
                $this->wpdb->query($this->wpdb->prepare("UPDATE " . DMM_TABLE_DONATIONS . " SET dm_status = %s, payment_method = %s, payment_mode = %s, customer_id = %s, subscription_id = %s WHERE id = %d",
                    $payment->status,
                    $payment->method,
                    $payment->mode,
                    $payment->customerId,
                    $payment->subscriptionId,
                    $donation->id
                ));


                if (($query->query_vars['first'] && $query->query_vars['secret']) && ($payment->isPaid() && !$payment->isRefunded()))
                {
                    $customer = $this->wpdb->get_row("SELECT * FROM " . DMM_TABLE_DONORS . " WHERE id = '" . esc_sql($query->query_vars['first']) . "' AND secret='" . esc_sql($query->query_vars['secret']) . "'");

                    if (!$customer->id)
                    {
                        status_header(404);
                        return 'Customer not found';
                    }

                    $this->wpdb->query($this->wpdb->prepare("INSERT INTO " . DMM_TABLE_SUBSCRIPTIONS . "
                    ( customer_id, created_at )
                    VALUES ( %s, NOW())",
                        $customer->id
                    ));

                    $sub_id = $this->wpdb->insert_id;
                    $subscription = $mollie->customers_subscriptions->withParentId($customer->customer_id)->create(array(
                        "amount"      => $customer->sub_amount,
                        "interval"    => $this->dmm_get_interval($customer->sub_interval),
                        "description" => $customer->sub_description,
                        "webhookUrl"  => $dmm_webhook . 'sub/' . $sub_id,
                    ));

                    $this->wpdb->query($this->wpdb->prepare("UPDATE " . DMM_TABLE_SUBSCRIPTIONS . " SET subscription_id = %s, sub_mode = %s, sub_amount = %s, sub_times = %s, sub_interval = %s, sub_description = %s, sub_method = %s, sub_status = %s WHERE id = %d",
                        $subscription->id,
                        $subscription->mode,
                        $subscription->amount,
                        $subscription->times,
                        $subscription->interval,
                        $subscription->description,
                        $subscription->method,
                        $subscription->status,
                        $sub_id
                    ));
                }

                return 'OK, ' . $payment_id;
            }
            else
            {
                // Subscription
                $sub = $this->wpdb->get_row("SELECT * FROM " . DMM_TABLE_SUBSCRIPTIONS . " WHERE id = '" . esc_sql($query->query_vars['sub']) . "'");
                if (!$sub->id)
                {
                    status_header(404);
                    return 'Subscription not found';
                }


                $firstDonation = $this->wpdb->get_row("SELECT * FROM " . DMM_TABLE_DONATIONS . " WHERE subscription_id = '" . esc_sql($sub->subscription_id) . "'");
                if (!$firstDonation->id)
                {
                    status_header(404);
                    return 'Donation not found';
                }

                $payment_id = $_POST['id'];
                if (!$payment_id)
                {
                    status_header(404);
                    return 'No payment id';
                }


                $donation_id = uniqid(rand(1,99));
                $payment = $mollie->payments->get($payment_id);

                $donation = $this->wpdb->get_row("SELECT * FROM " . DMM_TABLE_DONATIONS . " WHERE payment_id = '" . esc_sql($payment->id) . "'");
                if (!$donation->id)
                {
                    // New payment
                    $this->wpdb->query($this->wpdb->prepare("INSERT INTO " . DMM_TABLE_DONATIONS . "
                    ( `time`, payment_id, customer_id, subscription_id, donation_id, dm_status, dm_amount, dm_name, dm_email, dm_project, dm_company, dm_address, dm_zipcode, dm_city, dm_country, dm_message, dm_phone, payment_method, payment_mode )
                    VALUES ( %s, %s, %s, %s, %s, %s, %f, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
                        date('Y-m-d H:i:s'),
                        $payment->id,
                        $payment->customerId,
                        $payment->subscriptionId,
                        $donation_id,
                        $payment->status,
                        $payment->amount,
                        $firstDonation->dm_name,
                        $firstDonation->dm_email,
                        $firstDonation->dm_project,
                        $firstDonation->dm_company,
                        $firstDonation->dm_address,
                        $firstDonation->dm_zipcode,
                        $firstDonation->dm_city,
                        $firstDonation->dm_country,
                        $firstDonation->dm_message,
                        $firstDonation->dm_phone,
                        $payment->method,
                        $payment->mode
                    ));
                }
                else
                {
                    // Update payment
                    $this->wpdb->query($this->wpdb->prepare("UPDATE " . DMM_TABLE_DONATIONS . " SET dm_status = %s, payment_method = %s, payment_mode = %s WHERE payment_id = %s",
                        $payment->status,
                        $payment->method,
                        $payment->mode,
                        $payment->id
                    ));
                }

                return 'OK, ' . $payment_id;
            }

        } catch (Mollie_API_Exception $e) {
            status_header(404);
            return"API call failed: " . $e->getMessage();
        }
    }

    /**
     * Get interval for subscription
     *
     * @since 2.1.0
     * @param $string
     * @return string
     */
    private function dmm_get_interval($string)
    {
        switch ($string) {
            case 'month':
                $interval = '1 month';
                break;
            case 'quarter':
                $interval = '3 months';
                break;
            case 'year':
                $interval = '12 months';
                break;
        }

        return $interval;
    }
}