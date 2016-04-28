<?php declare(strict_types=1);

namespace Room11\Jeeves\Chat\Plugin;

use Room11\Jeeves\Chat\Client\ChatClient;
use Room11\Jeeves\Chat\Command\Command;
use Room11\Jeeves\Chat\Command\Message;
use Amp\Artax\Response;

class NoComprendeException extends \RuntimeException {}

class Docs implements Plugin
{
    const COMMAND = 'docs';

    const URL_BASE = 'http://php.net';
    const LOOKUP_URL_BASE = self::URL_BASE . '/manual-lookup.php?scope=quickref&pattern=';
    const MANUAL_URL_BASE = self::URL_BASE . '/manual/en';

    private $chatClient;

    private $specialCases = [
        /* operators */
        'arithmetic' => 'Remember basic arithmetic from school? [Arithmetic operators](' . self::MANUAL_URL_BASE . '/language.operators.arithmetic.php)'
            . ' still work just like they did then.',
        '-' => '@arithmetic', '+' => '@arithmetic', '*' => '@arithmetic', '/' => '@arithmetic', '%' => '@arithmetic',
        '**' => '@arithmetic',
        'assignment' => '[Assignment operators](' . self::MANUAL_URL_BASE . '/language.operators.assignment.php) are used'
            . ' to change the value of a variable.',
        '=' => '@assignment', '+=' => '@assignment', '-=' => '@assignment', '*=' => '@assignment', '/=' => '@assignment',
        '%=' => '@assignment', '.=' => '@assignment', '&=' => '@assignment', '|=' => '@assignment', '^=' => '@assignment',
        '<<=' => '@assignment', '>>=' => '@assignment', '**=' => '@assignment', '??=' => '@assignment',
        'bitwise' => '[Bitwise operators](' . self::MANUAL_URL_BASE . '/language.operators.bitwise.php) allow evaluation'
            . ' and manipulation of specific bits within a value.',
        '&' => '@bitwise', '|' => '@bitwise', '^' => '@bitwise', '<<' => '@bitwise', '>>' => '@bitwise',
        'comparison' => '[Comparison operators](' . self::MANUAL_URL_BASE . '/language.operators.bitwise.php) allow you to'
            . ' compare two values.',
        '==' => '@comparison', '===' => '@comparison', '!=' => '@comparison', '!==' => '@comparison', '<' => '@comparison',
        '>' => '@comparison', '<=' => '@comparison', '>=' => '@comparison', '<=>' => '@comparison', '??' => '@comparison',
        '<>' => '@comparison',
        'increment' => '[Incrementing/decrementing operators](' . self::MANUAL_URL_BASE . '/language.operators.increment.php)'
            . ' adjust the value of an integer variable by 1.',
        '++' => '@increment', '--' => '@increment',
        'logical' => '[Logical operators](' . self::MANUAL_URL_BASE . '/language.operators.logical.php) are used to create'
            . ' complex boolean expressions.',
        'and' => '@logical', 'or' => '@logical', 'xor' => '@logical', '&&' => '@logical', '||' => '@logical',
        '@' => '`@` is the [error suppression operator](' . self::MANUAL_URL_BASE . '/language.operators.errorcontrol.php).'
            . ' When prepended to an expression in PHP, any error messages that might be generated by that expression will be'
            . ' ignored. Since ignoring errors is usually bad, it should almost never be used.',
        '.' => '`.` is the [string concatenation operator](' . self::MANUAL_URL_BASE . '/language.operators.string.php).'
            . ' It is used to join two string values together.',
        '`' => '` is the [execution operator](' . self::MANUAL_URL_BASE . '/language.operators.execution.php). It is identical'
            . ' to the [`shell_exec()`](' . self::MANUAL_URL_BASE . '/function.shell-exec.php) function, which should be'
            . ' preferred for readability reasons.',
        '::' => '`::` is the [scope resolution operator](' . self::MANUAL_URL_BASE . '/language.oop5.paamayim-nekudotayim.php).'
            . ' It is used for accessing class members defined in a different class than the current scope.',

        /* variables */
        '$_cookie' => 'The [`$_COOKIE`](' . self::MANUAL_URL_BASE . '/reserved.variables.cookie.php) superglobal variable'
            . ' is an associative array of variables passed to the current script via HTTP Cookies',
        '$_env' => 'The [`$_ENV`](' . self::MANUAL_URL_BASE . '/reserved.variables.environment.php) superglobal variable'
            . ' is an associative array of variables passed to the current script via the environment method.',
        '$_files' => 'The [`$_FILES`](' . self::MANUAL_URL_BASE . '/reserved.variables.files.php) superglobal variable'
            . ' is an associative array of items uploaded to the current script via the HTTP POST method.',
        '$_get' => 'The [`$_GET`](' . self::MANUAL_URL_BASE . '/reserved.variables.get.php) superglobal variable'
            . ' is an associative array of variables passed to the current script via the URL parameters.',
        '$_post' => 'The [`$_POST`](' . self::MANUAL_URL_BASE . '/reserved.variables.post.php) superglobal variable'
            . ' is an associative array of variables passed to the current script via the HTTP POST method when'
            . ' using application/x-www-form-urlencoded or multipart/form-data as the HTTP Content-Type in the request.',
        '$_request' => 'The [`$_REQUEST`](' . self::MANUAL_URL_BASE . '/reserved.variables.request.php) superglobal variable'
            . ' is an associative array that by default contains the contents of `$_GET`, `$_POST` and `$_COOKIE`.'
            . ' The presence and order of variables listed in this array is defined according to the PHP `variables_order`'
            . ' configuration directive. As a result, the contents of this variable are dependent on server configuration'
            . ' and it should be avoided for portability reasons.',
        '$_server' => 'The [`$_SERVER`](' . self::MANUAL_URL_BASE . '/reserved.variables.server.php) superglobal variable'
            . ' is an associative array containing information such as headers, paths, and script locations.',
        '$_session' => 'The [`$_SESSION`](' . self::MANUAL_URL_BASE . '/reserved.variables.session.php) superglobal variable'
            . ' is an associative array containing session variables available to the current script. See the'
            . ' [sessions](' . self::MANUAL_URL_BASE . '/book.session.php) documentation for more information on'
            . ' how this is used.',

        /* types */
        'arrays' => 'An [array](' . self::MANUAL_URL_BASE . '/language.types.array.php) in PHP is actually an ordered map.'
            . ' A map is a type that associates values to keys. This type is optimized for several different uses;'
            . ' it can be treated as an array, list (vector), hash table (an implementation of a map), dictionary,'
            . ' collection, stack, queue, and probably more. As array values can be other arrays, trees and multidimensional'
            . ' arrays are also possible.',
        'bools' => 'A [boolean](' . self::MANUAL_URL_BASE . '/language.types.boolean.php) expresses a truth value. It can be'
            . ' either `TRUE` or `FALSE`.',
        'ints' => 'An [integer](' . self::MANUAL_URL_BASE . '/language.types.integer.php) is a whole number',
        'floats' => '[Floating point](' . self::MANUAL_URL_BASE . '/language.types.float.php) is a data type capable of'
            . ' representing a fractional number.',
        'null' => 'The special [`NULL`](' . self::MANUAL_URL_BASE . '/language.types.null.php) value represents a variable'
            . ' with no value.',
        'objects' => 'An [object](' . self::MANUAL_URL_BASE . '/language.types.object.php) is an instance of a'
            . ' [class](' . self::MANUAL_URL_BASE . '/language.oop5.basic.php#language.oop5.basic.class).',
        'resources' => 'A [resources](' . self::MANUAL_URL_BASE . '/language.types.resource.php) is a special variable,'
            . ' holding a reference to an external resource.',
        'strings' => 'A [string](' . self::MANUAL_URL_BASE . '/language.types.string.php) is series of characters, where a'
            . ' character is the same as a byte.',
        'types' => 'PHP does not require (or support) explicit type definition in variable declaration; a variable\'s type'
            . ' is determined by the context in which the variable is used. However, it is possible to'
            . ' [change or ensure the type](' . self::MANUAL_URL_BASE . '/language.types.type-juggling.php) of a value, and'
            . ' the type of a function or method argument can be [specified](' . self::MANUAL_URL_BASE . '/language.oop5.typehinting.php).',

        /* keywords and general language features */
        'abstract' => '[Abstract classes](' . self::MANUAL_URL_BASE . '/language.oop5.abstract.php) may not be instantiated,'
            . ' and any class that contains at least one abstract method must also be abstract. Methods defined as'
            . ' abstract simply declare the method\'s signature - they cannot define the implementation.',
        'autoloading' => '[Class autoloaders](' . self::MANUAL_URL_BASE . '/language.oop5.autoload.php) enable classes,'
            . ' interfaces and traits to be automatically loaded if they are currently not defined.',
        'callables' => 'Callbacks can be denoted by the [callable](' . self::MANUAL_URL_BASE . '/language.types.callable.php)'
            . ' type hint. This special pseudo-type will accept function and method references, as well as anonymous functions.',
        'casting' => 'A C-like [casting](' . self::MANUAL_URL_BASE . '/language.types.type-juggling.php#language.types.typecasting)'
            . ' syntax can be use to change or ensure the type of a value in PHP.',
        'class' => ' A [class](' . self::MANUAL_URL_BASE . '/language.oop5.basic.php#language.oop5.basic.class) defines the'
            . ' behaviour of an object.',
        'clone' => 'An object copy is created by using the [`clone`](' . self::MANUAL_URL_BASE . '/language.oop5.cloning.php)'
            . ' keyword (which calls the object\'s `__clone()` method if possible).',
        'context' => '[Stream context options and parameters](' . self::MANUAL_URL_BASE . '/context.php',
        'const' => '`const` can be used to define [constants](' . self::MANUAL_URL_BASE . '/language.constants.syntax.php)'
            . ' and [class constants](' . self::MANUAL_URL_BASE . '/language.oop5.constants.php)',
        'errors' => 'PHP will report [errors, warnings and notices](' . self::MANUAL_URL_BASE . '/language.errors.basics.php)'
            . ' for many common coding and runtime problems. PHP 7 changes how most errors are reported by PHP. Instead of'
            . ' reporting errors through the traditional error reporting mechanism used by PHP 5, most errors are now reported'
            . ' by throwing [Error exceptions](' . self::MANUAL_URL_BASE . '/language.errors.php7.php).',
        'exceptions' => 'An [exception](' . self::MANUAL_URL_BASE . '/language.exceptions.php) can be thrown and caught.'
            . ' Code may be surrounded in a `try` block, to facilitate the catching of potential exceptions. Each `try` must'
            . ' have at least one corresponding `catch` or `finally` block.',
        'extends' => 'The `extends` keyword is used to define a class that [inherits](' . self::MANUAL_URL_BASE . '/language.oop5.inheritance.php)'
            . '  another class.',
        'final' => 'The [`final`](' . self::MANUAL_URL_BASE . '/language.oop5.final.php) can be used to prevent a child class'
            . ' from overriding a method. It can also be used on the class itself, to prevent further inheritance.',
        'functions' => 'A [function](' . self::MANUAL_URL_BASE . '/language.functions.php) is a sub-routine that may be called'
            . ' from other parts of the code.',
        'generators' => '[Generators](' . self::MANUAL_URL_BASE . '/language.generators.php) provide an easy way to implement'
            . ' simple iterators without the overhead or complexity of creating a class that implements the `Iterator` interface.',
        'hints' => '[Type hinting](' . self::MANUAL_URL_BASE . '/functions.arguments.php#functions.arguments.type-declaration) can be used to specify the'
            . ' type of a function or method argument.',
        'inheritance' => 'In PHP object-oriented programming, [inheritance](' . self::MANUAL_URL_BASE . '/language.oop5.inheritance.php)'
            . ' enables a class to build on the functionality of another class.',
        'interfaces' => '[Interfaces](' . self::MANUAL_URL_BASE . '/language.oop5.interfaces.php) allow you to create code'
            . ' which specifies which methods a class must implement, without having to define how these methods are handled.',
        'magic' => 'PHP was designed by wizards and so uses magic extensively.'
            . ' [Magic constants](' . self::MANUAL_URL_BASE . '/language.constants.predefined.php) and'
            . ' [magic methods](' . self::MANUAL_URL_BASE . '/language.oop5.magic.php) are both available.',
        'methods' => '[Methods](' . self::MANUAL_URL_BASE . '/language.oop5.basic.php#language.oop5.basic.properties-methods)'
            . ' are functions defined within a class. They have access to the internal state of the class or instance upon'
            . ' which they are called.',
        'namespaces' => '[Namespaces](' . self::MANUAL_URL_BASE . '/language.namespaces.php) are used to segregate and organise'
            . ' symbols. They can be used to organise and group classes, interfaces, traits, functions and constants and avoid'
            . ' naming collisions.',
        'new' => 'The new keyword is used to create an object instance from a class.',
        'oop' => '[Object-oriented programming](' . self::MANUAL_URL_BASE . '/language.oop5.php) (OOP) is a programming paradigm'
            . ' based on the concept of "objects", which may contain data, in the form of properties; and code, in the form'
            . ' of methods.',
        'operators' => 'An [operator](' . self::MANUAL_URL_BASE . '/language.operators.php) is something that takes one or'
            . ' more values (or expressions, in programming jargon) and yields another value (so that the construction itself'
            . ' becomes an expression).',
        'refs' => '[References](' . self::MANUAL_URL_BASE . '/language.references.php) in PHP are a means to access the'
            . ' same variable content by different names. They are frequently the cause of difficult-to-find bugs and memory'
            . ' consumption problems, and are almost always best avoided.',
        'return' => 'The [`return`](' . self::MANUAL_URL_BASE . '/functions.returning-values.php) keyword is used to pass a'
            . ' value back to the caller from within a function.',
        'precedence' => 'The [precedence](' . self::MANUAL_URL_BASE . '/language.operators.precedence.php) of an operator'
            . ' specifies how "tightly" it binds two expressions together.',
        'properties' => '[Properties](' . self::MANUAL_URL_BASE . '/language.oop5.properties.php) are variables defined within'
            . ' a class. They can be used to hold state within a class or object.',
        'scopes' => 'The [scope](' . self::MANUAL_URL_BASE . '/language.variables.scope.php) of a symbol is the context'
            . ' within which it is defined.',
        'static' => 'The `static` keyword can be used to create [static class members](' . self::MANUAL_URL_BASE . '/language.oop5.static.php)'
            . ' and [static variables](' . self::MANUAL_URL_BASE . '/language.variables.scope.php#language.variables.scope.static).'
            . ' In general, you shouldn\'t be doing either of these!',
        'traits' => '[Traits](' . self::MANUAL_URL_BASE . '/language.oop5.traits.php) are a mechanism for code reuse in single'
            . ' inheritance languages such as PHP. A Trait is similar to a class, but only intended to group functionality'
            . ' in a fine-grained and consistent way. It is not possible to instantiate a Trait on its own.',
        'tags' => 'When PHP parses a file, it looks for opening and closing [tags](' . self::MANUAL_URL_BASE . '/language.basic-syntax.phptags.php),'
            . ' which are `<?php` and `?>`. These tell PHP to start and stop interpreting the code between them.',
        'vars' => '[Variables](' . self::MANUAL_URL_BASE . '/language.variables.php) in PHP are represented by a dollar sign'
            . ' `$` followed by the name of the variable. Variable names are case-sensitive.',
        'use' => 'The `use` keyword is used to [import symbols from another namespace](' . self::MANUAL_URL_BASE . '/language.namespaces.importing.php)'
            . ' and to import a [trait](' . self::MANUAL_URL_BASE . '/language.oop5.traits.php) into a class.',
        'visibility' => 'Class members have a [visibility](' . self::MANUAL_URL_BASE . '/language.oop5.visibility.php) modifier,'
            . ' enabling control over the scopes from which they may be accessed.',
        'yield' => 'The `yield` keyword is used to emit a value from a [generator](' . self::MANUAL_URL_BASE . '/language.generators.php).',

        /* hilarity */
        'global' => 'Global ---All--- None Of The Things!',
        'javascript' => 'I think you\'re in the [wrong room](http://chat.stackoverflow.com/rooms/17/javascript).',

        /* aliases */
        '$globals' => '@global',
        'booleans' => '@bools',
        'callbacks' => '@callables',
        'casts' => '@casting',
        'classes' => '@class',
        'decls' => '@hints',
        'declaration' => '@hints',
        'implements' => '@interfaces',
        'integers' => '@ints',
        'private' => '@visibility',
        'propertys' => '@properties',
        'protected' => '@visibility',
        'public' => '@visibility',
        'references' => '@refs',
        'type decls' => '@hints',
        'type declaration' => '@hints',
        'variables' => '@vars',
        'yeild' => '@yield',
    ];

    public function __construct(ChatClient $chatClient) {
        $this->chatClient = $chatClient;
    }

    public function handle(Message $message): \Generator {
        if (!$this->validMessage($message)) {
            return;
        }

        /** @var Command $message */
        yield from $this->getResult($message);
    }

    private function validMessage(Message $message): bool {
        return $message instanceof Command
            && $message->getCommand() === self::COMMAND
            && $message->getParameters();
    }

    private function getResult(Command $message): \Generator {
        $pattern = strtolower(implode(' ', $message->getParameters()));

        foreach ([$pattern, '$' . $pattern, $pattern . 's', $pattern . 'ing'] as $candidate) {
            if (isset($this->specialCases[$candidate])) {
                $result = $this->specialCases[$candidate][0] === '@' && isset($this->specialCases[substr($this->specialCases[$candidate], 1)])
                    ? $this->specialCases[substr($this->specialCases[$candidate], 1)]
                    : $this->specialCases[$candidate];

                yield from $this->chatClient->postMessage($result);

                return;
            }
        }

        if (substr($pattern, 0, 6) === "mysql_") {
            yield from $this->chatClient->postMessage(
                $this->getMysqlMessage()
            );

            return;
        }

        $pattern = str_replace('::', '.', $pattern);
        $url = self::LOOKUP_URL_BASE . rawurlencode($pattern);

        /** @var Response $response */
        $response = yield from $this->chatClient->request($url);

        if ($response->getPreviousResponse() !== null) {
            yield from $this->chatClient->postMessage(
                $this->getMessageFromMatch(yield from $this->preProcessMatch($response, $pattern))
            );
        } else {
            yield from $this->chatClient->postMessage(
                yield from $this->getMessageFromSearch($response)
            );
        }
    }

    private function preProcessMatch(Response $response, string $pattern) : \Generator
    {
        if (preg_match('#/book\.[^.]+\.php$#', $response->getRequest()->getUri(), $matches)) {
            /** @var Response $classResponse */
            $classResponse = yield from $this->chatClient->request(self::MANUAL_URL_BASE . '/class.' . rawurlencode($pattern) . '.php');
            if ($classResponse->getStatus() != 404) {
                return $classResponse;
            }
        }

        return $response;
    }

    private function getMysqlMessage(): string {
        // See https://gist.github.com/MadaraUchiha/3881905
        return "[**Please, don't use `mysql_*` functions in new code**](http://bit.ly/phpmsql). "
             . "They are no longer maintained [and are officially deprecated](http://j.mp/XqV7Lp). "
             . "See the [**red box**](http://j.mp/Te9zIL)? Learn about [*prepared statements*](http://j.mp/T9hLWi) instead, "
             . "and use [PDO](http://php.net/pdo) or [MySQLi](http://php.net/mysqli) - "
             . "[this article](http://j.mp/QEx8IB) will help you decide which. If you choose PDO, "
             . "[here is a good tutorial](http://j.mp/PoWehJ).";
    }

    /**
     * @uses getFunctionDetails()
     * @uses getClassDetails()
     * @uses getBookDetails()
     * @uses getPageDetailsFromH2()
     * @param Response $response
     * @return string
     * @internal param string $pattern
     */
    private function getMessageFromMatch(Response $response): string {
        $doc = $this->getHTMLDocFromResponse($response);
        $url = $response->getRequest()->getUri();

        try {
            $details = preg_match('#/(book|class|function)\.[^.]+\.php$#', $url, $matches)
                ? $this->{"get{$matches[1]}Details"}($doc)
                : $this->getPageDetailsFromH2($doc);
            return sprintf("[ [`%s`](%s) ] %s", $details[0], $url, $details[1]);
        } catch (NoComprendeException $e) {
            return sprintf("That [manual page](%s) seems to be in a format I don't understand", $url);
        } catch (\Throwable $e) {
            return 'Something went badly wrong with that lookup... ' . $e->getMessage();
        }
    }

    /**
     * Get details for pages like http://php.net/manual/en/control-structures.foreach.php
     *
     * @used-by getMessageFromMatch()
     * @param \DOMDocument $doc
     * @return array
     */
    private function getPageDetailsFromH2(\DOMDocument $doc) : array
    {
        $h2Elements = $doc->getElementsByTagName("h2");
        if ($h2Elements->length < 1) {
            throw new NoComprendeException('No h2 elements in HTML');
        }

        $descriptionElements = (new \DOMXPath($doc))->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' para ')]");

        $symbol = $this->normalizeMessageContent($h2Elements->item(0)->textContent);
        $description = $descriptionElements->length > 0
            ? $this->normalizeMessageContent($descriptionElements->item(0)->textContent)
            : $symbol;

        return [$symbol, $description];
    }

    /**
     * @used-by getMessageFromMatch()
     * @param \DOMDocument $doc
     * @return array
     */
    private function getFunctionDetails(\DOMDocument $doc) : array
    {
        $h1Elements = $doc->getElementsByTagName("h1");
        if ($h1Elements->length < 1) {
            throw new NoComprendeException('No h1 elements in HTML');
        }

        $descriptionElements = (new \DOMXPath($doc))->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' dc-title ')]");

        $name = $this->normalizeMessageContent($h1Elements->item(0)->textContent) . '()';
        $description = $descriptionElements->length > 0
            ? $this->normalizeMessageContent($descriptionElements->item(0)->textContent)
            : $name . ' function';

        return [$name, $description];
    }

    /**
     * @used-by getMessageFromMatch()
     * @param \DOMDocument $doc
     * @return array
     * @internal param string $pattern
     */
    private function getBookDetails(\DOMDocument $doc) : array
    {
        $h1Elements = $doc->getElementsByTagName("h1");
        if ($h1Elements->length < 1) {
            throw new NoComprendeException('No h1 elements in HTML');
        }

        $title = $this->normalizeMessageContent($h1Elements->item(0)->textContent);
        return [$title, $title . ' book'];
    }

    /**
     * @used-by getMessageFromMatch()
     * @param \DOMDocument $doc
     * @return array
     */
    private function getClassDetails(\DOMDocument $doc) : array
    {
        $h1Elements = $doc->getElementsByTagName("h1");
        if ($h1Elements->length < 1) {
            throw new NoComprendeException('No h1 elements in HTML');
        }

        $descriptionElements = (new \DOMXPath($doc))->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' para ')]");

        $title = $this->normalizeMessageContent($h1Elements->item(0)->textContent);
        $symbol = preg_match('/^\s*the\s+(\S+)\s+class\s*$/i', $title, $matches)
            ? $matches[1]
            : $title;
        $description = $descriptionElements->length > 0
            ? $this->normalizeMessageContent($descriptionElements->item(0)->textContent)
            : $title;

        return [$symbol, $description];
    }

    // Handle broken SO's chat MD
    private function normalizeMessageContent(string $message): string
    {
        return trim(preg_replace('/\s+/', ' ', $message));
    }

    private function getHTMLDocFromResponse(Response $response) : \DOMDocument
    {
        $internalErrors = libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($response->getBody());

        libxml_use_internal_errors($internalErrors);

        return $dom;
    }

    private function getMessageFromSearch(Response $response): \Generator {
        try {
            $dom = $this->getHTMLDocFromResponse($response);

            /** @var \DOMElement $firstResult */
            $firstResult = $dom->getElementById("quickref_functions")->getElementsByTagName("li")->item(0);
            /** @var \DOMElement $anchor */
            $anchor = $firstResult->getElementsByTagName("a")->item(0);

            $response = yield from $this->chatClient->request(
                self::URL_BASE . $anchor->getAttribute("href")
            );

            return $this->getMessageFromMatch($response);
        } catch (\Throwable $e) {
            return 'Something went badly wrong with that lookup... ' . $e->getMessage();
        }
    }
}
