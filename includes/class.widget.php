<?php

/**
 * Widget Class Page
 * 
 * PHP version 5.6.x | 7.x | 8.x
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */

/**
 * Widget Class
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */
class WoocommerceIR_Widget_SMS extends WP_Widget
{

    /**
     * Class Constructor
     *
     * @return void
     */
    function __construct()
    {
        parent::__construct(
            'WoocommerceIR_Widget_SMS', 
            __('اطلاع رسانی پیامکی ووکامرس', 'persianwoosms'), 
            array('description' => __('این ابزارک را فقط باید در صفحه محصولات استفاده کنید .', 'persianwoosms'),) 
        );
    }

    /**
     * Building Form
     *
     * @param array $instance instance
     *
     * @return void
     */
    public function form($instance)
    {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('اطلاع رسانی پیامکی', 'persianwoosms');
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php 
    }

    /**
     * Widget
     *
     * @param integer $args     argumans
     * @param string  $instance instance
     *
     * @return void
     */
    public function widget($args, $instance)
    {
        if (!is_product()) return;
        $title = apply_filters('widget_title', $instance['title']);
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];
        echo do_shortcode('[woo_ps_sms]');
        echo $args['after_widget'];
    }

    /**
     * Update Widget
     *
     * @param array $new_instance new_instance
     * @param array $old_instance old_instance
     *
     * @return array instance
     */
    public function update( $new_instance, $old_instance ) 
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * Woocommerce Load Widget Function
 *
 * @return array instance
 */
function wooPsSmsLoadWidget() 
{
    register_widget('WoocommerceIR_Widget_SMS');
}