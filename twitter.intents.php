<?php
/*
Plugin Name: Twitter Intents
Plugin URI: https://github.com/jkup/twitter-intents
Description: Add Twitter intents functionality to your blog. Easily let your readers reply, retweet, or favorite your posts!
Author: Jon Kuperman
Version: 0.1.0
Author URI: http://jonkuperman.com
*/

/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function TwitterIntents_add_meta_box() {

    $screens = array( 'post', 'page' );

    foreach ( $screens as $screen ) {

        add_meta_box(
            'TwitterIntents_sectionid',
            __( 'Twitter Intents', 'TwitterIntents_textdomain' ),
            'TwitterIntents_meta_box_callback',
            $screen
        );
    }
}

add_action( 'add_meta_boxes', 'TwitterIntents_add_meta_box' );

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function TwitterIntents_meta_box_callback($post) {

    // Add an nonce field so we can check for it later.
    wp_nonce_field('TwitterIntents_meta_box', 'TwitterIntents_meta_box_nonce');

    /*
     * Use get_post_meta() to retrieve an existing value
     * from the database and use the value for the form.
     */
    $value = get_post_meta($post->ID, '_my_meta_value_key', true);

    echo '<label for="TwitterIntents_new_field">';
    _e('Tweet ID: ', 'TwitterIntents_textdomain');
    echo '</label> ';
    echo '<input type="text" id="TwitterIntents_new_field" name="TwitterIntents_new_field" value="' . esc_attr( $value ) . '" size="25" />';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function TwitterIntents_save_meta_box_data($post_id) {

    // Check if our nonce is set.
    if (!isset($_POST['TwitterIntents_meta_box_nonce']))
        return;

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['TwitterIntents_meta_box_nonce'], 'TwitterIntents_meta_box'))
        return;

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // Check the user's permissions.
    if (isset($_POST['post_type'] ) && 'page' == $_POST['post_type']) {

        if (!current_user_can('edit_page', $post_id))
            return;

    } else {

        if (!current_user_can( 'edit_post', $post_id))
            return;
    }

    
    // Make sure that it is set.
    if (!isset($_POST['TwitterIntents_new_field']))
        return;

    // Sanitize user input.
    $my_data = sanitize_text_field($_POST['TwitterIntents_new_field']);

    // Update the meta field in the database.
    update_post_meta($post_id, '_my_meta_value_key', $my_data);
}

add_action('save_post', 'TwitterIntents_save_meta_box_data');

function TwitterIntents_show_intents() {
    $id = get_the_ID();

    $tweet_id = get_post_meta($id, '_my_meta_value_key', true);

    if (isset($tweet_id)) {
        echo '<div class="social">
            <a href="https://twitter.com/intent/tweet?in_reply_to=' . $tweet_id . '">
                <span>Reply</span>
            </a>
            <a href="https://twitter.com/intent/retweet?tweet_id=' . $tweet_id . '">
                <span>Retweet</span>
            </a>
            <a href="https://twitter.com/intent/favorite?tweet_id=' . $tweet_id . '">
                <span>Favorite</span>
            </a>
        </div>';
    }
}

add_filter('the_content', 'TwitterIntents_show_intents');