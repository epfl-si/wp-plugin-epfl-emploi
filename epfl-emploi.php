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

function echo_job_offers_list($job_offers) {

    foreach ($job_offers as $job_offer):
        $fonction =  $job_offer['Fonction'] ?? '';
        $intitule =  $job_offer['Intitule'] ?? '';
        $id =  $job_offer['ID'] ?? '';
        $type_de_contrat =  $job_offer['TypeDeContrat'] ?? '';
        $taux =  $job_offer['Taux'] ?? '';
        $faculte =  $job_offer['Faculte'] ?? '';
        $lieu =  $job_offer['Lieu'] ?? '';
        $en_ligne_depuis =  $job_offer['EnLigneDepuis'] ?? '';
        $url =  $job_offer['URL'] ?? '';
?>
      <div class="job-offer-row pl-2 mb-0 mt-0 pb-3 pt-2 border-bottom border-top align-items-center">
        <div class="job-offer-row-1 d-md-flex pl-md-0 pt-1 pb-0">
          <div class="col-12">
            <span><?= __('online since', 'epfl-emploi') ?>&nbsp;<span class="job-offer-enlignedepuis"><?= esc_html($en_ligne_depuis); ?></span></span>
          </div>
        </div>
        <div class="job-offer-row-2 d-md-flex pl-md-0 pt-1 pb-1">
          <div class="col mb-0 align-middle">
            <a class="job-offer-intitule" href="<?= esc_attr($url); ?>" target="_blank"><?= esc_html($intitule); ?></a>
          </div>
          <div class="col text-md-right align-middle">
            <span class="job-offer-taux"><?= esc_html($taux); ?></span><?php if (!empty($taux) && !empty($type_de_contrat)): ?>&nbsp;&ndash;<?php endif; ?><span class="job-offer-typedecontract"><?= esc_html($type_de_contrat); ?></span>
          </div>
        </div>
        <div class="job-offer-row-3 d-md-flex pl-md-0">
          <div class="col-12">
            <span><?= __('Function:', 'epfl-emploi') ?>&nbsp;<span class="job-offer-fonction"><?= esc_html($fonction); ?></span></span><?= (!empty($lieu)) ? '&nbsp;&nbsp;|&nbsp;' : ''  ?>
            <span><?= __('Location:', 'epfl-emploi') ?>&nbsp;<span class="job-offer-lieu"><?= esc_html($lieu); ?></span></span><?= (!empty($id)) ? '&nbsp;&nbsp;|&nbsp;' : ''  ?>
            <span><?= __('Job no.', 'epfl-emploi') ?>&nbsp;<span class="job-offer-id"><?= esc_html($id); ?></span></span>
          </div>
        </div>
      </div>

<?php
    endforeach;
}


function process_array_for_select_options($job_offers, $key) {
    if (empty($job_offers)) {
      return [];
    }
    $uniq = array_unique(array_column($job_offers, $key));
    sort($uniq);
    return $uniq ?? [];
}

// function has to be splitted by ',' for multiple values
function process_fonctions_for_select_options($job_offers) {
      $splitted_fonctions_select_options = [];
      $fonctions_select_options = process_array_for_select_options($job_offers, 'Fonction');

      foreach ($fonctions_select_options as $fonctions_option) {
          $splitted  = explode(',', $fonctions_option);

          foreach ($splitted as $fonction) {
              if (!in_array($fonction, $splitted_fonctions_select_options)) {
                  $splitted_fonctions_select_options[] = trim($fonction);
              }
          }
      }

      sort($splitted_fonctions_select_options);

      return $splitted_fonctions_select_options;
}

function epfl_emploi_process_shortcode( $atts, $content = null ) {

    wp_enqueue_script( 'epfl_emploi_list_script', plugin_dir_url(__FILE__).'js/list.min.js' );

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
    wp_enqueue_style( 'epfl_emploi_style', plugin_dir_url(__FILE__).'css/style.css', [], '2.0');

    $job_offers = get_job_offers($url);

    $fonctions_select_options = process_fonctions_for_select_options($job_offers);
    $lieu_select_options = process_array_for_select_options($job_offers, 'Lieu');
    $taux_select_options = process_array_for_select_options($job_offers, 'Taux');
    $type_de_contrat_select_options = process_array_for_select_options($job_offers, 'TypeDeContrat');

    ob_start();
    ?>
<div class="container">
  <div id="job-offers-list" class="d-flex flex-column">
    <div class="form-group">
      <form id="job-offers-form">
        <div class="col px-0">
          <input
                  type="text"
                  id="job-offers-search-input"
                  class="form-control search mb-2"
                  placeholder="<?= __('Search by keywords', 'epfl-emploi') ?>"
                  aria-describedby="job-offers-search-input"
          >
        </div>
        <div id="selects-filter" class="d-flex flex-wrap flex-column flex-md-row mb-2">
          <div class="col-md-3 px-0 pr-md-1 pl-md-0 mb-2">
            <select id="select-fonction" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Function', 'epfl-emploi'); ?>">
              <?php foreach ($fonctions_select_options as $fonction_option): ?>
                <option value="<?= esc_attr($fonction_option) ?>"><?= esc_html($fonction_option) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 px-0 pr-md-1 mb-2">
            <select id="select-lieu" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Location', 'epfl-emploi'); ?>">
                <?php foreach ($lieu_select_options as $lieu_option): ?>
                  <option value="<?= esc_attr($lieu_option) ?>"><?= esc_html($lieu_option) ?></option>
                <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 px-0 pr-md-1 mb-2">
            <select id="select-taux" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Work Rate', 'epfl-emploi'); ?>">
                <?php foreach ($taux_select_options as $taux_option): ?>
                  <option value="<?= esc_attr($taux_option) ?>"><?= esc_html($taux_option) ?></option>
                <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-3 px-0 pr-md-0 mb-2">
            <select id="select-typedecontract" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Term of employment', 'epfl-emploi'); ?>">
                <?php foreach ($type_de_contrat_select_options as $taux_option): ?>
                  <option value="<?= esc_attr($taux_option) ?>"><?= esc_html($taux_option) ?></option>
                <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>
    </div>

    <div class="list">
        <?= !empty($job_offers) ? echo_job_offers_list($job_offers) : ''; ?>
    </div>

    <nav aria-label="Page navigation">
      <ul class="pagination"></ul>
    </nav>
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
