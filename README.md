# The Digital Humanities Registry

This project visualizes an overview of courses and research projects among the digital humanities. 
Creation of this software started in 2014 with a Dutch & Flemish overview of courses among the digital humanities - better known as the DH-Courseregistry (DHCR). Later extensions include the DH-Projectregistry (DHPR) and upscaling of the DHCR to European level. 
The Code is based upon the [CakePHP](http://cakephp.org/) framework.


## DH-Courseregistry
The Courseregistry is available online at [DARIAH-EU](https://dh-registry.de.dariah.eu/)
The project visualizes higher education courses in the field of digital humanities, contributed to the database by the member institutions. 


## DH-Projectregistry
In 2015 work started to extend the project by a DH-Projectregistry (DHPR), aiming for a Dutch overview: [DODH](http://dh-projectregistry.org/). 



### SetUp
This application runs under very common webserver settings. It makes use of PHP and MySql.
For local adjustments (e.g. database access and Google API access) please change details in app/Config/core.php and app/Config/database.php
Also consider the CakePHP setup instructions (permissions on tmp folders, mod_rewrite).
### Requirements
* 400 MB storage space (extensible if database grows)
* a common Unix OS
* MySql (any version)
* PHP, version 5 of higher
* check if CURL extension is installed in PHP
* SSL certificate


