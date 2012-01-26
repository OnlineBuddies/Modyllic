I'm thinking it would be useful to decouple the parser and the schema objects.  Have the parser emit a series of simple schema mutation commands (as an array of structured data) that is then applied to a schema object.  This would grant a number of advantages:

* Simplify the parser as it will no longer need to worry about schema state.
* Improved serialization target– a JSON encoded version of this array.
* Separation will make it simpler to make the parser multidialect.

