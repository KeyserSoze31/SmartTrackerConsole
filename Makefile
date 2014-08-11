composer.phar:
	@curl -s http://getcomposer.org/installer | php


install: composer.phar
	@echo Installing...
	@php composer.phar install --dev

update: composer.phar
	@echo "Updating..."
	@php composer.phar self-update
	@php composer.phar update

compile:
	@echo "Compiling..."
	@./bin/compile

clean:
	@echo "Cleaning..."
	@rm composer.phar
