<?php
namespace san\renderer {
    use \san\runtime\_;
    function render ($data, $noDataOutput) {
        function sspId1 ($data, $noDataOutput = false, $parentCtx = [], $tagName = null, $sourceSlots = []) {
            $html = "";
            $ctx = (object)[
                "computedNames" => ["name"],
                "sspCid" => 0,
                "sourceSlots" => $sourceSlots,
                "data" => $data ? $data : (object)["name" => "undefined undefined"],
                "owner" => $parentCtx,
                "slotRenderers" => []
];
            $ctx->instance = _::createComponent($ctx);
            if ($data) {
                $ctx->data->name = isset($ctx->data->name) ? $ctx->data->name : "undefined undefined";
            }
            foreach ($ctx->computedNames as $i => $computedName) {
                $data->$computedName = _::callComputed($ctx, $computedName);
            }
            $html .= "<div";
            if (_::data($ctx, ["class"])) {
                $html .= _::attrFilter('class', _::escapeHTML(_::_classFilter(_::data($ctx, ["class"]))));
            }
            if (_::data($ctx, ["style"])) {
                $html .= _::attrFilter('style', _::escapeHTML(_::_styleFilter(_::data($ctx, ["style"]))));
            }
            if (_::data($ctx, ["id"])) {
                $html .= _::attrFilter('id', _::escapeHTML(_::data($ctx, ["id"])));
            }
            $html .= ">";
            if (!$noDataOutput) {
                $html .= "<!--s-data:" . json_encode(_::data($ctx, []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "-->";;
            }
            $html .= "<h1>";
            $html .= _::escapeHTML(_::data($ctx, ["name"]));
            $html .= "</h1></div>";
            return $html;
        }
        return sspId1($data, $noDataOutput);
    }
}
namespace san\components\test\component {
    use \san\runtime\_;
    use \san\runtime\Component;
    use \FilterDeclarations;
    use \ComputedDeclarations;
    class DemoComponent extends Component {
        public static $filters;
        public static $computed;
        public static $template = "<div><h1>{{name}}</h1></div>";
    }
    DemoComponent::$filters = array(
        "sum" => function ($a, $b) {
            return $a + $b;
        }
    );
    DemoComponent::$computed = array(
        "name" => function () {
            $f = $this->data->get("firstName");
            $l = $this->data->get("lastName");
            return "" . $f . " " . $l;
        }
    );
}
namespace san\runtime {
    ComponentRegistry::$comps = [
        "0" => "\\san\\components\\test\\component\\DemoComponent"
    ];
}
namespace san\runtime {
    final class _
    {
        private const HTML_ENTITY = [
            '&' => '&amp;',
            '<' => '&lt;',
            '>' => '&gt;',
            '"' => '&quot;',
            "'" => '&#39;'
        ];
    
        public static function data($ctx, $seq = []) {
            $data = $ctx->data;
            foreach ($seq as $name) {
                if (is_array($data)) {
                    if (isset($data[$name])) $data = $data[$name];
                    else return null;
                } else {
                    if (isset($data->$name)) $data = $data->$name;
                    else return null;
                }
            }
            return $data;
        }
    
        public static function sortedStringify($obj) {
            if (!is_object($obj)) {
                return json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
    
            $keys = array_keys($obj);
            sort($keys);
            $lines = [];
            foreach ($keys as $key) {
                array_push($lines, '"' . $key . '":' . stringify($obj->$key));
            }
            return "{" . join(",", $lines) . "}";
        }
    
        public static function objSpread($arr, $needSpread) {
            $obj = (object)[];
            foreach ($arr as $idx => $val) {
                if ($needSpread[$idx]) {
                    foreach ($val as $subkey => $subvar) {
                        $obj->{$subkey} = $subvar;
                    }
                } else {
                    $obj->{$val[0]} = $val[1];
                }
            }
            return $obj;
        }
    
        public static function spread($arr, $needSpread) {
            $ret = [];
            foreach ($arr as $idx => $val) {
                if ($needSpread[$idx]) {
                    foreach ($val as $subvar) array_push($ret, $subvar);
                } else {
                    array_push($ret, $val);
                }
            }
            return $ret;
        }
    
        public static function extend($target, $source)
        {
            if (!$target) $target = (object)[];
            if ($source) {
                foreach ($source as $key => $val) {
                    $target->{$key} = $val;
                }
            }
            return $target;
        }
    
        public static function each($array, $iter)
        {
            if (!$array) {
                return;
            }
            foreach ($array as $key => $val) {
                if ($iter($val, $key) === false) {
                    break;
                }
            }
        }
    
        public static function contains($array, $value)
        {
            return in_array($value, $array);
        }
    
        public static function htmlFilterReplacer($c)
        {
            return _::HTML_ENTITY[$c];
        }
    
        public static function escapeHTML($source)
        {
            if (!$source) {
                return "";
            }
            if (is_string($source)) {
                return htmlspecialchars($source, ENT_QUOTES);
            }
            if (is_bool($source)) {
                return $source ? 'true' : 'false';
            }
            return strval($source);
        }
    
        public static function _classFilter($source)
        {
            if (is_array($source)) {
                return join(" ", $source);
            }
            return $source;
        }
    
        public static function _styleFilter($source)
        {
            return _::stringifyStyles($source);
        }
    
        public static function _xclassFilter($outer, $inner)
        {
            if (is_array($outer)) {
                $outer = join(" ", $outer);
            }
            if ($outer) {
                return $inner ? $inner . ' ' . $outer : $outer;
            }
            return $inner;
        }
    
        public static function _xstyleFilter($outer, $inner)
        {
            if ($outer) {
                $outer = _::stringifyStyles($outer);
                return $inner ? $inner . ';' . $outer : $outer;
            }
            return $inner;
        }
    
        public static function attrFilter($name, $value)
        {
            if (isset($value)) {
                return " " . $name . '="' . $value . '"';
            }
            return '';
        }
    
        public static function boolAttrFilter($name, $value)
        {
            return _::boolAttrTruthy($value) ? ' ' . $name : '';
        }
    
        private static function boolAttrTruthy($value) {
            if (is_string($value)) {
                return $value != '' && $value != 'false' && $value != '0';
            }
            return (boolean)$value;
        }
    
        public static function getClassByCtx($ctx) {
            $cid = $ctx->sspCid;
            if (\san\runtime\ComponentRegistry::has($cid)) {
                return \san\runtime\ComponentRegistry::get($cid);
            }
            return null;
        }
    
        public static function callFilter($ctx, $name, $args)
        {
            $func = _::getClassByCtx($ctx)::$filters[$name];
            if (is_callable($func)) {
                return call_user_func_array($func, $args);
            }
        }
    
        public static function createComponent (&$ctx) {
            $cls = _::getClassByCtx($ctx);
            if (!class_exists($cls)) {
              $cls = "\\san\\runtime\\Component";
            }
            $obj = new $cls();
            $obj->data = new Data($ctx);
            return $obj;
        }
    
        public static function callComputed($ctx, $name)
        {
            $func = _::getClassByCtx($ctx)::$computed[$name];
            if (is_callable($func)) {
                $result = call_user_func($func->bindTo($ctx->instance));
                return is_array($result) ? (object)$result : $result;
            }
        }
    
        public static function stringifyStyles($source)
        {
            if (is_array($source) || is_object($source)) {
                $result = '';
                foreach ($source as $key => $val) {
                    $result .= $key . ':' . $val . ';';
                }
                return $result;
            }
            return $source;
        }
    }
    class Data {
        private $ctx;
        private $data;
        private $computedNames;
    
        public function __construct(&$ctx) {
            $this->ctx = &$ctx;
            $this->data = &$ctx->data;
            $this->computedNames = array_flip($ctx->computedNames);
        }
    
        public function get ($path) {
            if (array_key_exists($path, $this->computedNames)) {
                return _::callComputed($this->ctx, $path);
            }
            return $this->data->$path;
        }
    }
    
    class Component {
        public $data;
        public function __construct () {}
    }
    /**
     * @file class Ts2Php_String_Helper
     * @author cxtom (cxtom@gmail.com)
     */
    
    class Ts2Php_Helper {
    
        /**
         * 是否索引数组
         * @param $arr mixed
         * @return bool
         */
        public static function isPlainArray($arr) {
            if (is_array($arr)) {
                $i = 0;
                foreach ($arr as $k => $v) {
                    if ($k !== $i++) {
                        return false;
                    }
                }
                return true;
            }
            return false;
        }
    
        /**
         * replace once helper for string.prototype.replace
         * @param $needle {string}
         * @param $replace {string}
         * @param $haystack {string}
         * @return {string}
         */
        static public function str_replace_once($needle, $replace, $haystack) {
            $pos = strpos($haystack, $needle);
            if ($pos === false) {
                return $haystack;
            }
            return substr_replace($haystack, $replace, $pos, strlen($needle));
        }
    
        /**
         * slice helper for string.prototype.slice
         * @param $origin {string}
         * @param $start {number}
         * @param $end {number}
         * @return {string}
         */
        static public function str_slice($origin, $start, $end = null) {
            $end = isset($end) ? $end : mb_strlen($origin, 'utf8');
            return substr($origin, $start, $end - $start);
        }
    
        /**
         * string.prototype.startsWidth
         * @param $haystack {string}
         * @param $needle {string}
         * @param $postion {number}
         * @return {boolean}
         */
        static public function startsWith($origin, $substr, $postion = 0){
            return strncmp($substr, $origin, strlen($substr)) === $postion;
        }
    
        /**
         * string.prototype.endsWith
         * @param $haystack {string}
         * @param $needle {string}
         * @param $postion {number}
         * @return {boolean}
         */
        static public function endsWith($haystack, $needle, $postion = null){
            $left = isset($postion) ? strlen($haystack) - $postion : 0;
            $postion = $left + (strlen($needle));
    
            return $needle === '' || substr_compare($haystack, $needle, -$postion) === $left;
        }
    
        /**
         * string.prototype.includes
         * @param $haystack {string}
         * @param $needle {string}
         * @param $postion {number}
         * @return {boolean}
         */
        static public function includes($haystack, $needle, $postion = 0){
            $pos = strpos($haystack, $needle);
            return $pos !== false && $pos >= $postion;
        }
    
        /**
         * string.prototype.indexOf
         * @param $haystack {string}
         * @param $needle {string}
         * @return {number}
         */
        static public function str_pos($haystack, $needle){
            $pos = strpos($haystack, $needle);
            return $pos === false ? -1 : $pos;
        }
    
        /**
         * string.prototype.padStart
         * @param $input {string}
         * @param $pad_length {number}
         * @param $pad_string {string}
         * @return {string}
         */
        static public function padStart($input, $pad_length, $pad_string = " "){
            return str_pad($input, $pad_length, $pad_string, STR_PAD_LEFT);
        }
    
        /**
         * replace once helper for Array.prototype.slice
         * @param $origin {string}
         * @param $start {string}
         * @param $end {string}
         * @return {string}
         */
        static public function arraySlice($origin, $start, $end = null) {
            $end = isset($end) ? $end : count($origin);
            return array_slice($origin, $start, $end - $start);
        }
    
        /**
         * Array.prototype.indexOf
         * @param $haystack {string}
         * @param $needle {string}
         * @return {number}
         */
        static public function array_pos($needle, $haystack) {
            $pos = array_search($needle, $haystack);
            return $pos === false ? -1 : $pos;
        }
    
        /**
         * Array.prototype.some
         *
         * @param $array {array}
         * @param $fn {callable}
         * @return {boolean}
         */
        static function array_some(array $array, callable $fn) {
            foreach ($array as $index => $value) {
                if($fn($value, $index)) {
                    return true;
                }
            }
            return false;
        }
    
        /**
         * Array.prototype.every
         *
         * @param $array {array}
         * @param $fn {callable}
         * @return {boolean}
         */
        static function array_every(array $array, callable $fn) {
            foreach ($array as $index => $value) {
                if(!$fn($value, $index)) {
                    return false;
                }
            }
            return true;
        }
    
        /**
         * encodeURI
         * @param $uri {string}
         * @return {string}
         */
        static public function encodeURI($uri) {
    
            // http://php.net/manual/en/function.rawurlencode.php
            // https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/encodeURI
            $unescaped = array(
                '%2D' => '-',
                '%5F' => '_',
                '%2E' => '.',
                '%21' => '!',
                '%7E' => '~',
                '%2A' => '*',
                '%27' => "'",
                '%28' => '(',
                '%29' => ')',
                '%3B' => ';',
                '%2C' => ',',
                '%2F' => '/',
                '%3F' => '?',
                '%3A' => ':',
                '%40' => '@',
                '%26' => '&',
                '%3D' => '=',
                '%2B' => '+',
                '%24' => '$',
                '%23' => '#',
            );
    
            return strtr(rawurlencode($uri), $unescaped);
    
        }
    
        /**
         * get type of $var
         * @param $origin {*}
         * @return {string}
         */
        static public function typeof($var) {
            if (is_string($var)) {
                return 'string';
            }
            if (is_numeric($var)) {
                return 'number';
            }
            if (is_bool($var)) {
                return 'boolean';
            }
            if (is_null($var)) {
                return 'object';
            }
            if (!isset($var)) {
                return 'undefined';
            }
            if (self::isPlainArray($var)) {
                return 'array';
            }
            if (is_array($var) || is_object($var)) {
                return 'object';
            }
        }
    
        /**
         * get type of $var
         * @return {float}
         */
        static public function random() {
            return (float)rand() / (float)getrandmax();
        }
    
    }
    
    /**
     * from https://github.com/utopszkij/ts2php/blob/master/ts2php_core/tsphpx.php#L41
     *
     * @class Ts2Php_Date
     */
    class Ts2Php_Date {
        private $value = 0;
        function __construct($v = -1) {
            if ($v == -1) {
                $v = time() * 1000;
            }
            $this->value = intval($v / 1000);
        }
        // Returns the day of the month (from 1-31)
        public function getDate() {
            $result = 0 + date('d', $this->value);
            return $result;
        }
        //Returns the day of the week (from 0-6)
        public function getDay() {
            $result = 0 + date('w', $this->value);
            return $result;
        }
        // Returns the year
        public function getFullYear() {
            $result = 0 + date('Y', $this->value);
            return $result;
        }
        // Returns the hour (from 0-23)
        public function getHours() {
            $result = 0 + date('H', $this->value);
            return $result;
        }
        // Returns the minutes (from 0-59)
        public function getMinutes() {
            $result = 0 + date('i', $this->value);
            return $result;
        }
        // Returns the month (from 0-11)
        public function getMonth() {
            $result = 0 + date('m', $this->value) - 1;
            return $result;
        }
        // Returns the seconds (from 0-59)
        public function getSeconds() {
            $result = 0 + date('s', $this->value);
            return $result;
        }
        // Returns the number of milliseconds since midnight Jan 1 1970, and a specified date
        public function getTime() {
            return $this->value * 1000;
        }
        // Returns the number of milliseconds since midnight Jan 1, 1970
        public static function now() {
            return time() * 1000;
        }
        // Parses a date string and returns the number of milliseconds since January 1, 1970
        public static function parse($s) {
            return strtotime($s) * 1000;
        }
        // Sets the day of the month of a date object
        public function setDate($x) {
            $x = $this->padTime($x);
            $s = date('Y-m-d H:i:s', $this->value);
            $s = substr($s,0,8).$x.substr($s,10,9);
            $this->value = strtotime($s);
        }
        // Sets the year of a date object
        public function setFullYear($x, $y = null, $z = null) {
            $x = ''.$x;
            $y = $this->padTime($y);
            $z = $this->padTime($z);
    
            $s = date('Y-m-d H:i:s', $this->value);
            if (isset($y) && isset($z)) {
                $s = $x . '-' . $y . '-' . $z . substr($s,10,9);
            }
            else if (isset($y)) {
                $s = $x . '-' . $y . substr($s,7,12);
            }
            else {
                $s = $x . substr($s,4,15);
            }
            $this->value = strtotime($s);
        }
        // Sets the hour of a date object
        public function setHours($x, $y = null, $z = null) {
            $x = $this->padTime($x);
            $y = $this->padTime($y);
            $z = $this->padTime($z);
    
            $s = date('Y-m-d H:i:s', $this->value);
    
            if (isset($y) && isset($z)) {
                $s = substr($s,0,11).$x.':'.$y.':'.$z;
            }
            else if (isset($y)) {
                $s = substr($s,0,11).$x.':'.$y.substr($s,15,3);
            }
            else {
                $s = substr($s,0,11).$x.substr($s,13,6);
            }
            $this->value = strtotime($s);
        }
        // Set the minutes of a date object
        public function setMinutes($x) {
            $x = $this->padTime($x);
            $s = date('Y-m-d H:i:s', $this->value);
            $s = substr($s,0,14).$x.substr($s,16,3);
            $this->value = strtotime($s);
        }
        // Sets the month of a date object
        public function setMonth($x, $y = null) {
            $x += 1;
            $x = $this->padTime($x);
            $y = $this->padTime($y);
    
            $s = date('Y-m-d H:i:s', $this->value);
    
            if (isset($y)) {
                $s = substr($s,0,5).$x.'-'.$y.substr($s,10,9);
            }
            else {
                $s = substr($s,0,5).$x.substr($s,7,12);
            }
            $this->value = strtotime($s);
        }
        // Sets the seconds of a date object
        public function setSeconds($x) {
            if ($x < 10) {
                $x = '0'.$x;
            }
            else {
                $x = ''.$x;
            }
            $s = date('Y-m-d H:i:s', $this->value);
            $s = substr($s,0,17).$x;
            $this->value = strtotime($s);
        }
        // Sets a date to a specified number of milliseconds after/before January 1, 1970
        public function setTime($x) {
            $this->value = round($x / 1000);
        }
        // Returns the date portion of a Date object as a string, using locale conventions
        public function toLocaleDateString() {
            return date('Y-m-d',$this->value);
        }
        // Returns the time portion of a Date object as a string, using locale conventions
        public function toLocaleTimeString() {
            return date('H:i:s',$this->value);
        }
        // Converts a Date object to a string, using locale conventions
        public function toLocaleString() {
            return date('Y-m-d H:i:s',$this->value);
        }
    
        private function padTime($time) {
            if (!is_numeric($time)) {
                return $time;
            }
            if ($time < 10) {
                return '0' . $time;
            }
            return '' . $time;
        }
    }
    class ComponentRegistry {
        public static $comps = [];
        public static function has($cid){
            return array_key_exists($cid, ComponentRegistry::$comps);
        }
        public static function get($cid){
            return ComponentRegistry::$comps[$cid];
        }
    }
}
