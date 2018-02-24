# Bibliograph - Online Bibliographic Data Manager
# Build script for Debian/Ubuntu 

set -o errexit # Exit on error

# Colorize output, see https://linuxtidbits.wordpress.com/2008/08/11/output-color-on-bash-scripts/
txtbld=$(tput bold)             # Bold
bldred=${txtbld}$(tput setaf 1) #  red
bldblu=${txtbld}$(tput setaf 4) #  blue
txtrst=$(tput sgr0)             # Reset
function section {
  echo $bldblu
  echo ==============================================================================
  echo $1
  echo ==============================================================================
  echo $txtrst
}

section "Installing required packages..."

# General packages
apt-get update && apt-get install -y \
  apache2 \
  mysql-server \
  wget curl zip \
  build-essential \
  bibutils 

# PHP 
apt-get install -y \
  php7.0 php7.0-dev php-pear \
  php7.0-cli php7.0-ldap php7.0-curl php7.0-gd \
  php7.0-intl php7.0-json php7.0-mbstring \
  php7.0-mcrypt php7.0-mysql php7.0-opcache \
  php7.0-readline php7.0-xml php7.0-xsl php7.0-zip

# sudo add-apt-repository -y ppa:ondrej/php && sudo apt-get update && sudo apt-get install -y php7.0-xsl php7.0-intl; fi  
# sudo apt-get install -y yaz libyaz4-dev
# pear channel-update pear.php.net && yes $'\n' | pecl install yaz && pear install Structures_LinkedList-0.2.2 && pear install File_MARC 
# sudo service apache2 restart
# if php -i | grep yaz --quiet && echo '<?php exit(function_exists("yaz_connect")?0:1);' | php ; then echo "YAZ is installed"; else echo "YAZ installation failed"; exit 1; fi;

section "Installing composer..."
php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm /tmp/composer-setup.php
composer --version

section "Installing node and npm ..."
curl -sL https://deb.nodesource.com/setup_8.x | bash -
apt-get install -y nodejs

section "Installing qooxdoo and qxcompiler..."
pushd src/vcslib
bash install.sh
popd

section "Building Bibliograph..."
npm install
npm link mocha
pushd src/client/bibliograph
qx contrib update
qx contrib install
qx compile ../../../build-env/debian-ubuntu/compile.json --all-targets 
popd
cp build-env/debian-ubuntu/bibliograph.ini.php.dist src/server/config/bibliograph.ini.php

section "Setting up Yii2 backend..."
pushd src/server
composer install
pushd vendor
[[ -d bower ]] || ln -s bower-asset/ bower
popd
popd

section "Starting MySql Server"
service mysql start
mysql -e 'CREATE DATABASE IF NOT EXISTS tests;'
mysql -e 'CREATE DATABASE IF NOT EXISTS bibliograph;'
mysql -e "DROP USER IF EXISTS 'bibliograph'@'localhost';"
mysql -e "CREATE USER 'bibliograph'@'localhost' IDENTIFIED BY 'bibliograph';"
mysql -e "GRANT ALL PRIVILEGES ON bibliograph.* TO 'bibliograph'@'localhost';"

#section "Install deployment tool"
#curl -LO https://deployer.org/deployer.phar
#mv deployer.phar /usr/local/bin/dep
#chmod +x /usr/local/bin/dep

section "Installation finished."
echo "Please review and adapt the 'src/server/config/bibliograph.ini.php' config file:"
echo "- Enter the email address of the administrator in the [email] section (The application"
echo "  won't start otherwise) "
echo "- If you use an LDAP server for authentication, adapt the settings in the [ldap] section."
echo 
echo "You can now execute:"
echo "- 'npm test': run unit, functional and api tests"
echo "- 'npm run test-dev': run unit, functional and api tests in development mode"
echo "- 'npm run serve-dev': start a development server on localhost:9090"