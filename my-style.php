<?php

/**
 * Plugin Name: My style
 * Description: My style
 * Version: 1.0
 * Author: m-jalali
 * Author URI: http://www.m-jalali.ir
 */

const mystyle_folder_style = 'style';
const mystyle_dir_style = WP_PLUGIN_DIR . '//my-style//' . mystyle_folder_style . '/';
const mystyle_option_name = 'mystyle_list';

function mystyle_get_data()
{
    $mystyle_list = unserialize(get_option(mystyle_option_name, ''));
    if (empty($mystyle_list))
        $mystyle_list = array();
    return $mystyle_list;
}
function mystyle_update_data($mystyle_list)
{
    return update_option(mystyle_option_name, serialize((array)$mystyle_list));
}

function mystyle_added_style()
{
    // handle, src, deps, ver, media
    $mystyle_list =  mystyle_get_data();
    foreach ((array)$mystyle_list as $key => $value) {
        $src = mystyle_dir_style . "{$value['file_name']}.css";
        if (file_exists($src)) {
            wp_enqueue_style((string)$value['handle'], plugins_url(mystyle_folder_style . "/{$value['file_name']}.css", __FILE__), (array) $value['deps'], (string) $value['ver'], (string) $value['media']);
        } else if (!empty($value['src'])) {
            wp_enqueue_style((string)$value['handle'], (string)$value['src'], (array) $value['deps'], (string) $value['ver'], (string) $value['media']);
        }
    }
}
// mystyle_added_style();
// add_filter('wp_head', 'mystyle_added_style');
add_action('after_setup_theme', 'mystyle_added_style');
function mystyle_add_menu()
{
    add_menu_page("My style", "My style", "edit_pages", "mystyle-panel", "mystyle_admin_panel_display", null, 99);
}
add_action("admin_menu", "mystyle_add_menu");

function mystyle_admin_panel_display()
{
    $action = !empty($_GET) && !empty($_GET['action']) ? $_GET['action'] : "first";
    $id = !empty($_GET) && !empty($_GET['id']) ? $_GET['id'] : -1;

    if (!empty($_POST)) {
        $successful = true;
        $mystyle_list = (array) mystyle_get_data();

        // handle, src, deps, ver, media
        // ms_handle, ms_file_name, ms_src, ms_deps, ms_ver, ms_media, ms_file_text
        // action Add
        if (!empty($_POST['action']) && $_POST['action'] == "add" && !empty($_POST['ms_handle'])) {
            $new['handle'] = str_replace(' ', '-', $_POST['ms_handle']);
            $new['file_name'] = str_replace(' ', '-', $_POST['ms_file_name']);
            $new['src'] = $_POST['ms_src'];
            $new['deps'] = empty($_POST['ms_deps']) ? array() : (array) explode(',', $_POST['ms_deps']);
            $new['ver'] = $_POST['ms_ver'];
            $new['media'] = $_POST['ms_media'];
            $file_path =  mystyle_dir_style . $_POST['ms_file_name'] . ".css";
            if (!file_exists($file_path) && !empty($new['file_name'])) {
                $myfile = fopen($file_path, "w");
                $text = wp_unslash($_POST['ms_file_text']);
                $successful = fwrite($myfile, $text) === false ? false : $successful;
                fclose($myfile);
                $mystyle_list[] = $new;
                $successful = $successful && mystyle_update_data($mystyle_list);
            } else if (!empty($new['src'])) {
                $mystyle_list[] = $new;
                $successful = $successful && mystyle_update_data($mystyle_list);
            } else
                $successful = false;
        }
        // action Edit
        else if (!empty($_POST['action']) && $_POST['action'] == "edit" && !empty($_POST['mystyle_id'])) {
            $id = $_POST['mystyle_id'];
            $ids = explode('~', (string)$id);
            if (key_exists($ids[1], $mystyle_list) && $mystyle_list[$ids[1]]['handle'] === $ids[0]) {
                $args = array();
                $args['handle'] = str_replace(' ', '-', $_POST['ms_handle']);
                $args['file_name'] = str_replace(' ', '-', $_POST['ms_file_name']);
                $args['src'] = $_POST['ms_src'];
                $args['deps'] = empty($_POST['ms_deps']) ? array() : (array) explode(',', $_POST['ms_deps']);
                $args['ver'] = $_POST['ms_ver'];
                $args['media'] = $_POST['ms_media'];
                $file_path =  mystyle_dir_style . $args['file_name'] . ".css";;
                if (!file_exists($file_path) && !empty($new['file_name'])) {
                    $myfile = fopen($file_path, "w");
                    $text = wp_unslash($_POST['ms_file_text']);
                    $successful = (fwrite($myfile, $text) === false ? false : true) && $successful;
                    fclose($myfile);
                }
                if ($successful && serialize($mystyle_list[$ids[1]]) != serialize($args)) {
                    $mystyle_list[$ids[1]] = $args;
                    $successful = $successful && mystyle_update_data($mystyle_list);
                }
            } else
                $successful = false;
        }
        // action remove
        else if (!empty($_POST['action']) && $_POST['action'] == "remove" && !empty($_POST['mystyle_id'])) {
            $id = $_POST['mystyle_id'];
            $ids = explode('~', (string)$id);
            if (key_exists($ids[1], $mystyle_list) && $mystyle_list[$ids[1]]['handle'] === $ids[0]) {
                $file_name = $mystyle_list[$ids[1]]['file_name'];
                $file_path =  mystyle_dir_style . $file_name . ".css";
                if (file_exists($file_path)) {
                    $successful = $successful && unlink($file_path);
                }
                unset($mystyle_list[$ids[1]]);
                $successful = $successful && mystyle_update_data($mystyle_list);
            } else
                $successful = false;
        } else
            $successful = false;

        if ($successful) {
            echo "<div class=\"\">successful</div>";
        } else {
            echo "<div class=\"\">un successful</div>";
        }
    }
?>
    <div class="wrap">
        <?php
        if ($action == 'add')
            mystyle_add_page_display();
        else if ($action == 'edit' && $id != -1)
            mystyle_add_page_display($id);
        else if ($action == 'remove' && $id != -1)
            mystyle_remove_page_display($id);
        else
            mystyle_first_page_display();
        ?>
    </div>
<?php
}



function mystyle_remove_page_display($id)
{
    $ids = explode('~', (string)$id);
?>
    <form action="admin.php?page=mystyle-panel" method="POST">
        <p>Are you sure you want to delete the <?php echo $ids[0]; ?> style?</p>
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="mystyle_id" value="<?php echo $id; ?>">
        <input type="submit" value="Remove" class="button button-primary">
        <a class="button button-cancel" href="admin.php?page=mystyle-panel&action=first" class="page-title-action">Cancel</a>
    </form>
<?php
}

function mystyle_first_page_display()
{
    $mystyle_list = mystyle_get_data();
?>
    <style>
        .mystyle_ul {
            display: block;
        }

        .mystyle_ul li {}

        .mystyle_ul li {
            display: inline-block;
            float: left;
        }

        .mystyle_ul li:first-child::after {
            content: '';
        }

        .mystyle_ul li::after {
            content: ',';
            margin-right: 5px;
            color: #ff0000;
        }
    </style>
    <h1 class="wp-heading-inline">My style</h1>
    <div class="row"><a href="admin.php?page=mystyle-panel&action=add" class="page-title-action">add</a></div>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column">handle</th>
                <th scope="col" class="manage-column">file name</th>
                <th scope="col" class="manage-column">src</th>
                <th scope="col" class="manage-column">deps</th>
                <th scope="col" class="manage-column">ver</th>
                <th scope="col" class="manage-column">media</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($mystyle_list)) {
                foreach ((array)$mystyle_list as $i => $item) {
            ?>
                    <tr class="">
                        <td class="">
                            <strong><?php echo $item['handle']; ?></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=mystyle-panel&action=edit&id=<?php echo $item['handle'] . "~" . $i; ?>" aria-label="ویرایش">ویرایش</a> | </span>
                                <span class="trash"><a href="admin.php?page=mystyle-panel&action=remove&id=<?php echo $item['handle'] . "~" . $i; ?>" class="submitdelete" aria-label="حذف">حذف</a> | </span>
                            </div>
                        </td>
                        <td class="">
                            <?php echo $item['file_name']; ?>
                        </td>
                        <td class="">
                            <?php echo $item['src']; ?>
                        </td>
                        <td class="">
                            <?php echo implode(',', (array)$item['deps']); ?>
                        </td>
                        <td class="">
                            <?php echo $item['ver']; ?>
                        </td>
                        <td class="">
                            <?php echo $item['media']; ?>
                        </td>
                    </tr>
            <?php }
            } else echo '<tr class=""><td>null</td></tr>'; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column">handle</th>
                <th scope="col" class="manage-column">file name</th>
                <th scope="col" class="manage-column">src</th>
                <th scope="col" class="manage-column">deps</th>
                <th scope="col" class="manage-column">ver</th>
                <th scope="col" class="manage-column">media</th>
            </tr>
        </tfoot>

    </table>
<?php
}


function mystyle_add_page_display($id = false)
{
    $file_path = ".css";
    $data = array(
        'handle' => 'my-style',
        'file_name' => 'my-style',
        'src' => '',
        'deps' => '',
        'ver' => '1.0.0',
        'media' => 'all',
        'file_text' => ''
    );
    if ($id !== false) {
        $mystyle_list = (array) mystyle_get_data();
        $ids = explode('~', (string)$id);
        if (key_exists($ids[1], $mystyle_list) && $mystyle_list[$ids[1]]['handle'] === $ids[0]) {
            $data['handle'] =  $mystyle_list[$ids[1]]['file_name'];
            $data['file_name'] = $mystyle_list[$ids[1]]['file_name'];
            $data['src'] = $mystyle_list[$ids[1]]['src'];
            $data['deps'] = implode(',', (array)$mystyle_list[$ids[1]]['deps']);
            $data['ver'] = $mystyle_list[$ids[1]]['ver'];
            $data['media'] = $mystyle_list[$ids[1]]['media'];
            $file_path = mystyle_dir_style . $data['file_name'] . ".css";
            if (file_exists($file_path)) {
                $myfile = fopen($file_path, "r") or die("Unable to open file!");
                $data['file_text'] = fread($myfile, filesize($file_path));
                fclose($myfile);
            }
        }
    }
    $settings = array(
        'codeEditor' => wp_enqueue_code_editor(array('file' => $file_path)),
    );
    wp_enqueue_script('wp-theme-plugin-editor');
    wp_add_inline_script('wp-theme-plugin-editor', sprintf('jQuery( function( $ ) { wp.themePluginEditor.init( $( "#mystyle_form_" ), %s ); } )', wp_json_encode($settings)));
?>
    <style>
        .mystyle_row {
            padding: 20px;
        }

        .mystyle_row label {
            display: inline-block;
            width: 20%;
        }

        .mystyle_row .mystyle_sec {
            display: inline-block;
            width: 70%;
        }

        .mystyle_row .mystyle_sec input[type=text],
        .mystyle_row .mystyle_sec input[type=number],
        .mystyle_row .mystyle_sec select,
        .mystyle_row .mystyle_sec textarea {
            width: 30%;
        }
    </style>
    <h1 class="wp-heading-inline">Add Function</h1>
    <div class="row"><a href="admin.php?page=mystyle-panel&action=first" class="page-title-action">back</a></div>
    <form id="mystyle_form" action="admin.php?page=mystyle-panel" method="post">
        <div class="mystyle_row">
            <label for="ms_handle">handle</label>
            <div class="mystyle_sec">
                <input type="text" name="ms_handle" value="<?php echo $data['handle']; ?>">
            </div>
        </div>
        <div class="mystyle_row">
            <label for="ms_file_name">file name</label>
            <div class="mystyle_sec">
                <input type="text" name="ms_file_name" value="<?php echo $data['file_name']; ?>">
            </div>
        </div>
        <div class="mystyle_row">
            <label for="ms_src">src</label>
            <div class="mystyle_sec">
                <input type="text" name="ms_src" value="<?php echo $data['src']; ?>">
            </div>
        </div>
        <div class="mystyle_row">
            <label for="ms_deps">depends</label>
            <div class="mystyle_sec">
                <input type="text" name="ms_deps" value="<?php echo $data['deps']; ?>">
            </div>
        </div>
        <div class="mystyle_row">
            <label for="ms_ver">version</label>
            <div class="mystyle_sec">
                <input type="text" name="ms_ver" value="<?php echo $data['ver']; ?>">
            </div>
        </div>
        <div class="mystyle_row">
            <label for="ms_media">media</label>
            <div class="mystyle_sec">
                <input type="text" name="ms_media" value="<?php echo $data['media']; ?>">
            </div>
        </div>
        <div class="mystyle_row">
            <textarea name="ms_file_text" id="newcontent" cols="30" rows="20"><?php echo $data['file_text']; ?></textarea>
        </div>
        <div class="mystyle_row">
            <input type="hidden" name="mystyle_id" value="<?php echo $id ? $id : ''; ?>">
            <input type="hidden" name="action" value="<?php echo $id ? 'edit' : 'add'; ?>">
            <input class="button button-primary" type="submit" name="submit" value="<?php echo $id ? 'Save' : 'Add'; ?>">
            <a class="button button-cancel" href="admin.php?page=mystyle-panel&action=first" class="page-title-action">back</a>
        </div>
    </form>
<?php
}
