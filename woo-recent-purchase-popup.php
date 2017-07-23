<?php
/*
Plugin Name: Leodan WooCommerce Recent Activity Popup
Plugin URI: https://leodandesign.co.uk
Description: Displays a popup on front pages that give users ideas for what they should look at or purchase
Author: Ben Matthews / Leodan:Design
Author URI: https://benjaminmatthews.me.uk
Version: 1.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

function leodan_woo_recent_footer_integration() {
    if ( !is_cart() && !is_checkout()) {
        ?>
        <div class="footer_popup" style="display:none;">
            <div class="footer_popup_info">

            </div>
            <p style="text-align: right;">
                <button class="close_popup">Close</button>
            </p>

        </div>
        <?php
        ?>
        <style>
            .footer_popup {
                position: fixed;
                bottom: 2vw;
                left: 2vw;
                width: 320px;
                background-color: white;
                z-index: 999;
                padding: 10px 10px 10px 10px;
                max-width: 75vw;
            }

            .footer_popup_info {
                border: 1px solid black;
                padding: 0px 5px;
                margin-bottom: 5px;
            }

            .footer_popup img {
                width: 100% !important;
                height: auto !important;
            }

            .footer_popup table, .footer_popup tr, .footer_popup td {
                border: none;
            }

            .footer_popup p {
                margin: 0px;
            }
            .footer_popup button, .footer_popup table, .footer_popup h6 {
                margin: 0px;
            }
        </style>
        <script>
            jQuery(document).ready(function ($) {
                var popup_stopped = 0;
                var shown_popups = new Array();
                $(window).on('load', function () {
                    function show_popup() {
                        var random_number = Math.floor(Math.random() * 20000) + 4000;
                        if (popup_stopped != 1) {
                            var data = {'action': 'leodan_get_popup', 'exclude': shown_popups};
                            var admin_ajax_url = '<?php bloginfo('url'); ?>/wp-admin/admin-ajax.php';
                            $.post(admin_ajax_url, data, function (response) {
                                if (response != 'Nothing To Return') {
                                    var popup_id = $(".footer_popup_info table").attr('data-id');
                                    shown_popups.push (popup_id);
                                    $(".footer_popup_info").append(response);
                                    $(".footer_popup").fadeIn(1000, 'swing', function () {
                                        setTimeout(hide_popup, 3000);
                                        setTimeout(show_popup, random_number);
                                    });
                                } else {
                                    popup_stopped = 1;
                                    setTimeout(show_popup, 15000);
                                }
                            });
                        } else {
                            setTimeout(show_popup, 15000);
                            popup_stopped = 0;
                        }
                    };

                    function hide_popup() {
                        $(".footer_popup").fadeOut(1000, 'swing', function () {
                            $(".footer_popup_info").empty();
                        });
                    }

                    window.setTimeout(show_popup, 5000);
                    $('.close_popup').click(function () {
                        popup_stopped = 1;
                        hide_popup();
                    });
                });
            });
        </script>
        <?php
    }
}

add_action('wp_footer', 'leodan_woo_recent_footer_integration');

function leodan_woo_recent_get_order_ajax()
{
    $filters = array(
        'post_status' => array('processing', 'completed'),
        'post_type' => 'shop_order',
        'posts_per_page' => 1,
        'orderby' => 'rand',
        // Using the date_query to filter posts from last week
        'date_query' => array(
            array(
                'after' => '1 month ago'
            )
        )
    );

    $loop = new WP_Query($filters);
    if ($loop->have_posts()) {
        while ($loop->have_posts()) {
            $loop->the_post();
            $order = new WC_Order($loop->post->ID);
            ?>
            <h6><?php echo $order->data['billing']['first_name'];
                if (isset($order->data['billing']['city'])) {
                    echo ' from ' . $order->data['billing']['city'];
                } ?> purchased:</h6>
            <table data-id="<?php echo $order->id; ?>">

                <?php
                $product_array = array();
                foreach ($order->get_items() as $key => $lineItem) {
                    if (0 != $lineItem['variation_id']) {
                        $product_array[$lineItem['variation_id']] = wc_get_product($lineItem['variation_id']);
                    } else if (0 != $lineItem['product_id']) {
                        $product_array[$lineItem['product_id']] = wc_get_product($lineItem['product_id']);
                    }
                }
                $product = array_rand($product_array, 1);
                $product_sku = $product_array[$product]->id;
                $product_name = preg_replace('~\(#.*\)~', '', $product_array[$product]->get_formatted_name());
                echo '<tr><td style="width: 35%;"><a href="' . get_the_permalink($product) . '">' . $product_array[$product]->get_image() . '</a></td>';
                echo '<td style="width: 65%;"><p><a href="' . get_the_permalink($product) . '">' . $product_name . '</p></a></td></tr>';
                ?>
            </table>
            <?php
        }
    } else {
        echo 'Nothing To Return';
    }
    wp_die();
}
add_action( 'wp_ajax_leodan_get_popup', 'leodan_woo_recent_get_order_ajax' );
add_action( 'wp_ajax_nopriv_leodan_get_popup', 'leodan_woo_recent_get_order_ajax' );