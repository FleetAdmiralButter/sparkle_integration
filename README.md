# sparkle_integration

## Introduction

Provides auto-update services for XIV on Mac.

This module exposes a form that allows for new data to be pushed to various update API endpoints.

Currently, auto-updates for the following components are supported:

1) The XIV on Mac app itself, via the Sparkle framework.
2) XIVLauncher Patcher.

Endpoints are as follows:

Sparkle version feed: https://www.xivmac.com/sites/default/files/update_data/xivonmac_appcast.xml
XIVLauncher Patcher version info: https://www.xivmac.com/sites/default/files/seventh_dawn/version.txt

## Requirements
This module currently relies on the following:

* Drupal 9
* Fully configured QuantCDN integration
* PHP 8.x or higher

*NOTE:* This module is purpose-specific to the XIV on Mac project and is not intended for installation on other sites.

## Security

The update system relies on Drupal's authentication and role-based access control middleware to mediate access.

In addition to the above, Drupal itself handles brute-force and CSRF protection.

App binaries are code signed with an Apple Developer certificate and pushed to the CDN platform via HTTPS.

**Only** project leads have the requisite access to push update data.

Please visit https://www.xivmac.com/.well-known/security.txt for up to date contact information to disclose security vulnerabilities.