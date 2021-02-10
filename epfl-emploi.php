<?php
/**
 * Plugin Name: EPFL Emploi
 * Description: provides a shortcode to display job offers
 * Version: 1.8.1
 * Author: Lucien Chaboudez
 * Contributors:
 * License: Copyright (c) 2019 Ecole Polytechnique Federale de Lausanne, Switzerland
 **/


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

    wp_enqueue_style( 'epfl_emploi_filter_style', plugin_dir_url(__FILE__).'css/style.css' );
    wp_enqueue_script( 'epfl_emploi_list_js', plugin_dir_url(__FILE__).'js/list.min.js' );

    /* We have to remove all URL parameters named 'searchPosition' to have 'searchPositionUrl' value for JS */
    $url_query = parse_url($url, PHP_URL_QUERY);

    parse_str($url_query, $parameters);

    if(array_key_exists('searchPosition', $parameters))
    {
       unset($parameters['searchPosition']);
    }

    $new_url_query = http_build_query($parameters);
    /* We replace query in original url to have 'searchPositionUrl' value for JS */
    $url_search_position = str_replace($url_query, $new_url_query, $url);

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

    <?php
    $job_offers = array_fill(0, 83, '1');

    $i = 0;

    foreach ($job_offers as $job_offer):
        $i += 1;
    ?>
      <div class="job-offer-row pl-2 mb-0 mt-0 pb-3 pt-3 border-bottom border-top align-items-center">
        <div class="job-offer-row-1 d-md-flex pl-0 pt-0 pb-1">
          <div class="col-12 small font-weight-bold">
            <span class="job-offer-fonction">IT Staff</span>
          </div>
        </div>
        <div class="job-offer-row-2 d-md-flex pl-md-1 pt-1 pb-0">
          <div class="col font-weight-bold h4 mb-1">
            <a class="job-offer-intitule" href="https://recruiting.epfl.ch/Vacancies/1690/Description/2" target="_blank">Scientific Programmer (W/M) <?= $i ?></a>
          </div>
          <div class="col text-md-right">
            <span class=" job-offer-taux">Full time</span>,&nbsp;<span class="job-offer-typedecontract">Fixed-term (CDD)</span>
          </div>
        </div>
        <div class="job-offer-row-4 d-md-flex pt-md-1 pb-md-0">
          <div class="col-md-4">School / VP: <span class="job-offer-faculte">ENT-R</span></div>

        </div>
        <div class="job-offer-row-5 d-md-flex pt-md-0 pb-md-0">
          <div class="col-md-4">Location: <span class="job-offer-lieu">Geneva</span></div>
        </div>
        <div class="job-offer-row-6 d-md-flex pt-md-0 pb-md-0 small">
          <div class="col-md text-right">
            <span>Job no. <span class="job-offer-id">1691</span>, </span>
            <span>online since <span class="job-offer-enlignedepuis font-weight-bold">02/09/2021</span></span>
          </div>
        </div>
      </div>

    <?php
      endforeach;
    ?>
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
  add_shortcode('epfl_emploi', 'epfl_emploi_process_shortcode');
});

// Load .mo file for translation
add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'epfl-emploi', FALSE, basename( plugin_dir_path( __FILE__ )) . '/languages/');
});
