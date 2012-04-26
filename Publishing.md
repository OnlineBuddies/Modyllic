Setting up
==========

First, you need to have Pirum installed:

1. pear channel-discover pear.pirum-project.org
2. pear install pirum/Pirum-beta

You also need to have PEAR_PackageManager_Git installed.  For now, you'll need @iarna's fork of it:

1. `git clone git://github.com/iarna/PEAR_PackageFileManager_Git.git`
2. `cd PEAR_PackageFileManager_Git`
3. `pear install package.xml`

When @armen takes @iarna's pull request, the instructions will be:
1. DONOTUSE: `pear channel-discover pear.vardump.org`
2. DONOTUSE: `pear install vd/PEAR_PackageFileManager_Git`


You'll need to have the OLB pear repository, in order to publish the PEAR update:

1. `git clone git://github.com/OnlineBuddies/pear.git`

You'll of course, also need the Modyllic repository:

1. `git clone git://github.com/OnlineBuddies/Modyllic.git`

Inside your Modyllic repository, you'll need to ensure a few 

1. `git remote add upstream-wiki git://github.com/OnlineBuddies/Modyllic.wiki.git`
2. `git remote add upstream-testlib git://github.com/shiflett/testmore.git`


Actually Publishing
===================
From your Modyllic checkout:

1. `./release-version #.#.# beta`
2. This will give you a Modyllic-#.#.#.tgz it will also commit the CHANGELOG for this release and create a tag.
3. `git push`

Now in go to your previously checked out copy of the OLB pear repository:

1. `./release /path/to/Modyllic-#.#.#.tgz`
2. `git add -A` (or add the changed/created files by hand)
3. `git commit -m'Release Modyllic-#.#.#'`
4. `git push`

And you're done.  It may take a minute or so for your changes to appear at:
http://onlinebuddies.github.com/pear/

Once they do, you can upgrade a Modyllic installation with:

`pear upgrade OnlineBuddies/Modyllic-beta`
