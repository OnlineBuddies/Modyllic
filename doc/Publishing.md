Publishing
==========

The release-version command will try to install its prerequisites by calling
`make isntall-dist-prereqs`.  If you run into problems with this (for
instance, due to the pear install commands), see the Manual Setup section.

From your Modyllic checkout:

1. `./release-version #.#.# beta`
2. This will give you a Modyllic-#.#.#.tgz it will also commit the CHANGELOG for this release and create a tag.  If everything looks ok:
3. `./publish-version`

And you're done.  It may take a minute or so for your changes to appear at:
http://onlinebuddies.github.com/pear/

Once they do, you can upgrade a Modyllic installation with:

`pear upgrade OnlineBuddies/Modyllic-beta`


Manual Setup
============

1. `pear channel-discover pear.pirum-project.org`
2. `pear install pirum/Pirum-beta`
3. `pear channel-discover onlinebuddies.github.com/pear`
4. `pear install OnlineBuddies/PEAR_PackageFileManager_Gitrepoonly`
5. `git remote add upstream-testlib git://github.com/shiflett/testmore.git`
6. `git remote add upstream git://github.com/OnlineBuddies/Modyllic.git`
7. `git remote add upstream-wiki git://github.com/OnlineBuddies/Modyllic.wiki.git`
8. `git fetch upstream-wiki ; git branch upstream-wiki upstream-wiki/master`
9. `git remote add upstream-pear git@github.com:OnlineBuddies/pear.git`
10. `git fetch upstream-pear ; git branch upstream-pear upstream-pear/gh-pages`
