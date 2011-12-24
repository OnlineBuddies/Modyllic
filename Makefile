default:

.PHONY: test

test: check
	prove --exec 'php -d include_path=lib' --ext '.phpt' test

check:
	@[ -f testlib/testmore.php ] || (echo "You must initialize submodules with 'git submodule update --init' first"; exit 1)
