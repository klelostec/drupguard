<?php

namespace App;

use App\Exception\InstallException;

/**
 * Provides methods for checking if Drupal is already installed.
 */
trait InstallerRedirectTrait {

    /**
     * Returns whether the current PHP process runs on CLI.
     *
     * @return bool
     */
    protected function isCli() {
        return PHP_SAPI === 'cli';
    }

    /**
     * Returns whether the current PHP process runs on CLI.
     *
     * @return bool
     */
    protected function isInstall() {
        return $this->id === 'install';
    }

  /**
   * Determines if an exception handler should redirect to the installer.
   *
   * @param \Exception $exception
   *   The exception to check.
   * @param \Doctrine\DBAL\Connection $connection
   *   (optional) The default database connection. If not provided, a less
   *   comprehensive check will be performed. This can be the case if the
   *   exception occurs early enough that a database connection object isn't
   *   available from the container yet.
   *
   * @return bool
   *   TRUE if the exception handler should redirect to the installer because
   *   Drupal is not installed yet, or FALSE otherwise.
   */
  protected function shouldRedirectToInstaller(\Exception $exception) {
    // Never redirect on the command line or from install app.
    if ($this->isCli() || $this->isInstall()) {
      return FALSE;
    }

    if ($exception instanceof InstallException) {
      return TRUE;
    }

    // When in doubt, don't redirect.
    return FALSE;
  }

}
