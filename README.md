PHP driven HTML rendering
=========================

A class to provide HTML encapsulation in PHP

This class is designed as an helper to output HTML in your app.

One can use it in many ways as described here.

Using the constructor

```php

<?php

$href = new HtmlElement('a.link',array(
    'href' => 'http://www.google.com',
    'text' => 'Visit Google',
);

echo $href;

```

will output :

```html

<a class="link" href="http://www.google.com"> Visit Google </a>

```
