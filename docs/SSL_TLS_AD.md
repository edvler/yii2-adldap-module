# Informations for configuring SSL/TLS

## Problem 1 - Cert of server cannot be verified (self-signed)
If the parameters bellow uncommented the error message **Adldap\Auth\BindException: Can't contact LDAP server** is thrown.

```php
...
                                //'port' => 636,
                                //'use_ssl' => true,
                                //'use_tls' => true, 
...
```

## Windows
**Links:**
* http://se2.php.net/manual/en/ref.ldap.php#47427
* https://stackoverflow.com/questions/5258556/problems-with-secure-bind-to-active-directory-using-php

A possible reason could be that the client cannot verify the server cert.
To disable the server cert verification create a file

**Solution:**
* create the file c:\openldap\sysconf\ldap.conf
* And put into it:

```
TLS_REQCERT never
```