<?php

namespace App\Plugin\Service\Type\Analyse\Drupal;

/**
 * Manages project update information.
 */
interface UpdateManagerInterface {

  /**
   * Project is missing security update(s).
   */
  const NOT_SECURE = 1;

  /**
   * Current release has been unpublished and is no longer available.
   */
  const REVOKED = 2;

  /**
   * Current release is no longer supported by the project maintainer.
   */
  const NOT_SUPPORTED = 3;

  /**
   * Project has a new release available, but it is not a security release.
   */
  const NOT_CURRENT = 4;

  /**
   * Project is up to date.
   */
  const CURRENT = 5;

}
