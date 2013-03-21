Writing documentation for your packages
=======================================

A good package should be documented. Indeed, what's the use of writing reusable code if nobody can use it? Mouf makes documentation writing easy for developers. The idea is simple: you write your documentation inside your package directory, in HTML or in <a href="http://daringfireball.net/projects/markdown/basics">Markdown</a> format. The documentation will be available to the users of the package (accessible through the **"packages => documentation"** menu in the Mouf admin interface), and will also be directly published to the Mouf website if you decide to upload your package.

Declaring the composer.json pages
---------------------------------

The first page you write should be named README.html or README.md and placed at the root of your package. It will be automatically detected by Mouf and proposed in the documentation of your package.
If you need to add additional pages, the documentation should be declared in the composer.json file.

```js
{
    ...
    "extra": {
	    "mouf": {
	    	"logo": "logo.png",
	    	"doc": [
	    		{
	    			"title": "Using FINE",
	    			"url": "doc/using_fine.html"
	    		},
	    		{
	    			"title": "Date functions",
	    			"url": "doc/date_functions.html"
	    		},
	    		{
	    			"title": "Currency functions",
	    			"url": "doc/currency_functions.html"
	    		}
	    	]
	    	
	    }
	}
}
```

Declaring documentation pages
-----------------------------

Any file ending with the ".html" extension in the "doc" directory will be accessible as a documentation page. However, it will not automatically appear in the documentation menu. To have a link to your page displayed in the documentation menu, you must declare it in the package.xml file. As you can see in the sample above, you must use the &lt;page&gt; tag to declare a page.

The doc array in composer.json is an array of JSON objects accepting 2 attributes:

- **title**: this is the text of the menu
- **url**: the is the URL to your file, relative to the package's root directory

A few things to know
--------------------

Only pure HTML files (with the .html extension) or Markdown files (with the .md extension) are accessible in the documentation. You cannot use PHP files.
You can use images (PNG, JPG, etc...)
You do not need to write a full HTML file, you can start directly with the content of the &lt;body&gt; tag.
Only the &lt;body&gt; tag will be displayed. The content of the &lt;head&gt; tag will be discarded.

Generating a complete website for your packages documentation
-------------------------------------------------------------

As a bonus, if you want to publish the documentation of your package on the web, we provide a webapp for that!
The [Composer package documentation generator](https://github.com/thecodingmachine/services.package-website-generator) can be used to generate a website containing your package's documentation.
The Mouf documentation website is built with this tool. Don't hesitate using it too! 