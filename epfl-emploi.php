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
        // fonction can have multiple values, seperated by comma
        if (!empty($fonction)) {
          $fonctions = explode(',', $fonction);
        }

        $intitule =  $job_offer['Intitule'] ?? '';
        $id =  $job_offer['ID'] ?? '';
        $type_de_contrat =  $job_offer['TypeDeContrat'] ?? '';
        $taux =  $job_offer['Taux'] ?? '';
        $faculte =  $job_offer['Faculte'] ?? '';
        $lieu =  $job_offer['Lieu'] ?? '';
        $en_ligne_depuis =  $job_offer['EnLigneDepuis'] ?? '';
        $url =  $job_offer['URL'] ?? '';
?>
      <div class="job-offer-row pl-2 mb-0 mt-0 pb-3 pt-3 border-bottom border-top align-items-center">
        <div class="job-offer-row-1 d-md-flex pl-0 pt-0 pb-1">
          <div class="col-12 small font-weight-bold">
          <?php
          $last_fonction = end($fonctions);
          foreach ($fonctions as $one_fonction): ?>
            <span class="job-offer-fonction"><?= esc_html($one_fonction); ?></span><?php if ($one_fonction != $last_fonction):?>, <?php endif ?>
          <?php endforeach; ?>
          </div>
        </div>
        <div class="job-offer-row-2 d-md-flex pl-md-1 pt-1 pb-0">
          <div class="col font-weight-bold h4 mb-1">
            <a class="job-offer-intitule" href="<?= esc_attr($url); ?>" target="_blank"><?= esc_html($intitule); ?></a>
          </div>
          <div class="col text-md-right">
            <span class="job-offer-taux"><?= esc_html($taux); ?></span><?php if (!empty($taux) && !empty($type_de_contrat)): ?>,&nbsp;<?php endif; ?><span class="job-offer-typedecontract"><?= esc_html($type_de_contrat); ?></span>
          </div>
        </div>
        <div class="job-offer-row-4 d-md-flex pt-md-1 pb-md-0">
          <div class="col-md-4"><?= __('School / VP:', 'epfl-emploi') ?>&nbsp;<span class="job-offer-faculte"><?= esc_html($faculte); ?></span></div>

        </div>
        <div class="job-offer-row-5 d-md-flex pt-md-0 pb-md-0">
          <div class="col-md-4"><?= __('Location:', 'epfl-emploi') ?>&nbsp;<span class="job-offer-lieu"><?= esc_html($lieu); ?></span></div>
        </div>
        <div class="job-offer-row-6 d-md-flex pt-md-0 pb-md-0 small">
          <div class="col-md text-right">
            <span><?= __('Job no.', 'epfl-emploi') ?>&nbsp;<span class="job-offer-id"><?= esc_html($id); ?></span>, </span>
            <span><?= __('online since', 'epfl-emploi') ?>&nbsp;<span class="job-offer-enlignedepuis font-weight-bold"><?= esc_html($en_ligne_depuis); ?></span></span>
          </div>
        </div>
      </div>

<?php
    endforeach;
}


function process_array_for_select_options($job_offers, $key) {
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

    $job_offers = get_job_offers($url);

    $fonctions_select_options = process_fonctions_for_select_options($job_offers);
    $lieu_select_options = process_array_for_select_options($job_offers, 'Lieu');
    $taux_select_options = process_array_for_select_options($job_offers, 'Taux');
    $type_de_contrat_select_options = process_array_for_select_options($job_offers, 'TypeDeContrat');

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
            <?php foreach ($fonctions_select_options as $fonction_option): ?>
              <option value="<?= esc_attr($fonction_option) ?>"><?= esc_html($fonction_option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <select id="select-lieu" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Location', 'epfl-emploi'); ?>">
              <?php foreach ($lieu_select_options as $lieu_option): ?>
                <option value="<?= esc_attr($lieu_option) ?>"><?= esc_html($lieu_option) ?></option>
              <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <select id="select-taux" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Work Rate', 'epfl-emploi'); ?>">
              <?php foreach ($taux_select_options as $taux_option): ?>
                <option value="<?= esc_attr($taux_option) ?>"><?= esc_html($taux_option) ?></option>
              <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <select id="select-typedecontract" class="select-multiple" multiple="multiple" data-placeholder="<?= __('Term of employment', 'epfl-emploi'); ?>">
              <?php foreach ($type_de_contrat_select_options as $taux_option): ?>
                <option value="<?= esc_attr($taux_option) ?>"><?= esc_html($taux_option) ?></option>
              <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="list">
        <?= echo_job_offers_list($job_offers); ?>
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
