# Balsamiq-to-HTML

A script to create an HTML version of a Balsamiq project (from BMML and PNG files)

# Usage

Create 2 directories in the same folder as `balsamiq.php`, named `source` and `images`:
- source: put the exported .bmml files and the asset folder there
- images: put the exported .png files there
Run `balsamiq.php`. The HTML result will be put in a new folder named "dest". That's it.

# Caveat

Be aware that text links like `[Hello](MyLink)` will not work, use links over the whole control instead.
There are a few other gotchas but you might not notice them.
The script is not perfect but it's good enough for me; if you'd like to improve it, pull requests are welcome.

# Credits

This script is just an update on @ffub's [gist](https://gist.github.com/ffub/1084424), his work is much appreciated.
The update mainly adds support for symbols, links on icons and fixes encoding issues.
