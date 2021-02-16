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

    # at this point, we should have a nice fullfilled var :)
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

                $job_offers = "<table>" . parse_job_offers($remote_xml) . "</table>";

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
        $job_offers = '<p><b><font color="red">The URL provided is having problem. Data can be obsolete. Showing the last working version</font></b></p>' . $job_offers;

    } finally {
        # show what we built
        echo $job_offers;
    }
}
