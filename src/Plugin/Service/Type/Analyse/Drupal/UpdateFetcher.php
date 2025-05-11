<?php

namespace App\Plugin\Service\Type\Analyse\Drupal;

use GuzzleHttp\Client;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Fetches project information from remote locations.
 */
class UpdateFetcher implements UpdateFetcherInterface {

    /**
     * URL to check for updates, if a given project doesn't define its own.
     */
    const UPDATE_DEFAULT_URL = 'https://updates.drupal.org/release-history';

    /**
     * The HTTP client to fetch the feed data with.
     *
     * @var \GuzzleHttp\Client
     */
    protected Client $httpClient;

    protected string $compat;

    /**
     * Constructs an UpdateFetcher.
     */
    public function __construct() {
        $this->httpClient = new Client();
    }

    public function setCompat(string $compat): void {
        $this->compat = $compat;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchProjectData(array $project) {
        $url = $this->buildFetchUrl($project);
        return $this->doRequest($url, ['headers' => ['Accept' => 'text/xml']]);
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
     *
     * @return string
     *   The body of the HTTP(S) request, or an empty string on failure.
     */
    protected function doRequest(string $url, array $options): string {
        $data = '';
        try {
            $data = (string) $this->httpClient
                ->get($url, ['headers' => ['Accept' => 'text/xml']])
                ->getBody();
        }
        catch (ClientExceptionInterface $exception) {

        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function buildFetchUrl(array $project) {
        $name = $project['name'];
        $url = $this->getFetchBaseUrl($project);
        $url .= '/' . $name . '/' . ($this->compat ?? 'current');
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
