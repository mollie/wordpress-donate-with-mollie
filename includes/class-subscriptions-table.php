<?php

class Dmm_Subscriptions_Table extends WP_List_Table {
    function get_columns(){
        $columns = array();
        $columns['created_at'] = __('Date/time', DMM_TXT_DOMAIN);
        $columns['customer_name'] = __('Name', DMM_TXT_DOMAIN);
        $columns['sub_amount'] = __('Amount', DMM_TXT_DOMAIN);
        $columns['sub_interval'] = __('Interval', DMM_TXT_DOMAIN);
        $columns['sub_status'] = __('Status', DMM_TXT_DOMAIN);

        $columns['subscription_id'] = __('Subscription ID', DMM_TXT_DOMAIN);

        return $columns;
    }

    function column_subscription_id($item){
        if ($item['sub_status'] != 'active')
            return $item['subscription_id'];

        $url_view = '?page=' . DMM_PAGE_DONATIONS . '&subscription=' . $item['subscription_id'];
        $url_cancel = wp_nonce_url('?page=' . DMM_PAGE_SUBSCRIPTIONS . '&action=cancel&subscription=' . $item['subscription_id'] . '&customer=' . $item['customer_id'], 'cancel-subscription_' . $item['subscription_id']);
        $actions = array(
            'view'    => sprintf('<a href="%s">' . esc_html__('View', DMM_TXT_DOMAIN) . '</a>', $url_view),
            'cancel'    => sprintf('<a href="%s" style="color:#a00;" onclick="return confirm(\'' . __('Are you sure?', DMM_TXT_DOMAIN) . '\')">' . esc_html__('Cancel', DMM_TXT_DOMAIN) . '</a>', $url_cancel),
        );

        //Return the title contents
        return sprintf('%1$s %2$s',
            $item['subscription_id'],
            $this->row_actions($actions)
        );
    }

    function column_customer_name($item){
        global $wpdb;
        $customer = $wpdb->get_row("SELECT * FROM " . DMM_TABLE_DONORS . " WHERE id = '" . esc_sql($item['customer_id']) . "'");
        return $customer->customer_name;
    }

    function prepare_items() {
        global $wpdb;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $subscriptions = $wpdb->get_results("SELECT * FROM " . DMM_TABLE_SUBSCRIPTIONS, ARRAY_A);

        $per_page = 25;
        $current_page = $this->get_pagenum();
        $total_items = count($donors);

        $d = array_slice($subscriptions,(($current_page-1)*$per_page),$per_page);

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
        $this->items = $d;
    }

    function getInterval($interval) {
        switch ($interval) {
            case '1 month':
                $return = __('Monthly', DMM_TXT_DOMAIN);
                break;
            case '3 months':
                $return = __('Each quarter', DMM_TXT_DOMAIN);
                break;
            case '12 months':
                $return = __('Annually', DMM_TXT_DOMAIN);
                break;
        }

        return $return;
    }

    function getStatus($status) {
        switch ($status) {
            case 'pending':
                $return = __('Pending', DMM_TXT_DOMAIN);
                break;
            case 'active':
                $return = __('Active', DMM_TXT_DOMAIN);
                break;
            case 'cancelled':
                $return = __('Cancelled', DMM_TXT_DOMAIN);
                break;
            case 'suspended':
                $return = __('Suspended', DMM_TXT_DOMAIN);
                break;
            case 'completed':
                $return = __('Completed', DMM_TXT_DOMAIN);
                break;
        }

        return $return;
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'created_at':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item[ $column_name ]));
                break;
            case 'sub_amount':
                return '&euro; ' . number_format($item['sub_amount'], 2, ',', '');
                break;
            case 'sub_interval':
                return $this->getInterval($item['sub_interval']);
                break;
            case 'sub_status':
                return $this->getStatus($item['sub_status']);
                break;
            default:
                return $item[ $column_name ];
        }
    }

    public function display_tablenav( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
            <?php $this->pagination( $which );?>
            <br class="clear" />
        </div>
        <?php
    }
}