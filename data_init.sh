#/bin/bash
php bin/console doctrine:generate:entities AppBundle
php bin/console doctrine:schema:drop --force
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load
php bin/console cache:clear
