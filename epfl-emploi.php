<?php
/**
 * Plugin Name: EPFL Emploi
 * Description: provides a shortcode to display job offers
 * Version: 2.0.0
 * Author: Lucien Chaboudez, Julien Delasoie
 * Contributors:
 * License: Copyright (c) 2021 Ecole Polytechnique Federale de Lausanne, Switzerland
 **/


namespace EPFL\Plugins\Emploi;

require_once(dirname(__FILE__).'/parse_emplois.php');


function epfl_emploi_process_shortcode( $atts, $content = null ) {

    $atts = shortcode_atts( array(
        'url' => '',
        'except_positions' => '',
        // to choose filter display location. "left" is default (for 2018) and we can select "top"
        'filter_pos' => 'left',
    ), $atts );

    /* We transform &amp; to & (and also others encoded things) to have a clean URL to work with*/
    $url                = htmlspecialchars_decode($atts['url']);
    $except_positions   = sanitize_text_field($atts['except_positions']);
    $filter_pos         = sanitize_text_field($atts['filter_pos']);

    if($url == '')
    {
        return '<b><font color="red">Please provide an URL</font></b>';
    }

    /* Including CSS file*/
    wp_enqueue_style( 'epfl_emploi_style', plugin_dir_url(__FILE__).'css/style.css' );

    ob_start();
    ?>
    <div class="container">
      <?= echo_job_offers($url);?>
    </div>

    <?php
    return ob_get_clean();
}

add_action( 'init', function() {
    // define the shortcode
    add_shortcode('epfl_emploi', __NAMESPACE__ . '\epfl_emploi_process_shortcode');
});

// Load .mo file for translation
add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'epfl-emploi', FALSE, basename( plugin_dir_path( __FILE__ )) . '/languages/');
});
