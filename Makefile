help:                                                                           ## shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

init:                                                                           ## initialize project
	composer install
	bin/console do:da:cr --if-not-exists
	bin/console do:da:cr --connection eventstore --if-not-exists
	bin/console event-store:create
	bin/console event-store:projection:rebuild

destroy:                                                                        ## destroy project
	bin/console do:da:drop --force --if-exists
	bin/console do:da:drop --connection eventstore --force --if-exists

rebuild: destroy init                                                           ## rebuild project

projection-rebuild:                                                             ## rebuild projection
	bin/console event-store:projection:rebuild

logs:                                                                           ## display logs
	tail -f var/log/dev.log

dumps:                                                                           ## display dumps
	bin/console server:dump

phpunit:                                                                        ## run phpunit tests
	vendor/bin/phpunit --testdox --colors=always -v $(OPTIONS)

coverage:                                                                       ## run phpunit tests with coverage
	php -dpcov.enabled=1 -dpcov.directory=. -dpcov.exclude="~vendor~" vendor/bin/phpunit --testdox --colors=always -v --coverage-text --coverage-html coverage/  $(OPTIONS)

snapshots:                                                                      ## update snapshots
	vendor/bin/phpunit --testdox -v -d --update-snapshots $(OPTIONS)

phpstan:                                                                        ## run phpstan static code analyser
	phpstan analyse -l max src

psalm:                                                                          ## run psalm static code analyser
	psalm $(OPTIONS) --show-info=false

psalm-info:                                                                     ## run psalm static code analyser with info
	psalm $(OPTIONS)

php-cs-check:																	## run cs fixer (dry-run)
	PHP_CS_FIXER_FUTURE_MODE=1 php-cs-fixer fix --allow-risky=yes --diff --dry-run

php-cs-fix:																		## run cs fixer
	PHP_CS_FIXER_FUTURE_MODE=1 php-cs-fixer fix --allow-risky=yes

test: phpunit                                                                   ## run tests

static: php-cs-fix psalm phpstan                                                ## run static analysers

dev: static test                                                                ## run dev checks

.PHONY: php-cs-check php-cs-fix phpstan phpunit help test dev coverage
