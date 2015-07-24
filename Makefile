example:
	composer install
	php example/app/console doctrine:database:drop --force --if-exists --quiet || true
	php example/app/console doctrine:database:create
	php example/app/console doctrine:schema:update --force
	php example/app/console doctrine:fixtures:load --append
	php example/app/console cache:clear
	php example/app/console server:run

test:
	echo "todo"

.PHONY: example test
