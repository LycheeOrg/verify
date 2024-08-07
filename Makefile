.PHONY: dist-gen dist-clean dist clean test formatting phpstan

composer:
	rm -r vendor  2> /dev/null || true
	composer install --prefer-dist --no-dev
	php artisan vendor:publish --tag=log-viewer-asset

test:
	@if [ -x "vendor/bin/phpunit" ]; then \
		./vendor/bin/phpunit --stop-on-failure; \
	else \
		echo ""; \
		echo "Please install phpunit:"; \
		echo ""; \
		echo "  composer install"; \
		echo ""; \
	fi

formatting:
	@rm .php_cs.cache 2> /dev/null || true
	@if [ -x "vendor/bin/php-cs-fixer" ]; then \
		PHP_CS_FIXER_IGNORE_ENV=1 ./vendor/bin/php-cs-fixer fix -v --config=.php-cs-fixer.php; \
	else \
		echo ""; \
		echo "Please install php-cs-fixer:"; \
		echo ""; \
		echo "  composer install"; \
		echo ""; \
	fi

phpstan:
	vendor/bin/phpstan analyze