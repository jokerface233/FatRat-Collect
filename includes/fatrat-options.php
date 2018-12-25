<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class FRC_Configuration_List_Table extends WP_List_Table
{

    /** Class constructor */
    public function __construct()
    {

        parent::__construct(
            array(
                'singular' => esc_html__('采集配置', 'Far Rat Collect'),
                'plural' => esc_html__('采集配置', 'Far Rat Collect'),
                'ajax' => false,
            )
        );
    }

    /**
     * Retrieve snippets data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_snippets($per_page = 10, $page_number = 1, $customvar = 'all')
    {

        global $wpdb;
        $table_name = "{$wpdb->prefix}fr_options";
        $sql = "SELECT * FROM $table_name";

        if (in_array($customvar, array('list', 'single'))) {
            $sql .= " where `collect_type` = '$customvar'";
        }

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $result = $wpdb->get_results($sql, 'ARRAY_A');
        return $result;
    }

    /**
     * Delete a snipppet record.
     *
     * @param int $id snippet ID
     */
    public static function delete_snippet($id)
    {

        global $wpdb;
        $table_name = "{$wpdb->prefix}fr_options";

        $wpdb->delete(
            $table_name, array('id' => $id), array('%d')
        );
    }

    /**
     * Activate a snipppet record.
     *
     * @param int $id snippet ID
     */
    public static function activate_snippet($id)
    {

    }

    /**
     * Deactivate a snipppet record.
     *
     * @param int $id snippet ID
     */
    public static function deactivate_snippet($id)
    {

    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count($customvar = 'all')
    {

        global $wpdb;
        $table_name = "{$wpdb->prefix}fr_options";
        $sql = "SELECT COUNT(*) FROM $table_name";

        if (in_array($customvar, array('list', 'single'))) {
            $sql .= " where collect_type = '$customvar'";
        }

        return $wpdb->get_var($sql);
    }

    /** Text displayed when no snippet data is available */
    public function no_items()
    {
        esc_html_e('配置信息空空如也~请去创建', 'Far Rat Collect');
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {

        switch ($column_name) {
            case 'id':
//            case 'collect_name':
            case 'collect_type' :
            case 'collect_list_url' :
            case 'collect_list_range' :
            case 'collect_list_rules' :
            case 'collect_content_range' :
            case 'collect_content_rules' :
            case 'created' :
                return esc_html($item[$column_name]);
                break;
            case 'collect_name':
                $edit_url = admin_url('admin.php?page=frc-options-add-edit&option_id=' . $item['id']);
                return esc_html($item[$column_name]) . "<br /><a href='{$edit_url}'>编</a> | <a><span class='delete-option-button' data-value='{$item['id']}'>删</span></a> ";
            case 'collect_remove_outer_link' :
                return esc_html($item[$column_name] == 1 ? '移除' : '不移');
                break;
            case 'collect_keywords_replace_rule' :
                return esc_html('... 点击查看');
                break;
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="snippets[]" value="%s" />', $item['id']
        );
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_name($item)
    {

    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'id' => esc_html__('ID', 'Far Rat Collect'),
            'collect_name' => esc_html__('爬虫代号', 'Far Rat Collect'),
            'collect_type' => esc_html__('采集类型', 'Far Rat Collect'),
            'collect_list_url' => esc_html__('采集地址', 'Far Rat Collect'),
            'collect_remove_outer_link' => esc_html__('移除内容里面A标签', 'Far Rat Collect'),
            'created' => esc_html__('创建时间', 'Far Rat Collect'),
        );

        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {

        return array(
            'id' => array('id', true),
            'collect_type' => array('collect_type', true),
        );
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {

        return array(
            'bulk-delete' => esc_html__('暂未开放批量功能', 'Far Rat Collect'),
        );
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        //Retrieve $customvar for use in query to get items.
        $customvar = (isset($_REQUEST['customvar']) ? sanitize_text_field($_REQUEST['customvar']) : 'all');
        $this->_column_headers = array($columns, $hidden, $sortable);

        /** Process bulk action */
        $this->process_bulk_action();
        $this->views();
        $per_page = $this->get_items_per_page('snippets_per_page', 10);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
        ));

        $this->items = self::get_snippets($per_page, $current_page, $customvar);
    }

    public function get_views()
    {
        $views = array();
        $current = (!empty($_REQUEST['customvar']) ? sanitize_text_field($_REQUEST['customvar']) : 'all');

        //All link
        $class = 'all' === $current ? ' class="current"' : '';
        $all_url = remove_query_arg('customvar');
        $views['all'] = "<a href='{$all_url }' {$class} >" . esc_html__('全部', 'Far Rat Collect') . ' (' . $this->record_count() . ')</a>';

        //列表 link
        $foo_url = add_query_arg('customvar', 'list');
        $class = ('list' === $current ? ' class="current"' : '');
        $views['list'] = "<a href='{$foo_url}' {$class} >" . esc_html__('批量列表爬虫', 'Far Rat Collect') . ' (' . $this->record_count('list') . ')</a>';

        //单个 link
        $bar_url = add_query_arg('customvar', 'single');
        $class = ('single' === $current ? ' class="current"' : '');
        $views['single'] = "<a href='{$bar_url}' {$class} >" . esc_html__('单爬虫', 'Far Rat Collect') . ' (' . $this->record_count('single') . ')</a>';

        return $views;
    }

    public function process_bulk_action()
    {

    }
}


/**
 * 存储配置
 */
function frc_ajax_frc_save_options() {
    global $wpdb;
    $table = $wpdb->prefix.'fr_options';

    $option_id                  = !empty($_REQUEST['option_id']) ? sanitize_text_field($_REQUEST['option_id']) : null;
    $collect_name               = !empty($_REQUEST['collect_name']) ? sanitize_text_field($_REQUEST['collect_name']) : '';
    $collect_type               = !empty($_REQUEST['collect_type']) ? (in_array(sanitize_text_field($_REQUEST['collect_type']), ['list', 'single']) ? sanitize_text_field($_REQUEST['collect_type']) : 'list') : '';
    $collect_remove_outer_link  = !empty($_REQUEST['collect_remove_outer_link']) ? (sanitize_text_field($_REQUEST['collect_remove_outer_link']) == 1 ? 1 : 0) : 1;
    $collect_remove_head        = !empty($_REQUEST['collect_remove_head']) ? ( sanitize_text_field($_REQUEST['collect_remove_head']) == 1 ? 1 : 0 ) : 0;
    $collect_list_url           = !empty($_REQUEST['collect_list_url']) ? esc_url( $_REQUEST['collect_list_url'] ) : '';
    $collect_list_range         = !empty($_REQUEST['collect_list_range']) ? sanitize_text_field($_REQUEST['collect_list_range']) : '';
    $collect_list_rules         = !empty($_REQUEST['collect_list_rules']) ? sanitize_text_field($_REQUEST['collect_list_rules'])  : '';
    $collect_content_range      = !empty($_REQUEST['collect_content_range']) ? sanitize_text_field($_REQUEST['collect_content_range']) : '';
    $collect_content_rules      = !empty($_REQUEST['collect_content_rules']) ? sanitize_text_field($_REQUEST['collect_content_rules']) : '';
    $collect_keywords_replace_rule  = !empty($_REQUEST['collect_keywords_replace_rule']) ? sanitize_text_field($_REQUEST['collect_keywords_replace_rule']) : '';

    $params = [
            'collect_name' => $collect_name,
            'collect_type' => $collect_type,
            'collect_remove_outer_link' => $collect_remove_outer_link,
            'collect_remove_head' => $collect_remove_head,
            'collect_list_url' => $collect_list_url,
            'collect_list_range' => $collect_list_range,
            'collect_list_rules' => $collect_list_rules,
            'collect_content_range' => $collect_content_range,
            'collect_content_rules' => $collect_content_rules,
            'collect_keywords_replace_rule' => $collect_keywords_replace_rule,
        ];

    if ($option_id === null){
        $wpdb->insert($table, $params);
        wp_send_json(['code'=>0, 'result'=>$wpdb->insert_id]);
        wp_die();
    }

    $wpdb->update(
            $table,
            $params,
            ['id' => $option_id],
            ['%s', '%s'],
            ['%d']
        );
    wp_send_json(['code'=>0, 'result'=>$option_id]);
    wp_die();
}
add_action( 'wp_ajax_frc_save_options', 'frc_ajax_frc_save_options' );


/**
 * 删除配置
 */
function frc_ajax_frc_delete_option() {
    global $wpdb;
    $table = $wpdb->prefix.'fr_options';

    $option_id = !empty($_REQUEST['option_id']) ? sanitize_text_field($_REQUEST['option_id']) : null;

    if ($option_id === null){
        wp_send_json(['code'=>1, 'msg'=>'option_id异常']);
        wp_die();
    }

    $wpdb->delete($table, ['id' => $option_id], ['%d']);
    wp_send_json(['code'=>0, 'msg'=>'删除成功，刷新即可']);
    wp_die();
}
add_action( 'wp_ajax_frc_delete_option', 'frc_ajax_frc_delete_option' );

function frc_options()
{
    $snippet_obj = new FRC_Configuration_List_Table();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( '采集配置（一个配置就是一个站点爬取规则）', 'Far Rat Collect' ) ?>
            <a href="<?php echo admin_url( 'admin.php?page=frc-options-add-edit' ) ?>" class="page-title-action"><?php esc_html_e( '新建采集配置', 'Far Rat Collect' ) ?></a>
        </h1>

        <form method="post">
            <input type="hidden" hidden id="request_url" value="<?php echo admin_url('admin-ajax.php'); ?>">
            <?php
            $snippet_obj->prepare_items();
            $snippet_obj->display();
            ?>
        </form>

    </div>
    <?php
}

