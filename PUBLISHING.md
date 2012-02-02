Before you begin you'll need to install Pirum:

1. pear channel-discover pear.pirum-project.org
2. pear install pirum/Pirum-beta

And checkout the OLB pear repository:

1. git clone git@github.com:OnlineBuddies/pear.git

To publish a new release:

1. Update the version, notes, etc in package.xml
2. Validate it: pear package-validate package.xml
3. Commit
4. Make a tag: git tag v#.#.#
5. Build a tarball: pear package package.xml
6. This will give you a Modyllic-#.#.#.tgz

Now in go to your previously checked out copy of the OLB pear repository:

1. pirum add . /path/to/Modyllic-#.#.#.tgz
2. git add -A (or add the changed/created files by hand)
3. git commit -m'Release Modyllic-#.#.#'
4. git push origin ghpages

And you're done.  You can visit http://onlinebuddies.github.com/pear/ to see your changes.
