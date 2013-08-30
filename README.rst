.. _start:

======================
LDAP Connector service
======================

.. only:: html

        :Classification:
                svconnector_ldap

        :Version:
                |release|

        :Language:
                en

        :Description:
                Utility service for connecting to a LDAP server, using a standardized connector

        :Copyright:
                2013

        :Author:
                François Suter (Cobweb)

        :Email:
                fsuter@cobweb.ch

        :License:
                This document is published under the Open Content License
                available from http://www.opencontent.org/opl.shtml

        :Rendered:
                |today|

        The content of this document is related to TYPO3,
        a GNU/GPL CMS/Framework available from `www.typo3.org <http://www.typo3.org/>`_.


.. _about:

About
=====

This extension provides a generic way of accessing to a LDAP server. Like all other
connector services it can return data in PHP array and XML format.


.. _installation:

Installation
============

This extension is part of the Connector family of services. As such it depends on extension
"svconnector".

It also requires the PHP "ldap" library to be installed. If this library is missing, the service
will not be available.


.. _configuration:

Configuration
=============

The following parameters can be used in this connector. For more information on the meaning
of the search parameters, please refer to the PHP manual for ``ldap_search()``
(http://www.php.net/manual/en/function.ldap-search.php).

host
  IP or domain name of the LDAP server. **Mandatory**.

port
  Port used to reach the LDAP server. Defaults to ``389``.

protocol
  LDAP protocol version to use. Defaults to ``3``.

login
  User name for identification on the LDAP server.

password
  Password for identification on the LDAP server. If ``login`` and ``password`` are empty,
  anonymous access is attempted.

search_base
  The base DN for the LDAP directory. **Mandatory**.

search_filter
  The search filter. To get all entries, use something like ``(cn=*)``. **Mandatory**.

search_attributes
  Comma-separated list of attributes to fetch from the server. If left empty, all attributes are fetched.

search_attributes_only
  Set to 1 to fetch only the attributes and not their values. Leave empty otherwise.

search_size_limit
  Define a limit to the result size. Defaults to ``0`` (i.e. unlimited, but server-side restrictions may apply).

search_time_limit
  Define a time limit for the search. Defaults to ``0`` (i.e. unlimited, but server-side restrictions may apply).

search_deref
  Specify the handling of aliases. Use the strings corresponding to the PHP constants
  ``LDAP_DEREF_NEVER``, ``LDAP_DEREF_SEARCHING``, ``LDAP_DEREF_FINDING``, ``LDAP_DEREF_ALWAYS``.
