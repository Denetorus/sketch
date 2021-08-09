
******************************************
Launching the project
******************************************

STEP 1

Download the files and place them in a directory on your disk
(For example, this directore is named "MyAPI")

CHECK:

On this STEP you can check project throw the terminal.

Open terminal (console).
Go to the directory when placed project files ("MyAPI")
Run command:
    
    php sk test
    
You have to see message

    test console is execute
    
If you see the mistake 

    'php' is not recognized as an internal or external command, 
    operable program or batch file."

You can fix it one of a methods for your system.
The method for Windows was description here
    
    https://www.codeandtuts.com/php-is-not-recognized-as-an-internal-or-external-command/ 
    
******************************************

STEP 2

Configure ENTRY POINT in the PHP server to directory "/gate" 

(For our example:
 
 If you use Windows Apache :
    Open Apache configure file "httpd.conf"
    In the file look for parameter "Document Root".
    Fill parameter:

    DocumentRoot "Path_to_directory/MyAPI/gate" 

 If you use Linux Apache2 :
   Change Sites-available configure file parameter  
     
    DocumentRoot "Path_to_directory/MyAPI/gate" 
 
) 

CHECK:

- Restart Apache
- Go to the link

        http://IP_SITE
    
    You have to see message
    
        Welcome to Jus Mundi API!!!

******************************************

STEP 3

To activate the file ".htaccess" in the directory "/gate"

(For our example:
 
 If you use Windows Apache :
    * Open Apache configure file "httpd.conf" and add the parameter:

        <Directory "Path_to_directory/MyAPI/gate">
            AllowOverride All
            Require all granted
        </Directory>

   * Restart Apache
   
    
 If you use Linux Apache2 :
    * Open Apache configure file "apache2.conf" and add parameter
    
        <Directory "Path_to_directory/MyAPI/gate">
            AllowOverride All
            Require all granted
        </Directory>

   * To activate apache module "rewrite"
    
         sudo a2enmod rewrite
    
   * Restart Apache
    
         sudo service apache2 restart

) 

CHECK:

   Go to the link

        http://IP_SITE/console/test
 
   You have to see message

        test console is execute

******************************************

STEP 4

Open then directory "/database" ("MyApi/database").
Create new file in this directory "DBDataConfig.php" with the following content:

    <?php
    
    return [
        'dsn' =>'pgsql:host=hostname;port=5432;dbname=DBNAME',
        'user' => 'DBUserName',
        'password' => 'DBPassword'
    ];

