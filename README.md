# Balsamiq-to-HTML

Balsamiq to HTML is a class to create an HTML version of a Balsamiq project (from exported BMML and PNG files)

# Usage

Create 2 directories in the same folder as `Balsamiq2html.class.php`, named `source` and `images`:
- source: put the exported .bmml files and the asset folder there
- images: put the exported .png files there

Require `Balsamiq2html.class.php` and run
```
$b = new Balsamiq2html();
$b->run();
```

The HTML result will be put in a new folder named "dest". That's it.

# Options

You can specify the folders to use and change styles a little, for example:
```
$b = new Balsamiq2html();
$b->run(
  [
    'source' => '../src',
    'images' => '../img',
    'dest' => '../html'
  ],
  [
    'body' => [
      'margin: 10px'
    ],
    'a' => [
      'background: green',
      'opacity: 0.4',
      'filter: alpha(opacity = 40)'
    ]
  ]
);
```

# Caveat

Be aware that text links like `[Hello](MyLink)` will not work, use links over the whole control instead.  
There are a few other gotchas but you might not even notice them.  
The script is not perfect but it's good enough for me; if you'd like to improve it, pull requests are welcome.

# Credits

This script is just an update on @ffub's [gist](https://gist.github.com/ffub/1084424), his work is much appreciated.  
The update mainly adds support for symbols, links on icons and fixes encoding issues.
