Before you begin you'll need to install Pirum:

1. pear channel-discover pear.pirum-project.org
2. pear install pirum/Pirum-beta

And checkout the OLB pear repository:

1. git clone git@github.com:OnlineBuddies/pear.git

To publish a new release, from your Modyllic checkout:

1. Update the version: ./release-version #.#.# beta
2. Edit the package.xml to:
   a. Add notes
   b. Update the date and time
   c. Copy the current release section down to the changelog (don't forget to delete the time section from the changelog)
3. Make sure there are no errors or warnings: pear package-validate package.xml
4. git add package.xml Modyllic/CommandLine.php
5. git commit -m'Release Modyllic-#.#.#'
6. git tag v#.#.#
7. Build a tarball: pear package package.xml
8. This will give you a Modyllic-#.#.#.tgz
9. Bump the version for future edits: ./release-version #.#.#+1 alpha
10. Edit the package.xml to clear the notes section
11. git add package.xml Modyllic/CommandLine.php
12. git commit -m'Begin Modyllic-#.#.#+1'
5. git push

Now in go to your previously checked out copy of the OLB pear repository:

1. ./release /path/to/Modyllic-#.#.#.tgz
2. git add -A (or add the changed/created files by hand)
3. git commit -m'Release Modyllic-#.#.#'
4. git push

And you're done.  It may take a minute or so for your changes to appear at:
http://onlinebuddies.github.com/pear/

Once they do, you can upgrade a Modyllic installation with:

pear upgrade OnlineBuddies/Modyllic-beta
