# CS-Cart Create Add-on

### Description
Create new add-on in bash or url  style


### Code Example
##### Browser
Example URL: http://%DOMAIN_NAME%/create_addon.php?addon=new_test_addon&lang=ru,en

##### Console
php ./create_addon.php --addon=super_test_addon


### Installation
Copy create_addon.php in the CS-Cart root directory


#### Params

##### Browser
Request params:

    help          Show info
    wibug         Display PHP notice
    addon         Addon id
    lang          Languages separated by comma

##### Console
Options:

            --help          Show info
            --wibug         Display PHP notice
        -a  --addon         Addon id
            --lang          Languages separated by comma

### License
GNU General Public License 3.0
