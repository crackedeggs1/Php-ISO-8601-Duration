# Php-ISO-8601-Duration
A basic ISO 8601 Duration parser and formatter that supports decimal input and output.

This class was created because PHP's built-in DateInterval failed to parse ISO-8601 strings
containing decimals. My_DateInterval can parse and output P[n]Y[n]M[n]DT[n]H[n]M[n]S format only.
If you need negative support, P[n]W support, or alternative formats you will need to modify the class.
When I wrote this I only needed one format.

When using this class, I recommend renaming it to something more suitable for your project.
#

Usage:
======
When you have a suspected ISO 8601 string, pass it into the constructor.
The new object will have the $y, $m, $d etc properties automatically populated,
with the special $f added for microseconds (for similarity to PHP's DateInterval).
If the string is not valid a ISO 8601 duration, an exception will be thrown.

Access the object's properties as needed to do your math, display the duration, or whatever:

Example:
<code>$duration = new My_DateInterval('P12YT13H3.267923S');
// yields
// $duration->y 12
// $duration->h 13
// $duration->s 3
// $duration->f 267923</code>

If you want to go the other direction, start with an empty object and set the properties manually:

Example:
<code>$duration = new My_DateInterval();
$duration->y = 12.5;
$duration->h = 13.25;
$duration->s = 3;
$duration->f = 267923;
echo $duration->value();
// yields P12Y6MT13H15M3.267923S</code>
