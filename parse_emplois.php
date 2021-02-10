<?php

namespace EPFL\Plugins\Emploi;

define(__NAMESPACE__ . "\REMOTE_SERVER_TIMEOUT", 10);  // time to wait until we consider the remote server out of the game
define(__NAMESPACE__ . "\REMOTE_SERVER_SSL", true);  // force the server to be https certified
define(__NAMESPACE__ . "\LOCAL_CACHE_NAME", 'EPFL_EMPLOI_OFFERS');  // the option and transient name for the caching
define(__NAMESPACE__ . "\LOCAL_CACHE_TIMEOUT", 15 * MINUTE_IN_SECONDS);  // cache time validity, in seconds

function load_DOMHTML_silently($html) {
    $dom = new \DomDocument;

    // We don't really care about errors for this part
    // modify state
    $libxml_previous_state = libxml_use_internal_errors(true);
    // parse
    $has_loaded_html = $dom->loadHTML($html);
    // handle errors
    libxml_clear_errors();
    // restore
    libxml_use_internal_errors($libxml_previous_state);

    if ($has_loaded_html === false) {
        # can't load the html
        throw new \Exception('Can not parse the provided html for epfl-emploi');
    }

    return $dom;
}

function parse_job_offers_recusively(string $url) {

}

/**
 * @param string $html html to parse
 * @param string $base_url the url used first time to crawlresults
 * @param int|null $total_count When null, we are doing the first iteration
 * @throws \Exception
 */

function parse_job_offers(string $html, string $base_url, int $total_count = null) {

    $dom = load_DOMHTML_silently($html);
    $xpath = new \DomXPath($dom);

    $advanced_information = $xpath->query("//table-navigation[@initial-data-string]/@initial-data-string");

    $job_offers_table = $xpath->query("//table[@class='tableaslist']");

    if (
        $advanced_information->length == 0 ||
        $job_offers_table->length == 0
    ) {
        # stop here as we can not find needed data
        throw new \Exception('Can not parse the provided html for epfl-emploi');
    }

    $job_offers_table = $job_offers_table->item(0)->nodeValue;
    $advanced_information = json_decode($advanced_information->item(0)->nodeValue);

    $umantis_next_url_parameters = $advanced_information->NextLink->EnhancedUrl ?? null;
    $table_max_entries = $advanced_information->TableMaxEntries ?? null;
    $table_from = $advanced_information->TableFrom ?? null;
    $table_to = $advanced_information->TableTo ?? null;
    $token_list_id = $advanced_information->token_list_id ?? null;
    $search_token = $advanced_information->search_token ?? null;

    if (
        $umantis_next_url_parameters === NULL ||
        $table_max_entries === NULL ||
        $table_from === NULL ||
        $table_to === NULL ||
        $token_list_id === NULL ||
        $search_token === NULL
    ) {
        # stop here as we can not find needed data
        throw new \Exception('Can not parse the provided html for epfl-emploi');
    }

    if ($total_count === NULL) {
        # fetch total, so we know when to stop
        $total_url = strtok($base_url, '?') . '/getTableTotalCount?token_list_id='. $token_list_id;

        $response = wp_remote_get($total_url, array('timeout' => REMOTE_SERVER_TIMEOUT, 'sslverify' => REMOTE_SERVER_SSL));

        if (is_wp_error($response)) {
            # unwanted error, throw it to get a fallback
            throw new \Exception($response->get_error_message());
        } else {
            # Remote server is responding; Hooray !
            $remote_count_json = wp_remote_retrieve_body($response);
            $total_count = intval(json_decode($remote_count_json)->data);
        }
    }

    # check if need more or stop here
    if ($table_to < $total_count) {
        # we have to continue, we have more results !
        # Build next url
        $next_url = strtok($base_url, '?') . $umantis_next_url_parameters;
        $response = wp_remote_get($next_url, array('timeout' => REMOTE_SERVER_TIMEOUT, 'sslverify' => REMOTE_SERVER_SSL));

        if (is_wp_error($response)) {
            # unwanted error, throw it to get a fallback
            throw new \Exception($response->get_error_message());
        } else {
            # Remote server is responding; Hooray !
            $remote_html = wp_remote_retrieve_body($response);
            # compile results of results
            $job_offers_table .= parse_job_offers($remote_html, $base_url, $total_count);
        }
    }

    # at this point, we should have a nice fullfilled var :)
    return $job_offers_table;
}

/**
 * Fetch and save job offers result in a transient, and in the WP options table as a fallback in case of
 * the url is not responding anymore
 * This process is deactivated if we are in DEBUG mode
 * @param string $url where to fetch all job offers in html
 * @return array the job offers or null if server is not responding
 */
function get_job_offers(string $url) {
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
                $remote_html = wp_remote_retrieve_body($response);

                $job_offers = "<table>" . parse_job_offers($remote_html, $url, null) . "</table>";


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
    } finally {
        # whatever return what we have
        return $job_offers;
    }
}
