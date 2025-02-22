# SPDX-FileCopyrightText: Bernhard Posselt <dev@bernhard-posselt.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

# Generic Makefile for building and packaging a Nextcloud app which uses npm and
# Composer.
#
# Dependencies:
# * make
# * which
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive
# * npm: for building and testing everything JS
#
# If no composer.json is in the app root directory, the Composer step
# will be skipped. The same goes for the package.json which can be located in
# the app root or the js/ directory.
#
# The npm command by launches the npm build script:
#
#    npm run build
#
# The npm test command launches the npm test script:
#
#    npm run test
#
# The idea behind this is to be completely testing and build tool agnostic. All
# build tools and additional package managers should be installed locally in
# your project, since this won't pollute people's global namespace.
#
# The following npm scripts in your package.json install and update the bower
# and npm dependencies and use gulp as build system (notice how everything is
# run from the node_modules folder):
#
#    "scripts": {
#        "test": "node node_modules/gulp-cli/bin/gulp.js karma",
#        "prebuild": "npm install && node_modules/bower/bin/bower install && node_modules/bower/bin/bower update",
#        "build": "node node_modules/gulp-cli/bin/gulp.js"
#    },

app_name=collaboration
# You must set version, eg. make -e version=v0.0.1 buildapp
version=$(version)
app_dir_name=$(notdir $(CURDIR))
build_tools_directory=$(CURDIR)/build/tools
source_build_directory=$(CURDIR)/build/artifacts/source
source_package_name=$(source_build_directory)/$(app_name)
appstore_build_directory=$(CURDIR)/build/artifacts/app
appstore_package_name=$(appstore_build_directory)/$(app_name)_$(version)
npm=$(shell which npm 2> /dev/null)
composer=$(shell which composer 2> /dev/null)
integration_tests_dir=${CURDIR}/tests/docker

all: clean dev-setup build-js-production

# Dev env management
dev-setup: clean npm-init

npm-init:
	npm ci

npm-update:
	npm update

# Building
build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

# Linting
lint-fix:
	npm run lint:fix

lint-fix-watch:
	npm run lint:fix-watch

# Cleaning
clean:
	rm -rf dist

clean-git: clean
	git checkout -- dist

# Code sniffing: PSR-12 is followed 
# full check, gives all errors and warnings
.PHONY: php-codesniffer-full
php-codesniffer-full:
	$(CURDIR)/vendor/bin/phpcs appinfo/ lib/ templates/ tests/docker/integration-tests/src --standard=PSR12 --report=full

# check for errors only, ignoring warnings
.PHONY: php-codesniffer-errors
php-codesniffer-errors:
	$(CURDIR)/vendor/bin/phpcs \
		appinfo/ \
		lib/ \
		templates/ --standard=PSR12 --report=full --warning-severity=0
		# TODO tests/docker/integration-tests/src --standard=PSR12 --report=full --warning-severity=0

# should fix (most) errors
.PHONY: php-codesniffer-errors-fix
php-codesniffer-errors-fix:
	$(CURDIR)/vendor/bin/phpcbf \
		appinfo/ \
		lib/ \
		templates/ --standard=PSR12
		# TODO tests/docker/integration-tests/src --standard=PSR12

# Fetches the PHP and JS dependencies and compiles the JS. If no composer.json
# is present, the composer step is skipped, if no package.json or js/package.json
# is present, the npm step is skipped
.PHONY: build
build:
ifneq (,$(wildcard $(CURDIR)/composer.json))
	make composer
endif
ifneq (,$(wildcard $(CURDIR)/package.json))
	make npm
endif
ifneq (,$(wildcard $(CURDIR)/js/package.json))
	make npm
endif

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist
else
	composer install --prefer-dist
endif

# Installs npm dependencies
.PHONY: npm
npm:
ifeq (,$(wildcard $(CURDIR)/package.json))
	cd js && $(npm) run build
else
	npm run build
endif

# Removes the appstore build
.PHONY: clean
clean:
	rm -rf ./build

# Same as clean but also removes dependencies installed by composer, bower and
# npm
.PHONY: distclean
distclean: clean
	rm -rf vendor
	rm -rf node_modules
	rm -rf js/vendor
	rm -rf js/node_modules

# Builds the source and appstore package
.PHONY: dist
dist:
	make source
	make appstore

# Builds the source package
.PHONY: source
source:
	rm -rf $(source_build_directory)
	mkdir -p $(source_build_directory)
	tar cvzf $(source_package_name).tar.gz \
	--exclude-vcs \
	--exclude="../$(app_name)/build" \
	--exclude="../$(app_name)/js/node_modules" \
	--exclude="../$(app_name)/node_modules" \
	--exclude="../$(app_name)/*.log" \
	--exclude="../$(app_name)/js/*.log" \
	../$(app_name) \

# Runs the integration tests on local src
.PHONY: integration-tests-local
integration-tests-local:
	@echo "Running the integration tests ..."
	# cd ${integration_tests_dir}
	docker compose --verbose --progress=plain -f ${integration_tests_dir}/docker-compose-local.yaml run \
	--build --entrypoint /bin/sh --rm integration-tests -- ./tmp/tests/tests.sh
	sh ${integration_tests_dir}/docker-cleanup.sh
	@echo "... Finished running the integration tests"

# Runs the integration tests on the github src
.PHONY: integration-tests-github
integration-tests-github:
	@echo "Running the integration tests against github src ..."
	# cd ${integration_tests_dir}
	docker compose --verbose --progress=plain -f ${integration_tests_dir}/docker-compose-github.yaml run \
	--build --entrypoint /bin/sh --rm integration-tests -- ./tmp/tests/tests.sh
	sh ${integration_tests_dir}/docker-cleanup.sh
	@echo "... Finished running the integration tests"

# Builds the source package for the app store, ignores php and js tests
# command: make version={version_number} buildapp
.PHONY: buildapp
buildapp:
	rm -rf $(appstore_build_directory)
	mkdir -p $(appstore_build_directory)
	# concatenate cd, ls and tar commands with '&&' otherwise the script context will remain the root instead of build
	cd build &&	\
	ln -s ../ $(app_name) && \
	tar cvzfh $(appstore_package_name).tar.gz \
	--exclude="$(app_name)/vendor" \
	--exclude="$(app_name)/build" \
	--exclude="$(app_name)/release" \
	--exclude="$(app_name)/tests" \
	--exclude="$(app_name)/Makefile" \
	--exclude="$(app_name)/*.log" \
	--exclude="$(app_name)/phpunit*xml" \
	--exclude="$(app_name)/composer.*" \
	--exclude="$(app_name)/node_modules" \
	--exclude="$(app_name)/js/node_modules" \
	--exclude="$(app_name)/js/tests" \
	--exclude="$(app_name)/js/test" \
	--exclude="$(app_name)/js/*.log" \
	--exclude="$(app_name)/js/package.json" \
	--exclude="$(app_name)/js/bower.json" \
	--exclude="$(app_name)/js/karma.*" \
	--exclude="$(app_name)/js/protractor.*" \
	--exclude="$(app_name)/package.json" \
	--exclude="$(app_name)/bower.json" \
	--exclude="$(app_name)/karma.*" \
	--exclude="$(app_name)/protractor\.*" \
	--exclude="$(app_name)/.*" \
	--exclude="$(app_name)/js/.*" \
	--exclude-vcs \
	$(app_name) && \
	rm $(app_name)

# Builds the source package for the app store, includes artifacts required for tests
# command: make version={version_number} buildapp
.PHONY: buildapp-tests
buildapp-tests:
	rm -rf $(appstore_build_directory)
	mkdir -p $(appstore_build_directory)
	# concatenate cd, ls and tar commands with '&&' otherwise the script context will remain the root instead of build
	cd build &&	\
	ln -s ../ $(app_name) && \
	tar cvzfh $(appstore_package_name).tar.gz \
	--exclude="$(app_name)/build" \
	--exclude="$(app_name)/*.log" \
	--exclude="$(app_name)/node_modules" \
	--exclude="$(app_name)/js/node_modules" \
	--exclude="$(app_name)/js/tests" \
	--exclude="$(app_name)/js/test" \
	--exclude="$(app_name)/js/*.log" \
	--exclude="$(app_name)/js/package.json" \
	--exclude="$(app_name)/js/bower.json" \
	--exclude="$(app_name)/js/karma.*" \
	--exclude="$(app_name)/js/protractor.*" \
	--exclude="$(app_name)/package.json" \
	--exclude="$(app_name)/bower.json" \
	--exclude="$(app_name)/karma.*" \
	--exclude="$(app_name)/protractor\.*" \
	--exclude="$(app_name)/.*" \
	--exclude="$(app_name)/js/.*" \
	--exclude-vcs \
	$(app_name) && \
	rm $(app_name)

.PHONY: test
test: composer
	$(CURDIR)/vendor/bin/phplint ./ --exclude=vendor
	$(CURDIR)/vendor/phpunit/phpunit/phpunit -c phpunit.xml
