<div class="addnew_box">
    <h3 class="heading3">Edit course</h3>
    <a href="?page=pfd&action=add" class="button-secondary">Add new</a>
</div>

<?php
$course_id = null;
if(isset($_GET['id'])){
    $course_id = intval($_GET['id']);
}

$course_name = null;
$course_tag = null;
$course_date = null;
$course_duration = null;
$working_days = null;
$temp_cat_id = null;
$temp_category = null;

$courseData = array();

if($course_id !== null){
    global $wpdb;
    $courseRow = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}pfd_courses WHERE ID = $course_id");

    $course_name = $courseRow->name;
    $course_tag =  $courseRow->course_tag;
    $course_date =  $courseRow->date;
    $course_duration =  $courseRow->duration;
    $course_duration = (($course_duration) ? $course_duration : 24);

    $working_days =  $courseRow->working_days;
    $working_days =  (($working_days) ? intval($working_days) : 7);

    $temp_cat_id =  $courseRow->temp_category;
    $temp_category = get_the_category_by_ID( intval($temp_cat_id) );

    $courseData = $courseRow->course_lines;
    $courseData = (( $courseData ) ? unserialize( $courseData ) : '');
}

?>

<form action="" method="post">
    <div id="courseViewBox">
        <div class="pfd_fields">

            <div class="postbox-container">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>Course</h2>
                    </div>
                    <div class="inside">
                        <div class="pfd_course_post">
                            <table>
                                <tr>
                                    <th>Course name</th>
                                    <td>
                                        <input class="course_name" type="text" name="course_name" placeholder="Name" value="<?php echo $course_name ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Course tag</th>
                                    <td>
                                        <select name="course_tag" class="course_tag">
                                            <option value="">Posts tag</option>
                                            <?php
                                            $tags = get_tags(array(
                                                'hide_empty' => false
                                            ));
                                            if($tags){
                                                foreach ($tags as $tag) {
                                                    echo '<option '.(($course_tag === $tag->name) ? 'selected' : '').' value="'.$tag->name.'">'.$tag->name.'</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Starting date</th>
                                    <td>
                                        <input type="date" placeholder="Date" class="course_date" name="course_date" value="<?php echo $course_date ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Duration</th>
                                    <td>
                                        <input type="number" oninput="this.value = Math.abs(this.value)" class="course_duration" min="1" placeholder="Duration" name="course_duration" value="<?php echo $course_duration ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Working days</th>
                                    <td>
                                        <select class="course_workingdays" name="course_workingdays">
                                            <option <?php echo (($working_days === 7) ? 'selected' : '') ?> value="7">Every Day</option>
                                            <option <?php echo (($working_days === 5) ? 'selected' : '') ?> value="5">Every Working Day</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Temporary Category</th>
                                    <td>
                                        <select class="course_temp_cat" name="course_temp_cat">
                                            <option value="">Temporary Category</option>
                                            <option selected value="<?php echo $temp_cat_id ?>"><?php echo $temp_category ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <button id="schedule_course" class="button-secondary">Schedule course</button>
                    </div>
                </div>
            </div>

            <div class="postbox-container">
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>Posts</h2>
                        <span class="posts_count">0</span>
                    </div>
                    <div class="inside">
                        <div id="pfd">
                            <div id="pfd_posts" class="pfd_posts">
                                <?php
                                global $wpdb;
                                
                                if(!empty($courseData)){
                                    foreach($courseData as $line){
                                        $post_id = $line["post_id"];
                                        $post_name = get_the_title( $post_id );
                                        $date = $line["date"];
                                        $duration = $line["duration"];
                                        $lineStatus = $this->get_line_status($date, $duration);

                                        $category_id = $line["temp_category"];
                                        $category_name = get_the_category_by_ID( $category_id );

                                        ?>
                                        <div data-id="<?php echo $post_id ?>" class="pfd_post">
                                            <span class="lineStatus <?php echo (($lineStatus) ? 'active' : 'deactive') ?>"></span>
                                            <div class="pfd_input">
                                                <select required name="pfdposts[<?php echo $post_id ?>][post]" class="pfd_post_select">
                                                    <option value="">Post (Search)</option>
                                                    <option selected value="<?php echo $post_id ?>"><?php echo $post_name ?></option>
                                                </select>
                                            </div>
                                            <div class="pfd_input">
                                                <input required type="date" placeholder="Date" name="pfdposts[<?php echo $post_id ?>][date]"
                                                    value="<?php echo date("Y-m-d", strtotime($date)) ?>">
                                            </div>
                                            <div class="pfd_input">
                                                <input type="number" placeholder="Duration" name="pfdposts[<?php echo $post_id ?>][duration]"
                                                    value="<?php echo $duration ?>">
                                            </div>
                                            <div class="pfd_input">
                                                <select required class="pfd_category_select" name="pfdposts[<?php echo $post_id ?>][category]">
                                                    <option value="">Temporary Category</option>
                                                    <option selected value="<?php echo $category_id ?>"><?php echo $category_name ?></option>
                                                </select>
                                            </div>

                                            <?php echo ((!$lineStatus) ? '<span class="delete_pfd_post">+</span>' : '') ?>

                                        </div>
                                    <?php
                                    }
                                }else{
                                    echo "<div class='notf'>No lines found!</div>";
                                }
                                ?>
                            </div>

                            <button id="nextpostBtn" class="button-secondary">Next Post</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="savebtn" class="postbox-container">
            <div class="postbox">
                <div class="postbox-header">
                    <h2>Course</h2>
                </div>
                <div class="inside">
                    <?php 
                    if(isset($_GET['id'])){
                        ?>
                        <input type="hidden" name="courseID" value="<?php echo $_GET['id'] ?>">
                        <?php
                    }
                    ?>
                    <button name="pfd_form_btn" class="button-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>
</form>

<div id="pfd_loader">
    <svg version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="50px" height="50px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve">
        <path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946
        s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634
        c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"></path>
        <path fill="<?php echo ((get_option('lr_selected_color')) ? get_option('lr_selected_color') : '#00bcd4') ?>" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0
        C22.32,8.481,24.301,9.057,26.013,10.047z">
        <animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 20 20" to="360 20 20" dur="0.9s" repeatCount="indefinite"></animateTransform>
        </path>
    </svg>
</div>