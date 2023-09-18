<?php
/**
 * Plugin Name: CopyPress Content Cloner
 * Description: Effortlessly duplicate posts from one WordPress website to another with no need for admin access. Streamline your content sharing and save time by securely copying articles, images, and categories across your WordPress sites. Achieve seamless content replication and boost your productivity with our user-friendly plugin today.
 * Version: 1.0
 * Author: Nerghum
 * Author URL: nerghum.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Register the menu item and settings page
function CPC_content_cloner_menu() {
    add_menu_page(
        'CopyPress Content Cloner Settings',
        'CopyPress Content Cloner',
        'manage_options',
        'cpc-content-cloner-settings',
        'CPC_content_cloner_settings_page',
        'dashicons-database-import'
    );
}
add_action('admin_menu', 'CPC_content_cloner_menu');

// Callback function to display the settings page
function CPC_content_cloner_settings_page() {
    require_once(plugin_dir_path(__FILE__) . 'CPC-content-cloner-ui.php');
}

// Register the settings and fields
function CPC_content_cloner_register_settings() {
    register_setting('cpc-content-cloner-settings', 'cpc_content_cloner_plugin_title');
    add_settings_section('cpc-content-cloner-plugin-settings', 'Plugin Settings', 'CPC_content_cloner_plugin_settings_section_callback', 'cpc-content-cloner-settings');
    add_settings_field('cpc_content_cloner_plugin_title', 'Plugin Title', 'CPC_content_cloner_plugin_title_callback', 'cpc-content-cloner-settings', 'cpc-content-cloner-plugin-settings');
}
add_action('admin_init', 'CPC_content_cloner_register_settings');

// Callback function for the settings section
function CPC_content_cloner_plugin_settings_section_callback() {
    echo 'You can configure the settings for the CopyPress Content Cloner plugin here.';
}

// Callback function to display the plugin title field
function CPC_content_cloner_plugin_title_callback() {
    $title = esc_attr(get_option('cpc_content_cloner_plugin_title'));
    echo "<input type='text' name='cpc_content_cloner_plugin_title' value='$title' />";
}

function CPC_import_new_post(){
    function CPC_create_post_with_categories_and_featured_image($title, $content, $post_type = 'post', $post_status = 'publish', $categories = array(), $featured_image = '') {
        // Prepare the post data
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_type'     => $post_type,
            'post_status'   => $post_status,
        );

        // Insert the post into the database
        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            // Set post categories
            if (!empty($categories)) {
                $category_ids = array();
                foreach ($categories as $category_name) {
                    $category = get_term_by('name', $category_name, 'category');
                    if (!$category) {
                        // If the category does not exist, create it
                        $category = wp_create_category($category_name);
                    }
                    $category_ids[] = $category->term_id;
                }
                wp_set_post_categories($post_id, $category_ids);
            }

            // Set featured image
            if (!empty($featured_image)) {
                // Download the image from the URL
                $image_id = CPC_upload_image_from_url($featured_image);

                if ($image_id) {
                    // Set the image as the featured image
                    set_post_thumbnail($post_id, $image_id);
                }
            }

            return $post_id; // Return the new post ID if successful
        } else {
            return 0; // Return 0 if post creation fails
        }
    }

    // Post data from ajax request
    $new_post_title = $_POST['title'];
    $new_post_content = $_POST['content'];
    $new_post_type = 'post'; 
    $new_post_status = 'publish';
    $new_categories = $_POST['categories']; // Category names to assign
    $new_featured_image_url = $_POST['imageUrl']; // URL of the featured image

    $new_post_id = CPC_create_post_with_categories_and_featured_image($new_post_title, $new_post_content, $new_post_type, $new_post_status, $new_categories, $new_featured_image_url);

    if ($new_post_id) {
        echo $new_post_title . ' - ID: ' . $new_post_id;
    } else {
        echo 'error';
    }
    return false;
}

add_action('wp_ajax_nopriv_CPC_import_new_post', 'CPC_import_new_post');
add_action('wp_ajax_CPC_import_new_post', 'CPC_import_new_post');

function CPC_upload_image_from_url($image_url) {
     if (empty($image_url)) {
        return false;
    }

    // Get the file name from the URL
    $file_name = basename($image_url);

    // Fetch the image data from the URL
    $image_data = wp_remote_get($image_url);

    // Check if the request was successful
    if (is_wp_error($image_data) || wp_remote_retrieve_response_code($image_data) !== 200) {
        return false;
    }

    // Upload the image to the media library
    $upload_dir = wp_upload_dir();
    $upload_file = $upload_dir['path'] . '/' . $file_name;

    // Save the image data to a file
    $saved_image = file_put_contents($upload_file, wp_remote_retrieve_body($image_data));

    if ($saved_image === false) {
        return false;
    }

    // Create attachment data
    $attachment = array(
        'post_title'     => sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME)),
        'post_mime_type' => wp_check_filetype($file_name)['type'],
        'post_status'    => 'inherit',
    );

    // Insert the attachment into the media library
    $attach_id = wp_insert_attachment($attachment, $upload_file);

    if (is_wp_error($attach_id)) {
        return false;
    }

    // Generate metadata for the attachment
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}
