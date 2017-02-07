#/bin/bash
php bin/console doctrine:generate:entities AppBundle
php bin/console doctrine:schema:drop --force
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load
rm -rf ./var/cache/* ./var/logs/* ./var/sessions/*
