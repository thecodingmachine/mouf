Creating the Sample classes
===========================

If you are currently reading the [Mouf Dependency Injection](mouf_di_ui.md) guide and if you are 
new to using Composer and are not very familiar with things like PSR-0 and PSR-4 autoloading schemes, this
chapter might help you a bit.

We are using Composer, and therefore, we can use the Composer autoloading system. The best thing to do is to
declare our classes using the PSR-0 notation. This is a PHP 5.3 thing (mostly), so we will also declare a namespace for the classes.
Let's put all our classes in the `Mouf\Sample` namespace.

Let's also say all the classes go into the `src` folder.

The first thing to do is to declare the namespace in `composer.json`:

```json
{
    "require": {
        "mouf/mouf": "~2.0"
    },
    "autoload": {
        "psr-0": {
            "Mouf\\Sample": "src/"
        }
    },
    "minimum-stability": "dev" 
}
````

Have a look at the `autoload` section. We are declaring that the **Mouf\Sample** namespace will be in the `src/` directory.
This means that Mouf will be able to find any class in the `src\Mouf\Sample` directory, as long as they are respecting
the [PSR-0 naming scheme](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md).

Note: each time you change the "autoload" section of composer, you MUST run one of these 2 commands
to update the autoloader:

`php composer.phar dumpautoload`

or

`php composer.phar install

In our particular case, there will be 3 classes and 1 interface:

**src/Mouf/Sample/Warrior.php**
```php
namespace Mouf\Sample;

class Warrior {
	private $leftHandWeapon;
	private $rightHandWeapon;
	private $hp;
	
	/**
	 * @param WeaponInterface $leftHandWeapon
	 * @param WeaponInterface $rightHandWeapon
	 * @param int $hp
	 */
	public function __construct(WeaponInterface $leftHandWeapon, 
			WeaponInterface $rightHandWeapon,
			$hp) {
		$this->leftHandWeapon = $leftHandWeapon;
		$this->rightHandWeapon = $rightHandWeapon;
		$this->hp = $hp;
	}
	
	public function attack(Warrior $warrior) {
		$this->leftHandWeapon->attack($warrior);
		$this->rightHandWeapon->attack($warrior);
	}
	
	public function removeHp($hp) {
		$this->hp -= $hp;
		if ($this->hp <= 0) {
			echo "I'm dead!";
		}
	}
}
```

**src/Mouf/Sample/WeaponInterface.php**
```php
namespace Mouf\Sample;

interface WeaponInterface {
	function attack(Warrior $warrior);
}
```

**src/Mouf/Sample/Axe.php**
```php
namespace Mouf\Sample;

class Axe implements WeaponInterface {
	const DAMAGE = 7;
	
	public function attack(Warrior $warrior) {
		$warrior->removeHp(self::DAMAGE);
	}
}
```

**src/Mouf/Sample/Sword.php**
```php
namespace Mouf\Sample;

class Sword implements WeaponInterface {
	const DAMAGE = 5;
	
	public function attack(Warrior $warrior) {
		$warrior->removeHp(self::DAMAGE);
	}
}
```



Ok, was it easy enough? Let's get back to the [Dependency Injection with Mouf documentation](mouf_di_ui.md)