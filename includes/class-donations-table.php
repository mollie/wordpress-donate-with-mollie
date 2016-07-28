<?php

class Dmm_List_Table extends WP_List_Table {
    function get_columns(){
        $dmm_fields = get_option('dmm_form_fields');

        $columns = array();
        $columns['time'] = __('Date/time', DMM_TXT_DOMAIN);

        if ($dmm_fields['Name']['active'])
            $columns['dm_name'] = __('Name', DMM_TXT_DOMAIN);

        if ($dmm_fields['Company name']['active'])
            $columns['dm_company'] = __('Company name', DMM_TXT_DOMAIN);

        if ($dmm_fields['Email address']['active'])
            $columns['dm_email'] = __('Email address', DMM_TXT_DOMAIN);

        $columns['dm_amount'] = __('Amount', DMM_TXT_DOMAIN);
        $columns['dm_status'] = __('Status', DMM_TXT_DOMAIN);
        $columns['donation_id'] = __('Donation ID', DMM_TXT_DOMAIN);

        return $columns;
    }

    function column_donation_id($item){
        $url_refund = wp_nonce_url('?page=doneren-met-mollie&action=refund&payment=' . $item['payment_id'], 'refund-donation_' . $item['payment_id']);
        $url_view = '?page=doneren-met-mollie-donatie&id=' . $item['id'];

        $actions = array();
        $actions['view'] = sprintf('<a href="%s">' . esc_html__('View', DMM_TXT_DOMAIN) . '</a>', $url_view);

        if ($item['dm_status'] == 'paid' && $item['dm_amount'] > 0.30)
            $actions['refund'] = sprintf('<a href="%s" style="color:#a00;" onclick="return confirm(\'' . __('Are you sure?', DMM_TXT_DOMAIN) . '\')">' . esc_html__('Refund', DMM_TXT_DOMAIN) . '</a>', $url_refund);

        //Return the title contents
        return sprintf('%1$s %2$s',
            $item['donation_id'],
            $this->row_actions($actions)
        );
    }

    function prepare_items() {
        global $wpdb;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $where = '';
        if (isset($_GET['subscription']))
            $where = ' WHERE subscription_id="' . esc_sql($_GET['subscription']) . '"';

        $donations = $wpdb->get_results("SELECT * FROM " . DMM_TABLE_DONATIONS . $where . " ORDER BY time DESC", ARRAY_A);

        $per_page = 25;
        $current_page = $this->get_pagenum();
        $total_items = count($donations);

        $d = array_slice($donations,(($current_page-1)*$per_page),$per_page);

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
        $this->items = $d;
    }

    function statusName( $status ) {
        switch( $status ) {
            case 'open':
                return __('Open', DMM_TXT_DOMAIN);
            case 'cancelled':
                return __('Cancelled', DMM_TXT_DOMAIN);
            case 'pending':
                return __('Pending', DMM_TXT_DOMAIN);
            case 'expired':
                return __('Expired', DMM_TXT_DOMAIN);
            case 'paid':
                return __('Paid', DMM_TXT_DOMAIN);
            case 'paidout':
                return __('Paid out', DMM_TXT_DOMAIN);
            case 'refunded':
                return __('Refunded', DMM_TXT_DOMAIN);
            case 'charged_back':
                return __('Charged back', DMM_TXT_DOMAIN);
            default:
                return $status;
        }
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'dm_amount':
                return ($item['payment_method'] ? '<img valign="top" src="https://www.mollie.com/images/payscreen/methods/' . $item['payment_method'] . '.png" width="18"> ' : '') . '&euro; ' . number_format($item[ $column_name ], 2, ',', '');
                break;
            case 'time':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item[ $column_name ]));
            case 'dm_status':
                return $this->statusName($item[ $column_name ]) . ($item['payment_mode'] == 'test' ? ' <small>(' . $item['payment_mode'] . ')</small>' : '');
            case 'dm_email':
            case 'dm_name':
            case 'dm_company':
            case 'donation_id':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
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