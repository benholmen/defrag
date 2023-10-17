# Defragment your PHPUnit tests
This package generates a satisfying Defrag-style output when running your [PHPUnit test suite](https://phpunit.de).

## Inspired by
MS-DOS 6.x included a [defrag utility](https://en.wikipedia.org/wiki/List_of_DOS_commands#DEFRAG) that honestly was just so satisfying to watch. The 90s were a different time, folks. Disk defragmentation took about an hour and physically rearranged the data on your hard drive so it was more efficient to read off the disk.

#### Defrag Running in MS-DOS 6.22
[![Defrag Running in MS-DOS 6.22](https://img.youtube.com/vi/Nidwz3BzFCM/0.jpg)](https://www.youtube.com/watch?v=Nidwz3BzFCM "Defrag Running in MS-DOS 6.22")

## Installation

Install the package via composer:

```bash
composer require benholmen/defrag --dev
```

Add the following lines to your `phpunit.xml` file:
```xml
<extensions>
    <bootstrap class="BenHolmen\Defrag\Extension"/>
</extensions>
```

## Usage
Run PHPUnit as usual:

```bash
vendor/bin/phpunit
```

## Testing
Of course, this produces the defrag output you'd expect.
```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Credits

- [Ben Holmen](https://github.com/benholmen)
- [Jess Archer](https://github.com/jessarcher) key intel, assistance, and the fantastic [laravel/prompts](https://github.com/laravel/prompts) package

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
