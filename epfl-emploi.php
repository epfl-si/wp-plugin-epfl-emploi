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

    /* Lang to numeric id for JS code */
    $lang_to_id = array('en' => 2, 'fr' => 3);

    /* If Polylang installed */
    if(function_exists('pll_current_language'))
    {
        $lang = pll_current_language();
        /* Set in english if not in allowed languages */
        if(!array_key_exists($lang, $lang_to_id)) $lang = 'en';

    }
    else /* Polylang not installed */
    {
        $lang = 'en';
    }


    /* Including CSS file*/
    wp_enqueue_style( 'epfl_emploi_style', plugin_dir_url(__FILE__).'css/style.css' );

    if($filter_pos == 'left')
    {
        wp_enqueue_style( 'epfl_emploi_filter_style', plugin_dir_url(__FILE__).'css/style-filter-left.css' );
    }
    else
    {
        wp_enqueue_style( 'epfl_emploi_filter_style', plugin_dir_url(__FILE__).'css/style-filter-top.css' );
    }

    $url_parts = parse_url($url);
    parse_str($url_parts['query'], $all_parameters);

    #$all_parameters["lang"] = $all_parameters["lang"] ?? $lang_to_id[$lang];
    $all_parameters["searchPosition"] = $except_positions;

    $url_parts['query'] = http_build_query($all_parameters);
    $url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '?' . $url_parts['query'];

    $job_offers = get_job_offers($url);

    ob_start();

    /* If filters must appears on the left, */
    if($filter_pos=='left')
    {
        ?>
      <div class="container">

      <div class="search-filters">
    <?PHP } ?>

  <div aria-expanded="true" aria-hidden="false" aria-labelledby="toggle-1" class="list-unstyled toggle-expanded" id="toggle-pane-0">&nbsp;</div>

    <?PHP
    /* If filters must appears on the left, */
if($filter_pos=='left')
{
    ?>
  </div>
<?PHP } ?>

  <div id="umantis_iframe">&nbsp;<?= $job_offers ?> </div>

    <?PHP
    /* If filters must appears on the left, */
    if($filter_pos=='left')
    {
        ?>
      </div>

    <?php }

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
