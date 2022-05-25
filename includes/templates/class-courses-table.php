<?php
class PFD_Courses extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $action = $this->current_action();

        $data = $this->table_data();
        usort($data, array(&$this, 'sort_data'));

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page' => $perPage,
        ));

        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
       
        $this->items = $data;
    }

    // function display_tablenav($which){

    // }
    
    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'course_name' => 'Name',
            'temp_category' => 'Category',
            'starting_date' => 'Starting date',
            'duration' => 'Duration',
            'posts_counts' => 'Posts',
            'date' => 'Created'
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns() {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns() {
        return array(
            'course_name' => array('course_name', true),
            'posts_counts' => array('posts_counts', true),
            'starting_date' => array('starting_date', true),
            'duration' => array('duration', true),
            'date' => array('date', true)
        );
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data() {
        global $wpdb;
        $data = array();
        
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pfd_courses");
        if($results){
            foreach($results as $result){
                $posts_counts = 0;
                if($result->course_lines){
                    $posts_counts = unserialize($result->course_lines);
                    $posts_counts = ((is_array($posts_counts)) ? sizeof($posts_counts) : 0);
                }
                $arr = array(
                    'ID' => $result->ID,
                    'course_name' => $result->name,
                    'temp_category' => get_the_category_by_ID( $result->temp_category ),
                    'starting_date' => $result->date,
                    'duration' => $result->duration,
                    'posts_counts' => $posts_counts,
                    'date' => date("F j, Y, g:i a", strtotime($result->created))
                );
        
                $data[] = $arr;
            }
        }

        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case $column_name:
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    public function column_course_name($item) {
        $actions = array(
            'edit' => '<a href="?page=pfd&action=edit&id='.$item['ID'].'">Edit</a>',
            'delete' => '<a href="?page=pfd&action=delete&course='.$item['ID'].'">Delete</a>',
        );

        return sprintf('%1$s %2$s', $item['course_name'], $this->row_actions($actions));
    }

    public function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete',
        );
        return $actions;
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="course[]" value="%s" />', $item['ID']
        );
    }

    function pfd_cat_replace_cron_cb($course_id){
		global $wpdb;
		$lines = null;
		$courseData = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}pfd_courses WHERE ID = $course_id");

		if($courseData){
			$lines = $courseData->course_lines;
            $lines = (( $lines ) ? unserialize( $lines ) : null);

            if($lines){
                foreach($lines as $line){
                    $post_ID = $line['post_id'];

                    $tempCat = $line['temp_category'];
                    $org_categories = $line['original_categories'];
    
                    wp_remove_object_terms( $post_ID, intval( $tempCat ), 'category' );
                    wp_set_object_terms( $post_ID, $org_categories, 'category' );
                }
            }
		}
	}

    // All form actions
    public function current_action() {
        global $wpdb;
        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'pfd') {
            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete' && isset($_REQUEST['course'])) {
                if(is_array($_REQUEST['course'])){
                    $ids = $_REQUEST['course'];
                    foreach($ids as $ID){
                        $this->pfd_cat_replace_cron_cb($ID);
                        $wpdb->query("DELETE FROM {$wpdb->prefix}pfd_courses WHERE ID = $ID");
                    }
                }else{
                    $ID = intval($_REQUEST['course']);
                    $this->pfd_cat_replace_cron_cb($ID);
                    $wpdb->query("DELETE FROM {$wpdb->prefix}pfd_courses WHERE ID = $ID");
                }
            }
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data($a, $b) {
        // If no sort, default to user_login
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'course_name';
        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strnatcmp($a[$orderby], $b[$orderby]);
        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

} //class
