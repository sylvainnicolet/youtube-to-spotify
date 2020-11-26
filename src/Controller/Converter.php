<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class Converter
{

    protected $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function index() {
        return $this->render('converter.html.twig');
    }

    protected function render(string $path, array $variables =[]) {
        $html = $this->twig->render($path, $variables);
        return new Response($html);
    }
}
