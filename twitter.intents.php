<?php
/*
Plugin Name: Twitter Intents
Plugin URI: https://github.com/jkup/twitter-intents
Description: Add Twitter intents functionality to your blog. Easily let your readers reply, retweet, or favorite your posts!
Author: Jon Kuperman
Version: 0.1.0
Author URI: http://jonkuperman.com
*/

class TwitterIntents {
    public static function add_admin_widget() {

        $screens = array( 'post', 'page' );

        foreach ( $screens as $screen ) {

            add_meta_box(
                'TwitterIntents_sectionid',
                __( 'Twitter Intents', 'TwitterIntents_textdomain' ),
                array(
                    'TwitterIntents',
                    'admin_widget_callback'
                ),
                $screen,
                'side'
            );
        }
    }

    /**
     * Prints the box content.
     * 
     * @param WP_Post $post The object for the current post/page.
     */
    public static function admin_widget_callback($post) {

        // Add an nonce field so we can check for it later.
        wp_nonce_field('TwitterIntents_meta_box', 'TwitterIntents_meta_box_nonce');

        /*
         * Use get_post_meta() to retrieve an existing value
         * from the database and use the value for the form.
         */
        $value = get_post_meta($post->ID, '_twitter_intents_id', true);

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
    public static function save_tweet_id($post_id) {

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
        if (!isset($_POST['TwitterIntents_new_field']) || !is_numeric($_POST['TwitterIntents_new_field']))
            return;

        // Sanitize user input.
        $my_data = sanitize_text_field($_POST['TwitterIntents_new_field']);

        // Update the meta field in the database.
        update_post_meta($post_id, '_twitter_intents_id', $my_data);
    }

    public static function show_intents($content) {
        $id = get_the_ID();

        $tweet_id = get_post_meta($id, '_twitter_intents_id', true);

        if (is_numeric($tweet_id)) {
            $content .= "
                <div class='TwitterIntents'>
                    <a href='https://twitter.com/intent/tweet?in_reply_to={$tweet_id}'>
                        <span class='TwitterIntents--reply'></span>
                    </a>
                    <a href='https://twitter.com/intent/retweet?tweet_id={$tweet_id}'>
                        <span class='TwitterIntents--retweet'></span>
                    </a>
                    <a href='https://twitter.com/intent/favorite?tweet_id={$tweet_id}'>
                        <span class='TwitterIntents--favorite'></span>
                    </a>
                </div>
            ";
        }

        return $content;
    }

    public static function add_stylesheet() {
        wp_enqueue_style( 'core', plugins_url() . '/twitter-intents/css/twitter-intents.css', false ); 
    }
}

add_filter('the_content', array('TwitterIntents', 'show_intents'));
add_action('save_post', array('TwitterIntents', 'save_tweet_id'));
add_action('add_meta_boxes', array('TwitterIntents', 'add_admin_widget'));
add_action('wp_enqueue_scripts', array('TwitterIntents', 'add_stylesheet'));