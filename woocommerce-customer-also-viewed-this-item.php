<?php
/**
 * Plugin Name: WooCommerce - Customer Who Viewed This Item Also Viewed
 * Plugin URL: http://wordpress.org/plugins/woocommerce-customer-also-viewed-this-item
 * Description:  This plugin will suggest your site visitors with products which were mostly explored by other customers. 
 * Version: 2.0
 * Author: ZealousWeb Technologies
 * Author URI: http://zealousweb.com
 * Developer: The Zealousweb Team
 * Developer E-Mail: info@opensource.zealousweb.com
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 * 
 * Copyright: © 2009-2015 ZealousWeb Technologies.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * Register the [customer_also_viewed] shortcode
 *
 * This shortcode displays customer who viewed this item also viewed using WooCommerce cookie
 * It has multiple options to choose number of items to show, show pricing, show add to cart button, show image, etc.
 *
 * @access      public
 * @since       1.0 
 * @return      $content
*/
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
require_once (dirname(__FILE__) . '/woocommerce-customer-also-viewed-this-item.php');

register_activation_hook (__FILE__, 'activation_check');
function activation_check()
{
    if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        wp_die( __( '<b>Warning</b> : Install/Activate Woocommerce to activate "WooCommerce - Customer Who Viewed This Item Also Viewed" plugin', 'woocommerce' ) );
    }
}
//Add settings link to plugins page
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );

function add_action_links ( $links ) {
     $settingslinks = array(
     '<a href="' . admin_url( 'admin.php?page=customer-also-viewed-settings' ) . '">Settings</a>',
     );
    return array_merge( $settingslinks, $links );
}

//Set up menu under woocommerce
add_action('admin_menu', 'customer_also_viewed_setup_menu');
//Register options of this plugin
add_action( 'admin_init', 'register_mysettings' );
//Use default woocommerce stylesheet
$stylefile = plugins_url().'/woocommerce/assets/css/woocommerce.scss';
if(file_exists($stylefile))
{
    wp_enqueue_style( 'woocommerce-style', $stylefile );
}
/**
 * Set up submenu under woocommerce main menu at admin side
 */
function customer_also_viewed_setup_menu(){
        add_submenu_page( 'woocommerce', 'Customer Also Viewed Settings', 'Customer Also Viewed Settings', 'manage_options', 'customer-also-viewed-settings', 'customer_also_viewed_init'); 
}
/**
 * Initialize the plugin and display all options at admin side
 */
function customer_also_viewed_init(){
?>
  <h1>WooCommerce - Customer Who Viewed This Item Also Viewed</h1>  
  <form method="post" action="options.php">
    <?php settings_fields( 'customer-also-viewed-settings' ); ?>
    <?php do_settings_sections( 'customer-also-viewed-settings' ); ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Title to be displayed:</th>
            <td><input type="text" name="customer_who_viewed_title" value="<?php echo get_option( 'customer_who_viewed_title' ); ?>"/></td>      
        </tr>
        <tr valign="top">
            <th scope="row">Number of items to be displayed:</th>
            <td><input type="text" name="total_items_display" value="<?php echo get_option( 'total_items_display' ); ?>"/>&nbsp;&nbsp;&nbsp;NOTE: You cannot display items more than 10.</td>
       </tr> 
        <tr valign="top">
            <th scope="row">Add category filter:</th>
            <td>
                <input type="checkbox" name="category_filter" value="1" <?php echo (get_option( 'category_filter' ) == 1) ? 'checked': '';?>/>                
            </td>
        </tr> 
        <tr valign="top">
            <th scope="row">Show product image:</th>
            <td>
                <input type="checkbox" name="show_image_filter" value="1" <?php echo (get_option( 'show_image_filter' ) == 1) ? 'checked': '';?>/>
            </td>
        </tr> 
        <tr valign="top">
            <th scope="row">Show product price:</th>
            <td>
                <input type="checkbox" name="show_price_filter" value="1" <?php echo (get_option( 'show_price_filter' ) == 1) ? 'checked': '';?>/>
            </td>
        </tr> 
         <tr valign="top">
            <th scope="row">Show add to cart button:</th>
            <td>
                <input type="checkbox" name="show_addtocart_filter" value="1" <?php echo (get_option( 'show_addtocart_filter' ) == 1) ? 'checked': '';?>/>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Order by:</th>
            <td>
                <select name = "product_order">                    
                    <option value="" >Recent</option>
                    <option value="rand" <?php echo (get_option( 'product_order' ) == 'rand') ? 'selected': '';?>>Random</option>
                </select>                
            </td>
        </tr>
    </table>
    <?php submit_button(); ?>
    </div>
  </form>

<?php
}

add_action('admin_footer', 'jwt_validate_number_of_items');

function jwt_validate_number_of_items(){ 
    $script = "<script>
            jQuery('input[name=total_items_display]').change(function() {
            var val = Math.abs(parseInt(this.value, 10) || 1);
            this.value = val > 10 ? 3 : val;
        });
    </script>";
    echo $script;
}
/**
 * Registers all the setting options
 */
function register_mysettings() {
        register_setting( 'customer-also-viewed-settings', 'customer_who_viewed_title' );
        register_setting( 'customer-also-viewed-settings', 'total_items_display' );
        register_setting( 'customer-also-viewed-settings', 'category_filter' );
        register_setting( 'customer-also-viewed-settings', 'show_image_filter' );
        register_setting( 'customer-also-viewed-settings', 'show_price_filter' );
        register_setting( 'customer-also-viewed-settings', 'show_addtocart_filter' );
        register_setting( 'customer-also-viewed-settings', 'product_order' );
 } 

 /**
   * Add related products to options
   */

add_action('woocommerce_after_single_product', 'zwt_woocommerce_relation_product_options');

function zwt_woocommerce_relation_product_options()
{
    global $post;   
    $customer_also_viewed = ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ? (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] ) : array();
    if(($key = array_search($post->ID, $customer_also_viewed)) !== false) { unset($customer_also_viewed[$key] ); }

    if(!empty($customer_also_viewed))
    {
        foreach($customer_also_viewed as $viewed)
        {
            $option = 'customer_also_viewed_'.$viewed;
            $option_value = get_option($option);
            
            if(isset($option_value) && !empty($option_value))
            {
                $option_value = explode(',', $option_value);
                if(!in_array($post->ID,$option_value))
                {
                    $option_value[] = $post->ID;
                }
            }
                       
            $option_value = (count($option_value) > 1) ? implode(',', $option_value) : $post->ID;

            update_option($option, $option_value);
        }
    }    
}
/**
 * 
 * @global type $woocommerce
 * @global type $post
 */
function zwt_woocommerce_customer_also_viewed( $atts, $content = null ) {
        $per_page = get_option( 'total_items_display' );
        $plugin_title = get_option( 'customer_who_viewed_title' );
        $category_filter = get_option( 'category_filter' );
        $show_image_filter = get_option( 'show_image_filter' );
        $show_price_filter = get_option( 'show_price_filter' );
        $show_addtocart_filter = get_option( 'show_addtocart_filter' );
        $product_order = get_option( 'product_order' );
        // Get WooCommerce Global
        global $woocommerce;
        global $post;
        // Get recently viewed product data using get_option

        $customer_also_viewed = get_option('customer_also_viewed_'.$post->ID);       
        $customer_also_viewed = explode(',',$customer_also_viewed);
        $customer_also_viewed = array_reverse($customer_also_viewed);
       
        if(empty($customer_also_viewed))        
        {           
            // If no data, quit           
            exit;
        }
        //Skip same product on product page from the list
        if(($key = array_search($post->ID, $customer_also_viewed)) !== false) { unset($customer_also_viewed[$key] ); }

        $per_page = ($per_page == "")? $per_page = 5 : $per_page;
        $plugin_title = ($plugin_title == "")? $plugin_title = 'Customer Who Viewed This Item Also Viewed' : $plugin_title;

        // Create the object
        ob_start();        

        $categories = get_the_terms( $post->ID, 'product_cat' );  
        
        // Create query arguments array
        $query_args = array(
                                'posts_per_page' => $per_page, 
                                'no_found_rows'  => 1, 
                                'post_status'    => 'publish', 
                                'post_type'      => 'product',                                
                                'post__in'       => $customer_also_viewed                                
                                );
       
        $query_args['orderby'] = ($product_order == '') ? 'ID(ID, explode('.$customer_also_viewed.'))' : $product_order;


        //Executes if category filter applied on product page
        if($category_filter == 1 && !empty($categories))
        {
            foreach ($categories as $category) {
            if($category->parent == 0){
                   $category_slug = $category->slug;
                }
            }
            $query_args['product_cat'] = $category_slug;
        }

        // Add meta_query to query args
        $query_args['meta_query'] = array();

        // Check products stock status
        $query_args['meta_query'][] = $woocommerce->query->stock_status_meta_query();

        // Create a new query
        $products = new WP_Query($query_args); 
      
        // If query return results
        if ( !$products->have_posts() ) {     
            // If no data, quit            
            exit;           
        } 
        else { //Displays title ?>        
        <h2><?php _e( $plugin_title, 'woocommerce' ) ?></h2>
            <div class="clear">
                <div class="woocommerce customer_also_viewed">                   
                    <?php // Start the loop     
                    $count = 1;
                     woocommerce_product_loop_start();
                     while ( $products->have_posts() ) : $products->the_post(); ?>
                         <li class="product">
                             <?php do_action( 'woocommerce_before_shop_loop_item' );?>
                                 <a href="<?php the_permalink(); ?>">
                                     <?php if($show_image_filter == 1) { do_action( 'woocommerce_before_shop_loop_item_title' ); } ?>
                                         <h3><?php the_title(); ?></h3>
                                     <?php if($show_price_filter == 1){ do_action( 'woocommerce_after_shop_loop_item_title' ); } ?>
                                 </a>
                             <?php if($show_addtocart_filter == 1) { do_action( 'woocommerce_after_shop_loop_item' ); } ?>                        
                         </li>
                     <?php endwhile; ?>
                     <?php woocommerce_product_loop_end(); ?>
                </div>
            <div class="clear">
       <?php }
        wp_reset_postdata();
}

// Register the shortcode
add_action("woocommerce_after_single_product", "zwt_woocommerce_customer_also_viewed");
