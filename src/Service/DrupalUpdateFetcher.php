<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;

/**
 * Fetches project information from remote locations.
 */
class DrupalUpdateFetcher {

  /**
   * URL to check for updates, if a given project doesn't define its own.
   */
  const UPDATE_DEFAULT_URL = 'https://updates.drupal.org/release-history';

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  public function __construct() {
    $this->httpClient = $default_config = [
      'verify' => TRUE,
      'timeout' => 30,
        //              'headers' => [
        //                'User-Agent' => 'Drupal/8.x (+https://www.drupal.org/) ' . Utils::defaultUserAgent(),
        //              ],
      'handler' => HandlerStack::create(),
        // Security consideration: prevent Guzzle from using environment variables
        // to configure the outbound proxy.
      'proxy' => [
        'http' => NULL,
        'https' => NULL,
        'no' => [],
      ],
    ];

      $this->httpClient = new Client($default_config);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchProjectData(array $project) {
    $url = $this->buildFetchUrl($project);
    return $this->doRequest($url);
  }

  /**
   * Applies a GET request with a possible HTTP fallback.
   *
   * This method falls back to HTTP in case there was some certificate
   * problem.
   *
   * @param string $url
   *   The URL.
   * @param array $options
   *   The guzzle client options.
   * @param bool $with_http_fallback
   *   Should the function fall back to HTTP.
   *
   * @return string
   *   The body of the HTTP(S) request, or an empty string on failure.
   */
  protected function doRequest(string $url): string {
    $data = '';
    try {
      $data = (string) $this->httpClient
        ->get($url, ['headers' => ['Accept' => 'text/xml']])
        ->getBody();
    }
    catch (GuzzleException $exception) {
        if (strpos($url, "http://") === FALSE) {
            $url = str_replace('https://', 'http://', $url);
            return $this->doRequest($url);
        }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFetchUrl(array $project) {
    $name = $project['name'];
    $url = $this->getFetchBaseUrl($project);
    $url .= '/' . $name . '/current';

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getFetchBaseUrl($project) {
    if (isset($project['info']['project status url'])) {
      $url = $project['info']['project status url'];
    }
    else {
      $url = static::UPDATE_DEFAULT_URL;
    }
    return $url;
  }

}
