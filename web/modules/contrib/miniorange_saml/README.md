# CONTENTS OF THIS FILE

  - Introduction
  - Requirements
  - Installation
  - Configuration
  - Setup Guides
  - Maintainers


## INTRODUCTION

miniOrange SAML 2.0 SSO allows users residing at SAML 2.0 compliant Identity Provider to login to your Drupal.
We support all known IdPs - Google Apps, ADFS, Okta, Salesforce, Shibboleth, SimpleSAMLphp, OpenAM, Centrify, Ping, RSA, IBM, Oracle, OneLogin, Bitium, WSO2, NetIQ etc.
If you need detailed instructions on setting up these IdPs, we can give you step by step instructions.

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/miniorange_saml

* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/search/miniorange_saml

## REQUIREMENTS

No special requirements.


## INSTALLATION

* Install as you would normally install a contributed Drupal module.
  See: https://www.drupal.org/docs/extending-drupal/installing-modules for further information.

## CONFIGURATION

Configure user permissions in Configuration » People » miniOrange SAML Login Configuration:

- **Setup Customer account with miniOrange (Optional)**\
  Login/Create account with miniOrange by entering email address, phone number and password.

- **Identity Provider Setup**\
  Make note of the Service Provider information from Service Provider Metadata tab. This will be required to configure your IdP.

- **Service Provider Setup**\
  Configure the Drupal site to act as a Service Provider(SP). Information such as IdP Entity ID, x.509 certificate and SAML Login URL are taken from IdP and stored here.

## SETUP GUIDES

We provide details step by step setup guide for various IDPs, please visit - https://plugins.miniorange.com/configure-drupal-saml-single-sign-on \
Note: If you dont find guide for your desired IDP, please contact us at [drupalsupport@xecurify.com](mailto:drupalsupport@xecurify.com)

## MAINTAINERS

Current maintainers:
  - abhay19 - https://www.drupal.org/u/abhay19
  - Arsh Sabharwal (arsh244) - https://www.drupal.org/u/arsh244
  - Gaurav Sood (gauravsood91) - https://www.drupal.org/u/gauravsood91
  - Shrishail Hiremath- https://www.drupal.org/u/shrishail-hiremath

This project has been sponsored by:
* miniOrange Inc\
  miniOrange is a Single Sign-on (SSO) and Identity & Access Management (IAM) provider.
  miniOrange has catered to the security and IAM requirements of government organizations, educational institutions, and NGOs through robust and resilient solutions.

  For more information visit www.miniorange.com, mail us at [drupalsupport@xecurify.com](mailto:drupalsupport@xecurify.com) or call [+1 978 658 9387](tel:+19786589387).
