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
<div class="container my-3">
  <div id="job-offers-list" class="d-flex flex-column">
    <div class="form-group">
      <div class="col">
        <input
                type="text"
                id="job-offers-search-input"
                class="form-control search mb-2"
                placeholder="<?= __('Search for a specific job offer...', 'epfl-emploi') ?>"
                aria-describedby="job-offers-search-input"
        >
      </div>
      <div id="selects-filter" class="d-flex flex-wrap flex-column flex-md-row mb-2">
        <div class="col-md-3">
          <select id="select-fonction" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Functions', 'epfl-emploi'); ?>">
            <option value="Administrative Staff">Administrative Staff</option>
            <option value="IT Staff">IT Staff</option>
            <option value="Technical Staff">Technical Staff</option>
            <option value="Management">Management</option>
          </select>
        </div>

        <div class="col-md-3">
          <select id="select-lieu" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Location', 'epfl-emploi'); ?>">
            <option value="Lausanne">Lausanne</option>
            <option value="Geneva">Geneva</option>
            <option value="Fribourg">Fribourg</option>
            <option value="Neuchâtel">Neuchâtel</option>
            <option value="Sion">Sion</option>
            <option value="Basel">Basel</option>
            <option value="Villigen">Villigen</option>
          </select>
        </div>

        <div class="col-md-3">
          <select id="select-taux" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Work Rate', 'epfl-emploi'); ?>">
            <option value="Full time">Full time</option>
            <option value="Part-time">Part-time</option>
          </select>
        </div>

        <div class="col-md-3">
          <select id="select-typedecontract" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Term of employment', 'epfl-emploi'); ?>">
            <option value="Unlimited (CDI)">Unlimited (CDI)</option>
            <option value="Fixed-term (CDD)">Fixed-term (CDD)</option>
          </select>
        </div>
      </div>
    </div>

    <div class="list">
        <?= echo_job_offers($url);?>
    </div>

    <ul class="pagination"></ul>

  </div>
</div>

<?php
load_template(dirname(__FILE__).'/list-js-config.php');
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
