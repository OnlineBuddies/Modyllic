default:

.PHONY: test install

install:
	pear install package.xml

uninstall
	pear uninstall __uri/Modyllic

test: check
	prove --exec 'php -d include_path=lib' --ext '.phpt' test

check:
	@[ -f testlib/testmore.php ] || (echo "You must initialize submodules with 'git submodule update --init' first"; exit 1)
