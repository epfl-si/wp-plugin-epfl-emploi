<?php

namespace EPFL\Plugins\Emploi;

define(__NAMESPACE__ . "\REMOTE_SERVER_TIMEOUT", 10);  // time to wait until we consider the remote server out of the game
define(__NAMESPACE__ . "\REMOTE_SERVER_SSL", true);  // force the server to be https certified
define(__NAMESPACE__ . "\LOCAL_CACHE_NAME", 'EPFL_EMPLOI_OFFERS');  // the option and transient name for the caching
define(__NAMESPACE__ . "\LOCAL_CACHE_TIMEOUT", 15 * MINUTE_IN_SECONDS);  // cache time validity, in seconds

/**
 * Remove empty values from JSON decode result
 */
function array_filter_recursive($input)
{
    foreach ($input as &$value)
    {
        if (is_array($value))
        {
            $value = array_filter_recursive($value);
        }
    }

    return array_filter($input);
}

/**
 * @param string $html html to parse
 * @param string $base_url the url used first time to crawlresults
 * @param int|null $total_count When null, we are doing the first iteration
 * @throws \Exception
 */

function parse_job_offers(string $xml_job_offers) {
    $xml = simplexml_load_string($xml_job_offers, "SimpleXMLElement", LIBXML_NOCDATA);
    $json = json_encode($xml);
    $job_offers = json_decode($json,TRUE);

    $job_offers = array_filter_recursive(array_values($job_offers)[0]);

    return $job_offers;
}

/**
 * Fetch and save job offers result in a transient, and in the WP options table as a fallback in case of
 * the url is not responding anymore
 * This process is deactivated if we are in DEBUG mode
 * @param string $url where to fetch all job offers in html
 * @return array the job offers or null if server is not responding
 */
function echo_job_offers(string $url) {
    $job_offers = null;

    try {
        # First, check if we have a transient
        if ( (defined('WP_DEBUG') && WP_DEBUG) || false === ( $job_offers = get_transient( LOCAL_CACHE_NAME ) ) ) {
            # no transient, then try to get some data

            $response = wp_remote_get($url, array('timeout' => REMOTE_SERVER_TIMEOUT, 'sslverify' => REMOTE_SERVER_SSL));

            if (is_wp_error($response)) {
                # unwanted error, throw it to get a fallback
                throw new \Exception($response->get_error_message());
            } else {
                # Remote server is responding; parse it and cache it in transient and option table
                $remote_xml = wp_remote_retrieve_body($response);

                $job_offers = parse_job_offers($remote_xml);

                if (!empty($job_offers)) {
                    set_transient(LOCAL_CACHE_NAME, $job_offers, LOCAL_CACHE_TIMEOUT);
                    # persist into options too, as a fallback if remote server get down
                    update_option(LOCAL_CACHE_NAME, $job_offers);
                } else {
                    # nothing or empty result has been returned from the server, reset local entries
                    set_transient(LOCAL_CACHE_NAME, [], LOCAL_CACHE_TIMEOUT);
                    delete_option(LOCAL_CACHE_NAME);
                }
            }
        }
    } catch (\Exception $e) {
        # Remote server is not responding or there is a general error, get the local option and
        # set a transient, so we dont refresh until the LOCAL_CACHE_TIMEOUT time

        $data_from_option = get_option(LOCAL_CACHE_NAME);
        if ($data_from_option === false) {
            # so we don't have option as fallback.. set transient to nothing as a refresh
            set_transient(LOCAL_CACHE_NAME, [], LOCAL_CACHE_TIMEOUT);
            $job_offers = null;
        } else {
            # update transient with what we got in option
            set_transient(LOCAL_CACHE_NAME, $data_from_option, LOCAL_CACHE_TIMEOUT);
            $job_offers = $data_from_option;
        }

        # add a friendly message about this problem
        echo '<p><b><font color="red">The URL provided is having problem. Data can be obsolete. Showing the last working version</font></b></p>';

    } finally {
        # show what we built
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
        <div class="job-offer-row pl-2 mb-0 mt-0 pb-3 pt-3 border-bottom border-top align-items-center">
            <div class="job-offer-row-1 d-md-flex pl-0 pt-0 pb-1">
                <div class="col-12 small font-weight-bold">
                    <span class="job-offer-fonction"><?= esc_html($fonction); ?></span>
                </div>
            </div>
            <div class="job-offer-row-2 d-md-flex pl-md-1 pt-1 pb-0">
                <div class="col font-weight-bold h4 mb-1">
                    <a class="job-offer-intitule" href="<?= esc_attr($url); ?>" target="_blank"><?= esc_html($intitule); ?></a>
                </div>
                <div class="col text-md-right">
                    <span class=" job-offer-taux"><?= esc_html($taux); ?></span><?php if (!empty($taux) && !empty($type_de_contrat)): ?>,&nbsp;<?php endif; ?><span class="job-offer-typedecontract"><?= esc_html($type_de_contrat); ?></span>
                </div>
            </div>
            <div class="job-offer-row-4 d-md-flex pt-md-1 pb-md-0">
                <div class="col-md-4">School / VP: <span class="job-offer-faculte"><?= esc_html($faculte); ?></span></div>

            </div>
            <div class="job-offer-row-5 d-md-flex pt-md-0 pb-md-0">
                <div class="col-md-4">Location: <span class="job-offer-lieu"><?= esc_html($lieu); ?></span></div>
            </div>
            <div class="job-offer-row-6 d-md-flex pt-md-0 pb-md-0 small">
                <div class="col-md text-right">
                    <span>Job no. <span class="job-offer-id"><?= esc_html($id); ?></span>, </span>
                    <span>online since <span class="job-offer-enlignedepuis font-weight-bold"><?= esc_html($en_ligne_depuis); ?></span></span>
                </div>
            </div>
        </div>
        <?php
        endforeach;
    }
}
