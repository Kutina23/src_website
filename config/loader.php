<?php

class Loader {
    private static $instance = null;
    private $visible = false;

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function show() {
        $this->visible = true;
        $this->renderInline();
    }

    public function hide() {
        $this->visible = false;
    }

    public function isVisible() {
        return $this->visible;
    }

    public function renderInline() {
        echo $this->getHtml();
    }

    public function getHtml() {
        return '<div class="spinner-container"><div class="spinner"><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div><div class="spinner-blade"></div></div></div>';
    }

    public static function render() {
        $loader = self::getInstance();
        $loader->renderInline();
    }

    public static function showPageLoader($condition = true) {
        if ($condition) {
            echo '<script>document.addEventListener("DOMContentLoaded", function() { Loader.show(); });</script>';
        }
    }
}

function loader() {
    return Loader::getInstance();
}

function showLoader() {
    Loader::render();
}

function showPageLoader($condition = true) {
    Loader::showPageLoader($condition);
}
?>