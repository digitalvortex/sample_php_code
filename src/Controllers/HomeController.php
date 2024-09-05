<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): string
    {
        return $this->render('home/index', [
            'title' => 'Welcome to Home Page'
        ]);
    }
}
