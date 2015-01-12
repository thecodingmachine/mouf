Integrating your package with Mouf full-text search
===================================================

What is a Mouf full-text search?
--------------------------------

In the Mouf interface, there is a search box available on all the pages of the application.
When you perform a search through this box, Mouf is searching through all the instances, etc...

When you write a package, you can decide to hook into the full-text search to add your own results.

###Things you should know about full-text search

Full-text search is run asynchronously. Each module is run as an Ajax request that returns HTML that will be displayed in the
results page.

Developing you own full-text search module
------------------------------------------

###Writing the controller part

The code that performs the search should be put in a controller that implements the `MoufSearchable` interface.

```php
namespace Mouf;

/**
 * This interface should be implemented by any controller that can be accessed for full-text search.
 */
interface MoufSearchable {
	
	/**
	 * Outputs HTML that will be displayed in the search result screen.
	 * If there are no results, this should not return anything.
	 * 
	 * @Action
	 * @param string $query The full-text search query performed.
	 * @param string $selfedit Whether we are in self-edit mode or not.
	 */
	public function search($query, $selfedit = "false");
	
	/**
	 * Returns the name of the search module.
	 * This name in displayed when the search is pending.
	 * 
	 * @return string
	 */
	public function getSearchModuleName();
}
```

Start by implementing this interface. For instance:

```php
...
class MyController extends Controller implements MoufSearchable {
   
    ...
   
    /**
     * Outputs HTML that will be displayed in the search result screen.
     * If there are no results, this should not return anything.
     *
     * @Action
     * @param string $query The full-text search query performed.
     * @param string $selfedit Whether we are in self-edit mode or not.
     */
    public function search($query, $selfedit = "false") {
        echo "<p>Your search results go here</p>";
    }
   
    /**
     * Returns the name of the search module.
     * This name in displayed when the search is pending.
     *
     * @return string
     */
    public function getSearchModuleName() {
        return "my module name";
    }
}
```

###Registering the full-text search

The controller should be registered inside the `searchService` instance.
For your controller to be taken into account, it must be added to the `$searchableServices` array of the `searchService` instance.

To do this automatically for your module, you can add a **src/RegisterSearchModule.php** file.

**src/RegisterSearchModule.php**
```php
MoufAdmin::getSearchService()->searchableServices[] = MoufAdmin::getMyController();
```

Now, it is time to register the `RegisterSearchModule.php` script into Mouf.
We do this in the **composer.json**:

**composer.json**
```json
{
	...
	"extra" : {
		"mouf" : {
			"require-admin" : [
				"src/RegisterSearchModule.php"
			]
		}
	},
	...
}
```

<div class="alert"><b>Important:</b> Mouf will not detect any changes you make to <code>composer.json</code>
unless you commit/push the changes in your repository and you run a <code>php composer.phar update</code>.
In other words, the <code>composer.json</code> file taken into account by Mouf is the one coming from
Packagist, not the one on your hard disk.</div>