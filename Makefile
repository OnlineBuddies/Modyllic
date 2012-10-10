CWD := $(shell pwd)
PHP_INCLUDE_PATH := $(shell echo '<?php echo get_include_path();'|php)
PHP := 'php -d include_path="$(CWD)/lib:$(PHP_INCLUDE_PATH)"'
PROVE := prove -r --exec $(PHP)
PACKAGEXML := base.xml
BUILDXML := package.xml

default:

.PHONY: test test-verbose install uninstall clean build-package-xml discover-channel

clean:
	rm -rf $(BUILDXML) tmp coverage

build-package-xml:
	php build-package-xml $(PACKAGEXML) $(BUILDXML)

discover-olb-channel:
	pear channel-info OnlineBuddies >/dev/null || pear channel-discover onlinebuddies.github.com/pear

install-build-prereqs: discover-olb-channel
# XML_Serializer is actually only needed by PEAR_PackageFileManager2, but
# PEAR's dependency tracking is extremely broken-- only beta versions of
# XML_Serializer are actually available so PEAR just breaks rather then
# doing the reasonable thing and installing the *most stable* version
# available.
	pear list -c PEAR | grep -q '^XML_Serializer ' || pear install --alldeps PEAR/XML_Serializer || pear install --alldeps PEAR/XML_Serializer-beta
	pear list -c PEAR | grep -q '^PEAR_PackageFileManager2 ' || pear install --alldeps PEAR/PEAR_PackageFileManager2
	pear list -c OnlineBuddies | grep -q '^PEAR_PackageFileManager_Gitrepoonly ' || pear install OnlineBuddies/PEAR_PackageFileManager_Gitrepoonly

install-dist-prereqs: install-build-prereqs
	if [ $$(git remote | grep -c '^upstream-testlib$$') -ne 1 ]; then git remote add upstream-testlib git://github.com/shiflett/testmore.git; fi
	if [ $$(git remote | grep -c '^upstream$$') -ne 1 ]; then git remote add upstream git@github.com:OnlineBuddies/Modyllic.git; fi
	if [ $$(git remote | grep -c '^upstream-wiki$$') -ne 1 ]; then git remote add upstream-wiki git@github.com:OnlineBuddies/Modyllic.wiki.git; fi
	if [ $$(git branch | grep -c '^  upstream-wiki$$') -ne 1 ]; then ( git fetch upstream-wiki ; git branch upstream-wiki upstream-wiki/master ); fi
	if [ $$(git remote | grep -c '^upstream-pear$$') -ne 1 ]; then git remote add upstream-pear git@github.com:OnlineBuddies/pear.git; fi
	if [ $$(git branch | grep -c '^  upstream-pear$$') -ne 1 ]; then ( git fetch upstream-pear ; git branch upstream-pear upstream-pear/gh-pages ); fi
	pear channel-info pirum >/dev/null || pear channel-discover pear.pirum-project.org
	pear list -c pirum | grep -q '^Pirum ' || pear install pirum/Pirum-beta

install: build-package-xml discover-olb-channel uninstall
	pear install $(BUILDXML)

uninstall:
	pear uninstall OnlineBuddies/Modyllic

package: build-package-xml
	pear package $(BUILDXML)

test:
	$(PROVE) test

test-cover:
	rm -rf tmp/test coverage
	TEST_COVERAGE=1 $(PROVE) -v test
	phpcov --merge --html coverage tmp/test/coverage
	rm -rf tmp/test

unit-test-cover:
	rm -rf tmp/test coverage
	TEST_COVERAGE=1 $(PROVE) -v test/unit
	phpcov --merge --html coverage tmp/test/coverage
	rm -rf tmp/test

test-verbose:
	$(PROVE) -v test
