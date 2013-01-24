LANGUAGES=ko ja en de zh-CN

.PHONY: check_xgettext filelist.txt

all: wing.pot

wing.pot: check_xgettext filelist.txt
	@echo -n "* Extracting i18n strings : "
	@xgettext -s -k_t -k_f --from-code=utf-8 -f filelist.txt -o $@
	@echo `grep msgid wing.pot | wc -l` strings extracted.

check_xgettext:
	@echo -n "* Check xgettext version to support php"
	@if test -z "`xgettext --help | grep -i php`"; then echo " : You need php enabled xgettext, update gettext package!"; \
	 else echo ": Yes, you have right version."; fi
		
filelist.txt:
	@echo -n "* Listing up php files ($@) : "
	@if test -f $@; then echo -n "$@ found, skip listing up : "; else find ../../../{index.php,menu.php,admin,components,feeds,mypage,rss,service,setup,skin} -name "*.php" > $@; fi
	@echo "`cat $@ | wc -l` files are found."
	
language_po:
	@for l in $(LANGUAGES); do ./po2php.pl $$l.po $$l.php || exit 1; done

check:
	@for l in $(LANGUAGES); do echo "* Check po format: $$l.po"; msgfmt -c --check-accelerators $$l.po || exit 1; done
