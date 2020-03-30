# Php-ISO-8601-Duration
A basic ISO 8601 Duration parser and formatter that supports decimal input and output.

This class was created because PHP's built-in DateInterval failed to parse ISO-8601 strings
containing decimals. My_DateInterval can parse and output P[n]Y[n]M[n]DT[n]H[n]M[n]S format,
including negative values. It can handle P[n]W, but if you set any other properties, weeks
are converted to days at 7 days = 1 week. If you need alternative formats you will need to
modify the class.

When using this class, I recommend renaming it to something more suitable for your project.

Usage:
======
When you have a suspected ISO 8601 string, pass it into the constructor.
The new object will have the $y, $m, $d etc properties automatically populated,
with the special $f added for microseconds (for similarity to PHP's DateInterval).
If the string is not a valid ISO 8601 duration, an exception will be thrown.

Access the object's properties as needed to do your math, display the duration, or whatever:

Example:
```
$duration = new My_DateInterval('P12YT13H3.267923S');
// yields
// $duration->y 12
// $duration->h 13
// $duration->s 3
// $duration->f 267923
```

If you want to go the other direction, start with an empty object and set the properties manually:

Example:
```
$duration = new My_DateInterval();
$duration->y = 12.5;
$duration->h = 13.25;
$duration->s = 3;
$duration->f = 267923;
echo $duration->value();
// yields P12Y6MT13H15M3.267923S
```
