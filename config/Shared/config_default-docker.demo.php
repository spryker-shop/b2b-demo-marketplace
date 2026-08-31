<?php

declare(strict_types = 1);

use SprykerFeature\Shared\SelfServicePortal\SelfServicePortalConstants;

// ############################################################################
// ############################## DEMO/TESTING CONFIGURATION ##################
// ############################################################################

// ----------------------------------------------------------------------------
// ------------------------------ OMS -----------------------------------------
// ----------------------------------------------------------------------------

require 'common/config_oms-development.php';

// ----------------------------------------------------------------------------
// ------------------------------ SelfServicePortal ----------------------------
// ----------------------------------------------------------------------------

// This AWS environment has no SPRYKER_S3_SSP_FILES_* credentials configured,
// so company files are stored on the local filesystem instead of S3.
$config[SelfServicePortalConstants::STORAGE_NAME] = 'files';
