<?php

/**
 * PHP driven HTML rendering
 * @author Clément Della Monica, started from David Walsh's works
 * @link http://davidwalsh.name/create-html-elements-php-htmlelement-class
 * @link http://www.w3schools.com/xpath/xpath_syntax.asp
 * @link http://code.google.com/p/zen-coding/
 */
class HtmlElement {

    /**
     * @var DOMElement
     */
    private $element;

    /**
     * Zen Coding child
     * @var string
     */
    private $son;
    private $attrs = array();

    /**
     * The document contains all the nodes
     * @var DOMDocument
     */
    private static $dom;
    private static $self_closers = array('input', 'img', 'hr', 'br', 'meta', 'link');

    /**
     * Allowing flow coding
     * @param type $tag
     * @param type $args
     * @return \HtmlElement 
     */
    public static function getInstance($tag, $args = array()) {
        return new \HtmlElement($tag, $args);
    }

    /**
     * Constructor
     * @param string $type
     * @param array $attributes 
     */
    public function __construct($type = '', $attributes = array()) {
        if (empty(self::$dom)) {
            self::$dom = new DOMDocument('1.0', 'utf-8');
        }
        //A bit of Zen Coding
        if (!ctype_alpha($type)) {
            $matches = array();
            $tag = '(?<tag>[a-zA-Z0-9]+)';
            $class = '\.(?<class>[^.>\[\]#]+)';
            $id = '\#(?<id>[^.>\[\]#]+)';
            $attr = '[\[](?<attr_name>[A-Za-z\-]+)=(?<attr_val>[^\"\[\]]+)[\]]';
            $son = '[>](?<son>.+)';
            preg_match_all(sprintf('/%s|%s|%s|%s|%s/', $tag, $class, $id, $attr, $son
                    ), $type, $matches);
            array_filter($matches, function($item) use (&$matches) {
                        if (is_numeric(key($matches))) {
                            unset($matches[key($matches)]);
                            return false;
                        }
                        $matches[key($matches)] = array_filter($item);
                        next($matches);
                    });
            if (!empty($matches['attr_name'])) {
                foreach ($matches['attr_name'] as $key => $value) {
                    $this->attrs[$value] = $matches['attr_val'][$key];
                }
            }
            $type = end($matches['tag']);
            $class = implode(' ', $matches['class']);
            $id = implode(' ', $matches['id']);
            $this->son = end($matches['son']);
            if (empty($type)) {
                throw new ErrorException('Element Has No Tag');
            }

            if (!is_array($attributes)) {
                $attributes = array('text' => $attributes);
            }

            if (!empty($class)) {
                if(!empty($attributes['class'])){
                    $current_class = trim($attributes['class']);
                }
                if (empty($current_class)) {
                    $attributes['class'] = $class;
                } else {
                    $attributes['class'] = $current_class . ' ' . $class;
                }
            }
            if (!empty($id)) {
                if(!empty($attributes['id'])){
                    $current_id = trim($attributes['id']);
                }
                if (empty($attributes['id'])) {
                    $attributes['id'] = $id;
                } else {
                    $attributes['id'] = $current_id . ' ' . $id;
                }
            }
        }
        $this->element = self::$dom->createElement($type);

        if (!empty($this->attrs)) {
            $attributes = array_merge($attributes, $this->attrs);
        }
        if (!is_array($attributes)) {
            $this->set('text', $attributes);
        } else {
            foreach ($attributes as $name => $value) {
                $this->set($name, $value);
            }
        }

        if ($this->son) {
            $this->inject(new HtmlElement($this->son));
        }

        if (!in_array($type, self::$self_closers) && !$this->element->hasChildNodes()) {
            $this->set('text', '');
        }

        self::$dom->appendChild($this->element);
    }

    /**
     * get an attribute
     * @return string
     */
    public function get($attribute) {
        return $this->element->getAttribute($attribute);
    }

    /**
     *  set -- array or key,value 
     * @param string $attribute
     * @param string $value
     */
    public function set($attribute, $value = '') {
        if ($attribute == 'text' || $attribute == 'innerHTML') {
            $this->element->appendChild(self::$dom->createTextNode(HtmlElement::accentsToHTML($value)));
            return;
        }
        $this->element->setAttribute($attribute, $value);
        return $this;
    }

    /**
     *  remove an attribute 
     * @param string $att
     */
    public function remove($att) {
        $this->element->removeAttribute($att);
        return $this;
    }

    /** clear delete all attributes
     */
    public function clear() {
        
    }

    /**
     * inject
     * @param string|HtmlElement $tag
     */
    public function inject($tag, $options = array()) {
        if (!($tag instanceof HtmlElement)) {
            $tag = self::getInstance($tag, $options);
        }
        $this->element->appendChild($tag->getElement());
        return $this;
    }

    /**
     * @return DOMElement
     */
    public function getElement() {
        return $this->element;
    }

    public function build() {
        return $this->__toString();
    }

    /**
     * XPath request the dom tree
     * @param string $path XPATH
     * @return DOMNodelist
     */
    public function search($path) {
        $xpath = new DOMXPath(self::$dom);
        return $xpath->query($path, $this->element);
    }

    public function __toString() {
        return html_entity_decode(self::$dom->saveXML($this->element));
    }

    private static function accentsToHTML($str) {
        if (!mb_detect_encoding($str, 'UTF-8', true)) {
            $str = htmlentities($str);
        } else {
            $str = htmlentities($str, ENT_IGNORE, 'UTF-8');
        }

        $str = htmlspecialchars_decode($str);
        return $str;
    }

}