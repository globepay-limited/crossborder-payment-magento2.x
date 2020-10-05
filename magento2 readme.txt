Globepay extension for magento2 Installation Help:
 
Step 1: upload the file to the app/code directory; directory structure: app/code/Globeweb/Globepay/...

Step 2: Using the Magento Command
Use xSHELL tool to go to the magento site root directory:
Php bin/magento module:status
You should see that Globeweb_Globepay and Globeweb_Globepayali is not enabled
 
Step 3: Enable
Php bin/magento module:enable Globeweb_Globepay
and
Php bin/magento module:enable Globeweb_Globepayali
 
Step 4: Then follow the prompts to run the command
Php bin/magento setup:upgrade
Note that when using the command, please use the magento file owner to run. The following configuration can be done in the background.
 
Step 5: Configure Plugins
 
After installing the Globepay extension for Magento2 , we now need to fill in the configuration information. Now go to Admin> Stores> Configuration> Sales> Payment Methods> Globepay Direct (PC and WAP) and Globepay(Alipay) Direct (PC and WAP)to fill in the configuration information. Mainly fill in the merchant ID and payment key. You can get the ID and Key after signing Globepay, they are the merchant ID and payment key. If you do not have an ID, contact Globepay BD for details.