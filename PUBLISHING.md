Before you begin you'll need to install Pirum:

1. pear channel-discover pear.pirum-project.org
2. pear install pirum/Pirum-beta

And checkout the OLB pear repository:

1. git clone git@github.com:OnlineBuddies/pear.git

To publish a new release, from your Modyllic checkout:

1. Update the version, notes, etc in package.xml
2. Make sure there are no errors or warnings: pear package-validate package.xml
3. git commit -m'Release Modyllic-#.#.#'
4. git tag v#.#.#
5. git push
5. Build a tarball: pear package package.xml
6. This will give you a Modyllic-#.#.#.tgz

Now in go to your previously checked out copy of the OLB pear repository:

1. pirum add . /path/to/Modyllic-#.#.#.tgz
2. git add -A (or add the changed/created files by hand)
3. git commit -m'Release Modyllic-#.#.#'
4. git push

And you're done.  It may take a minute or so for your changes to appear at:
http://onlinebuddies.github.com/pear/

Once they do, you can upgrade a Modyllic installation with:

pear upgrade OnlineBuddies/Modyllic-beta
