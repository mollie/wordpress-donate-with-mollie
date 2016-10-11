<?php

class Dmm_Donors_Table extends WP_List_Table {
    function get_columns(){
        $columns = array();
        $columns['customer_name'] = __('Name', DMM_TXT_DOMAIN);
        $columns['customer_email'] = __('Email address', DMM_TXT_DOMAIN);

        $columns['customer_id'] = __('Customer ID', DMM_TXT_DOMAIN);

        return $columns;
    }

    function prepare_items() {
        global $wpdb;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $donors = $wpdb->get_results("SELECT * FROM " . DMM_TABLE_DONORS, ARRAY_A);

        $per_page = 25;
        $current_page = $this->get_pagenum();
        $total_items = count($donors);

        $d = array_slice($donors,(($current_page-1)*$per_page),$per_page);

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page )
        ) );
        $this->items = $d;
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
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